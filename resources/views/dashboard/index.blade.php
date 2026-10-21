@extends('layouts.app')

@section('title', 'Dashboard - SIMIVA')

@php
    $number = fn ($value) => number_format((int) $value, 0, ',', '.');
    $summary = $dashboard['summary'];
    $stockSummary = $dashboard['stock_opname_summary'];
    $reportFilter = $dashboard['report_filter'] ?? ['selected_year' => now()->year, 'years' => [now()->year]];
    $reportMonitoringYears = $dashboard['report_monitoring_years'] ?? [];
    $stockCategories = $dashboard['stock_opname_categories'] ?? [];
    $assetCategories = $dashboard['asset_categories'] ?? [];
    $kirSummary = $dashboard['kir_summary'] ?? [
        'total_ruangan' => 0,
        'total_penanggung_jawab' => 0,
        'total_barang' => 0,
        'kondisi' => [
            'baik' => 0,
            'rusak_ringan' => 0,
            'rusak_sedang' => 0,
            'rusak_berat' => 0,
            'hilang' => 0,
        ],
        'ruangan' => [],
    ];
    $workflow = $dashboard['workflow'];
    $lowStockAssets = $dashboard['low_stock_assets'] ?? [];
    $lowStockInventory = $dashboard['low_stock_inventory'] ?? [];
    $kondisis = $dashboard['kondisi_distribution'];
    $chart = $dashboard['activity_chart'];
    $activities = $dashboard['recent_activities'];
    $chartMaximum = max(1, ...collect($chart)->flatMap(fn ($item) => [$item['incoming'], $item['borrowed'], $item['disposed']])->all());
    $totalCondition = max(1, array_sum($kondisis));
    $baikDeg = ($kondisis['baik'] / $totalCondition) * 360;
    $rusakRinganDeg = (($kondisis['baik'] + $kondisis['rusak_ringan']) / $totalCondition) * 360;
    $rusakSedangDeg = (($kondisis['baik'] + $kondisis['rusak_ringan'] + $kondisis['rusak_sedang']) / $totalCondition) * 360;
    $rusakBeratDeg = (($kondisis['baik'] + $kondisis['rusak_ringan'] + $kondisis['rusak_sedang'] + $kondisis['rusak_berat']) / $totalCondition) * 360;
    $baikPercent = round(($kondisis['baik'] / $totalCondition) * 100);
    $maintenanceNeeded = (int) ($kondisis['rusak_ringan'] ?? 0)
        + (int) ($kondisis['rusak_sedang'] ?? 0)
        + (int) ($kondisis['rusak_berat'] ?? 0);
    $stockCards = [
        ['stock-total', 'Total Barang Persediaan', $stockSummary['total_barang'], "{$stockSummary['total_barang']} jenis barang", 'bg-[#e8f3ff] text-[#1b79c8]', 'M4 6h16M4 10h16M4 14h10M4 18h8'],
        ['stock-quantity', 'Total Stok', $stockSummary['total_stok'], 'Total kuantitas tercatat', 'bg-[#e2f8ee] text-[#0a9875]', 'm4 7 8-4 8 4v10l-8 4-8-4V7Zm0 0 8 4 8-4m-8 4v10'],
        ['stock-low', 'Stok Menipis', $stockSummary['stok_menipis'], "{$stockSummary['stok_menipis']} jenis barang", 'bg-[#fff0d6] text-[#b54708]', 'M12 9v4m0 4h.01M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z'],
        ['stock-empty', 'Stok Habis', $stockSummary['stok_habis'], "{$stockSummary['stok_habis']} jenis barang", 'bg-[#fee2e2] text-[#b42318]', 'M6 6l12 12M18 6 6 18'],
        ['stock-request', 'Permintaan Aktif', $stockSummary['permintaan_aktif'], 'Menunggu tindak lanjut', 'bg-[#f0e8ff] text-[#7b4bd6]', 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
    ];
    $assetCards = [
        ['assets', 'Total Unit Aset', $summary['total_unit'], "{$summary['total_jenis_aset']} jenis aset", 'bg-[#e8f3ff] text-[#1b79c8]', 'm4 7 8-4 8 4v10l-8 4-8-4V7Zm0 0 8 4 8-4m-8 4v10'],
        ['available', 'Aset Tersedia', $summary['unit_tersedia'], "{$summary['unit_direservasi']} unit direservasi", 'bg-[#e2f8ee] text-[#0a9875]', 'm5 12 4 4L19 6'],
        ['dipinjam', 'Sedang Dipinjam', $summary['unit_dipinjam'], 'Unit belum dikembalikan', 'bg-[#fff0d6] text-[#a86d11]', 'M8 7V4m8 3V4M5 10h14M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Z'],
        ['maintenance-needed', 'Perlu Pemeliharaan', $maintenanceNeeded, 'Unit aset', 'bg-[#fff0d6] text-[#b54708]', 'M14.7 6.3a4 4 0 0 0-5-5L12 3.6 9.6 6 7.3 3.7a4 4 0 0 0 5 5L4 17l3 3 7.7-7.7a4 4 0 0 0 5-5L17.4 9.6 15 7.2l-.3-.9Z'],
    ];
    $kondisiLabels = [
        'baik' => ['Baik', 'bg-[#3ca982]'],
        'rusak_ringan' => ['Rusak Ringan', 'bg-[#d7a742]'],
        'rusak_sedang' => ['Rusak Sedang', 'bg-[#f97316]'],
        'rusak_berat' => ['Rusak Berat', 'bg-[#dc6b58]'],
        'hilang' => ['Hilang', 'bg-[#8b96a6]'],
    ];
    $workflowItems = [
        ['Permintaan Persediaan', $stockSummary['permintaan_aktif'], 'bg-[#0a9875]'],
        ['Pemeliharaan Menunggu Verifikasi', $workflow['pemeliharaan_verifikasi'] ?? 0, 'bg-[#b54708]'],
        ['Pemeliharaan Prioritas Tinggi', $workflow['pemeliharaan_prioritas_tinggi'] ?? 0, 'bg-[#dc6b58]'],
        ['Peminjaman Aset', $workflow['peminjaman'], 'bg-[#2384c6]'],
        ['Usulan Pengadaan Aset', $workflow['usulan_pengadaan'], 'bg-[#7b4bd6]'],
        ['Pengadaan Barang', $workflow['pengadaan'], 'bg-[#e58b62]'],
        ['Penghapusan Aset', $workflow['penghapusan_aset'], 'bg-[#dc6b58]'],
    ];
    $inventoryStatusClass = fn (string $status) => match ($status) {
        'Tersedia' => 'bg-[#dcfce7] text-[#166534]',
        'Stok Menipis' => 'bg-[#fef3c7] text-[#92400e]',
        default => 'bg-[#fee2e2] text-[#991b1b]',
    };
    $kirConditionCards = [
        ['B', 'Baik', 'baik', 'condition-baik'],
        ['RR', 'Rusak Ringan', 'rusak_ringan', 'condition-rusak_ringan'],
        ['RS', 'Rusak Sedang', 'rusak_sedang', 'condition-rusak_sedang'],
        ['RB', 'Rusak Berat', 'rusak_berat', 'condition-rusak_berat'],
    ];
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <header class="mb-8 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 text-[28px] font-extrabold tracking-[-.03em] text-[#0b2b50] max-[580px]:text-[24px]">Dashboard</h1>
            <p class="m-0 mt-1.5 text-[13px] text-[#66758a]">Ringkasan informasi aset, persediaan, dan kelengkapan laporan.</p>
        </div>
        <form method="GET" action="{{ route('dashboard') }}" class="shrink-0">
            <label class="grid gap-1.5 text-[10px] font-bold text-[#60708a]">
                <span>Filter Tahun</span>
                <select name="tahun" onchange="this.form.submit()" class="h-[38px] min-w-[148px] rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[12px] font-semibold text-[#34465a] outline-0 transition focus:border-[#86b7e7] focus:ring-2 focus:ring-[#d8ecff] max-[580px]:w-full" aria-label="Filter tahun dashboard">
                    <option value="all" @selected($reportFilter['selected_year'] === null)>Semua Tahun</option>
                    @foreach ($reportFilter['years'] as $year)
                        <option value="{{ $year }}" @selected((int) $reportFilter['selected_year'] === (int) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </header>

    @forelse ($reportMonitoringYears as $monitoringYear)
        @php
            $reconciliation = $monitoringYear['reconciliation'];
            $assetReportMonitoring = $monitoringYear['asset_reports'];
        @endphp
    <section class="mb-8" aria-labelledby="reconciliation-dashboard-title-{{ $reconciliation['year'] }}">
        <div class="mb-3 flex items-end justify-between gap-4 max-[580px]:items-start max-[580px]:flex-col">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-[12px] bg-[#e8f3ff] text-[#146bb5]">
                    <svg class="h-5 w-5 fill-none stroke-current stroke-[2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6V3Zm9 0v5h4M9 13h6m-6 4h6" /></svg>
                </span>
                <div>
                    <h2 class="m-0 text-[18px] font-extrabold text-[#0b2b50]" id="reconciliation-dashboard-title-{{ $reconciliation['year'] }}">Rekonsiliasi Bulanan</h2>
                    <p class="m-0 mt-1 text-[12px] text-[#66758a]">Monitoring kelengkapan dokumen rekonsiliasi tahun {{ $reconciliation['year'] }}.</p>
                </div>
            </div>
            @if (auth()->user()?->role !== 'pimpinan')
            <a class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white no-underline shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" href="{{ route('laporan-dokumen.index', ['jenis' => 'rekonsiliasi-bulanan', 'tahun' => $reconciliation['year']]) }}">
                Lihat Rekonsiliasi Bulanan
                <svg class="h-3.5 w-3.5 fill-none stroke-current stroke-[2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
            </a>
            @endif
        </div>

        <article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[22px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]">
            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                <div class="rounded-[12px] bg-[#effcf6] p-4">
                    <span class="text-[10px] font-bold uppercase tracking-[.05em] text-[#08714e]">Terakhir Diunggah</span>
                    <strong class="mt-1 block text-[18px] font-extrabold text-[#0b2b50]">{{ $reconciliation['last_uploaded'] ?: 'Belum ada dokumen diunggah' }}</strong>
                    @if ($reconciliation['last_uploaded'])
                        <span class="mt-2 inline-flex rounded-full bg-[#dcfce7] px-2.5 py-1 text-[9px] font-bold text-[#166534]">Sudah Diunggah</span>
                    @else
                        <span class="mt-2 inline-flex rounded-full bg-[#fef3c7] px-2.5 py-1 text-[9px] font-bold text-[#92400e]">Belum Diunggah</span>
                    @endif
                </div>
                <div class="rounded-[12px] bg-[#fff7ed] p-4">
                    <span class="text-[10px] font-bold uppercase tracking-[.05em] text-[#9a3412]">Belum Diunggah</span>
                    <strong class="mt-1 block text-[18px] font-extrabold text-[#0b2b50]">{{ count($reconciliation['missing_months']) }} Bulan Belum Diunggah</strong>
                    <small class="mt-2 block text-[10px] text-[#9a3412]">Dari {{ $reconciliation['required_month_count'] }} periode yang telah selesai.</small>
                </div>
            </div>

            <div class="mt-4 border-t border-[#edf1f5] pt-4">
                <span class="block text-[10px] font-bold uppercase tracking-[.05em] text-[#60708a]">Status Rekonsiliasi Bulanan</span>
                @if ($reconciliation['months'] !== [])
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($reconciliation['months'] as $month)
                            @php
                                $statusClass = match ($month['status']) {
                                    'uploaded' => 'bg-[#dcfce7] text-[#166534]',
                                    'missing' => 'bg-[#fef3c7] text-[#92400e]',
                                    'current' => 'bg-[#dbeafe] text-[#1d4f91]',
                                    default => 'bg-[#e2e8f0] text-[#475569]',
                                };
                            @endphp
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-bold {{ $statusClass }}">{{ $month['label'] }}</span>
                        @endforeach
                    </div>
                    @if ($reconciliation['missing_months'] === [] && $reconciliation['required_month_count'] > 0)
                        <p class="m-0 mt-3 text-[10px] font-semibold text-[#08714e]">Seluruh laporan rekonsiliasi periode selesai sudah diunggah.</p>
                    @endif
                @else
                    <p class="m-0 mt-2 text-[11px] font-semibold text-[#718095]">Status rekonsiliasi belum dapat ditampilkan.</p>
                @endif
            </div>
        </article>
    </section>

    <section class="mb-8" aria-labelledby="asset-report-monitoring-title-{{ $monitoringYear['year'] }}">
        <div class="mb-3">
            <h2 class="m-0 text-[18px] font-extrabold text-[#0b2b50]" id="asset-report-monitoring-title-{{ $monitoringYear['year'] }}">Monitoring Kelengkapan Laporan Aset</h2>
            <p class="m-0 mt-1 text-[12px] text-[#66758a]">Status kelengkapan dokumen laporan aset tahun {{ $monitoringYear['year'] }}.</p>
        </div>
        <article class="overflow-hidden rounded-[14px] border border-[#e3e9f0] bg-white shadow-[0_8px_22px_rgb(35_63_92_/_4%)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] table-fixed border-collapse text-center">
                    <thead>
                        <tr class="bg-[#f8fafc]">
                            @foreach ($assetReportMonitoring as $report)
                                <th class="border-b border-r border-[#e3e9f0] px-4 py-4 text-[11px] font-extrabold text-[#0b2b50] last:border-r-0">{{ $report['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach ($assetReportMonitoring as $report)
                                <td class="border-b border-r border-[#edf1f5] px-4 py-3 last:border-r-0 {{ $report['uploaded_at'] ? 'bg-[#eaf8f0]' : 'bg-[#f8fafc]' }}">
                                    @if ($report['uploaded_at'])
                                        <span class="inline-flex text-[#16a34a]" title="Dokumen sudah diunggah">
                                            <svg class="h-5 w-5 fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="m8 12 2.5 2.5L16 9" /></svg>
                                        </span>
                                    @else
                                        <span class="text-[18px] font-semibold text-[#a8b3c1]" aria-label="Belum ada dokumen">–</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($assetReportMonitoring as $report)
                                <td class="border-r border-[#edf1f5] px-4 py-3 text-[10px] font-medium text-[#718095] last:border-r-0">{{ $report['uploaded_at'] ?: '–' }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
    @empty
        <section class="mb-8 rounded-[14px] border border-[#e3e9f0] bg-white p-[22px] text-center text-[12px] font-semibold text-[#718095] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]">
            Data monitoring laporan belum tersedia.
        </section>
    @endforelse

    <section aria-labelledby="stock-opname-dashboard-title">
        <div class="mb-3">
            <h2 class="m-0 text-[18px] font-extrabold text-[#0b2b50]" id="stock-opname-dashboard-title">Persediaan / Stock Opname</h2>
            <p class="m-0 mt-1 text-[12px] text-[#66758a]">Ringkasan stok barang persediaan dan barang habis pakai saat ini.</p>
        </div>

        <div class="grid grid-cols-5 gap-4 max-[1300px]:grid-cols-3 max-[900px]:grid-cols-2 max-[580px]:grid-cols-1">
            @foreach ($stockCards as [$type, $label, $value, $hint, $colorClass, $iconPath])
                <article class="flex min-h-[118px] items-center gap-4 rounded-[14px] border border-[#e3e9f0] bg-white p-[22px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)] transition duration-200 hover:-translate-y-0.5 hover:scale-[1.01]">
                    <div class="grid h-[58px] w-[58px] shrink-0 place-items-center rounded-[14px] {{ $colorClass }}">
                        <svg class="h-7 w-7 fill-none stroke-current stroke-[1.9] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $iconPath }}" /></svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-medium text-[#60708a]">{{ $label }}</span>
                        <strong class="block text-[28px] font-extrabold leading-tight text-[#0b2b50]">{{ $number($value) }}</strong>
                        <small class="text-[10px] text-[#96a3b4]">{{ $hint }}</small>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mt-8" aria-labelledby="stock-category-dashboard-title">
        <div class="mb-3">
            <h2 class="m-0 text-[18px] font-extrabold text-[#0b2b50]" id="stock-category-dashboard-title">Barang Persediaan Per Kategori</h2>
            <p class="m-0 mt-1 text-[12px] text-[#66758a]">Ringkasan stok barang persediaan berdasarkan kategori.</p>
        </div>

        @if ($stockCategories === [])
            <article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[22px] text-center text-[12px] font-semibold text-[#718095] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]">
                Belum ada kategori Stock Opname.
            </article>
        @else
            <div class="grid grid-cols-3 gap-4 max-[1100px]:grid-cols-2 max-[700px]:grid-cols-1">
                @foreach ($stockCategories as $category)
                    <article class="min-w-0 rounded-[14px] border border-[#e3e9f0] bg-white p-[18px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]" data-stock-category-card>
                        <header class="mb-4 flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="m-0 text-[14px] font-extrabold text-[#20364f]">{{ $category['nama_kategori'] }}</h3>
                                <p class="m-0 mt-1 text-[10px] font-semibold text-[#94a0af]">{{ $number($category['items_count']) }} jenis barang</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                                <select class="h-[28px] min-w-[112px] rounded-[8px] border border-[#dce4ec] bg-white px-2.5 text-[11px] font-semibold text-[#34465a] outline-0 transition focus:border-[#86b7e7] focus:ring-2 focus:ring-[#d8ecff] disabled:cursor-not-allowed disabled:bg-[#f8fafc] disabled:text-[#8b99aa] disabled:opacity-75" data-stock-status-filter aria-label="Filter status stok {{ $category['nama_kategori'] }}" @disabled($category['items_count'] === 0)>
                                    <option value="all">Semua Status</option>
                                    <option value="tersedia">Tersedia</option>
                                    <option value="stok-menipis">Stok Menipis</option>
                                    <option value="stok-habis">Stok Habis</option>
                                </select>
                            </div>
                        </header>

                        @if ($category['items'] === [])
                            <div class="grid min-h-[128px] place-items-center rounded-[12px] bg-[#f7f9fc] px-4 text-center text-[11px] font-semibold text-[#718095]">
                                Belum ada barang pada kategori ini.
                            </div>
                        @else
                            <div class="dashboard-inventory-scroll max-h-[260px] overflow-y-auto rounded-[12px] border border-[#edf1f5]" data-stock-scroll-container>
                                <table class="w-full border-collapse text-left">
                                    <thead class="sticky top-0 bg-[#f8fafc]">
                                        <tr>
                                            <th class="w-[44px] px-3 py-2 text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]">No</th>
                                            <th class="px-3 py-2 text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]">Nama Barang</th>
                                            <th class="px-3 py-2 text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]">Stok Tersedia</th>
                                            <th class="px-3 py-2 text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($category['items'] as $itemIndex => $item)
                                            @php
                                                $statusKey = match ($item['status']) {
                                                    'Tersedia' => 'tersedia',
                                                    'Stok Menipis' => 'stok-menipis',
                                                    'Stok Habis' => 'stok-habis',
                                                    default => 'unknown',
                                                };
                                            @endphp
                                            <tr class="border-t border-[#edf1f5]" data-stock-row data-stock-status="{{ $statusKey }}">
                                                <td class="px-3 py-2 text-center text-[10px] text-[#718095]" data-stock-row-number>{{ $itemIndex + 1 }}</td>
                                                <td class="px-3 py-2"><strong class="text-[10px] font-extrabold text-[#263b52]">{{ $item['nama_barang'] }}</strong></td>
                                                <td class="px-3 py-2 text-[10px] font-semibold text-[#52657a]">{{ $number($item['stok']) }} {{ $item['satuan'] }}</td>
                                                <td class="px-3 py-2">
                                                    <span class="inline-flex rounded-full px-2 py-1 text-[8px] font-extrabold {{ $inventoryStatusClass($item['status']) }}">{{ $item['status'] }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="hidden border-t border-[#edf1f5]" data-stock-filter-empty>
                                            <td class="px-4 py-8 text-center text-[11px] font-semibold text-[#718095]" colspan="4">
                                                Tidak ada barang dengan status yang dipilih.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-8" aria-labelledby="asset-dashboard-title">
        <div class="mb-3">
            <h2 class="m-0 text-[18px] font-extrabold text-[#0b2b50]" id="asset-dashboard-title">Data Aset</h2>
            <p class="m-0 mt-1 text-[12px] text-[#66758a]">Ringkasan aset inventaris dan aktivitas pengelolaannya.</p>
        </div>

        <div class="grid grid-cols-4 gap-4 max-[1100px]:grid-cols-2 max-[580px]:grid-cols-1" aria-label="Ringkasan aset">
            @foreach ($assetCards as [$type, $label, $value, $hint, $colorClass, $iconPath])
                <article class="flex min-h-[118px] items-center gap-4 rounded-[14px] border border-[#e3e9f0] bg-white p-[22px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)] transition duration-200 hover:-translate-y-0.5 hover:scale-[1.01]">
                    <div class="grid h-[58px] w-[58px] shrink-0 place-items-center rounded-[14px] {{ $colorClass }}">
                        <svg class="h-7 w-7 fill-none stroke-current stroke-[1.9] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $iconPath }}" /></svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-medium text-[#60708a]">{{ $label }}</span>
                        <strong class="block text-[28px] font-extrabold leading-tight text-[#0b2b50]">{{ $number($value) }}</strong>
                        <small class="text-[10px] text-[#96a3b4]">{{ $hint }}</small>
                    </div>
                </article>
            @endforeach
        </div>

        <article class="mt-5 rounded-[14px] border border-[#e3e9f0] bg-white p-[22px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]" aria-labelledby="kir-dashboard-title">
            <header>
                <h3 class="m-0 text-[15px] font-extrabold text-[#20364f]" id="kir-dashboard-title">Kartu Inventaris Ruangan (KIR)</h3>
                <p class="m-0 mt-1 text-[10px] text-[#94a0af]">Ringkasan inventaris barang berdasarkan ruangan.</p>
            </header>

            <div class="mt-5 grid grid-cols-3 gap-4 max-[820px]:grid-cols-1">
                <div class="rounded-[12px] border border-[#edf1f5] bg-[#f8fafc] p-4">
                    <span class="text-[10px] font-extrabold uppercase tracking-[.04em] text-[#7a899c]">Jumlah Ruangan</span>
                    <strong class="mt-2 block text-[26px] font-extrabold leading-none text-[#0b2b50]">{{ $number($kirSummary['total_ruangan'] ?? 0) }}</strong>
                    <small class="mt-1 block text-[10px] font-semibold text-[#94a0af]">Ruangan</small>
                </div>
                <div class="rounded-[12px] border border-[#edf1f5] bg-[#f8fafc] p-4">
                    <span class="text-[10px] font-extrabold uppercase tracking-[.04em] text-[#7a899c]">Penanggung Jawab</span>
                    <strong class="mt-2 block text-[26px] font-extrabold leading-none text-[#0b2b50]">{{ $number($kirSummary['total_penanggung_jawab'] ?? 0) }}</strong>
                    <small class="mt-1 block text-[10px] font-semibold text-[#94a0af]">Orang</small>
                </div>
                <div class="rounded-[12px] border border-[#edf1f5] bg-[#f8fafc] p-4">
                    <span class="text-[10px] font-extrabold uppercase tracking-[.04em] text-[#7a899c]">Jumlah Barang</span>
                    <strong class="mt-2 block text-[26px] font-extrabold leading-none text-[#0b2b50]">{{ $number($kirSummary['total_barang'] ?? 0) }}</strong>
                    <small class="mt-1 block text-[10px] font-semibold text-[#94a0af]">Unit</small>
                </div>
            </div>

            <div class="mt-5">
                <h4 class="m-0 text-[12px] font-extrabold text-[#20364f]">Kondisi Barang</h4>
                <div class="mt-3 flex flex-wrap gap-2.5">
                    @foreach ($kirConditionCards as [$code, $label, $key, $class])
                        <span class="condition-badge {{ $class }} gap-2 px-3 py-2" title="{{ $code }} = {{ $label }}">
                            <strong class="text-[11px]">{{ $code }}</strong>
                            <span class="text-[11px]">{{ $number($kirSummary['kondisi'][$key] ?? 0) }}</span>
                        </span>
                    @endforeach
                </div>
                <p class="m-0 mt-2 text-[10px] text-[#94a0af]">B = Baik, RR = Rusak Ringan, RS = Rusak Sedang, RB = Rusak Berat.</p>
            </div>

            <div class="mt-5 border-t border-[#edf1f5] pt-5">
                <h4 class="m-0 mb-3 text-[12px] font-extrabold uppercase tracking-[.02em] text-[#20364f]">Rincian KIR</h4>

                @if (($kirSummary['ruangan'] ?? []) === [])
                    <div class="grid min-h-[120px] place-items-center rounded-[12px] bg-[#f7f9fc] px-4 text-center text-[11px] font-semibold text-[#718095]">
                        Belum ada data Kartu Inventaris Ruangan.
                    </div>
                @else
                    <div class="dashboard-inventory-scroll max-h-[292px] overflow-auto rounded-[12px] border border-[#edf1f5]">
                        <table class="w-full min-w-[760px] border-collapse text-left">
                            <thead class="sticky top-0 bg-[#f8fafc]">
                                <tr>
                                    <th class="w-[52px] px-3 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]">No</th>
                                    <th class="px-3 py-2 text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]">Ruangan</th>
                                    <th class="px-3 py-2 text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]">Penanggung Jawab</th>
                                    <th class="w-[118px] px-3 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]">Jumlah Barang</th>
                                    <th class="w-[62px] px-2 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" title="B = Baik">B</th>
                                    <th class="w-[62px] px-2 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" title="RR = Rusak Ringan">RR</th>
                                    <th class="w-[62px] px-2 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" title="RS = Rusak Sedang">RS</th>
                                    <th class="w-[62px] px-2 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" title="RB = Rusak Berat">RB</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($kirSummary['ruangan'] as $roomIndex => $room)
                                    <tr class="border-t border-[#edf1f5]">
                                        <td class="px-3 py-2 text-center text-[10px] text-[#718095]">{{ $roomIndex + 1 }}</td>
                                        <td class="px-3 py-2"><strong class="text-[10px] font-extrabold text-[#263b52]">{{ $room['nama_ruangan'] }}</strong></td>
                                        <td class="px-3 py-2 text-[10px] font-semibold text-[#52657a]">{{ filled($room['penanggung_jawab'] ?? null) ? $room['penanggung_jawab'] : '-' }}</td>
                                        <td class="px-3 py-2 text-center text-[10px] font-extrabold text-[#0b2b50]">{{ $number($room['jumlah_barang'] ?? 0) }}</td>
                                        <td class="px-2 py-2 text-center"><span class="condition-badge condition-baik px-2 py-1 text-[9px]">{{ $number($room['kondisi']['baik'] ?? 0) }}</span></td>
                                        <td class="px-2 py-2 text-center"><span class="condition-badge condition-rusak_ringan px-2 py-1 text-[9px]">{{ $number($room['kondisi']['rusak_ringan'] ?? 0) }}</span></td>
                                        <td class="px-2 py-2 text-center"><span class="condition-badge condition-rusak_sedang px-2 py-1 text-[9px]">{{ $number($room['kondisi']['rusak_sedang'] ?? 0) }}</span></td>
                                        <td class="px-2 py-2 text-center"><span class="condition-badge condition-rusak_berat px-2 py-1 text-[9px]">{{ $number($room['kondisi']['rusak_berat'] ?? 0) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>

        <article class="mt-5 rounded-[14px] border border-[#e3e9f0] bg-white p-[22px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]" aria-labelledby="asset-category-dashboard-title">
            <header>
                <h3 class="m-0 text-[15px] font-extrabold text-[#20364f]" id="asset-category-dashboard-title">Daftar Kategori Aset</h3>
                <p class="m-0 mt-1 text-[10px] text-[#94a0af]">Ringkasan jumlah dan kondisi aset berdasarkan kategori.</p>
            </header>

            <div class="mt-5">
                @if ($assetCategories === [])
                    <div class="grid min-h-[120px] place-items-center rounded-[12px] bg-[#f7f9fc] px-4 text-center text-[11px] font-semibold text-[#718095]">
                        Belum ada kategori aset.
                    </div>
                @else
                    <div class="dashboard-inventory-scroll dashboard-category-table-scroll max-h-[340px] overflow-auto rounded-[12px] border border-[#edf1f5]">
                        <table class="w-full min-w-[720px] border-collapse text-left">
                            <thead class="bg-[#f8fafc]">
                                <tr>
                                    <th class="dashboard-category-sticky-first w-[52px] px-3 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" rowspan="2">No</th>
                                    <th class="dashboard-category-sticky-first px-3 py-2 text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" rowspan="2">Kategori Barang</th>
                                    <th class="dashboard-category-sticky-first w-[118px] px-3 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" rowspan="2">Jumlah</th>
                                    <th class="dashboard-category-sticky-first px-3 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" colspan="4">Kondisi</th>
                                </tr>
                                <tr>
                                    <th class="dashboard-category-sticky-second w-[62px] px-2 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" title="B = Baik">B</th>
                                    <th class="dashboard-category-sticky-second w-[62px] px-2 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" title="RR = Rusak Ringan">RR</th>
                                    <th class="dashboard-category-sticky-second w-[62px] px-2 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" title="RS = Rusak Sedang">RS</th>
                                    <th class="dashboard-category-sticky-second w-[62px] px-2 py-2 text-center text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a899c]" title="RB = Rusak Berat">RB</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assetCategories as $categoryIndex => $category)
                                    @php
                                        $categoryItems = $category['items'] ?? [];
                                        $hasCategoryItems = $categoryItems !== [];
                                        $categoryDetailId = 'asset-category-detail-'.$category['id'];
                                    @endphp
                                    <tr class="border-t border-[#edf1f5]">
                                        <td class="px-3 py-2 text-center text-[10px] text-[#718095]">{{ $categoryIndex + 1 }}</td>
                                        <td class="px-3 py-2">
                                            <div class="flex items-start gap-2">
                                                @if ($hasCategoryItems)
                                                    <button type="button" class="mt-0.5 inline-grid h-5 w-5 shrink-0 place-items-center rounded-full border border-[#dbe5ef] bg-white text-[#60708a] transition hover:border-[#bcd4ea] hover:bg-[#edf6ff] hover:text-[#146bb5] focus:outline-none focus:ring-2 focus:ring-[#d8ecff]" data-asset-category-toggle aria-expanded="false" aria-controls="{{ $categoryDetailId }}" title="Lihat rincian {{ $category['nama_kategori'] }}">
                                                        <svg class="h-3 w-3 transition-transform" data-asset-category-chevron viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path fill-rule="evenodd" d="M7.21 5.23a.75.75 0 0 1 1.06-.02l4.25 4a.75.75 0 0 1 0 1.08l-4.25 4a.75.75 0 1 1-1.04-1.08L10.9 9.75 7.23 6.29a.75.75 0 0 1-.02-1.06Z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                @else
                                                    <span class="mt-0.5 h-5 w-5 shrink-0"></span>
                                                @endif
                                                <div class="min-w-0">
                                                    <strong class="text-[10px] font-extrabold text-[#263b52]">{{ $category['nama_kategori'] }}</strong>
                                                    @if (filled($category['kode_kategori'] ?? null))
                                                        <small class="mt-1 block text-[9px] font-semibold text-[#94a0af]">{{ $category['kode_kategori'] }}</small>
                                                    @endif
                                                    <small class="mt-1 block text-[9px] font-semibold text-[#94a0af]">{{ $number($category['jenis_count'] ?? 0) }} jenis aset</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-center text-[10px] font-extrabold text-[#0b2b50]">{{ $number($category['jumlah'] ?? 0) }}</td>
                                        <td class="px-2 py-2 text-center"><span class="condition-badge condition-baik px-2 py-1 text-[9px]">{{ $number($category['kondisi']['baik'] ?? 0) }}</span></td>
                                        <td class="px-2 py-2 text-center"><span class="condition-badge condition-rusak_ringan px-2 py-1 text-[9px]">{{ $number($category['kondisi']['rusak_ringan'] ?? 0) }}</span></td>
                                        <td class="px-2 py-2 text-center"><span class="condition-badge condition-rusak_sedang px-2 py-1 text-[9px]">{{ $number($category['kondisi']['rusak_sedang'] ?? 0) }}</span></td>
                                        <td class="px-2 py-2 text-center"><span class="condition-badge condition-rusak_berat px-2 py-1 text-[9px]">{{ $number($category['kondisi']['rusak_berat'] ?? 0) }}</span></td>
                                    </tr>
                                    @if ($hasCategoryItems)
                                        <tr class="hidden border-t border-[#edf1f5] bg-[#f8fbff]" id="{{ $categoryDetailId }}" data-asset-category-detail>
                                            <td class="px-3 py-3"></td>
                                            <td class="px-3 py-3" colspan="6">
                                                <div class="ml-7 rounded-[12px] border border-[#e7eef6] bg-white/80 p-3">
                                                    <div class="mb-2 text-[9px] font-extrabold uppercase tracking-[.04em] text-[#7a899c]">Rincian Aset</div>
                                                    <div class="grid gap-2">
                                                        @foreach ($categoryItems as $item)
                                                            <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 rounded-[10px] bg-[#f7f9fc] px-3 py-2">
                                                                <span class="min-w-0 truncate text-[10px] font-extrabold text-[#263b52]">{{ $item['nama_aset'] }}</span>
                                                                <span class="shrink-0 text-[10px] font-bold text-[#52657a]">{{ $number($item['jumlah'] ?? 0) }} unit</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="m-0 mt-2 text-[10px] text-[#94a0af]">B = Baik, RR = Rusak Ringan, RS = Rusak Sedang, RB = Rusak Berat.</p>
                @endif
            </div>
        </article>

    </section>

    <section class="mt-5 grid grid-cols-[minmax(0,1fr)_470px] gap-5 max-[1180px]:grid-cols-1">
        <article class="min-w-0 rounded-[14px] border border-[#e3e9f0] bg-white p-[22px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]">
            <header><h2 class="m-0 mb-[3px] text-[15px] font-extrabold text-[#20364f]">Aktivitas Aset</h2><p class="m-0 text-[10px] text-[#94a0af]">Pergerakan unit selama enam bulan terakhir</p></header>
            <div class="mt-1 flex justify-end gap-5 text-[10px] text-[#617188]">
                <span class="inline-flex items-center gap-1.5"><i class="h-2 w-2 rounded-sm bg-[#2384c6]"></i>Barang masuk</span>
                <span class="inline-flex items-center gap-1.5"><i class="h-2 w-2 rounded-sm bg-[#43ad89]"></i>Dipinjam</span>
                <span class="inline-flex items-center gap-1.5"><i class="h-2 w-2 rounded-sm bg-[#e58b62]"></i>Dihapus</span>
            </div>
            <div class="mt-8 grid h-[230px] grid-cols-6 items-end gap-4 border-b border-[#e3e9f0] px-3">
                @foreach ($chart as $item)
                    <div class="grid h-full grid-rows-[1fr_auto] justify-items-center gap-2">
                        <div class="flex h-full items-end gap-1.5">
                            <span class="block w-2.5 rounded-t-full bg-[#2384c6]" title="{{ $item['incoming'] }} barang masuk" style="height: {{ max($item['incoming'] ? 8 : 0, $item['incoming'] / $chartMaximum * 100) }}%"></span>
                            <span class="block w-2.5 rounded-t-full bg-[#43ad89]" title="{{ $item['borrowed'] }} dipinjam" style="height: {{ max($item['borrowed'] ? 8 : 0, $item['borrowed'] / $chartMaximum * 100) }}%"></span>
                            <span class="block w-2.5 rounded-t-full bg-[#e58b62]" title="{{ $item['disposed'] }} dihapus" style="height: {{ max($item['disposed'] ? 8 : 0, $item['disposed'] / $chartMaximum * 100) }}%"></span>
                        </div>
                        <small class="text-[10px] text-[#74839a]">{{ \Carbon\Carbon::createFromFormat('Y-m', $item['month'])->translatedFormat('M') }}</small>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="min-w-0 rounded-[14px] border border-[#e3e9f0] bg-white p-[22px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]">
            <header><h2 class="m-0 mb-[3px] text-[15px] font-extrabold text-[#20364f]">Kondisi Aset</h2><p class="m-0 text-[10px] text-[#94a0af]">{{ $number($totalCondition) }} unit aktif</p></header>
            <div class="mx-auto mt-7 grid h-[174px] w-[174px] place-items-center rounded-full" style="background: conic-gradient(#3ca982 0 {{ $baikDeg }}deg,#d7a742 {{ $baikDeg }}deg {{ $rusakRinganDeg }}deg,#f97316 {{ $rusakRinganDeg }}deg {{ $rusakSedangDeg }}deg,#dc6b58 {{ $rusakSedangDeg }}deg {{ $rusakBeratDeg }}deg,#8b96a6 {{ $rusakBeratDeg }}deg 360deg)">
                <div class="grid h-[118px] w-[118px] place-items-center rounded-full bg-white text-center">
                    <div><strong class="block text-[26px] font-extrabold text-[#0b2b50]">{{ $baikPercent }}%</strong><span class="text-[10px] text-[#74839a]">kondisi baik</span></div>
                </div>
            </div>
            <div class="mt-7 grid grid-cols-2 gap-3">
                @foreach ($kondisiLabels as $key => [$label, $color])
                    <div class="flex items-center gap-2 text-[11px] text-[#60708a]"><span class="h-2.5 w-2.5 rounded-full {{ $color }}"></span>{{ $label }}<strong class="ml-auto text-[#0b2b50]">{{ $number($kondisis[$key]) }}</strong></div>
                @endforeach
            </div>
        </article>
    </section>
</main>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-stock-category-card]').forEach((card) => {
            const filter = card.querySelector('[data-stock-status-filter]')
            if (!filter || filter.dataset.stockFilterReady === 'true') return

            filter.dataset.stockFilterReady = 'true'

            const rows = Array.from(card.querySelectorAll('[data-stock-row]'))
            const emptyState = card.querySelector('[data-stock-filter-empty]')
            const scrollContainer = card.querySelector('[data-stock-scroll-container]')

            const applyFilter = () => {
                const selectedStatus = filter.value
                let visibleIndex = 1
                let visibleCount = 0

                rows.forEach((row) => {
                    const shouldShow = selectedStatus === 'all' || row.dataset.stockStatus === selectedStatus
                    row.hidden = !shouldShow

                    if (shouldShow) {
                        const numberCell = row.querySelector('[data-stock-row-number]')
                        if (numberCell) numberCell.textContent = visibleIndex
                        visibleIndex += 1
                        visibleCount += 1
                    }
                })

                emptyState?.classList.toggle('hidden', visibleCount > 0)

                if (scrollContainer) {
                    scrollContainer.scrollTop = 0
                }
            }

            filter.addEventListener('change', applyFilter)
            applyFilter()
        })

        document.querySelectorAll('[data-asset-category-toggle]').forEach((button) => {
            if (button.dataset.assetCategoryToggleReady === 'true') return

            button.dataset.assetCategoryToggleReady = 'true'

            const detailId = button.getAttribute('aria-controls')
            const detailRow = detailId ? document.getElementById(detailId) : null
            const chevron = button.querySelector('[data-asset-category-chevron]')

            if (!detailRow) return

            button.addEventListener('click', () => {
                const isExpanded = button.getAttribute('aria-expanded') === 'true'
                const nextExpanded = !isExpanded

                button.setAttribute('aria-expanded', String(nextExpanded))
                detailRow.classList.toggle('hidden', !nextExpanded)
                chevron?.classList.toggle('rotate-90', nextExpanded)
            })
        })
    })
</script>
@endsection
