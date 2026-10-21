<?php

namespace App\Services;

use App\Enums\AssetCondition;
use App\Enums\AssetDisposalStatus;
use App\Enums\AssetMaintenancePriority;
use App\Enums\AssetMaintenanceStatus;
use App\Enums\LoanStatus;
use App\Enums\PermintaanPersediaanStatus;
use App\Enums\ProcurementProposalStatus;
use App\Enums\ProcurementStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDisposalRequest;
use App\Models\AssetMaintenance;
use App\Models\DetailKir;
use App\Models\LoanRequest;
use App\Models\PermintaanPersediaan;
use App\Models\Procurement;
use App\Models\ProcurementProposal;
use App\Models\ReportDocument;
use App\Models\Ruangan;
use App\Models\StockOpnameCategory;
use App\Models\StockOpnameItem;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardDataService
{
    /** @return array<string, mixed> */
    public function data(?int $reportYear = null): array
    {
        $showAllReportYears = $reportYear === null;
        $selectedReportYear = $reportYear ?? now()->year;
        $reportYears = $this->reportYears($selectedReportYear);
        $monitoringYears = $showAllReportYears ? $reportYears : [$selectedReportYear];
        $assets = Asset::query()
            ->whereNull('tanggal_penghapusan')
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
            ->get();

        $totalUnits = $assets->sum('jumlah');
        $borrowedUnits = $assets->sum(fn (Asset $asset) => (int) $asset->borrowed_jumlah);
        $reservedUnits = $assets->sum(fn (Asset $asset) => (int) $asset->reserved_jumlah);
        $availableUnits = $assets->sum(function (Asset $asset): int {
            if ($asset->kondisi !== AssetCondition::Good) {
                return 0;
            }

            return max(0, $asset->jumlah - (int) $asset->borrowed_jumlah - (int) $asset->reserved_jumlah);
        });

        $workflow = [
            'pemeliharaan_verifikasi' => $this->maintenanceWaitingVerification(),
            'pemeliharaan_prioritas_tinggi' => $this->maintenanceHighPriority(),
            'peminjaman' => LoanRequest::query()
                ->whereIn('status', [LoanStatus::Submitted->value, LoanStatus::VerifiedByAssetTeam->value])
                ->count(),
            'usulan_pengadaan' => ProcurementProposal::query()
                ->whereIn('status', [
                    ProcurementProposalStatus::Submitted->value,
                    ProcurementProposalStatus::Verified->value,
                ])->count(),
            'pengadaan' => Procurement::query()
                ->whereIn('status', [ProcurementStatus::Ordered->value, ProcurementStatus::Received->value])
                ->count(),
            'penghapusan_aset' => AssetDisposalRequest::query()
                ->where('status', AssetDisposalStatus::Submitted->value)
                ->count(),
        ];

        return [
            'summary' => [
                'total_jenis_aset' => $assets->count(),
                'total_unit' => $totalUnits,
                'unit_tersedia' => $availableUnits,
                'unit_direservasi' => $reservedUnits,
                'unit_dipinjam' => $borrowedUnits,
                'pengajuan_aktif' => array_sum($workflow),
            ],
            'stock_opname_summary' => $this->stockOpnameSummary(),
            'report_filter' => [
                'selected_year' => $showAllReportYears ? null : $selectedReportYear,
                'years' => $reportYears,
            ],
            'report_monitoring_years' => collect($monitoringYears)->map(fn (int $year): array => [
                'year' => $year,
                'reconciliation' => $this->reconciliationMonthly($year),
                'asset_reports' => $this->assetReportMonitoring($year),
            ])->all(),
            'stock_opname_categories' => $this->stockOpnameCategories(),
            'asset_categories' => $this->assetCategorySummary(),
            'kir_summary' => $this->kirSummary(),
            'workflow' => $workflow,
            'low_stock_assets' => $this->lowStockAssets($assets),
            'low_stock_inventory' => $this->lowStockInventory(),
            'kondisi_distribution' => $this->kondisiDistribution(),
            'activity_chart' => $this->activityChart(),
            'recent_activities' => $this->recentActivities(),
        ];
    }

    /** @return array{year: int, last_uploaded: ?string, missing_months: list<string>, required_month_count: int, months: list<array{label: string, status: string}>} */
    private function reconciliationMonthly(int $year): array
    {
        $now = now();
        $completedMonths = $year < $now->year
            ? range(1, 12)
            : ($year === $now->year && $now->month > 1 ? range(1, $now->month - 1) : []);
        $uploadedMonths = collect();

        if (Schema::hasTable('laporan_dokumen') && Schema::hasColumns('laporan_dokumen', ['jenis_laporan', 'tahun', 'bulan', 'periode'])) {
            $uploadedMonths = ReportDocument::query()
                ->where('jenis_laporan', 'rekonsiliasi-bulanan')
                ->where('tahun', $year)
                ->get(['bulan', 'periode'])
                ->map(function (ReportDocument $document): ?int {
                    $month = (int) $document->bulan;
                    if ($month >= 1 && $month <= 12) {
                        return $month;
                    }

                    if (preg_match('/^bulan_(\d{1,2})$/', (string) $document->periode, $matches) === 1) {
                        $month = (int) $matches[1];

                        return $month >= 1 && $month <= 12 ? $month : null;
                    }

                    return null;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        $missingMonths = collect($completedMonths)
            ->reject(fn (int $month): bool => $uploadedMonths->contains($month))
            ->map(fn (int $month): string => $this->reconciliationMonthLabel($year, $month))
            ->values()
            ->all();
        $monthlyStatuses = collect(range(1, 12))
            ->map(function (int $month) use ($uploadedMonths, $now, $year): array {
                $isCompleted = $year < $now->year || ($year === $now->year && $month < $now->month);
                $status = $uploadedMonths->contains($month)
                    ? 'uploaded'
                    : ($isCompleted ? 'missing' : ($year === $now->year && $month === $now->month ? 'current' : 'upcoming'));

                return [
                    'label' => $this->reconciliationMonthLabel($year, $month, false),
                    'status' => $status,
                ];
            })
            ->all();
        $lastUploadedMonth = $uploadedMonths->last();

        return [
            'year' => $year,
            'last_uploaded' => $lastUploadedMonth ? $this->reconciliationMonthLabel($year, $lastUploadedMonth) : null,
            'missing_months' => $missingMonths,
            'required_month_count' => count($completedMonths),
            'months' => $monthlyStatuses,
        ];
    }

    private function reconciliationMonthLabel(int $year, int $month, bool $includeYear = true): string
    {
        return Carbon::create($year, $month, 1, 0, 0, 0, config('app.timezone'))
            ->locale('id')
            ->translatedFormat($includeYear ? 'F Y' : 'F');
    }

    /** @return list<array{label: string, uploaded_at: ?string}> */
    private function assetReportMonitoring(int $year): array
    {
        $reports = [
            ['label' => 'Laporan Semester 1', 'types' => ['semester-1'], 'berita_acara' => false],
            ['label' => 'Laporan Tahunan', 'types' => ['tahunan'], 'berita_acara' => false],
            ['label' => 'Laporan Aset Unaudited', 'types' => ['aset-unaudited'], 'berita_acara' => false],
            ['label' => 'Laporan Aset Audited', 'types' => ['aset-audited'], 'berita_acara' => false],
            ['label' => 'Berita Acara BMD', 'types' => ['berita-acara-bmd', 'berita_acara_bmd', 'berita-acara-pemakaian-bmd', 'berita-acara-serah-terima'], 'berita_acara' => true],
        ];

        if (! Schema::hasTable('laporan_dokumen') || ! Schema::hasColumns('laporan_dokumen', ['jenis_laporan', 'tahun', 'created_at'])) {
            return collect($reports)->map(fn (array $report): array => [
                'label' => $report['label'],
                'uploaded_at' => null,
            ])->all();
        }

        $hasBeritaAcaraDate = Schema::hasColumn('laporan_dokumen', 'tanggal_berita_acara');

        return collect($reports)->map(function (array $report) use ($year, $hasBeritaAcaraDate): array {
            $query = ReportDocument::query()->whereIn('jenis_laporan', $report['types']);

            if ($report['berita_acara'] && $hasBeritaAcaraDate) {
                $query->where(function (Builder $dateQuery) use ($year): void {
                    $dateQuery->whereYear('tanggal_berita_acara', $year)
                        ->orWhere(function (Builder $legacyQuery) use ($year): void {
                            $legacyQuery->whereNull('tanggal_berita_acara')->where('tahun', $year);
                        });
                });
            } else {
                $query->where('tahun', $year);
            }

            $document = $query->latest('created_at')->first(['created_at']);

            return [
                'label' => $report['label'],
                'uploaded_at' => $document?->created_at?->locale('id')->translatedFormat('d M Y, H:i'),
            ];
        })->all();
    }

    /** @return list<int> */
    private function reportYears(int $selectedYear): array
    {
        $years = collect([2023, 2024, 2025, 2026, 2027, 2028, now()->year, $selectedYear]);

        if (Schema::hasTable('laporan_dokumen') && Schema::hasColumn('laporan_dokumen', 'tahun')) {
            $years = $years->merge(
                ReportDocument::query()->distinct()->orderByDesc('tahun')->pluck('tahun')
            );
        }

        return $years
            ->map(fn (mixed $year): int => (int) $year)
            ->filter(fn (int $year): bool => $year >= 2000 && $year <= 2100)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /** @return list<array{id: int, nama_kategori: string, items_count: int, items: list<array{id: int, nama_barang: string, stok: int, satuan: string, status: string}>}> */
    private function stockOpnameCategories(): array
    {
        if (! Schema::hasTable('stock_opname_categories') || ! Schema::hasTable('stock_opname_items')) {
            return [];
        }

        return StockOpnameCategory::query()
            ->with(['items' => fn ($query) => $query
                ->where('is_active', true)
                ->where('status_verifikasi', 'disetujui_tim_aset')
                ->orderBy('nama_barang')
                ->select(['id', 'stock_opname_category_id', 'nama_barang', 'stok', 'satuan'])])
            ->orderBy('nama_kategori')
            ->get(['id', 'nama_kategori'])
            ->map(function (StockOpnameCategory $category): array {
                $items = $category->items->map(fn (StockOpnameItem $item): array => [
                    'id' => $item->id,
                    'nama_barang' => $item->nama_barang,
                    'stok' => (int) $item->stok,
                    'satuan' => $item->satuan,
                    'status' => match (true) {
                        (int) $item->stok <= 0 => 'Stok Habis',
                        (int) $item->stok <= 5 => 'Stok Menipis',
                        default => 'Tersedia',
                    },
                ])->values()->all();

                return [
                    'id' => $category->id,
                    'nama_kategori' => $category->nama_kategori,
                    'items_count' => count($items),
                    'items' => $items,
                ];
            })
            ->values()
            ->all();
    }

    /** @return list<array{id: int, kode_kategori: string, nama_kategori: string, jumlah: int, jenis_count: int, kondisi: array<string, int>, items: list<array{nama_aset: string, jumlah: int}>}> */
    private function assetCategorySummary(): array
    {
        if (! Schema::hasTable('kategori_aset') || ! Schema::hasTable('aset')) {
            return [];
        }

        return AssetCategory::query()
            ->with(['assets' => fn ($query) => $query
                ->whereNull('tanggal_penghapusan')
                ->where('status_verifikasi', 'disetujui')
                ->with('conditionStocks')])
            ->orderBy('nama_kategori')
            ->get(['id', 'kode_kategori', 'nama_kategori'])
            ->map(function (AssetCategory $category): array {
                $conditions = $this->emptyConditionSummary();
                $items = $category->assets
                    ->groupBy(fn (Asset $asset): string => trim((string) $asset->nama_aset) !== '' ? trim((string) $asset->nama_aset) : '-')
                    ->map(fn (Collection $assets, string $assetName): array => [
                        'nama_aset' => $assetName,
                        'jumlah' => (int) $assets->sum('jumlah'),
                    ])
                    ->sortBy('nama_aset', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();

                foreach ($category->assets as $asset) {
                    foreach ($asset->condition_stock_summary as $condition => $total) {
                        if (array_key_exists($condition, $conditions)) {
                            $conditions[$condition] += (int) $total;
                        }
                    }
                }

                return [
                    'id' => (int) $category->id,
                    'kode_kategori' => $category->kode_kategori,
                    'nama_kategori' => $category->nama_kategori,
                    'jumlah' => (int) $category->assets->sum('jumlah'),
                    'jenis_count' => count($items),
                    'kondisi' => $conditions,
                    'items' => $items,
                ];
            })
            ->values()
            ->all();
    }

    /** @return array{total_ruangan: int, total_penanggung_jawab: int, total_barang: int, kondisi: array<string, int>, kondisi_tidak_diketahui: int, ruangan: list<array{id: int, nama_ruangan: string, penanggung_jawab: string, jumlah_barang: int, kondisi: array<string, int>, kondisi_tidak_diketahui: int}>} */
    private function kirSummary(): array
    {
        $empty = [
            'total_ruangan' => 0,
            'total_penanggung_jawab' => 0,
            'total_barang' => 0,
            'kondisi' => $this->emptyConditionSummary(),
            'kondisi_tidak_diketahui' => 0,
            'ruangan' => [],
        ];

        if (! Schema::hasTable('ruangan') || ! Schema::hasTable('kir') || ! Schema::hasTable('detail_kir')) {
            return $empty;
        }

        $rooms = Ruangan::query()
            ->with([
                'kir:id,ruangan_id,penanggung_jawab_id',
                'kir.penanggungJawab:id,name',
                'kir.details' => fn ($query) => $query->select(['id', 'kir_id', 'aset_id', 'jumlah', 'keterangan']),
                'kir.details.asset:id,jumlah,kondisi',
                'kir.details.asset.conditionStocks:id,asset_id,kondisi,jumlah',
            ])
            ->where('status', 'aktif')
            ->orderBy('nama_ruangan')
            ->get()
            ->values();

        $grandConditions = $this->emptyConditionSummary();
        $unknownGrandTotal = 0;
        $roomSummaries = [];

        foreach ($rooms as $room) {
            $kir = $room->kir;
            $roomConditions = $this->emptyConditionSummary();
            $unknownRoomTotal = 0;
            $roomItemTotal = 0;

            if ($kir) {
                foreach ($kir->details as $detail) {
                    $quantity = max(0, (int) $detail->jumlah);
                    $roomItemTotal += $quantity;

                    if ($quantity === 0) {
                        continue;
                    }

                    $detailConditions = $this->conditionSummaryForKirDetail($detail, $quantity);
                    $classifiedTotal = array_sum($detailConditions);

                    foreach ($detailConditions as $condition => $total) {
                        $roomConditions[$condition] += $total;
                    }

                    $unknownRoomTotal += max(0, $quantity - $classifiedTotal);
                }
            }

            foreach ($roomConditions as $condition => $total) {
                $grandConditions[$condition] += $total;
            }

            $unknownGrandTotal += $unknownRoomTotal;

            $roomSummaries[] = [
                'id' => (int) $room->id,
                'nama_ruangan' => $room->nama_ruangan,
                'penanggung_jawab' => $kir?->penanggungJawab?->name ?? '-',
                'jumlah_barang' => $roomItemTotal,
                'kondisi' => $roomConditions,
                'kondisi_tidak_diketahui' => $unknownRoomTotal,
            ];
        }

        return [
            'total_ruangan' => $rooms->count(),
            'total_penanggung_jawab' => $rooms->pluck('kir.penanggung_jawab_id')->filter()->unique()->count(),
            'total_barang' => collect($roomSummaries)->sum('jumlah_barang'),
            'kondisi' => $grandConditions,
            'kondisi_tidak_diketahui' => $unknownGrandTotal,
            'ruangan' => $roomSummaries,
        ];
    }

    /** @return array<string, int> */
    private function emptyConditionSummary(): array
    {
        return collect(AssetCondition::cases())
            ->mapWithKeys(fn (AssetCondition $condition) => [$condition->value => 0])
            ->all();
    }

    /** @return array<string, int> */
    private function conditionSummaryForKirDetail(DetailKir $detail, int $quantity): array
    {
        $summary = $this->emptyConditionSummary();
        $asset = $detail->asset;

        if ($asset) {
            if ($quantity === 1 && $asset->kondisi instanceof AssetCondition) {
                $summary[$asset->kondisi->value] = 1;

                return $summary;
            }

            $assetSummary = $asset->condition_stock_summary;
            $assetSummaryTotal = array_sum($assetSummary);

            if ($assetSummaryTotal > 0 && $assetSummaryTotal <= $quantity) {
                foreach ($summary as $condition => $total) {
                    $summary[$condition] = min($quantity, (int) ($assetSummary[$condition] ?? 0));
                }

                return $summary;
            }
        }

        $fallbackCondition = $this->normalizeKirCondition($detail->keterangan);

        if ($fallbackCondition !== null) {
            $summary[$fallbackCondition] = $quantity;
        }

        return $summary;
    }

    private function normalizeKirCondition(?string $condition): ?string
    {
        $normalized = str($condition ?? '')
            ->trim()
            ->lower()
            ->replace(['-', '_'], ' ')
            ->squish()
            ->value();

        return match ($normalized) {
            'b', 'baik' => AssetCondition::Good->value,
            'rr', 'rusak ringan' => AssetCondition::LightDamage->value,
            'rs', 'rusak sedang' => AssetCondition::MediumDamage->value,
            'rb', 'rusak berat' => AssetCondition::HeavyDamage->value,
            'h', 'hilang' => AssetCondition::Lost->value,
            default => null,
        };
    }

    private function maintenanceWaitingVerification(): int
    {
        if (! Schema::hasTable('pemeliharaan_aset')) {
            return 0;
        }

        return AssetMaintenance::query()
            ->where('status', AssetMaintenanceStatus::Submitted->value)
            ->count();
    }

    private function maintenanceHighPriority(): int
    {
        if (! Schema::hasTable('pemeliharaan_aset')) {
            return 0;
        }

        return AssetMaintenance::query()
            ->whereIn('status', [
                AssetMaintenanceStatus::Approved->value,
                AssetMaintenanceStatus::InProgress->value,
            ])
            ->where('prioritas', AssetMaintenancePriority::High->value)
            ->count();
    }

    /** @return array{total_barang: int, total_stok: int, stok_menipis: int, stok_habis: int, permintaan_aktif: int} */
    private function stockOpnameSummary(): array
    {
        if (! Schema::hasTable('stock_opname_items')) {
            return [
                'total_barang' => 0,
                'total_stok' => 0,
                'stok_menipis' => 0,
                'stok_habis' => 0,
                'permintaan_aktif' => 0,
            ];
        }

        $inventory = StockOpnameItem::query()
            ->where('is_active', true)
            ->where('status_verifikasi', 'disetujui_tim_aset')
            ->selectRaw('COUNT(*) as total_barang')
            ->selectRaw('COALESCE(SUM(stok), 0) as total_stok')
            ->selectRaw('SUM(CASE WHEN stok > 0 AND stok < 5 THEN 1 ELSE 0 END) as stok_menipis')
            ->selectRaw('SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) as stok_habis')
            ->first();

        return [
            'total_barang' => (int) ($inventory?->total_barang ?? 0),
            'total_stok' => (int) ($inventory?->total_stok ?? 0),
            'stok_menipis' => (int) ($inventory?->stok_menipis ?? 0),
            'stok_habis' => (int) ($inventory?->stok_habis ?? 0),
            'permintaan_aktif' => $this->activeInventoryRequests(),
        ];
    }

    private function activeInventoryRequests(): int
    {
        if (! Schema::hasTable('permintaan_persediaan')) {
            return 0;
        }

        return PermintaanPersediaan::query()
            ->whereIn('status', [
                PermintaanPersediaanStatus::Submitted->value,
                PermintaanPersediaanStatus::Approved->value,
            ])
            ->count();
    }

    private function lowStockInventory(): array
    {
        if (! Schema::hasTable('stock_opname_items')) {
            return [];
        }

        return StockOpnameItem::query()
            ->with('category:id,nama_kategori')
            ->where('is_active', true)
            ->where('status_verifikasi', 'disetujui_tim_aset')
            ->where('stok', '<', 5)
            ->orderBy('stok')
            ->orderBy('nama_barang')
            ->get()
            ->map(fn (StockOpnameItem $item): array => [
                'id' => $item->id,
                'kode_persediaan' => $item->kode_persediaan,
                'nama_barang' => $item->nama_barang,
                'kategori' => $item->category?->nama_kategori,
                'stok' => $item->stok,
                'satuan' => $item->satuan,
                'status' => $item->stok === 0 ? 'Stok Habis' : 'Stok Menipis',
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @return list<array<string, int|string>>
     */
    private function lowStockAssets(Collection $assets): array
    {
        return $assets
            ->map(function (Asset $asset): array {
                $borrowed = (int) $asset->borrowed_jumlah;
                $reserved = (int) $asset->reserved_jumlah;
                $available = $asset->kondisi === AssetCondition::Good
                    ? max(0, $asset->jumlah - $borrowed - $reserved)
                    : 0;

                return [
                    'id' => $asset->id,
                    'display_code' => $asset->display_code,
                    'nama_aset' => $asset->nama_aset,
                    'jumlah' => $asset->jumlah,
                    'tersedia' => $available,
                    'dipinjam' => $borrowed,
                    'direservasi' => $reserved,
                ];
            })
            ->filter(fn (array $asset): bool => $asset['tersedia'] < 5)
            ->sortBy('tersedia')
            ->values()
            ->all();
    }

    /** @return array<string, int> */
    private function kondisiDistribution(): array
    {
        $distribution = collect(AssetCondition::cases())
            ->mapWithKeys(fn (AssetCondition $kondisi) => [$kondisi->value => 0])
            ->all();

        if (Schema::hasTable('asset_condition_stocks')) {
            DB::table('asset_condition_stocks')
                ->join('aset', 'aset.id', '=', 'asset_condition_stocks.asset_id')
                ->whereNull('aset.tanggal_penghapusan')
                ->where('aset.status_verifikasi', 'disetujui')
                ->select('asset_condition_stocks.kondisi', DB::raw('SUM(asset_condition_stocks.jumlah) as total'))
                ->groupBy('asset_condition_stocks.kondisi')
                ->get()
                ->each(function (object $row) use (&$distribution): void {
                    $distribution[$row->kondisi] = (int) $row->total;
                });

            return $distribution;
        }

        DB::table('aset')
            ->whereNull('tanggal_penghapusan')
            ->where('status_verifikasi', 'disetujui')
            ->select('kondisi', DB::raw('SUM(jumlah) as total'))
            ->groupBy('kondisi')
            ->get()
            ->each(function (object $row) use (&$distribution): void {
                $distribution[$row->kondisi] = (int) $row->total;
            });

        return $distribution;
    }

    /** @return list<array{month: string, incoming: int, borrowed: int, disposed: int}> */
    private function activityChart(): array
    {
        $start = today()->startOfMonth()->subMonths(5);
        $chart = collect(range(0, 5))->mapWithKeys(function (int $offset) use ($start): array {
            $month = $start->copy()->addMonths($offset)->format('Y-m');

            return [$month => ['month' => $month, 'incoming' => 0, 'borrowed' => 0, 'disposed' => 0]];
        });

        Procurement::query()
            ->where('status', ProcurementStatus::Verified->value)
            ->where('tanggal_verifikasi', '>=', $start)
            ->get(['jumlah_diterima', 'tanggal_verifikasi'])
            ->each(function (Procurement $procurement) use ($chart): void {
                $month = $procurement->tanggal_verifikasi->format('Y-m');
                if ($chart->has($month)) {
                    $chart[$month] = [
                        ...$chart[$month],
                        'incoming' => $chart[$month]['incoming'] + $procurement->jumlah_diterima,
                    ];
                }
            });

        LoanRequest::query()
            ->withCount('details')
            ->whereNotNull('tanggal_penyerahan')
            ->where('tanggal_penyerahan', '>=', $start)
            ->get(['id', 'jumlah', 'tanggal_penyerahan'])
            ->each(function (LoanRequest $loan) use ($chart): void {
                $month = $loan->tanggal_penyerahan->format('Y-m');
                if ($chart->has($month)) {
                    $chart[$month] = [
                        ...$chart[$month],
                        'borrowed' => $chart[$month]['borrowed'] + ((int) $loan->details_count ?: (int) $loan->jumlah),
                    ];
                }
            });

        AssetDisposalRequest::query()
            ->with('asset:id,jumlah')
            ->where('status', AssetDisposalStatus::Approved->value)
            ->where('tanggal_pemeriksaan', '>=', $start)
            ->get(['id', 'aset_id', 'tanggal_pemeriksaan'])
            ->each(function (AssetDisposalRequest $disposal) use ($chart): void {
                $month = $disposal->tanggal_pemeriksaan->format('Y-m');
                if ($chart->has($month)) {
                    $chart[$month] = [
                        ...$chart[$month],
                        'disposed' => $chart[$month]['disposed'] + ($disposal->asset?->jumlah ?? 0),
                    ];
                }
            });

        return $chart->values()->all();
    }

    /** @return list<array{type: string, description: string, status: string, occurred_at: string}> */
    private function recentActivities(): array
    {
        $activities = new Collection;

        LoanRequest::query()->with(['asset:id,nama_aset,kode_internal', 'details.asset:id,nama_aset,kode_internal'])->latest('updated_at')->limit(5)->get()
            ->each(fn (LoanRequest $loan) => $activities->push($this->activity(
                'loan',
                "Peminjaman {$loan->itemSummary()}",
                $loan->status->label(),
                $loan->updated_at,
            )));

        Procurement::query()->latest('updated_at')->limit(5)->get()
            ->each(fn (Procurement $procurement) => $activities->push($this->activity(
                'procurement',
                "Pengadaan {$procurement->nama_barang}",
                $procurement->status->label(),
                $procurement->updated_at,
            )));

        ProcurementProposal::query()->latest('updated_at')->limit(5)->get()
            ->each(fn (ProcurementProposal $proposal) => $activities->push($this->activity(
                'procurement_proposal',
                "Usulan pengadaan {$proposal->nama_barang}",
                $proposal->status->label(),
                $proposal->updated_at,
            )));

        AssetDisposalRequest::query()->with('asset:id,nama_aset')->latest('updated_at')->limit(5)->get()
            ->each(fn (AssetDisposalRequest $disposal) => $activities->push($this->activity(
                'asset_disposal',
                "Penghapusan {$disposal->asset?->nama_aset}",
                $disposal->status->label(),
                $disposal->updated_at,
            )));

        if (Schema::hasTable('pemeliharaan_aset')) {
            AssetMaintenance::query()->with('asset:id,nama_aset')->latest('updated_at')->limit(5)->get()
                ->each(fn (AssetMaintenance $maintenance) => $activities->push($this->activity(
                    'maintenance',
                    "Pemeliharaan {$maintenance->asset?->nama_aset}",
                    $maintenance->status->label(),
                    $maintenance->updated_at,
                )));
        }

        return $activities
            ->sortByDesc('occurred_at')
            ->take(10)
            ->values()
            ->all();
    }

    /** @return array{type: string, description: string, status: string, occurred_at: string} */
    private function activity(string $type, string $description, string $status, CarbonInterface $occurredAt): array
    {
        return [
            'type' => $type,
            'description' => $description,
            'status' => $status,
            'occurred_at' => $occurredAt->toISOString(),
        ];
    }
}