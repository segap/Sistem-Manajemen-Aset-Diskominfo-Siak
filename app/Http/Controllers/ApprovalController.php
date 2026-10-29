<?php

namespace App\Http\Controllers;

use App\Enums\AssetDisposalStatus;
use App\Enums\LoanStatus;
use App\Enums\ProcurementProposalStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Enums\WorkUnit;
use App\Models\AssetDisposalRequest;
use App\Models\LoanRequest;
use App\Models\ProcurementProposal;
use App\Models\RiwayatPersetujuan;
use App\Services\RiwayatPersetujuanService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __invoke(Request $request): View
    {
        $role = $this->roleValue($request->user()?->role);
        $selectedType = $request->string('jenis', 'all')->toString();
        $selectedType = in_array($selectedType, ['all', 'loan', 'procurement', 'disposal'], true)
            ? $selectedType
            : 'all';
        $perPage = in_array($request->integer('per_page', 10), [10, 25, 50, 100], true)
            ? $request->integer('per_page', 10)
            : 10;

        $canApproveDisposal = $request->user()?->hasPermission('penghapusan.approve') ?? false;
        $items = $this->itemsForRole($role, $canApproveDisposal);
        $filteredItems = $selectedType === 'all'
            ? $items
            : $items->where('type', $selectedType)->values();
        $lastPage = max(1, (int) ceil($filteredItems->count() / $perPage));
        $currentPage = min(max(LengthAwarePaginator::resolveCurrentPage(), 1), $lastPage);
        $visibleItems = new LengthAwarePaginator(
            $filteredItems->forPage($currentPage, $perPage)->values(),
            $filteredItems->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('persetujuan.index', [
            'items' => $items,
            'visibleItems' => $visibleItems,
            'selectedType' => $selectedType,
            'selectedPerPage' => $perPage,
            'counts' => [
                'all' => $items->count(),
                'loan' => $items->where('type', 'loan')->count(),
                'procurement' => $items->where('type', 'procurement')->count(),
                'disposal' => $items->where('type', 'disposal')->count(),
            ],
            'waitingCount' => $items->filter(fn (array $item) => $item['can_decide'])->count(),
        ]);
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        abort_if(
            $type === 'procurement',
            403,
            'Usulan pengadaan hanya dapat dihapus melalui halaman Usulan Pengadaan.',
        );

        if ($type === 'disposal') {
            return $this->destroySubmittedDisposal($request, $id);
        }

        $model = match ($type) {
            'loan' => LoanRequest::query()->findOrFail($id),
            default => abort(404),
        };

        try {
            $model->delete();
        } catch (QueryException) {
            return redirect()
                ->route('persetujuan.index', $this->redirectQuery($request))
                ->withErrors(['delete' => 'Data persetujuan tidak dapat dihapus karena masih terhubung dengan proses lain.']);
        }

        return redirect()
            ->route('persetujuan.index', $this->redirectQuery($request))
            ->with('success', 'Data persetujuan berhasil dihapus.');
    }

    private function destroySubmittedDisposal(Request $request, int $id): RedirectResponse
    {
        try {
            DB::transaction(function () use ($id): void {
                // Keep the same lock order as the approval decision: request, then asset.
                $disposal = AssetDisposalRequest::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($disposal->getRawOriginal('status') !== AssetDisposalStatus::Submitted->value) {
                    throw ValidationException::withMessages([
                        'delete' => 'Hanya pengajuan penghapusan berstatus Diajukan yang dapat dihapus.',
                    ]);
                }

                if ($disposal->diperiksa_oleh !== null
                    || $disposal->tanggal_pemeriksaan !== null
                    || $disposal->alasan_penolakan !== null) {
                    throw ValidationException::withMessages([
                        'delete' => 'Pengajuan ini sudah memiliki indikator keputusan dan tidak dapat dihapus.',
                    ]);
                }

                $asset = Asset::query()->lockForUpdate()->findOrFail($disposal->aset_id);

                if ($asset->tanggal_penghapusan !== null) {
                    throw ValidationException::withMessages([
                        'delete' => 'Aset terkait sudah ditandai dihapus. Pengajuan tidak dapat dihapus dan harus diperiksa terlebih dahulu.',
                    ]);
                }

                $histories = RiwayatPersetujuan::query()
                    ->where('jenis_pengajuan', 'disposal')
                    ->where('pengajuan_id', $disposal->id)
                    ->lockForUpdate()
                    ->get();

                $initialHistory = $histories->first(
                    fn (RiwayatPersetujuan $history): bool => $history->tahap === 'pengajuan' && $history->status === 'dibuat',
                );

                if ($histories->count() !== 1 || ! $initialHistory) {
                    throw ValidationException::withMessages([
                        'delete' => 'Riwayat persetujuan pengajuan tidak konsisten. Penghapusan dibatalkan untuk menjaga integritas data.',
                    ]);
                }

                // The single initial history belongs only to this pending request. Evidence
                // records are removed by their database FK cascade; physical files are kept.
                $initialHistory->delete();
                $disposal->delete();
            });
        } catch (ValidationException $exception) {
            return redirect()
                ->route('persetujuan.index', $this->redirectQuery($request))
                ->withErrors($exception->errors());
        } catch (QueryException) {
            return redirect()
                ->route('persetujuan.index', $this->redirectQuery($request))
                ->withErrors(['delete' => 'Data persetujuan tidak dapat dihapus karena masih terhubung dengan data lain yang harus dipertahankan.']);
        }

        return redirect()
            ->route('persetujuan.index', $this->redirectQuery($request))
            ->with('success', 'Pengajuan penghapusan aset berstatus Diajukan berhasil dihapus.');
    }

    private function itemsForRole(?string $role, bool $canApproveDisposal): Collection
    {
        $historyService = app(RiwayatPersetujuanService::class);

        $loans = LoanRequest::query()
            ->with(['requester', 'asset.category', 'asset.thumbnailPhoto', 'details.asset.category', 'details.asset.thumbnailPhoto', 'reviewer', 'handedOverBy', 'returnedBy'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (LoanRequest $loan) use ($role, $historyService) {
                $approval = $historyService->statusSummary('loan', $loan);

                return [
                'type' => 'loan',
                'id' => $loan->id,
                'status' => $loan->status->value,
                'status_label' => $approval['label'],
                'approval' => $approval,
                'created_at' => $loan->created_at,
                'title' => $loan->itemSummary(),
                'code' => $loan->itemPreview(),
                'requester' => $loan->requester->name,
                'requester_work_unit' => WorkUnit::labelFor($loan->requester?->work_unit),
                'jumlah' => $loan->itemCount(),
                'can_decide' => ($role === UserRole::TimAset->value && $loan->status === LoanStatus::Submitted)
                    || ($role === UserRole::KasubbagUmumPerencanaan->value && $loan->status === LoanStatus::VerifiedByAssetTeam),
                'data' => $loan,
                ];
            });

        $proposals = ProcurementProposal::query()
            ->with(['requester', 'intendedUser', 'responsiblePerson', 'details.responsiblePerson', 'assetTeamReviewer', 'supervisorReviewer'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (ProcurementProposal $proposal) use ($role, $historyService) {
                $approval = $historyService->statusSummary('procurement', $proposal);
                $hasDetails = $proposal->details->isNotEmpty();
                $itemLines = $hasDetails
                    ? $proposal->details->map(fn ($detail): string => $detail->nama_barang.' ('.$detail->jumlah.')')->all()
                    : [$proposal->nama_barang.' ('.$proposal->jumlah.')'];

                return [
                'type' => 'procurement',
                'id' => $proposal->id,
                'status' => $proposal->status->value,
                'status_label' => $approval['label'],
                'approval' => $approval,
                'created_at' => $proposal->created_at,
                'title' => $hasDetails ? $proposal->details->pluck('nama_barang')->implode(', ') : $proposal->nama_barang,
                'code' => 'Usulan #'.$proposal->id,
                'requester' => $proposal->requester?->name ?: '-',
                'requester_work_unit' => WorkUnit::labelFor($proposal->unit_kerja_snapshot ?: $proposal->requester?->work_unit),
                'jumlah' => $hasDetails ? $proposal->details->count().' jenis' : $proposal->jumlah,
                'item_lines' => $itemLines,
                'can_decide' => $role === UserRole::KasubbagUmumPerencanaan->value && $proposal->status === ProcurementProposalStatus::Verified,
                'data' => $proposal,
                ];
            });

        $disposalRelations = ['requester', 'asset.category', 'reviewer'];

        if (Schema::hasColumn('pengajuan_penghapusan_aset', 'asset_condition_mutation_id')) {
            $disposalRelations[] = 'conditionMutation.user';
        }

        if (Schema::hasTable('pengajuan_penghapusan_bukti')) {
            $disposalRelations[] = 'evidences';
        }

        $disposals = AssetDisposalRequest::query()
            ->with($disposalRelations)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (AssetDisposalRequest $disposal) use ($canApproveDisposal, $historyService) {
                $approval = $historyService->statusSummary('disposal', $disposal);

                return [
                'type' => 'disposal',
                'id' => $disposal->id,
                'status' => $disposal->status->value,
                'status_label' => $approval['label'],
                'approval' => $approval,
                'created_at' => $disposal->created_at,
                'title' => $disposal->asset->nama_aset,
                'code' => $disposal->asset->display_code,
                'requester' => $disposal->requester->name,
                'jumlah' => null,
                'can_decide' => $canApproveDisposal && $disposal->status === AssetDisposalStatus::Submitted,
                'data' => $disposal,
                ];
            });

        return $loans
            ->concat($proposals)
            ->concat($disposals)
            ->sort(function (array $left, array $right): int {
                $dateComparison = $right['created_at']->getTimestamp() <=> $left['created_at']->getTimestamp();

                return $dateComparison !== 0
                    ? $dateComparison
                    : [$left['type'], $left['id']] <=> [$right['type'], $right['id']];
            })
            ->values();
    }

    private function roleValue(mixed $role): ?string
    {
        return $role instanceof UserRole ? $role->value : $role;
    }

    private function redirectQuery(Request $request): array
    {
        return $request->only(['jenis', 'per_page', 'page']);
    }
}
