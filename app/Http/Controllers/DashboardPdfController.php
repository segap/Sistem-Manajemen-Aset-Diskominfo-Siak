<?php

namespace App\Http\Controllers;

use App\Services\DashboardDataService;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardPdfController extends Controller
{
    public function download(Request $request, DashboardDataService $dashboard, string $section): Response
    {
        $requestedYear = $request->query('tahun');
        $reportYear = $requestedYear === 'all'
            ? null
            : (filter_var($requestedYear, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]) ?: now()->year);
        $data = $dashboard->data($reportYear);
        $printedAt = now()->locale('id')->translatedFormat('d F Y, H:i').' WIB';

        return match ($section) {
            'keseluruhan' => $this->fullDashboardPdf($data, $printedAt),
            'rekonsiliasi' => $this->reconciliationPdf($data, $printedAt),
            'monitoring-laporan' => $this->reportMonitoringPdf($data, $printedAt),
            'monitoring-persediaan' => $this->inventoryMonitoringPdf($data, $printedAt),
            'persediaan' => $this->stockOpnamePdf($data, $printedAt),
            'kategori-aset' => $this->tablePdf(
                'Laporan Daftar Kategori Aset',
                ['No.', 'Kategori', 'Kode', 'Jenis Aset', 'Total Unit', 'B', 'RR', 'RS', 'RB', 'H'],
                collect($data['asset_categories'] ?? [])->values()->map(fn (array $category, int $index): array => [
                    $index + 1,
                    $category['nama_kategori'] ?? '-',
                    $category['kode_kategori'] ?: '-',
                    (int) ($category['jenis_count'] ?? 0),
                    (int) ($category['jumlah'] ?? 0),
                    (int) ($category['kondisi']['baik'] ?? 0),
                    (int) ($category['kondisi']['rusak_ringan'] ?? 0),
                    (int) ($category['kondisi']['rusak_sedang'] ?? 0),
                    (int) ($category['kondisi']['rusak_berat'] ?? 0),
                    (int) ($category['kondisi']['hilang'] ?? 0),
                ]),
                $printedAt,
                'Kategori aset aktif dan disetujui.',
                'laporan-daftar-kategori-aset.pdf'
            ),
            'barang-kondisi' => $this->assetItemsPdf($request, $data, $printedAt),
            'kir' => $this->tablePdf(
                'Laporan Kartu Inventaris Ruangan',
                ['No.', 'Ruangan', 'Kode Ruangan', 'Penanggung Jawab', 'Total Aset', 'B', 'RR', 'RS', 'RB', 'H'],
                collect($data['kir_summary']['ruangan'] ?? [])->values()->map(fn (array $room, int $index): array => [
                    $index + 1,
                    $room['nama_ruangan'] ?? '-',
                    $room['kode_ruangan'] ?: '-',
                    $room['penanggung_jawab'] ?: '-',
                    (int) ($room['jumlah_barang'] ?? 0),
                    (int) ($room['kondisi']['baik'] ?? 0),
                    (int) ($room['kondisi']['rusak_ringan'] ?? 0),
                    (int) ($room['kondisi']['rusak_sedang'] ?? 0),
                    (int) ($room['kondisi']['rusak_berat'] ?? 0),
                    (int) ($room['kondisi']['hilang'] ?? 0),
                ]),
                $printedAt,
                'Ruangan aktif beserta ringkasan kondisi asetnya.',
                'laporan-kartu-inventaris-ruangan.pdf'
            ),
            'aktivitas-aset' => $this->activityPdf($data, $printedAt),
            'kondisi-aset' => $this->conditionPdf($data, $printedAt),
            default => abort(404),
        };
    }

    /** @param array<string, mixed> $data */
    private function fullDashboardPdf(array $data, string $printedAt): Response
    {
        $activity = collect($data['activity_chart'] ?? [])->first() ?? [];
        $activityMonth = filled($activity['month'] ?? null)
            ? Carbon::createFromFormat('Y-m', $activity['month'])->locale('id')->translatedFormat('F Y')
            : '-';

        return $this->pdf(
            'A4',
            'reports.dashboard-full',
            [
                'dashboard' => $data,
                'printedAt' => $printedAt,
                'reportPeriod' => $this->reportYearFilter($data),
                'activityMonth' => $activityMonth,
                'activity' => $activity,
            ],
            'laporan-dashboard-simiva-'.now()->format('Y-m-d').'.pdf',
            'landscape',
            'Times-Roman'
        );
    }

    /** @param array<string, mixed> $data */
    private function reconciliationPdf(array $data, string $printedAt): Response
    {
        $statusLabels = ['uploaded' => 'Sudah Diunggah', 'missing' => 'Belum Diunggah', 'current' => 'Bulan Berjalan', 'upcoming' => 'Belum Jatuh Tempo'];
        $rows = collect($data['report_monitoring_years'] ?? [])
            ->flatMap(function (array $yearData) use ($statusLabels): array {
                $reconciliation = $yearData['reconciliation'] ?? [];

                return collect($reconciliation['months'] ?? [])
                    ->map(fn (array $month, int $index): array => [
                        $reconciliation['year'] ?? '-',
                        $index + 1,
                        $month['label'] ?? '-',
                        $statusLabels[$month['status'] ?? 'upcoming'] ?? 'Tidak Diketahui',
                    ])
                    ->all();
            })
            ->values()
            ->map(fn (array $row, int $index): array => [$index + 1, ...$row]);

        return $this->tablePdf(
            'Laporan Monitoring Rekonsiliasi Bulanan',
            ['No.', 'Tahun', 'Bulan', 'Status'],
            $rows,
            $printedAt,
            $this->reportYearFilter($data),
            'laporan-monitoring-rekonsiliasi.pdf'
        );
    }

    /** @param array<string, mixed> $data */
    private function reportMonitoringPdf(array $data, string $printedAt): Response
    {
        $rows = collect($data['report_monitoring_years'] ?? [])
            ->flatMap(function (array $yearData): array {
                return collect($yearData['asset_reports'] ?? [])
                    ->map(fn (array $report): array => [
                        $yearData['year'] ?? '-',
                        $report['label'] ?? '-',
                        $report['uploaded_at'] ?: 'Belum diunggah',
                    ])
                    ->all();
            })
            ->values()
            ->map(fn (array $row, int $index): array => [$index + 1, ...$row]);

        return $this->tablePdf(
            'Laporan Monitoring Kelengkapan Laporan Aset',
            ['No.', 'Tahun', 'Jenis Laporan', 'Status / Terakhir Diunggah'],
            $rows,
            $printedAt,
            $this->reportYearFilter($data),
            'laporan-monitoring-laporan-aset.pdf'
        );
    }

    /** @param array<string, mixed> $data */
    private function inventoryMonitoringPdf(array $data, string $printedAt): Response
    {
        $rows = collect($data['report_monitoring_years'] ?? [])
            ->flatMap(fn (array $yearData): array => collect($yearData['inventory_reports']['reports'] ?? [])
                ->map(fn (array $report): array => [
                    $yearData['year'] ?? '-',
                    $report['label'] ?? '-',
                    ($report['is_complete'] ?? false) ? 'Lengkap' : 'Belum lengkap',
                    $report['detail'] ?? $report['uploaded_at'] ?? '-',
                ])->all())
            ->values()
            ->map(fn (array $row, int $index): array => [$index + 1, ...$row]);

        return $this->tablePdf('Laporan Monitoring Kelengkapan Laporan Persediaan', ['No.', 'Tahun', 'Jenis Laporan', 'Status', 'Keterangan'], $rows, $printedAt, $this->reportYearFilter($data), 'laporan-monitoring-persediaan.pdf');
    }

    /** @param array<string, mixed> $data */
    private function stockOpnamePdf(array $data, string $printedAt): Response
    {
        return $this->pdf(
            'Laporan Barang Persediaan',
            'reports.dashboard-stock-opname',
            ['categories' => collect($data['stock_opname_categories'] ?? [])->filter(fn (array $category): bool => ($category['items'] ?? []) !== [])->values(), 'printedAt' => $printedAt],
            'laporan-barang-persediaan.pdf',
            'portrait'
        );
    }

    /** @param array<string, mixed> $data */
    private function assetItemsPdf(Request $request, array $data, string $printedAt): Response
    {
        $categoryId = filter_var($request->query('kategori'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $items = collect($data['asset_items_by_condition'] ?? []);
        $categories = collect($data['asset_categories'] ?? [])->keyBy('id');
        $filterInfo = 'Semua kategori';

        if ($categoryId) {
            abort_unless($categories->has($categoryId), 422, 'Kategori aset tidak valid.');
            $items = $items->where('category_id', $categoryId)->values();
            $filterInfo = 'Kategori: '.($categories->get($categoryId)['nama_kategori'] ?? '-');
        }

        return $this->tablePdf(
            'Laporan Barang Berdasarkan Kondisi',
            ['No.', 'Nama Barang', 'Kategori', 'Total Unit', 'B', 'RR', 'RS', 'RB', 'TA'],
            $items->values()->map(fn (array $item, int $index): array => [
                $index + 1,
                $item['asset_name'] ?? '-',
                $item['category_name'] ?? '-',
                (int) ($item['total'] ?? 0),
                (int) ($item['conditions']['baik'] ?? 0),
                (int) ($item['conditions']['rusak_ringan'] ?? 0),
                (int) ($item['conditions']['rusak_sedang'] ?? 0),
                (int) ($item['conditions']['rusak_berat'] ?? 0),
                (int) ($item['tidak_ada'] ?? 0),
            ]),
            $printedAt,
            $filterInfo.' | TA = Tidak Ada (hasil pendataan); tidak termasuk Total Unit.',
            'laporan-barang-berdasarkan-kondisi.pdf'
        );
    }

    /** @param array<string, mixed> $data */
    private function activityPdf(array $data, string $printedAt): Response
    {
        $activity = collect($data['activity_chart'] ?? [])->first() ?? [];
        $month = filled($activity['month'] ?? null)
            ? Carbon::createFromFormat('Y-m', $activity['month'])->locale('id')->translatedFormat('F Y')
            : '-';
        $labels = [
            'incoming' => 'Barang Masuk',
            'borrowed' => 'Dipinjam',
            'returned' => 'Dikembalikan',
            'maintenance' => 'Pemeliharaan',
            'condition_changed' => 'Perubahan Kondisi',
            'disposed' => 'Dihapus',
        ];

        return $this->tablePdf(
            'Laporan Aktivitas Aset',
            ['No.', 'Jenis Aktivitas', 'Jumlah Unit'],
            collect($labels)
                ->map(fn (string $label, string $key): array => [$label, (int) ($activity[$key] ?? 0)])
                ->values()
                ->map(fn (array $row, int $index): array => [$index + 1, ...$row]),
            $printedAt,
            'Periode: '.$month,
            'laporan-aktivitas-aset.pdf'
        );
    }

    /** @param array<string, mixed> $data */
    private function conditionPdf(array $data, string $printedAt): Response
    {
        $labels = ['baik' => 'Baik', 'rusak_ringan' => 'Rusak Ringan', 'rusak_sedang' => 'Rusak Sedang', 'rusak_berat' => 'Rusak Berat', 'hilang' => 'Hilang'];

        return $this->tablePdf(
            'Laporan Kondisi Aset',
            ['No.', 'Kondisi', 'Jumlah Unit'],
            collect($labels)
                ->map(fn (string $label, string $key): array => [$label, (int) ($data['kondisi_distribution'][$key] ?? 0)])
                ->values()
                ->map(fn (array $row, int $index): array => [$index + 1, ...$row]),
            $printedAt,
            'Aset aktif dan disetujui.',
            'laporan-kondisi-aset.pdf'
        );
    }

    /** @param array<string, mixed> $data */
    private function reportYearFilter(array $data): string
    {
        $year = $data['report_filter']['selected_year'] ?? null;

        return $year === null ? 'Semua tahun' : 'Tahun: '.$year;
    }

    /** @param \Illuminate\Support\Collection<int, array<int, string|int>> $rows */
    private function tablePdf(string $title, array $headers, \Illuminate\Support\Collection $rows, string $printedAt, string $filterInfo, string $filename): Response
    {
        return $this->pdf('A4', 'exports.table', compact('title', 'headers', 'rows', 'printedAt', 'filterInfo'), $filename, count($headers) > 7 ? 'landscape' : 'portrait');
    }

    /** @param array<string, mixed> $viewData */
    private function pdf(string $paper, string $view, array $viewData, string $filename, string $orientation, string $font = 'DejaVu Sans'): Response
    {
        $options = new Options();
        $options->set('defaultFont', $font);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $viewData)->render());
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
