<?php

namespace App\Services;

use App\Enums\AssetDisposalStatus;
use App\Enums\AssetMaintenanceStatus;
use App\Enums\LoanStatus;
use App\Enums\ProcurementProposalStatus;
use App\Models\RiwayatPersetujuan;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RiwayatPersetujuanService
{
    public function record(string $type, Model $model, string $stage, string $status, ?string $note = null, ?User $user = null): RiwayatPersetujuan
    {
        return RiwayatPersetujuan::query()->create([
            'jenis_pengajuan' => $type,
            'pengajuan_id' => $model->getKey(),
            'tahap' => $stage,
            'status' => $status,
            'user_id' => $user?->id,
            'nama_user_snapshot' => $user?->name,
            'catatan' => $note,
            'diproses_pada' => now(),
        ]);
    }

    public function histories(string $type, Model $model): Collection
    {
        return RiwayatPersetujuan::query()
            ->where('jenis_pengajuan', $type)
            ->where('pengajuan_id', $model->getKey())
            ->orderBy('diproses_pada')
            ->orderBy('id')
            ->get();
    }

    public function statusSummary(string $type, Model $model): array
    {
        $histories = $this->histories($type, $model);
        $last = $histories->last();
        $status = $this->statusValue($model->status ?? null);

        return [
            'label' => $this->currentStatusLabel($type, $model, $status, $last),
            'actor_label' => $this->actorLabel($last, $model),
            'processed_at' => $last?->diproses_pada ?? $model->created_at,
            'has_history' => $histories->isNotEmpty(),
            'last_activity' => $last,
            'timeline' => $this->timeline($type, $model, $histories),
        ];
    }

    public function timeline(string $type, Model $model, ?Collection $histories = null): array
    {
        $histories ??= $this->histories($type, $model);
        $byStage = $histories->keyBy('tahap');
        $status = $this->statusValue($model->status ?? null);

        return match ($type) {
            'procurement' => $this->procurementTimeline($model, $status, $byStage, $histories),
            'disposal' => $this->singleApprovalTimeline($model, $status, $byStage, 'Persetujuan Kasubbag', 'kasubbag', $histories),
            'loan' => $this->loanTimeline($model, $status, $byStage, $histories),
            'maintenance' => $this->maintenanceTimeline($model, $status, $byStage, $histories),
            default => [],
        };
    }

    private function currentStatusLabel(string $type, Model $model, ?string $status, ?RiwayatPersetujuan $last): string
    {
        return match ($type) {
            'procurement' => match ($status) {
                ProcurementProposalStatus::Submitted->value => 'Menunggu Verifikasi Tim Aset',
                ProcurementProposalStatus::Verified->value => 'Menunggu Persetujuan Kasubbag',
                ProcurementProposalStatus::Approved->value => 'Disetujui Kasubbag',
                ProcurementProposalStatus::Rejected->value => ($model->tahap_penolakan === 'tim_aset' || $last?->tahap === 'verifikasi_tim_aset') ? 'Ditolak Tim Aset' : 'Ditolak Kasubbag',
                default => $model->status?->label() ?? 'Status tidak diketahui',
            },
            'loan' => match ($status) {
                LoanStatus::Submitted->value => 'Menunggu Verifikasi Tim Aset',
                LoanStatus::VerifiedByAssetTeam->value => 'Menunggu Persetujuan Kasubbag',
                LoanStatus::Approved->value => 'Disetujui Kasubbag',
                LoanStatus::LegacyApproved->value => 'Disetujui',
                LoanStatus::Rejected->value => $last?->tahap === 'verifikasi_tim_aset' ? 'Ditolak Tim Aset' : 'Ditolak Kasubbag',
                default => $model->status?->label() ?? 'Status tidak diketahui',
            },
            'disposal' => match ($status) {
                AssetDisposalStatus::Submitted->value => 'Menunggu Persetujuan Kasubbag',
                AssetDisposalStatus::Approved->value => 'Disetujui Kasubbag',
                AssetDisposalStatus::Rejected->value => 'Ditolak Kasubbag',
                default => $model->status?->label() ?? 'Status tidak diketahui',
            },
            default => $model->status?->label() ?? 'Status tidak diketahui',
        };
    }

    private function actorLabel(?RiwayatPersetujuan $last, Model $model): string
    {
        if ($last) {
            return $last->status === 'dibuat'
                ? 'Diajukan oleh: '.($last->nama_user_snapshot ?: '-')
                : 'Terakhir diproses: '.($last->nama_user_snapshot ?: '-');
        }

        return 'Riwayat aktivitas belum tersedia untuk proses sebelum fitur riwayat diterapkan.';
    }

    private function procurementTimeline(Model $model, ?string $status, Collection $byStage, Collection $histories): array
    {
        $rejected = $status === ProcurementProposalStatus::Rejected->value;
        $rejectedStage = $model->tahap_penolakan ?: $histories->where('status', 'ditolak')->last()?->tahap;

        $timeline = [
            $this->step('Pengajuan Pengadaan Dibuat', 'pengajuan', $byStage, true, 'created'),
            $this->step(
                $rejected && $rejectedStage === 'verifikasi_tim_aset' ? 'Ditolak Tim Aset' : 'Diverifikasi Tim Aset',
                'verifikasi_tim_aset',
                $byStage,
                $status !== ProcurementProposalStatus::Submitted->value,
                $rejected && $rejectedStage === 'verifikasi_tim_aset' ? 'rejected' : ($status === ProcurementProposalStatus::Submitted->value ? 'waiting' : 'done'),
                $status === ProcurementProposalStatus::Submitted->value ? 'Menunggu Verifikasi Tim Aset' : null,
            ),
            $this->step(
                $rejected && $rejectedStage !== 'verifikasi_tim_aset' ? 'Ditolak Kasubbag' : 'Disetujui Kasubbag',
                'persetujuan_kasubbag',
                $byStage,
                in_array($status, [ProcurementProposalStatus::Approved->value, ProcurementProposalStatus::Rejected->value], true) && $rejectedStage !== 'verifikasi_tim_aset',
                $this->procurementSupervisorState($status, $rejectedStage),
                $status === ProcurementProposalStatus::Verified->value ? 'Menunggu Persetujuan Kasubbag' : null,
            ),
        ];

        // Data lama mungkin belum memiliki audit trail. Gunakan hanya field proses
        // yang benar-benar tersimpan pada usulan sebagai fallback, tanpa membuat
        // waktu atau pemeriksa baru.
        if (! $byStage->has('pengajuan') && $model->created_at) {
            $timeline[0]['actor'] = $model->requester?->name;
            $timeline[0]['time'] = $model->created_at;
            $timeline[0]['processed'] = true;
        }

        if (! $byStage->has('verifikasi_tim_aset') && $model->tanggal_pemeriksaan_tim_aset) {
            $timeline[1]['actor'] = $model->assetTeamReviewer?->name;
            $timeline[1]['time'] = $model->tanggal_pemeriksaan_tim_aset;
            $timeline[1]['note'] = $rejectedStage === 'verifikasi_tim_aset'
                ? $model->alasan_penolakan
                : $model->catatan_tim_aset;
            $timeline[1]['processed'] = true;
        }

        if (! $byStage->has('persetujuan_kasubbag') && $model->tanggal_pemeriksaan_kasubbag) {
            $timeline[2]['actor'] = $model->supervisorReviewer?->name;
            $timeline[2]['time'] = $model->tanggal_pemeriksaan_kasubbag;
            $timeline[2]['note'] = $rejectedStage === 'kasubbag'
                ? $model->alasan_penolakan
                : $model->catatan_kasubbag;
            $timeline[2]['processed'] = true;
        }

        return $timeline;
    }

    private function procurementSupervisorState(?string $status, ?string $rejectedStage): string
    {
        if ($status === ProcurementProposalStatus::Verified->value) {
            return 'waiting';
        }

        if ($status === ProcurementProposalStatus::Rejected->value && $rejectedStage !== 'verifikasi_tim_aset') {
            return 'rejected';
        }

        if ($status === ProcurementProposalStatus::Approved->value) {
            return 'done';
        }

        return 'upcoming';
    }

    private function loanTimeline(Model $model, ?string $status, Collection $byStage, Collection $histories): array
    {
        $rejected = $status === LoanStatus::Rejected->value;
        $rejectedStage = $histories->where('status', 'ditolak')->last()?->tahap;
        $legacyApproved = $status === LoanStatus::LegacyApproved->value
            && ! $byStage->has('verifikasi_tim_aset')
            && ! $byStage->has('persetujuan_kasubbag');

        $assetTeamState = match (true) {
            $rejected && $rejectedStage === 'verifikasi_tim_aset' => 'rejected',
            in_array($status, [
                LoanStatus::VerifiedByAssetTeam->value,
                LoanStatus::Approved->value,
                LoanStatus::Borrowed->value,
                LoanStatus::Returned->value,
            ], true) => 'done',
            $status === LoanStatus::Submitted->value => 'waiting',
            default => 'upcoming',
        };

        $supervisorState = match (true) {
            $rejected && $rejectedStage !== 'verifikasi_tim_aset' => 'rejected',
            in_array($status, [
                LoanStatus::Approved->value,
                LoanStatus::LegacyApproved->value,
                LoanStatus::Borrowed->value,
                LoanStatus::Returned->value,
            ], true) => 'done',
            $status === LoanStatus::VerifiedByAssetTeam->value => 'waiting',
            default => 'upcoming',
        };

        $timeline = [
            $this->step('Pengajuan Peminjaman Dibuat', 'pengajuan', $byStage, true, 'created'),
            $this->step(
                $rejected && $rejectedStage === 'verifikasi_tim_aset' ? 'Ditolak Tim Aset' : 'Diverifikasi Tim Aset',
                'verifikasi_tim_aset',
                $byStage,
                $assetTeamState === 'done' || $assetTeamState === 'rejected',
                $assetTeamState,
                $status === LoanStatus::Submitted->value ? 'Menunggu Verifikasi Tim Aset' : null,
            ),
            $this->step(
                $rejected && $rejectedStage !== 'verifikasi_tim_aset' ? 'Ditolak Kasubbag' : 'Disetujui Kasubbag',
                'persetujuan_kasubbag',
                $byStage,
                $supervisorState === 'done' || $supervisorState === 'rejected',
                $supervisorState,
                $status === LoanStatus::VerifiedByAssetTeam->value ? 'Menunggu Persetujuan Kasubbag' : null,
            ),
        ];

        if ($byStage->has('serah_terima') || in_array($status, [LoanStatus::Borrowed->value, LoanStatus::Returned->value], true)) {
            $timeline[] = $this->step(
                'Barang Diserahkan',
                'serah_terima',
                $byStage,
                in_array($status, [LoanStatus::Borrowed->value, LoanStatus::Returned->value], true),
                $byStage->has('serah_terima') ? 'done' : 'upcoming',
            );
        }

        if ($byStage->has('pengembalian') || $status === LoanStatus::Returned->value) {
            $timeline[] = $this->step(
                'Barang Dikembalikan',
                'pengembalian',
                $byStage,
                $status === LoanStatus::Returned->value,
                $byStage->has('pengembalian') ? 'done' : 'upcoming',
            );
        }

        if ($legacyApproved) {
            $timeline[1]['note'] = 'Riwayat verifikasi Tim Aset belum tersedia untuk pengajuan sebelum workflow verifikasi bertingkat diterapkan.';
            $timeline[1]['state'] = 'upcoming';
            $timeline[1]['processed'] = false;
        }

        return $timeline;
    }

    private function singleApprovalTimeline(Model $model, ?string $status, Collection $byStage, string $approvalLabel, string $approvalStage, Collection $histories): array
    {
        $rejected = in_array($status, [LoanStatus::Rejected->value, AssetDisposalStatus::Rejected->value], true);
        $approved = in_array($status, [LoanStatus::Approved->value, AssetDisposalStatus::Approved->value], true);
        $waiting = in_array($status, [LoanStatus::Submitted->value, AssetDisposalStatus::Submitted->value], true);

        return [
            $this->step('Pengajuan Dibuat', 'pengajuan', $byStage, true, 'created'),
            $this->step(
                $rejected ? 'Ditolak Kasubbag' : $approvalLabel,
                $approvalStage,
                $byStage,
                $approved || $rejected,
                $rejected ? 'rejected' : ($approved ? 'done' : ($waiting ? 'waiting' : 'done')),
                $waiting ? 'Menunggu Persetujuan Kasubbag' : null,
            ),
        ];
    }

    private function maintenanceTimeline(Model $model, ?string $status, Collection $byStage, Collection $histories): array
    {
        $rejected = $status === AssetMaintenanceStatus::Rejected->value;
        $approved = in_array($status, [
            AssetMaintenanceStatus::Approved->value,
            AssetMaintenanceStatus::InProgress->value,
            AssetMaintenanceStatus::Completed->value,
        ], true);

        $timeline = [
            $this->step('Pengajuan Pemeliharaan Dibuat', 'pengajuan', $byStage, true, 'created'),
            $this->step(
                $rejected ? 'Ditolak Tim Aset' : 'Diverifikasi Tim Aset',
                'verifikasi_tim_aset',
                $byStage,
                $approved || $rejected,
                $rejected ? 'rejected' : ($approved ? 'done' : 'waiting'),
                $status === AssetMaintenanceStatus::Submitted->value ? 'Menunggu Verifikasi Tim Aset' : null,
            ),
            $this->step(
                'Pemeliharaan Dimulai',
                'mulai_pemeliharaan',
                $byStage,
                in_array($status, [AssetMaintenanceStatus::InProgress->value, AssetMaintenanceStatus::Completed->value], true),
                $status === AssetMaintenanceStatus::Approved->value ? 'waiting' : (in_array($status, [AssetMaintenanceStatus::InProgress->value, AssetMaintenanceStatus::Completed->value], true) ? 'done' : 'upcoming'),
                $status === AssetMaintenanceStatus::Approved->value ? 'Menunggu Pemeliharaan Dimulai' : null,
            ),
            $this->step(
                'Pemeliharaan Selesai',
                'selesai',
                $byStage,
                $status === AssetMaintenanceStatus::Completed->value,
                $status === AssetMaintenanceStatus::InProgress->value ? 'waiting' : ($status === AssetMaintenanceStatus::Completed->value ? 'done' : 'upcoming'),
                $status === AssetMaintenanceStatus::InProgress->value ? 'Menunggu Pemeriksaan Hasil' : null,
            ),
        ];

        $histories
            ->where('tahap', 'data_diperbarui')
            ->each(function (RiwayatPersetujuan $history) use (&$timeline): void {
                $timeline[] = [
                    'label' => 'Data Pemeliharaan Diperbarui',
                    'state' => 'done',
                    'actor' => $history->nama_user_snapshot,
                    'time' => $history->diproses_pada,
                    'note' => $history->catatan,
                    'processed' => true,
                ];
            });

        return $timeline;
    }

    private function step(string $label, string $stage, Collection $byStage, bool $processed, string $state, ?string $waitingLabel = null): array
    {
        $history = $byStage->get($stage);

        return [
            'label' => $waitingLabel ?: $label,
            'state' => $state,
            'actor' => $history?->nama_user_snapshot,
            'time' => $history?->diproses_pada,
            'note' => $history?->catatan,
            'processed' => $processed && (bool) $history,
        ];
    }

    private function statusValue(mixed $status): ?string
    {
        return $status instanceof BackedEnum ? (string) $status->value : $status;
    }
}
