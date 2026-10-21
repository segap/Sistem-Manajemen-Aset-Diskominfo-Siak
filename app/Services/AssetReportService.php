<?php

namespace App\Services;

use App\Enums\AssetCondition;
use App\Enums\LoanStatus;
use App\Http\Requests\AssetReportRequest;
use App\Models\Asset;
use App\Models\StockOpnameItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AssetReportService
{
    /** @return array<string, mixed> */
    public function report(AssetReportRequest $request): array
    {
        $type = $request->string('report_type', 'asset')->toString();
        $assetRows = in_array($type, ['asset', 'all'], true) ? $this->rows($this->filteredAssets($request)) : [];
        $stockRows = in_array($type, ['stock_opname', 'all'], true) ? $this->stockRows($this->filteredStockOpname($request)) : [];

        $rows = $type === 'all'
            ? collect([...$assetRows, ...$stockRows])->sortBy([['source_order', 'asc'], ['nama_barang', 'asc']])->values()->all()
            : ($type === 'stock_opname' ? $stockRows : $assetRows);

        return [
            'filters' => $request->validated(),
            'type' => $type,
            'summary' => $type === 'stock_opname' ? $this->stockSummary($stockRows) : $this->summary($assetRows),
            'asset_summary' => $this->summary($assetRows),
            'stock_summary' => $this->stockSummary($stockRows),
            'rows' => $this->paginateRows($rows, $request),
            'export_rows' => $rows,
        ];
    }

    /** @param list<array<string, mixed>> $rows */
    private function paginateRows(array $rows, AssetReportRequest $request): LengthAwarePaginator
    {
        $perPage = $request->integer('per_page', 10);
        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $items = collect($rows)->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            count($rows),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    /** @return Collection<int, Asset> */
    public function filteredAssets(AssetReportRequest $request): Collection
    {
        $lifecycle = $request->string('lifecycle', 'all')->toString();

        return Asset::query()
            ->with('category')
            ->where('status_verifikasi', 'disetujui')
            ->withCount([
                'loanDetails as borrowed_jumlah' => fn (Builder $query) => $query
                    ->whereHas('loanRequest', fn (Builder $loanQuery) => $loanQuery->where('status', LoanStatus::Borrowed->value)),
                'loanDetails as reserved_jumlah' => fn (Builder $query) => $query
                    ->whereHas('loanRequest', fn (Builder $loanQuery) => $loanQuery
                        ->whereIn('status', [LoanStatus::Approved->value, LoanStatus::LegacyApproved->value])
                        ->whereDate('tanggal_pinjam', '<=', today())
                        ->whereDate('tanggal_kembali', '>=', today())),
            ])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('kode_internal', 'like', "%{$search}%")
                        ->orWhere('nama_aset', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('kategori_id'), fn (Builder $query, int $id) => $query->where('kategori_aset_id', $id))
            ->when($request->filled('kondisi'), fn (Builder $query) => $query->where('kondisi', $request->string('kondisi')))
            ->when($lifecycle === 'active', fn (Builder $query) => $query->whereNull('tanggal_penghapusan'))
            ->when($lifecycle === 'disposed', fn (Builder $query) => $query->whereNotNull('tanggal_penghapusan'))
            ->when($request->date('tanggal_dari'), fn (Builder $query, $date) => $query->where('created_at', '>=', $date->startOfDay()))
            ->when($request->date('tanggal_sampai'), fn (Builder $query, $date) => $query->where('created_at', '<=', $date->endOfDay()))
            ->when($request->integer('tahun_perolehan_dari'), fn (Builder $query, int $year) => $query->where('tahun_perolehan', '>=', $year))
            ->when($request->integer('tahun_perolehan_sampai'), fn (Builder $query, int $year) => $query->where('tahun_perolehan', '<=', $year))
            ->orderBy('nama_aset')
            ->get();
    }

    /** @return Collection<int, StockOpnameItem> */
    public function filteredStockOpname(AssetReportRequest $request): Collection
    {
        return StockOpnameItem::query()
            ->with('category')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(fn (Builder $query) => $query->where('kode_internal', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%"));
            })
            ->when($request->integer('stock_category_id'), fn (Builder $query, int $id) => $query->where('stock_opname_category_id', $id))
            ->when($request->filled('verification'), fn (Builder $query) => $query->where('status_verifikasi', $request->string('verification')->toString()))
            ->when($request->string('stock_status')->toString() === 'tersedia', fn (Builder $query) => $query->where('stok', '>=', 5))
            ->when($request->string('stock_status')->toString() === 'menipis', fn (Builder $query) => $query->whereBetween('stok', [1, 4]))
            ->when($request->string('stock_status')->toString() === 'habis', fn (Builder $query) => $query->where('stok', 0))
            ->orderBy('nama_barang')
            ->get();
    }

    /** @param Collection<int, Asset> $assets
     * @return list<array<string, mixed>>
     */
    public function rows(Collection $assets): array
    {
        return $assets->map(function (Asset $asset): array {
            $borrowed = (int) $asset->borrowed_jumlah;
            $reserved = (int) $asset->reserved_jumlah;
            $available = $asset->kondisi === AssetCondition::Good && ! $asset->tanggal_penghapusan
                ? max(0, $asset->jumlah - $borrowed - $reserved)
                : 0;

            return [
                'source' => 'Aset Tetap',
                'source_order' => 1,
                'display_code' => $asset->display_code,
                'kode_internal' => $asset->kode_internal,
                'nama_aset' => $asset->nama_aset,
                'kategori' => $asset->category->nama_kategori,
                'merek_tipe' => $asset->merek_tipe,
                'jumlah' => $asset->jumlah,
                'tersedia' => $available,
                'direservasi' => $reserved,
                'dipinjam' => $borrowed,
                'kondisi' => $asset->kondisi->value,
                'label_kondisi' => $asset->kondisi->label(),
                'tahun_perolehan' => $asset->tahun_perolehan,
                'lokasi_penyimpanan' => $asset->lokasi_penyimpanan,
                'lifecycle' => $asset->tanggal_penghapusan ? 'disposed' : 'active',
                'label_status_siklus' => $asset->tanggal_penghapusan ? 'Dihapus' : 'Aktif',
            ];
        })->values()->all();
    }

    /** @param list<array<string, mixed>> $rows
     * @return array<string, int>
     */
    public function summary(array $rows): array
    {
        return [
            'total_jenis_aset' => count($rows),
            'total_unit' => array_sum(array_column($rows, 'jumlah')),
            'unit_tersedia' => array_sum(array_column($rows, 'tersedia')),
            'unit_direservasi' => array_sum(array_column($rows, 'direservasi')),
            'unit_dipinjam' => array_sum(array_column($rows, 'dipinjam')),
        ];
    }

    /** @param Collection<int, StockOpnameItem> $items
     * @return list<array<string, mixed>>
     */
    public function stockRows(Collection $items): array
    {
        return $items->map(function (StockOpnameItem $item): array {
            $status = $item->stock_status;

            return [
                'source' => 'Stock Opname',
                'source_order' => 2,
                'display_code' => $item->kode_internal ?: '-',
                'nama_barang' => $item->nama_barang,
                'kategori' => $item->category?->nama_kategori ?: '-',
                'satuan' => $item->satuan ?: '-',
                'jumlah' => (int) $item->stok,
                'stok' => (int) $item->stok,
                'tersedia' => $status === 'tersedia' ? (int) $item->stok : 0,
                'stock_status' => $status,
                'label_stock_status' => match ($status) { 'habis' => 'Stok Habis', 'menipis' => 'Stok Menipis', default => 'Tersedia' },
                'label_verification' => $item->status_verifikasi?->label() ?: '-',
                'status' => match ($status) { 'habis' => 'Stok Habis', 'menipis' => 'Stok Menipis', default => 'Tersedia' },
            ];
        })->values()->all();
    }

    /** @param list<array<string, mixed>> $rows
     * @return array<string, int>
     */
    public function stockSummary(array $rows): array
    {
        return [
            'total_jenis_barang' => count($rows),
            'total_stok' => array_sum(array_column($rows, 'stok')),
            'stok_menipis' => count(array_filter($rows, fn (array $row) => $row['stock_status'] === 'menipis')),
            'stok_habis' => count(array_filter($rows, fn (array $row) => $row['stock_status'] === 'habis')),
        ];
    }
}