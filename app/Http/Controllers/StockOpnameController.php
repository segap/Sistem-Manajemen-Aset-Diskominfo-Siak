<?php

namespace App\Http\Controllers;

use App\Enums\StockOpnameVerificationStatus;
use App\Enums\UserRole;
use App\Http\Requests\AddStockOpnameStockRequest;
use App\Http\Requests\AdjustStockOpnameItemRequest;
use App\Http\Requests\StoreStockOpnameItemRequest;
use App\Http\Requests\UpdateStockOpnameItemRequest;
use App\Models\KodeBarang;
use App\Models\Ruangan;
use App\Models\StockOpnameCategory;
use App\Models\StockOpnameItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockOpnameController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $stockStatus = $request->string('stock_status')->toString();
        $items = StockOpnameItem::query()
            ->with(['category', 'kodePersediaan', 'kodeBarang', 'requester', 'verifier', 'mutations'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('kode_internal', 'like', "%{$search}%")
                        ->orWhere('kode_persediaan', 'like', "%{$search}%")
                        ->orWhere('nama_barang', 'like', "%{$search}%")
                        ->orWhere('merek_tipe', 'like', "%{$search}%")
                        ->orWhere('lokasi_penyimpanan', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->where('stock_opname_category_id', $request->integer('category')))
            ->when($stockStatus === 'rendah', fn (Builder $query) => $query->where('stok', '<', 5))
            ->when($stockStatus === 'tersedia', fn (Builder $query) => $query->where('stok', '>=', 5))
            ->when($stockStatus === 'menipis', fn (Builder $query) => $query->where('stok', '>', 0)->where('stok', '<', 5))
            ->when($stockStatus === 'habis', fn (Builder $query) => $query->where('stok', 0))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('stock-opname.index', [
            'items' => $items,
            'categories' => StockOpnameCategory::query()->orderBy('nama_kategori')->get(),
            'kodeBarangOptions' => KodeBarang::query()->orderBy('kode_barang')->limit(500)->get(),
            'ruanganOptions' => Ruangan::query()->orderBy('nama_ruangan')->get(),
            'selectedPerPage' => $perPage,
        ]);
    }

    public function store(StoreStockOpnameItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $kodeBarang = filled($data['kode_barang_id'] ?? null)
            ? KodeBarang::query()->findOrFail($data['kode_barang_id'])
            : null;
        $kodeInternal = $this->generateInternalCode($data['nama_barang']);

        DB::transaction(function () use ($data, $kodeBarang, $kodeInternal, $request): void {
            $item = StockOpnameItem::query()->create([
                ...$data,
                'kode_persediaan_id' => null,
                'kode_persediaan' => $kodeBarang?->kode_barang ?? $kodeInternal,
                'kode_internal' => $kodeInternal,
                'pengusul_id' => $request->user()->id,
                'status_verifikasi' => StockOpnameVerificationStatus::Approved,
                'is_active' => true,
            ]);

            $this->recordOpeningBalance($item, $request->user()?->id, 'Saldo awal saat barang ditambahkan.');
            $this->recordHistory($item, 'diajukan', null, StockOpnameVerificationStatus::Approved->value, $request->user()?->id, 'Barang ditambahkan dan stok langsung tersedia.');
        });

        return back()->with('success', 'Barang berhasil ditambahkan dan stok langsung tersedia.');
    }

    public function update(UpdateStockOpnameItemRequest $request, StockOpnameItem $stockOpnameItem): RedirectResponse
    {
        $data = $request->validated();
        $kodeBarang = filled($data['kode_barang_id'] ?? null)
            ? KodeBarang::query()->findOrFail($data['kode_barang_id'])
            : null;
        $beforeStock = $stockOpnameItem->stok;
        $stockOpnameItem->update([
            ...$data,
            'kode_persediaan_id' => null,
            'kode_persediaan' => $kodeBarang?->kode_barang ?? $stockOpnameItem->kode_internal,
        ]);

        $this->recordHistory(
            $stockOpnameItem,
            'diubah',
            null,
            $stockOpnameItem->status_verifikasi?->value,
            $request->user()?->id,
            'Data barang diperbarui.',
            $beforeStock,
            $stockOpnameItem->stok,
        );

        return back()->with('success', 'Barang persediaan berhasil diperbarui.');
    }

    public function addStock(AddStockOpnameStockRequest $request, StockOpnameItem $stockOpnameItem): RedirectResponse
    {
        DB::transaction(function () use ($request, $stockOpnameItem): void {
            $item = StockOpnameItem::query()->lockForUpdate()->findOrFail($stockOpnameItem->id);
            $before = (int) $item->stok;
            $quantity = $request->integer('jumlah_masuk');
            $after = $before + $quantity;

            $item->update(['stok' => $after]);
            $item->mutations()->create([
                'user_id' => $request->user()?->id,
                'jenis' => 'stok_masuk',
                'jumlah' => $quantity,
                'stok_sebelum' => $before,
                'stok_sesudah' => $after,
                'catatan' => $request->validated('keterangan'),
                'waktu_transaksi' => $request->date('tanggal_masuk')?->setTimeFrom(now()) ?? now(),
            ]);
            $this->recordHistory($item, 'stok_masuk', null, null, $request->user()?->id, $request->validated('keterangan'), $before, $after);
        });

        return back()->with('success', 'Stok barang persediaan berhasil ditambahkan.');
    }

    public function adjust(AdjustStockOpnameItemRequest $request, StockOpnameItem $stockOpnameItem): RedirectResponse
    {
        abort_if($stockOpnameItem->status_verifikasi !== StockOpnameVerificationStatus::Approved, 403, 'Stock opname fisik hanya untuk barang yang sudah disetujui Tim Aset.');

        DB::transaction(function () use ($request, $stockOpnameItem): void {
            $item = StockOpnameItem::query()->lockForUpdate()->findOrFail($stockOpnameItem->id);
            $systemStock = (int) $item->stok;
            $physicalStock = $request->integer('stok_fisik');
            $difference = $physicalStock - $systemStock;

            $this->recordHistory(
                $item,
                'stock_opname_fisik',
                null,
                null,
                $request->user()?->id,
                $request->validated('catatan'),
                $systemStock,
                $physicalStock,
                $difference === 0 ? 'sesuai' : 'tidak_sesuai',
            );
        });

        return back()->with('success', 'Hasil stock opname fisik berhasil disimpan.');
    }

    public function destroy(Request $request, StockOpnameItem $stockOpnameItem): RedirectResponse
    {
        abort_unless($this->roleValue($request) === UserRole::SuperAdmin->value, 403, 'Penghapusan barang persediaan hanya dapat dilakukan oleh Super Admin.');

        DB::transaction(function () use ($stockOpnameItem): void {
            $item = StockOpnameItem::query()->lockForUpdate()->findOrFail($stockOpnameItem->id);
            $hasRequest = $item->requestDetails()->exists();
            $hasOperationalHistory = $item->histories()
                ->whereNotIn('aksi', ['diajukan', 'verifikasi', 'diubah'])
                ->exists();
            $hasOperationalMutation = $item->mutations()
                ->where('jenis', '!=', 'saldo_awal')
                ->exists();

            if ($hasRequest || $hasOperationalHistory || $hasOperationalMutation) {
                $reasons = array_filter([
                    $hasRequest ? 'sudah digunakan pada permintaan barang' : null,
                    $hasOperationalHistory ? 'memiliki riwayat transaksi operasional' : null,
                    $hasOperationalMutation ? 'memiliki mutasi stok operasional' : null,
                ]);

                throw ValidationException::withMessages([
                    'stock_opname' => 'Barang tidak dapat dihapus karena '.implode(', ', $reasons).'.',
                ]);
            }

            // Riwayat pengajuan, verifikasi, dan perubahan data adalah jejak administratif
            // yang hanya milik item ini. Riwayat operasional sudah ditolak di atas.
            $item->histories()->whereIn('aksi', ['diajukan', 'verifikasi', 'diubah'])->delete();
            $item->delete();
        });

        return back()->with('success', 'Barang persediaan berhasil dihapus.');
    }

    public function importTemplate()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['kode_internal_opsional', 'nama_barang', 'kategori_stock_opname', 'merek_tipe_opsional', 'satuan', 'stok_awal', 'lokasi_penyimpanan_opsional', 'keterangan_opsional'],
            ['ATK/001', 'Pulpen', 'Alat Tulis Kantor', 'Standard', 'Kotak', 10, 'Gudang ATK', 'Contoh data — hapus sebelum import'],
        ]);
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-import-stock-opname.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function importPreview(Request $request)
    {
        $data = $this->parseImportFile($request);

        return response()->json([
            'rows' => $data['rows'],
            'errors' => $data['errors'],
            'valid' => $data['errors'] === [],
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $this->parseImportFile($request);
        if ($data['errors'] !== []) {
            throw ValidationException::withMessages(['file' => $data['errors'][0]]);
        }

        DB::transaction(function () use ($data, $request): void {
            foreach ($data['payloads'] as $payload) {
                $kodeInternal = $payload['kode_internal'] ?: $this->generateInternalCode($payload['nama_barang']);
                $item = StockOpnameItem::query()->create([
                    ...$payload,
                    'kode_internal' => $kodeInternal,
                    'kode_persediaan_id' => null,
                    'kode_persediaan' => $kodeInternal,
                    'pengusul_id' => $request->user()->id,
                    'status_verifikasi' => StockOpnameVerificationStatus::Approved,
                    'is_active' => true,
                ]);
                $this->recordOpeningBalance($item, $request->user()?->id, 'Saldo awal dari Import Excel Stock Opname.');
                $this->recordHistory($item, 'diajukan', null, StockOpnameVerificationStatus::Approved->value, $request->user()?->id, 'Dibuat dari import Excel dan stok langsung tersedia.');
            }
        });

        return redirect()->route('stock-opname.index')->with('success', count($data['payloads']).' barang persediaan berhasil diimport dan stok langsung tersedia.');
    }

    /** @return array{rows: list<array<string, mixed>>, payloads: list<array<string, mixed>>, errors: list<string>} */
    private function parseImportFile(Request $request): array
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240']]);
        $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, false);
        $headers = array_map(fn ($value) => Str::of((string) $value)->trim()->lower()->ascii()->replace(' ', '_')->toString(), array_shift($rawRows) ?? []);
        $required = ['kode_internal_opsional', 'nama_barang', 'kategori_stock_opname', 'merek_tipe_opsional', 'satuan', 'stok_awal', 'lokasi_penyimpanan_opsional', 'keterangan_opsional'];
        if ($headers !== $required) {
            return ['rows' => [], 'payloads' => [], 'errors' => ['Header Excel tidak sesuai. Unduh dan gunakan template import Stock Opname.']];
        }

        $categories = StockOpnameCategory::query()->get()->keyBy(fn (StockOpnameCategory $category) => Str::lower(trim($category->nama_kategori)));
        $errors = [];
        $rows = [];
        $payloads = [];
        $seenCodes = [];
        foreach ($rawRows as $offset => $cells) {
            if (collect($cells)->every(fn ($cell) => blank($cell))) continue;
            $rowNumber = $offset + 2;
            $row = array_combine($required, array_pad(array_slice($cells, 0, count($required)), count($required), null));
            $code = strtoupper(trim((string) $row['kode_internal_opsional']));
            $name = trim((string) $row['nama_barang']);
            $category = $categories->get(Str::lower(trim((string) $row['kategori_stock_opname'])));
            $unit = trim((string) $row['satuan']);
            $stock = filter_var($row['stok_awal'], FILTER_VALIDATE_INT);
            $rowErrors = [];
            if ($name === '') $rowErrors[] = 'Nama barang wajib diisi.';
            if (! $category) $rowErrors[] = 'Kategori Stock Opname tidak ditemukan.';
            if ($unit === '') $rowErrors[] = 'Satuan wajib diisi.';
            if ($stock === false || $stock < 0) $rowErrors[] = 'Stok awal harus bilangan bulat nol atau lebih.';
            if ($code !== '' && ! preg_match('/^[A-Z0-9][A-Z0-9\-]*\/\d+$/', $code)) $rowErrors[] = 'Kode internal tidak sesuai format, contoh ATK/001.';
            if ($code !== '' && (isset($seenCodes[$code]) || StockOpnameItem::query()->where('kode_internal', $code)->exists())) $rowErrors[] = 'Kode internal sudah digunakan.';
            if ($code !== '') $seenCodes[$code] = true;
            if ($rowErrors !== []) $errors[] = 'Baris '.$rowNumber.': '.implode(' ', $rowErrors);
            $rows[] = ['row' => $rowNumber, 'kode_internal' => $code ?: '(otomatis)', 'nama_barang' => $name, 'kategori' => $category?->nama_kategori ?: (string) $row['kategori_stock_opname'], 'satuan' => $unit, 'stok' => $stock === false ? (string) $row['stok_awal'] : $stock, 'errors' => $rowErrors];
            if ($rowErrors === []) $payloads[] = ['stock_opname_category_id' => $category->id, 'kode_internal' => $code ?: null, 'nama_barang' => $name, 'merek_tipe' => trim((string) $row['merek_tipe_opsional']) ?: null, 'satuan' => $unit, 'stok' => $stock, 'lokasi_penyimpanan' => trim((string) $row['lokasi_penyimpanan_opsional']) ?: null, 'keterangan' => trim((string) $row['keterangan_opsional']) ?: null];
        }
        if ($rows === []) $errors[] = 'File Excel tidak memiliki baris data.';
        return compact('rows', 'payloads', 'errors');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_if(! $this->hasRole($request, [UserRole::Admin]), 403, 'Aksi ini hanya dapat dilakukan oleh Admin.');
    }

    private function hasRole(Request $request, array $roles): bool
    {
        $roleValue = $this->roleValue($request);

        if ($roleValue === UserRole::SuperAdmin->value) {
            return true;
        }

        return in_array($roleValue, array_map(fn (UserRole $role) => $role->value, $roles), true);
    }

    private function roleValue(Request $request): ?string
    {
        $role = $request->user()?->role;

        return $role instanceof UserRole ? $role->value : $role;
    }

    private function generateInternalCode(string $itemName): string
    {
        $prefix = $this->internalCodePrefix($itemName);
        $sequence = $this->nextInternalSequence($prefix);

        do {
            $code = sprintf('%s/%03d', $prefix, $sequence++);
        } while (StockOpnameItem::query()->where('kode_internal', $code)->exists());

        return $code;
    }

    private function internalCodePrefix(string $itemName): string
    {
        $cleanName = preg_replace('/[^A-Za-z0-9\s]/', ' ', Str::ascii($itemName)) ?: '';
        $words = collect(preg_split('/\s+/', trim($cleanName)) ?: [])->filter()->values();

        if ($words->count() >= 2) {
            return $words
                ->map(fn (string $word): string => strtoupper(substr($word, 0, 1)))
                ->implode('');
        }

        $singleWord = strtoupper((string) $words->first());

        return substr($singleWord ?: 'BRG', 0, 3);
    }

    private function nextInternalSequence(string $prefix): int
    {
        $lastSequence = StockOpnameItem::query()
            ->where('kode_internal', 'like', $prefix.'/%')
            ->pluck('kode_internal')
            ->map(function (string $code) use ($prefix): int {
                if (preg_match('/^'.preg_quote($prefix, '/').'\/(\d+)$/', $code, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return $lastSequence + 1;
    }

    private function recordHistory(
        StockOpnameItem $item,
        string $action,
        ?string $previousStatus,
        ?string $nextStatus,
        ?int $userId,
        ?string $note = null,
        ?int $systemStock = null,
        ?int $physicalStock = null,
        ?string $opnameResult = null,
    ): void {
        $item->histories()->create([
            'user_id' => $userId,
            'aksi' => $action,
            'status_sebelum' => $previousStatus,
            'status_sesudah' => $nextStatus,
            'stok_sistem' => $systemStock,
            'stok_fisik' => $physicalStock,
            'selisih' => $systemStock !== null && $physicalStock !== null ? $physicalStock - $systemStock : null,
            'hasil_opname' => $opnameResult,
            'catatan' => $note,
        ]);
    }

    private function recordOpeningBalance(StockOpnameItem $item, ?int $userId, string $note): void
    {
        $initialStock = (int) $item->stok;

        $item->mutations()->create([
            'user_id' => $userId,
            'jenis' => 'saldo_awal',
            'jumlah' => $initialStock,
            'stok_sebelum' => 0,
            'stok_sesudah' => $initialStock,
            'catatan' => $note,
            'waktu_transaksi' => now(),
        ]);
    }
}
