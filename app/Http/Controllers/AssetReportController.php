<?php

namespace App\Http\Controllers;

use App\Enums\AssetCondition;
use App\Http\Requests\AssetReportRequest;
use App\Models\AssetCategory;
use App\Models\StockOpnameCategory;
use App\Services\AssetReportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetReportController extends Controller
{
    public function __construct(private readonly AssetReportService $reportService)
    {
    }

    public function index(AssetReportRequest $request): View
    {
        return view('laporan.index', [
            'report' => $this->reportService->report($request),
            'categories' => AssetCategory::query()->orderBy('nama_kategori')->get(),
            'stockCategories' => StockOpnameCategory::query()->orderBy('nama_kategori')->get(),
            'conditions' => AssetCondition::cases(),
        ]);
    }

    public function pdf(AssetReportRequest $request): Response
    {
        $report = $this->reportService->report($request);
        $rows = $report['export_rows'];
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reports.assets', [
            'rows' => $rows,
            'summary' => $report['summary'],
            'reportType' => $report['type'],
            'filters' => $request->validated(),
            'generatedAt' => now(),
        ])->render());
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename('pdf').'"',
        ]);
    }

    public function xlsx(AssetReportRequest $request): StreamedResponse
    {
        $report = $this->reportService->report($request);
        $rows = $report['export_rows'];
        $summary = $report['summary'];
        $type = $report['type'];
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Sistem Informasi Manajemen Aset')
            ->setTitle($type === 'stock_opname' ? 'Laporan Stock Opname' : 'Laporan Aset');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($type === 'stock_opname' ? 'Stock Opname' : 'Laporan Aset');
        $headers = $type === 'stock_opname'
            ? ['No', 'Kode Internal', 'Nama Barang', 'Kategori', 'Satuan', 'Total Stok', 'Stok Tersedia', 'Status Stok', 'Status Verifikasi']
            : ($type === 'all'
                ? ['No', 'Sumber Data', 'Kode Internal', 'Nama Barang', 'Kategori', 'Jumlah', 'Satuan', 'Keterangan Status']
                : ['No', 'Kode Internal', 'Nama Barang', 'Kategori', 'Merek/Tipe', 'Jumlah', 'Tersedia', 'Reservasi', 'Dipinjam', 'Kondisi', 'Tahun', 'Lokasi', 'Status']);
        $sheet->fromArray([['LAPORAN '.strtoupper($type === 'stock_opname' ? 'STOCK OPNAME' : ($type === 'all' ? 'SEMUA BARANG' : 'DATA ASET'))], [], $headers]);

        foreach ($rows as $index => $row) {
            $data = $type === 'stock_opname'
                ? [$index + 1, $row['display_code'], $row['nama_barang'], $row['kategori'], $row['satuan'], $row['stok'], $row['tersedia'], $row['label_stock_status'], $row['label_verification']]
                : ($type === 'all'
                    ? [$index + 1, $row['source'], $row['display_code'], $row['nama_barang'], $row['kategori'], $row['jumlah'], $row['satuan'] ?? '-', $row['status'] ?? $row['label_kondisi']]
                    : [$index + 1, $row['display_code'], $row['nama_aset'], $row['kategori'], $row['merek_tipe'], $row['jumlah'], $row['tersedia'], $row['direservasi'], $row['dipinjam'], $row['label_kondisi'], $row['tahun_perolehan'], $row['lokasi_penyimpanan'], $row['label_status_siklus']]);
            $sheet->fromArray([$data], null, 'A'.($index + 4));
        }

        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->mergeCells('A1:'.$lastColumn.'1');
        $sheet->getStyle('A1:'.$lastColumn.'1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:'.$lastColumn.'3')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A3:'.$lastColumn.'3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E78');
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $this->filename('xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function filename(string $extension): string
    {
        return 'laporan-aset-'.now()->format('Ymd-His').'.'.$extension;
    }
}
