<?php

namespace App\Http\Controllers;

use App\Enums\ProcurementProposalStatus;
use App\Enums\UserRole;
use App\Enums\WorkUnit;
use App\Http\Requests\ProcurementDecisionRequest;
use App\Http\Requests\StoreProcurementProposalRequest;
use App\Http\Requests\UpdateProcurementProposalRequest;
use App\Models\ProcurementProposal;
use App\Models\RiwayatPersetujuan;
use App\Models\User;
use App\Services\RiwayatPersetujuanService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Throwable;

class ProcurementProposalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $this->roleValue($user?->role);
        $status = ProcurementProposalStatus::tryFrom($request->string('status')->toString());
        $spendingTypes = ProcurementProposal::spendingTypes();
        $selectedSpendingType = $request->string('jenis_belanja')->toString();
        $selectedSpendingType = array_key_exists($selectedSpendingType, $spendingTypes) || $selectedSpendingType === 'unclassified'
            ? $selectedSpendingType
            : '';
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $proposals = ProcurementProposal::query()
            ->with($this->relations())
            ->when($role === UserRole::AdminBidang->value, fn (Builder $query) => $query->where('pengusul_id', $user->id))
            ->when(! in_array($role, [UserRole::AdminBidang->value, UserRole::SuperAdmin->value], true), fn (Builder $query) => $query->where('status', '!=', ProcurementProposalStatus::Approved->value))
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->when($selectedSpendingType === 'unclassified', fn (Builder $query) => $query->whereNull('jenis_belanja'))
            ->when(array_key_exists($selectedSpendingType, $spendingTypes), fn (Builder $query) => $query->where('jenis_belanja', $selectedSpendingType))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nama_barang', 'like', $search)
                        ->orWhereHas('details', fn (Builder $detailQuery) => $detailQuery->where('nama_barang', 'like', $search));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('usulan-pengadaan.index', [
            'proposals' => $proposals,
            'users' => $this->userOptions(),
            'statuses' => ProcurementProposalStatus::cases(),
            'spendingTypes' => $spendingTypes,
            'selectedSpendingType' => $selectedSpendingType,
            'proposalTimelines' => $proposals->getCollection()
                ->mapWithKeys(fn (ProcurementProposal $proposal): array => [
                    $proposal->id => app(RiwayatPersetujuanService::class)->timeline(
                        'procurement',
                        $proposal,
                        $proposal->approvalHistories,
                    ),
                ]),
        ]);
    }

    public function store(StoreProcurementProposalRequest $request): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasPermission('usulan_pengadaan.create'),
            403,
            'Anda tidak memiliki hak akses untuk membuat usulan pengadaan.',
        );

        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            $proposal = ProcurementProposal::query()->create([
                'pengusul_id' => $request->user()->id,
                'tahun_anggaran' => $validated['tahun_anggaran'],
                'jenis_belanja' => $validated['jenis_belanja'],
                'tanggal_pengajuan' => $validated['tanggal_pengajuan'],
                'unit_kerja_snapshot' => $request->user()->work_unit,
                'keterangan_umum' => $validated['keterangan_umum'] ?? null,
                'status' => ProcurementProposalStatus::Submitted,
            ]);

            $proposal->details()->createMany(collect($validated['details'])
                ->values()
                ->map(fn (array $detail, int $index): array => [
                    ...$detail,
                    'nomor_urut' => $index + 1,
                ])->all());

            app(RiwayatPersetujuanService::class)->record(
                'procurement',
                $proposal,
                'pengajuan',
                'dibuat',
                $proposal->keterangan_umum,
                $request->user(),
            );
        });

        return back()->with('success', 'Usulan pengadaan dikirim.');
    }

    public function show(Request $request, ProcurementProposal $procurementProposal): View
    {
        $this->authorizeView($request, $procurementProposal);
        $proposal = $procurementProposal->load($this->relations());

        return view('usulan-pengadaan.show', [
            'proposal' => $proposal,
            'timeline' => app(RiwayatPersetujuanService::class)->timeline(
                'procurement',
                $proposal,
                $proposal->approvalHistories,
            ),
        ]);
    }

    public function pdf(Request $request, ProcurementProposal $procurementProposal): Response
    {
        $this->authorizeView($request, $procurementProposal);

        abort_unless(
            $procurementProposal->status === ProcurementProposalStatus::Approved,
            403,
            'PDF kebutuhan Sarpras hanya tersedia untuk usulan yang telah disetujui Kasubbag.',
        );

        $proposal = $procurementProposal->load(['requester', 'responsiblePerson', 'details.responsiblePerson']);
        $details = $proposal->details->isNotEmpty()
            ? $proposal->details
            : collect([(object) [
                'nomor_urut' => 1,
                'nama_barang' => $proposal->nama_barang,
                'spesifikasi' => $proposal->spesifikasi,
                'jumlah' => $proposal->jumlah,
                'estimasi_harga_satuan' => $proposal->estimasi_harga_satuan,
                'link_produk' => $proposal->url_ekatalog ?: $proposal->url_referensi_gambar,
                'keterangan' => $proposal->alasan_kebutuhan,
                'responsiblePerson' => $proposal->responsiblePerson,
            ]]);
        $unitLabel = WorkUnit::labelFor($proposal->unit_kerja_snapshot ?: $proposal->requester?->work_unit);
        $yearLabel = $proposal->tahun_anggaran ? (string) $proposal->tahun_anggaran : 'Belum tersedia';
        $spendingTypeLabel = $proposal->jenis_belanja_label;

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('reports.procurement-proposal-needs', compact('proposal', 'details', 'unitLabel', 'yearLabel', 'spendingTypeLabel'))->render());
        $pdf->setPaper('a3', 'landscape');
        $pdf->render();

        $unitFilename = Str::slug($unitLabel) ?: 'bidang';
        $yearFilename = $proposal->tahun_anggaran ?: 'belum-tersedia';

        return response($pdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Kebutuhan_Sarpras_'.$unitFilename.'_TA_'.$yearFilename.'_Usulan_'.$proposal->id.'.pdf"',
        ]);
    }

    public function update(UpdateProcurementProposalRequest $request, ProcurementProposal $procurementProposal): RedirectResponse
    {
        if ($procurementProposal->details()->exists()) {
            return $this->updateMultiItemProposal($request, $procurementProposal);
        }

        $newPhotoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('procurement-proposals', 'public')
            : null;

        try {
            $oldPhotoPath = DB::transaction(function () use ($request, $procurementProposal, $newPhotoPath): ?string {
                $proposal = ProcurementProposal::query()
                    ->lockForUpdate()
                    ->findOrFail($procurementProposal->id);

                $this->authorizeUpdate($request, $proposal);

                if ($proposal->status !== ProcurementProposalStatus::Submitted) {
                    throw ValidationException::withMessages(['status' => 'Usulan hanya dapat diubah saat masih berstatus diajukan.']);
                }

                if ($proposal->procurement()->exists()) {
                    throw ValidationException::withMessages(['status' => 'Usulan yang sudah diproses menjadi pengadaan tidak dapat diubah.']);
                }

                $data = $request->safe()->except('photo');
                if ($newPhotoPath) {
                    $data['path_foto'] = $newPhotoPath;
                }

                $oldPhotoPath = $proposal->path_foto;
                $proposal->update($data);

                return $oldPhotoPath;
            });
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if ($newPhotoPath && $oldPhotoPath && $oldPhotoPath !== $newPhotoPath
            && ! ProcurementProposal::query()->where('path_foto', $oldPhotoPath)->exists()) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return back()->with('success', 'Usulan pengadaan berhasil diperbarui.');
    }

    private function updateMultiItemProposal(UpdateProcurementProposalRequest $request, ProcurementProposal $procurementProposal): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $procurementProposal, $validated): void {
            $proposal = ProcurementProposal::query()
                ->lockForUpdate()
                ->findOrFail($procurementProposal->id);

            $this->authorizeUpdate($request, $proposal);

            if ($proposal->status !== ProcurementProposalStatus::Submitted) {
                throw ValidationException::withMessages(['status' => 'Usulan hanya dapat diubah saat masih berstatus diajukan.']);
            }

            if ($proposal->procurement()->exists()) {
                throw ValidationException::withMessages(['status' => 'Usulan yang sudah diproses menjadi pengadaan tidak dapat diubah.']);
            }

            $existingDetails = $proposal->details()->lockForUpdate()->get()->keyBy('id');
            $retainedDetailIds = [];

            foreach (collect($validated['details'])->values() as $index => $detailData) {
                $detailId = $detailData['id'] ?? null;
                $data = collect($detailData)->except('id')->all() + ['nomor_urut' => $index + 1];

                if ($detailId !== null) {
                    $detail = $existingDetails->get((int) $detailId);
                    if (! $detail) {
                        throw ValidationException::withMessages(['details' => 'Rincian barang tidak sesuai dengan usulan yang sedang diubah.']);
                    }

                    $detail->update($data);
                    $retainedDetailIds[] = $detail->id;
                    continue;
                }

                $proposal->details()->create($data);
            }

            $proposal->details()
                ->whereNotIn('id', $retainedDetailIds)
                ->delete();

            $proposal->update([
                'tahun_anggaran' => $validated['tahun_anggaran'],
                'jenis_belanja' => $validated['jenis_belanja'],
                'tanggal_pengajuan' => $validated['tanggal_pengajuan'],
                'keterangan_umum' => $validated['keterangan_umum'] ?? null,
            ]);
        });

        return back()->with('success', 'Usulan pengadaan multi-barang berhasil diperbarui.');
    }

    public function destroy(Request $request, ProcurementProposal $procurementProposal): RedirectResponse
    {
        try {
            $photoPath = DB::transaction(function () use ($request, $procurementProposal): ?string {
                $proposal = ProcurementProposal::query()
                    ->lockForUpdate()
                    ->findOrFail($procurementProposal->id);

                $this->authorizeDestroy($request);

                if ($proposal->procurement()->exists()) {
                    throw ValidationException::withMessages(['delete' => 'Usulan tidak dapat dihapus karena sudah digunakan dalam proses pengadaan.']);
                }

                $histories = RiwayatPersetujuan::query()
                    ->where('jenis_pengajuan', 'procurement')
                    ->where('pengajuan_id', $proposal->id)
                    ->lockForUpdate()
                    ->get();

                $photoPath = $proposal->path_foto;

                // Riwayat tidak memiliki FK ke usulan. Hapus hanya riwayat yang
                // secara eksplisit milik usulan ini agar tidak menjadi yatim.
                $histories->each->delete();
                $proposal->delete();

                return $photoPath;
            });
        } catch (QueryException) {
            return back()->withErrors(['delete' => 'Usulan tidak dapat dihapus karena masih terhubung dengan proses lain.']);
        }

        $this->deleteProposalPhotoIfUnused($photoPath);

        return back()->with('success', 'Usulan pengadaan berhasil dihapus secara permanen.');
    }

    public function assetTeamDecision(
        ProcurementDecisionRequest $request,
        ProcurementProposal $procurementProposal,
    ): RedirectResponse {
        abort_unless(
            $request->user()?->hasPermission('usulan_pengadaan.verify'),
            403,
            'Anda tidak memiliki hak akses untuk memverifikasi usulan pengadaan.',
        );

        $decision = ProcurementProposalStatus::from($request->validated('decision'));

        if (! in_array($decision, [ProcurementProposalStatus::Verified, ProcurementProposalStatus::Rejected], true)) {
            throw ValidationException::withMessages(['decision' => 'Keputusan Tim Aset tidak valid.']);
        }

        DB::transaction(function () use ($request, $procurementProposal, $decision): void {
            $proposal = ProcurementProposal::query()->lockForUpdate()->findOrFail($procurementProposal->id);

            if ($proposal->status !== ProcurementProposalStatus::Submitted) {
                throw ValidationException::withMessages(['status' => 'Usulan ini sudah diproses oleh Tim Aset.']);
            }

            $proposal->update([
                'status' => $decision,
                'pemeriksa_tim_aset_id' => $request->user()->id,
                'tanggal_pemeriksaan_tim_aset' => now(),
                'catatan_tim_aset' => $request->validated('catatan'),
                'tahap_penolakan' => $decision === ProcurementProposalStatus::Rejected ? 'tim_aset' : null,
                'alasan_penolakan' => $decision === ProcurementProposalStatus::Rejected
                    ? $request->validated('alasan_penolakan')
                    : null,
            ]);

            app(RiwayatPersetujuanService::class)->record(
                'procurement',
                $proposal,
                'verifikasi_tim_aset',
                $decision === ProcurementProposalStatus::Verified ? 'disetujui' : 'ditolak',
                $decision === ProcurementProposalStatus::Rejected ? $request->validated('alasan_penolakan') : $request->validated('catatan'),
                $request->user(),
            );
        });

        return back()->with('success', $decision === ProcurementProposalStatus::Verified ? 'Usulan diverifikasi Tim Aset.' : 'Usulan ditolak Tim Aset.');
    }

    public function supervisorDecision(
        ProcurementDecisionRequest $request,
        ProcurementProposal $procurementProposal,
    ): RedirectResponse {
        abort_if(
            $this->roleValue($request->user()?->role) !== UserRole::KasubbagUmumPerencanaan->value,
            403,
            'Keputusan Kasubbag hanya dapat dilakukan oleh Kepala Sub Bagian Perencanaan dan Umum.',
        );

        $decision = ProcurementProposalStatus::from($request->validated('decision'));

        if (! in_array($decision, [ProcurementProposalStatus::Approved, ProcurementProposalStatus::Rejected], true)) {
            throw ValidationException::withMessages(['decision' => 'Keputusan Kasubbag tidak valid.']);
        }

        DB::transaction(function () use ($request, $procurementProposal, $decision): void {
            $proposal = ProcurementProposal::query()->lockForUpdate()->findOrFail($procurementProposal->id);

            if ($proposal->status !== ProcurementProposalStatus::Verified) {
                throw ValidationException::withMessages(['status' => 'Usulan harus diverifikasi Tim Aset terlebih dahulu.']);
            }

            $proposal->update([
                'status' => $decision,
                'pemeriksa_kasubbag_id' => $request->user()->id,
                'tanggal_pemeriksaan_kasubbag' => now(),
                'catatan_kasubbag' => $request->validated('catatan'),
                'tahap_penolakan' => $decision === ProcurementProposalStatus::Rejected ? 'kasubbag' : null,
                'alasan_penolakan' => $decision === ProcurementProposalStatus::Rejected
                    ? $request->validated('alasan_penolakan')
                    : null,
            ]);

            app(RiwayatPersetujuanService::class)->record(
                'procurement',
                $proposal,
                'persetujuan_kasubbag',
                $decision === ProcurementProposalStatus::Approved ? 'disetujui' : 'ditolak',
                $decision === ProcurementProposalStatus::Rejected ? $request->validated('alasan_penolakan') : $request->validated('catatan'),
                $request->user(),
            );
        });

        return back()->with('success', $decision === ProcurementProposalStatus::Approved ? 'Usulan disetujui Kasubbag.' : 'Usulan ditolak Kasubbag.');
    }

    private function userOptions()
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'nip']);
    }

    private function authorizeView(Request $request, ProcurementProposal $proposal): void
    {
        abort_if(
            $this->roleValue($request->user()?->role) === UserRole::AdminBidang->value
                && $proposal->pengusul_id !== $request->user()->id,
            403,
            'Anda tidak memiliki akses ke usulan ini.',
        );
    }

    private function authorizeUpdate(Request $request, ProcurementProposal $proposal): void
    {
        $role = $this->roleValue($request->user()?->role);

        abort_unless(
            $role === UserRole::SuperAdmin->value
                || ($role === UserRole::AdminBidang->value && $proposal->pengusul_id === $request->user()?->id),
            403,
            'Anda tidak memiliki akses untuk mengubah usulan ini.',
        );
    }

    private function authorizeDestroy(Request $request): void
    {
        abort_unless(
            $this->roleValue($request->user()?->role) === UserRole::SuperAdmin->value,
            403,
            'Hanya Super Admin yang dapat menghapus usulan pengadaan.',
        );
    }

    private function deleteProposalPhotoIfUnused(?string $photoPath): void
    {
        if (! $photoPath || ! str_starts_with($photoPath, 'procurement-proposals/')) {
            return;
        }

        if (ProcurementProposal::query()->where('path_foto', $photoPath)->exists()) {
            return;
        }

        try {
            if (! Storage::disk('public')->delete($photoPath)) {
                Log::warning('Foto usulan pengadaan tidak dapat dibersihkan setelah penghapusan.', [
                    'path_foto' => $photoPath,
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Gagal membersihkan foto usulan pengadaan setelah penghapusan.', [
                'path_foto' => $photoPath,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /** @return list<string> */
    private function relations(): array
    {
        return [
            'requester', 'intendedUser', 'responsiblePerson', 'details.responsiblePerson',
            'assetTeamReviewer', 'supervisorReviewer', 'procurement', 'approvalHistories',
        ];
    }

    private function roleValue(mixed $role): ?string
    {
        return $role instanceof UserRole ? $role->value : $role;
    }
}
