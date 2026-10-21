<?php

namespace App\Http\Controllers;

use App\Enums\PermintaanPersediaanStatus;
use App\Enums\StockOpnameVerificationStatus;
use App\Enums\UserRole;
use App\Http\Requests\StorePermintaanPersediaanRequest;
use App\Http\Requests\VerifyPermintaanPersediaanRequest;
use App\Models\PermintaanPersediaan;
use App\Models\StockOpnameItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PermintaanPersediaanController extends Controller
{
    public function index(Request $request): View
    {
        $role = $this->roleValue($request->user()?->role);
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $status = PermintaanPersediaanStatus::tryFrom($request->string('status')->toString());

        $requests = PermintaanPersediaan::query()
            ->with(['pemohon', 'verifier', 'handoverBy', 'details.persediaan.category'])
            ->when($role === UserRole::AdminBidang->value, fn (Builder $query) => $query->where('pemohon_id', $request->user()->id))
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nomor_permintaan', 'like', "%{$search}%")
                        ->orWhere('keperluan', 'like', "%{$search}%")
                        ->orWhereHas('pemohon', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%"))
                        ->orWhereHas('details', fn (Builder $query) => $query
                            ->where('nama_barang_snapshot', 'like', "%{$search}%")
                            ->orWhere('justifikasi_kebutuhan', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('permintaan-persediaan.index', [
            'requests' => $requests,
            'stockItems' => $this->eligibleStockItems(),
            'statuses' => PermintaanPersediaanStatus::cases(),
            'selectedPerPage' => $perPage,
        ]);
    }

    public function store(StorePermintaanPersediaanRequest $request): RedirectResponse
    {
        $this->authorizeRole($request, [UserRole::AdminBidang]);

        $data = $request->validated();
        $items = collect($data['items'])
            ->filter(fn (array $item) => filled($item['persediaan_id'] ?? null) && filled($item['jumlah_diminta'] ?? null))
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Minimal pilih satu barang persediaan.']);
        }

        if ($items->pluck('persediaan_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => 'Barang yang sama tidak boleh diminta dua kali dalam satu permintaan.']);
        }

        DB::transaction(function () use ($request, $data, $items): void {
            $stocks = StockOpnameItem::query()
                ->whereIn('id', $items->pluck('persediaan_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $permintaan = PermintaanPersediaan::query()->create([
                'pemohon_id' => $request->user()->id,
                'tanggal_permintaan' => now(),
                'keperluan' => $data['keperluan'] ?? '-',
                'status' => PermintaanPersediaanStatus::Submitted,
            ]);

            $permintaan->update(['nomor_permintaan' => sprintf('PRM-%03d', $permintaan->id)]);

            foreach ($items as $index => $item) {
                $stock = $stocks->get((int) $item['persediaan_id']);
                $this->validateEligibleStock($stock, "items.{$index}.persediaan_id");

                if ((int) $item['jumlah_diminta'] > (int) $stock->stok) {
                    throw ValidationException::withMessages(["items.{$index}.jumlah_diminta" => 'Jumlah permintaan melebihi stok yang tersedia.']);
                }

                $permintaan->details()->create([
                    'persediaan_id' => $stock->id,
                    'nama_barang_snapshot' => $stock->nama_barang,
                    'satuan_snapshot' => $stock->satuan,
                    'stok_saat_pengajuan' => $stock->stok,
                    'jumlah_diminta' => (int) $item['jumlah_diminta'],
                    'justifikasi_kebutuhan' => $item['justifikasi_kebutuhan'],
                ]);
            }
        });

        return back()->with('success', 'Permintaan barang persediaan berhasil diajukan.');
    }

    public function verify(VerifyPermintaanPersediaanRequest $request, PermintaanPersediaan $permintaanPersediaan): RedirectResponse
    {
        $this->authorizeRole($request, [UserRole::TimAsetPersediaan, 'tim_aset_persediaan']);

        abort_if($permintaanPersediaan->status !== PermintaanPersediaanStatus::Submitted, 403, 'Permintaan ini sudah diproses.');

        $data = $request->validated();
        $decision = PermintaanPersediaanStatus::from($data['decision']);

        DB::transaction(function () use ($request, $permintaanPersediaan, $data, $decision): void {
            $permintaan = PermintaanPersediaan::query()->lockForUpdate()->findOrFail($permintaanPersediaan->id);
            $permintaan->load('details.persediaan');

            if ($decision === PermintaanPersediaanStatus::Rejected) {
                $permintaan->update([
                    'status' => PermintaanPersediaanStatus::Rejected,
                    'catatan' => $data['catatan'],
                    'diverifikasi_oleh' => $request->user()->id,
                    'diverifikasi_pada' => now(),
                ]);

                return;
            }

            foreach ($permintaan->details as $detail) {
                $approved = (int) ($data['items'][$detail->id]['jumlah_disetujui'] ?? $detail->jumlah_diminta);
                $stock = StockOpnameItem::query()->lockForUpdate()->findOrFail($detail->persediaan_id);

                if ($approved < 1 || $approved > $detail->jumlah_diminta || $approved > $stock->stok) {
                    throw ValidationException::withMessages(["items.{$detail->id}.jumlah_disetujui" => 'Jumlah disetujui tidak valid atau melebihi stok tersedia.']);
                }

                $detail->update(['jumlah_disetujui' => $approved]);
            }

            $permintaan->update([
                'status' => PermintaanPersediaanStatus::Approved,
                'catatan' => $data['catatan'] ?? null,
                'diverifikasi_oleh' => $request->user()->id,
                'diverifikasi_pada' => now(),
            ]);
        });

        return back()->with('success', 'Permintaan persediaan berhasil diverifikasi.');
    }

    public function handover(Request $request, PermintaanPersediaan $permintaanPersediaan): RedirectResponse
    {
        $this->authorizeRole($request, [UserRole::TimAsetPersediaan, 'tim_aset_persediaan']);

        abort_if($permintaanPersediaan->status !== PermintaanPersediaanStatus::Approved, 403, 'Permintaan belum disetujui untuk diserahkan.');

        DB::transaction(function () use ($request, $permintaanPersediaan): void {
            $permintaan = PermintaanPersediaan::query()->lockForUpdate()->findOrFail($permintaanPersediaan->id);
            $permintaan->load('details');

            foreach ($permintaan->details as $detail) {
                $stock = StockOpnameItem::query()->lockForUpdate()->findOrFail($detail->persediaan_id);
                $qty = (int) ($detail->jumlah_disetujui ?? 0);

                if ($qty < 1 || $qty > $stock->stok) {
                    throw ValidationException::withMessages(['stok' => "Stok {$stock->nama_barang} tidak mencukupi untuk penyerahan."]);
                }

                $before = (int) $stock->stok;
                $after = $before - $qty;

                $stock->update(['stok' => $after]);
                $stock->mutations()->create([
                    'user_id' => $request->user()->id,
                    'jenis' => 'keluar',
                    'referensi_type' => 'permintaan_persediaan',
                    'referensi_id' => $permintaan->id,
                    'jumlah' => $qty,
                    'stok_sebelum' => $before,
                    'stok_sesudah' => $after,
                    'catatan' => 'Penyerahan '.$permintaan->nomor_permintaan,
                    'waktu_transaksi' => now(),
                ]);

                $detail->update(['jumlah_diserahkan' => $qty]);
            }

            $permintaan->update([
                'status' => PermintaanPersediaanStatus::Completed,
                'diserahkan_oleh' => $request->user()->id,
                'diserahkan_pada' => now(),
            ]);
        });

        return back()->with('success', 'Barang persediaan berhasil diserahkan dan stok sudah dikurangi.');
    }

    private function eligibleStockItems()
    {
        return StockOpnameItem::query()
            ->with('category')
            ->where('status_verifikasi', StockOpnameVerificationStatus::Approved->value)
            ->where('is_active', true)
            ->where('stok', '>', 0)
            ->orderBy('nama_barang')
            ->get();
    }

    private function validateEligibleStock(?StockOpnameItem $stock, string $field): void
    {
        if (! $stock
            || $stock->status_verifikasi !== StockOpnameVerificationStatus::Approved
            || ! $stock->is_active
            || $stock->stok < 1) {
            throw ValidationException::withMessages([$field => 'Barang persediaan tidak tersedia atau belum diverifikasi.']);
        }
    }

    private function authorizeRole(Request $request, array $roles): void
    {
        $role = $this->roleValue($request->user()?->role);

        if ($role === UserRole::SuperAdmin->value) {
            return;
        }

        $allowedRoles = array_map(fn (UserRole|string $role): string => $role instanceof UserRole ? $role->value : $role, $roles);

        abort_if(! in_array($role, $allowedRoles, true), 403, 'Anda tidak memiliki akses ke fitur ini.');
    }

    private function roleValue(mixed $role): ?string
    {
        return $role instanceof UserRole ? $role->value : $role;
    }
}
