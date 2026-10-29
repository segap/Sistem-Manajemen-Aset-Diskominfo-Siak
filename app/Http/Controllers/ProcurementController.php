<?php

namespace App\Http\Controllers;

use App\Enums\AssetCondition;
use App\Enums\ProcurementProposalStatus;
use App\Enums\ProcurementStatus;
use App\Enums\UserRole;
use App\Http\Requests\ReceiveProcurementRequest;
use App\Http\Requests\StoreProcurementRequest;
use App\Http\Requests\UpdateProcurementRequest;
use App\Http\Requests\VerifyIncomingProcurementRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Procurement;
use App\Models\ProcurementProposal;
use App\Services\KodeInternalService;
use App\Services\AssetConditionStockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    public function index(Request $request): View
    {
        $status = ProcurementStatus::tryFrom($request->query('status', ''));
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $procurements = Procurement::query()
            ->with($this->relations())
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('pemasok', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('pengadaan.index', [
            'procurements' => $procurements,
            'statuses' => ProcurementStatus::cases(),
            'conditions' => AssetCondition::cases(),
            'categories' => AssetCategory::query()->orderBy('nama_kategori')->get(),
            'approvedProposals' => $this->approvedProposalOptions(),
        ]);
    }

    public function store(StoreProcurementRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $proposalId = $request->validated('usulan_pengadaan_id');
            $proposal = ProcurementProposal::query()->lockForUpdate()->findOrFail($proposalId);

            if ($proposal->status !== ProcurementProposalStatus::Approved) {
                throw ValidationException::withMessages([
                    'usulan_pengadaan_id' => 'Usulan harus disetujui sebelum diproses menjadi pengadaan.',
                ]);
            }

            if (Procurement::query()->where('usulan_pengadaan_id', $proposalId)->exists()) {
                throw ValidationException::withMessages([
                    'usulan_pengadaan_id' => 'Usulan ini sudah diproses menjadi pengadaan.',
                ]);
            }

            if ($proposal->details()->exists()) {
                throw ValidationException::withMessages([
                    'usulan_pengadaan_id' => 'Usulan multi-barang belum dapat diproses melalui alur Pengadaan Barang satu-barang.',
                ]);
            }

            Procurement::query()->create([
                ...$request->validated(),
                'nama_barang' => $proposal->nama_barang,
                'spesifikasi' => $proposal->spesifikasi,
                'jumlah_dipesan' => $proposal->jumlah,
                'harga_satuan' => $proposal->estimasi_harga_satuan,
                'alasan_pengadaan' => $proposal->alasan_kebutuhan,
                'dibuat_oleh' => $request->user()->id,
                'status' => ProcurementStatus::Ordered,
            ]);
        });

        return back()->with('success', 'Pengadaan berhasil dicatat.');
    }

    public function show(Procurement $procurement): View
    {
        return view('pengadaan.show', [
            'procurement' => $procurement->load($this->relations()),
            'conditions' => AssetCondition::cases(),
        ]);
    }

    public function update(UpdateProcurementRequest $request, Procurement $procurement): RedirectResponse
    {
        DB::transaction(function () use ($request, $procurement): void {
            $locked = Procurement::query()->lockForUpdate()->findOrFail($procurement->id);

            $this->ensureUnprocessed($locked, 'diubah');

            // Nilai yang berasal dari usulan, identitas pembuat, dan status tidak pernah diperbarui dari form ini.
            $locked->update($request->validated());
        });

        return back()->with('success', 'Pengadaan berhasil diperbarui.');
    }

    public function destroy(Request $request, Procurement $procurement): RedirectResponse
    {
        $role = $request->user()?->role;
        $roleValue = $role instanceof UserRole ? $role->value : $role;

        abort_unless($roleValue === UserRole::SuperAdmin->value, 403, 'Hanya Super Admin yang dapat menghapus pengadaan.');

        try {
            DB::transaction(function () use ($procurement): void {
                $locked = Procurement::query()->lockForUpdate()->findOrFail($procurement->id);

                $this->ensureUnprocessed($locked, 'dihapus');

                $locked->delete();
            });
        } catch (QueryException) {
            return back()->withErrors([
                'delete' => 'Pengadaan tidak dapat dihapus karena masih terhubung dengan data lain yang harus dipertahankan.',
            ]);
        }

        return back()->with('success', 'Pengadaan berhasil dihapus secara permanen.');
    }

    public function receive(ReceiveProcurementRequest $request, Procurement $procurement): RedirectResponse
    {
        $proofPath = $request->file('bukti_penerimaan')->store('procurements/receipts', 'public');

        try {
            DB::transaction(function () use ($request, $procurement, $proofPath): void {
                $locked = Procurement::query()->lockForUpdate()->findOrFail($procurement->id);

                if ($locked->status !== ProcurementStatus::Ordered) {
                    throw ValidationException::withMessages(['status' => 'Pengadaan ini sudah dicatat penerimaannya.']);
                }

                if ($request->integer('jumlah_diterima') > $locked->jumlah_dipesan) {
                    throw ValidationException::withMessages([
                        'jumlah_diterima' => 'Jumlah diterima tidak boleh melebihi jumlah pesanan.',
                    ]);
                }

                $data = $request->validated();
                unset($data['bukti_penerimaan']);

                $locked->update([
                    ...$data,
                    'status' => ProcurementStatus::Received,
                    'diterima_oleh' => $request->user()->id,
                    'bukti_penerimaan' => $proofPath,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($proofPath);

            throw $exception;
        }

        return back()->with('success', 'Penerimaan barang berhasil dicatat.');
    }

    public function verify(VerifyIncomingProcurementRequest $request, Procurement $procurement, KodeInternalService $kodeInternalService, AssetConditionStockService $conditionStockService): RedirectResponse
    {
        $this->ensureAssetRelationTableAvailable();

        DB::transaction(function () use ($request, $procurement, $kodeInternalService, $conditionStockService): void {
            $locked = Procurement::query()->lockForUpdate()->findOrFail($procurement->id);

            // Ulangi pemeriksaan setelah lock agar tidak ada efek samping bila struktur berubah saat request berjalan.
            $this->ensureAssetRelationTableAvailable();

            if ($locked->status !== ProcurementStatus::Received) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya barang yang telah diterima yang dapat diverifikasi.',
                ]);
            }

            $decision = ProcurementStatus::from($request->validated('decision'));
            $assetId = null;
            $createdAssetIds = [];

            if ($decision === ProcurementStatus::Verified) {
                for ($unit = 1; $unit <= (int) $locked->jumlah_diterima; $unit++) {
                    $asset = Asset::query()->create([
                        'kategori_aset_id' => $locked->kategori_aset_id,
                        'nama_aset' => $locked->nama_barang,
                        'merek_tipe' => $locked->merek_tipe,
                        'spesifikasi' => $locked->spesifikasi,
                        'jumlah' => 1,
                        'tahun_perolehan' => $locked->tanggal_penerimaan->year,
                        'kondisi' => $request->validated('kondisi'),
                        'status_verifikasi' => 'disetujui',
                        'tanggal_verifikasi' => now(),
                        'diverifikasi_oleh' => $request->user()->id,
                        'catatan_verifikasi' => $request->validated('catatan_verifikasi'),
                        'pemasok' => $locked->pemasok,
                        'lokasi_penyimpanan' => $request->validated('lokasi_penyimpanan'),
                    ]);

                    $conditionStockService->resetToMainCondition($asset);
                    $createdAssetIds[] = $asset->id;
                    $assetId ??= $asset->id;

                    $kodeInternalService->generate($asset);
                }
            }

            $locked->update([
                'status' => $decision,
                'diverifikasi_oleh' => $request->user()->id,
                'tanggal_verifikasi' => now(),
                'kondisi' => $decision === ProcurementStatus::Verified ? $request->validated('kondisi') : null,
                'lokasi_penyimpanan' => $decision === ProcurementStatus::Verified
                    ? $request->validated('lokasi_penyimpanan')
                    : null,
                'catatan_verifikasi' => $request->validated('catatan_verifikasi'),
                'alasan_penolakan' => $decision === ProcurementStatus::Rejected
                    ? $request->validated('alasan_penolakan')
                    : null,
                'aset_id' => $assetId,
            ]);

            if ($decision === ProcurementStatus::Verified && $createdAssetIds !== []) {
                $locked->assets()->syncWithoutDetaching($createdAssetIds);

                $linkedAssetCount = $locked->assets()
                    ->whereIn('aset.id', $createdAssetIds)
                    ->distinct()
                    ->count('aset.id');

                if ($linkedAssetCount !== count($createdAssetIds)) {
                    throw ValidationException::withMessages([
                        'status' => 'Verifikasi pengadaan gagal karena relasi seluruh aset tidak dapat disimpan.',
                    ]);
                }
            }
        });

        return back()->with('success', 'Verifikasi pengadaan berhasil diproses.');
    }

    private function approvedProposalOptions()
    {
        return ProcurementProposal::query()
            ->with(['requester', 'procurement'])
            ->where('status', ProcurementProposalStatus::Approved->value)
            ->whereDoesntHave('procurement')
            ->doesntHave('details')
            ->latest()
            ->limit(100)
            ->get();
    }

    private function ensureUnprocessed(Procurement $procurement, string $action): void
    {
        if ($procurement->status !== ProcurementStatus::Ordered) {
            throw ValidationException::withMessages([
                'status' => "Pengadaan tidak dapat {$action} karena sudah masuk proses penerimaan atau verifikasi.",
            ]);
        }

        if ($procurement->jumlah_diterima !== null
            || $procurement->tanggal_penerimaan !== null
            || $procurement->diterima_oleh !== null
            || $procurement->catatan_penerimaan !== null
            || $procurement->bukti_penerimaan !== null
            || $procurement->diverifikasi_oleh !== null
            || $procurement->tanggal_verifikasi !== null
            || $procurement->kondisi !== null
            || $procurement->lokasi_penyimpanan !== null
            || $procurement->catatan_verifikasi !== null
            || $procurement->alasan_penolakan !== null) {
            throw ValidationException::withMessages([
                'status' => "Pengadaan tidak dapat {$action} karena memiliki data penerimaan atau verifikasi.",
            ]);
        }

        if ($procurement->asset()->exists()) {
            throw ValidationException::withMessages([
                'status' => "Pengadaan tidak dapat {$action} karena sudah terhubung dengan aset.",
            ]);
        }

        if (Schema::hasTable('pengadaan_aset') && $procurement->assets()->exists()) {
            throw ValidationException::withMessages([
                'status' => "Pengadaan tidak dapat {$action} karena sudah menghasilkan aset terkait.",
            ]);
        }
    }

    private function ensureAssetRelationTableAvailable(): void
    {
        if (! Schema::hasTable('pengadaan_aset')) {
            throw ValidationException::withMessages([
                'status' => 'Verifikasi pengadaan belum dapat diproses karena konfigurasi relasi aset belum tersedia. Hubungi administrator.',
            ]);
        }
    }

    /** @return list<string> */
    private function relations(): array
    {
        $relations = ['proposal.requester', 'category', 'creator', 'receiver', 'verifier', 'asset'];

        if (Schema::hasTable('pengadaan_aset')) {
            $relations[] = 'assets';
        }

        return $relations;
    }

    /** @param list<UserRole> $roles */
    private function hasRole(Request $request, array $roles): bool
    {
        $role = $request->user()?->role;
        $roleValue = $role instanceof UserRole ? $role->value : $role;

        if ($roleValue === UserRole::SuperAdmin->value) {
            return true;
        }

        return in_array($roleValue, array_map(fn (UserRole $role) => $role->value, $roles), true);
    }
}
