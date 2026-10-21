<?php

namespace App\Http\Controllers;

use App\Enums\ProcurementProposalStatus;
use App\Enums\UserRole;
use App\Http\Requests\ProcurementDecisionRequest;
use App\Http\Requests\StoreProcurementProposalRequest;
use App\Models\ProcurementProposal;
use App\Models\User;
use App\Services\RiwayatPersetujuanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ProcurementProposalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $this->roleValue($user?->role);
        $status = ProcurementProposalStatus::tryFrom($request->string('status')->toString());
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $proposals = ProcurementProposal::query()
            ->with($this->relations())
            ->when($role === UserRole::AdminBidang->value, fn (Builder $query) => $query->where('pengusul_id', $user->id))
            ->when(! in_array($role, [UserRole::AdminBidang->value, UserRole::SuperAdmin->value], true), fn (Builder $query) => $query->where('status', '!=', ProcurementProposalStatus::Approved->value))
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $query->where('nama_barang', 'like', '%'.$request->string('search')->toString().'%');
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('usulan-pengadaan.index', [
            'proposals' => $proposals,
            'users' => $this->userOptions(),
            'statuses' => ProcurementProposalStatus::cases(),
        ]);
    }

    public function store(StoreProcurementProposalRequest $request): RedirectResponse
    {
        abort_if(
            ! in_array($this->roleValue($request->user()?->role), [UserRole::AdminBidang->value, UserRole::SuperAdmin->value], true),
            403,
            'Usulan pengadaan hanya dapat dibuat oleh Admin Bidang.',
        );

        $photoPath = $request->file('photo')->store('procurement-proposals', 'public');

        try {
            $proposal = ProcurementProposal::query()->create([
                ...$request->safe()->except('photo'),
                'pengusul_id' => $request->user()->id,
                'path_foto' => $photoPath,
                'status' => ProcurementProposalStatus::Submitted,
            ]);

            app(RiwayatPersetujuanService::class)->record('procurement', $proposal, 'pengajuan', 'dibuat', $proposal->alasan_kebutuhan, $request->user());
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($photoPath);

            throw $exception;
        }

        return back()->with('success', 'Usulan pengadaan dikirim.');
    }

    public function show(Request $request, ProcurementProposal $procurementProposal): View
    {
        $this->authorizeView($request, $procurementProposal);

        return view('usulan-pengadaan.show', [
            'proposal' => $procurementProposal->load($this->relations()),
        ]);
    }

    public function assetTeamDecision(
        ProcurementDecisionRequest $request,
        ProcurementProposal $procurementProposal,
    ): RedirectResponse {
        abort_if(
            ! in_array($this->roleValue($request->user()?->role), [UserRole::TimAset->value, UserRole::SuperAdmin->value], true),
            403,
            'Keputusan Tim Aset hanya dapat dilakukan oleh Tim Aset.',
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
            ! in_array($this->roleValue($request->user()?->role), [UserRole::KasubbagUmumPerencanaan->value, UserRole::SuperAdmin->value], true),
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

    /** @return list<string> */
    private function relations(): array
    {
        return [
            'requester', 'intendedUser', 'responsiblePerson',
            'assetTeamReviewer', 'supervisorReviewer', 'procurement',
        ];
    }

    private function roleValue(mixed $role): ?string
    {
        return $role instanceof UserRole ? $role->value : $role;
    }
}
