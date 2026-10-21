<?php

namespace App\Http\Controllers;

use App\Enums\AssetDisposalStatus;
use App\Enums\LoanStatus;
use App\Enums\ProcurementProposalStatus;
use App\Enums\UserRole;
use App\Enums\WorkUnit;
use App\Models\AssetDisposalRequest;
use App\Models\LoanRequest;
use App\Models\ProcurementProposal;
use App\Services\RiwayatPersetujuanService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __invoke(Request $request): View
    {
        $role = $this->roleValue($request->user()?->role);
        $selectedType = $request->string('jenis', 'all')->toString();

        $items = $this->itemsForRole($role);
        $visibleItems = $selectedType === 'all'
            ? $items
            : $items->where('type', $selectedType)->values();

        return view('persetujuan.index', [
            'items' => $items,
            'visibleItems' => $visibleItems,
            'selectedType' => $selectedType,
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
        $model = match ($type) {
            'loan' => LoanRequest::query()->findOrFail($id),
            'procurement' => ProcurementProposal::query()->findOrFail($id),
            'disposal' => AssetDisposalRequest::query()->findOrFail($id),
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

    private function itemsForRole(?string $role): Collection
    {
        $historyService = app(RiwayatPersetujuanService::class);

        $loans = LoanRequest::query()
            ->with(['requester', 'asset.category', 'asset.thumbnailPhoto', 'details.asset.category', 'details.asset.thumbnailPhoto', 'reviewer', 'handedOverBy', 'returnedBy'])
            ->latest()
            ->limit(100)
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
            ->with(['requester', 'intendedUser', 'responsiblePerson', 'assetTeamReviewer', 'supervisorReviewer'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (ProcurementProposal $proposal) use ($role, $historyService) {
                $approval = $historyService->statusSummary('procurement', $proposal);

                return [
                'type' => 'procurement',
                'id' => $proposal->id,
                'status' => $proposal->status->value,
                'status_label' => $approval['label'],
                'approval' => $approval,
                'created_at' => $proposal->created_at,
                'title' => $proposal->nama_barang,
                'code' => 'Usulan #'.$proposal->id,
                'requester' => $proposal->requester->name,
                'requester_work_unit' => WorkUnit::labelFor($proposal->requester?->work_unit),
                'jumlah' => $proposal->jumlah,
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
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (AssetDisposalRequest $disposal) use ($role, $historyService) {
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
                'can_decide' => $role === UserRole::KasubbagUmumPerencanaan->value && $disposal->status === AssetDisposalStatus::Submitted,
                'data' => $disposal,
                ];
            });

        return $loans
            ->concat($proposals)
            ->concat($disposals)
            ->sortByDesc('created_at')
            ->values();
    }

    private function roleValue(mixed $role): ?string
    {
        return $role instanceof UserRole ? $role->value : $role;
    }

    private function redirectQuery(Request $request): array
    {
        $jenis = $request->string('jenis', 'all')->toString();

        return $jenis === 'all' ? [] : ['jenis' => $jenis];
    }
}
