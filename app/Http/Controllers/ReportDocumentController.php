<?php

namespace App\Http\Controllers;

use App\Enums\WorkUnit;
use App\Models\AssetHolder;
use App\Models\ReportDocument;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Writer\Word2007;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportDocumentController extends Controller
{
    /** @var array<string, array<string, mixed>> */
    private const REPORTS = [
        'rekonsiliasi-bulanan' => [
            'title' => 'Laporan Rekonsiliasi Bulanan',
            'description' => 'Arsip PDF laporan rekonsiliasi aset per bulan.',
            'type' => 'monthly',
            'table_by_month' => true,
            'monthly_table_heading' => 'Dokumen Rekonsiliasi',
            'monthly_document_label' => 'Rekonsiliasi Bulanan',
            'single_document_per_period' => true,
            'inventory' => false,
        ],
        'semester-1' => [
            'title' => 'Laporan Semester 1',
            'description' => 'Arsip PDF laporan aset periode Januari sampai Juni.',
            'type' => 'single',
            'period' => 'semester_1',
            'period_label' => 'Semester 1',
            'inventory' => false,
        ],
        'tahunan' => [
            'title' => 'Laporan Tahunan',
            'description' => 'Arsip PDF laporan aset tahunan.',
            'type' => 'single',
            'period' => 'tahunan',
            'period_label' => 'Tahunan',
            'inventory' => false,
        ],
        'aset-unaudited' => [
            'title' => 'Laporan Aset Unaudited',
            'description' => 'Arsip PDF laporan aset unaudited per tahun.',
            'type' => 'single',
            'period' => 'aset_unaudited',
            'period_label' => 'Unaudited',
            'inventory' => false,
        ],
        'aset-audited' => [
            'title' => 'Laporan Aset Audited',
            'description' => 'Arsip PDF laporan aset audited per tahun.',
            'type' => 'single',
            'period' => 'aset_audited',
            'period_label' => 'Audited',
            'inventory' => false,
        ],
        'rkbmd' => [
            'title' => 'Laporan RKBMD',
            'description' => 'Arsip PDF Rencana Kebutuhan Barang Milik Daerah.',
            'type' => 'multi',
            'periods' => ['apbd_murni', 'apbd_perubahan'],
            'period_labels' => [
                'apbd_murni' => 'APBD Murni',
                'apbd_perubahan' => 'APBD Perubahan',
            ],
            'filter_by_year' => true,
            'rkbmd' => true,
            'inventory' => false,
        ],
        'berita-acara-bmd' => [
            'title' => 'Dokumen Berita Acara Pemakaian BMD dan Serah Terima',
            'description' => 'Arsip dokumen berita acara pemakaian Barang Milik Daerah (BMD) dan serah terima berdasarkan tahun.',
            'type' => 'multi',
            'period' => 'berita_acara_bmd',
            'inventory' => false,
            'aliases' => ['berita-acara-pemakaian-bmd', 'berita-acara-serah-terima'],
            'asset_holder_archive' => true,
        ],
        'berita-acara-pemakaian-bmd' => [
            'title' => 'Berita Acara Pemakaian BMD',
            'description' => 'Arsip PDF berita acara pemakaian Barang Milik Daerah.',
            'type' => 'multi',
            'period' => 'berita_acara_pemakaian_bmd',
            'inventory' => false,
            'redirect_to' => 'berita-acara-bmd',
        ],
        'berita-acara-serah-terima' => [
            'title' => 'Berita Acara Serah Terima',
            'description' => 'Arsip PDF berita acara serah terima.',
            'type' => 'multi',
            'period' => 'berita_acara_serah_terima',
            'inventory' => false,
            'redirect_to' => 'berita-acara-bmd',
        ],
        'persediaan-bulanan' => [
            'title' => 'Laporan Persediaan Bulanan',
            'description' => 'Arsip PDF laporan persediaan per bulan.',
            'type' => 'monthly',
            'table_by_month' => true,
            'monthly_table_heading' => 'Dokumen Persediaan',
            'monthly_document_label' => 'Persediaan Bulanan',
            'single_document_per_period' => true,
            'inventory' => true,
        ],
        'persediaan-semester-1' => [
            'title' => 'Laporan Persediaan Semester 1',
            'description' => 'Arsip PDF laporan persediaan periode Januari sampai Juni.',
            'type' => 'single',
            'period' => 'persediaan_semester_1',
            'period_label' => 'Semester 1',
            'inventory' => true,
        ],
        'persediaan-tahunan' => [
            'title' => 'Laporan Persediaan Tahunan',
            'description' => 'Arsip PDF laporan persediaan tahunan.',
            'type' => 'single',
            'period' => 'persediaan_tahunan',
            'period_label' => 'Tahunan',
            'inventory' => true,
        ],
    ];

    /** @var array<int, string> */
    private const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function index(Request $request, string $jenis): View|RedirectResponse
    {
        $config = $this->reportConfig($jenis);
        if (isset($config['redirect_to'])) {
            return redirect()->route('laporan-dokumen.index', [
                'jenis' => $config['redirect_to'],
                'tahun' => $request->integer('tahun', now()->year),
            ]);
        }

        $this->authorizeReportView($request, $jenis, $config);

        $year = $request->integer('tahun', now()->year);
        $isAssetHolderArchive = $this->isAssetHolderArchive($config);
        $schemaReady = Schema::hasTable('laporan_dokumen')
            && (! $isAssetHolderArchive || $this->assetHolderArchiveSchemaReady());
        $reportTypes = $this->reportTypes($jenis, $config);
        $yearOptions = $this->yearOptions($year);
        $perPage = $this->perPage($request);
        $search = $this->searchTerm($request);
        $documentsQuery = $schemaReady
            ? $this->documentsQuery($jenis, $config, $year, $search)
            : null;
        $usesMonthlySlots = ($config['table_by_month'] ?? false) === true;
        $documents = $documentsQuery === null
            ? $this->emptyPaginator($request, $perPage)
            : ($usesMonthlySlots
                ? $documentsQuery->get()
                : $documentsQuery->paginate($perPage)->withQueryString());
        $documentCollection = $documents instanceof LengthAwarePaginator
            ? $documents->getCollection()
            : $documents;
        $slots = $this->slots($config, $documentCollection);
        $slotPagination = $usesMonthlySlots
            ? $this->paginateCollection(collect($slots), $request, $perPage)
            : null;
        $occupiedPeriodsByYear = $schemaReady && (($config['single_document_per_period'] ?? false) === true)
            ? ReportDocument::query()
                ->whereIn('jenis_laporan', $reportTypes)
                ->whereIn('tahun', $yearOptions)
                ->whereNotNull('periode')
                ->get(['tahun', 'periode'])
                ->groupBy('tahun')
                ->map(fn ($items) => $items->pluck('periode')->unique()->values()->all())
                ->all()
            : [];

        return view('laporan-dokumen.index', [
            'jenis' => $jenis,
            'config' => $config,
            'year' => $year,
            'yearOptions' => $yearOptions,
            'documents' => $documents,
            'slots' => $slots,
            'slotPagination' => $slotPagination,
            'selectedPerPage' => $perPage,
            'search' => $search,
            'canUpload' => $this->canManageReport($request, $jenis, 'upload'),
            'canDownload' => $this->canManageReport($request, $jenis, 'download'),
            'canDelete' => $this->canManageReport($request, $jenis, 'delete'),
            'canExport' => $this->canManageReport($request, $jenis, 'export'),
            'canComplete' => $this->canManageReport($request, $jenis, 'complete'),
            'months' => self::MONTHS,
            'schemaReady' => $schemaReady,
            'occupiedPeriodsByYear' => $occupiedPeriodsByYear,
            'assetHolders' => $isAssetHolderArchive && $schemaReady
                ? AssetHolder::query()->orderBy('nama_pegawai')->get()
                : collect(),
        ]);
    }

    public function exportPdf(Request $request, string $jenis): Response
    {
        ['config' => $config, 'year' => $year, 'search' => $search, 'documents' => $documents] = $this->exportData($request, $jenis);
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('reports.report-documents', [
            'title' => $config['title'],
            'year' => $year,
            'search' => $search,
            'documents' => $documents,
            'periodLabels' => $config['period_labels'] ?? [],
            'months' => self::MONTHS,
            'generatedAt' => now(),
        ])->render());
        $pdf->setPaper('a4', 'landscape');
        $pdf->render();

        return response($pdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rekap-'.Str::slug($jenis).'-'.$year.'.pdf"',
        ]);
    }

    public function exportWord(Request $request, string $jenis): BinaryFileResponse
    {
        ['config' => $config, 'year' => $year, 'search' => $search, 'documents' => $documents] = $this->exportData($request, $jenis);
        $word = new PhpWord;
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(9);
        $section = $word->addSection(['marginTop' => 720, 'marginBottom' => 720, 'marginLeft' => 720, 'marginRight' => 720]);
        $section->addText($config['title'], ['bold' => true, 'size' => 15, 'color' => '0B2B50']);
        $section->addText('SIMIVA · Tahun '.$year.' · Diekspor '.now()->translatedFormat('d M Y H:i'));
        $section->addText($documents->count().' dokumen'.($search !== '' ? ' · Pencarian: '.$search : ''));
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'DCE4EC', 'cellMargin' => 70]);
        foreach (['No.', 'Periode / Tahun', 'Jenis APBD', 'Judul Dokumen', 'Nama File', 'Tanggal Upload', 'Pengunggah'] as $header) {
            $table->addCell()->addText($header, ['bold' => true, 'color' => '0B2B50']);
        }

        foreach ($documents as $index => $document) {
            $period = ($config['period_labels'][$document->periode] ?? null)
                ?? (self::MONTHS[$document->bulan] ?? $document->periode ?? '-');
            foreach ([
                $index + 1,
                $period.($document->tahun ? ' '.$document->tahun : ''),
                $config['period_labels'][$document->periode] ?? '-',
                $document->judul ?: '-',
                $document->nama_file_asli,
                $document->created_at?->translatedFormat('d M Y H:i') ?: '-',
                $document->uploader?->name ?: '-',
            ] as $value) {
                $table->addCell()->addText((string) $value);
            }
        }

        if ($documents->isEmpty()) {
            $table->addRow();
            $table->addCell(9000, ['gridSpan' => 7])->addText('Data tidak ditemukan.');
        }

        $path = tempnam(sys_get_temp_dir(), 'simiva-report-document-');
        (new Word2007($word))->save($path);

        return response()->download($path, 'rekap-'.Str::slug($jenis).'-'.$year.'.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @return array{config: array<string, mixed>, year: int, search: string, documents: Collection<int, ReportDocument>}
     */
    private function exportData(Request $request, string $jenis): array
    {
        $config = $this->reportConfig($jenis);
        abort_if(isset($config['redirect_to']), Response::HTTP_NOT_FOUND);
        $this->authorizeReportAction($request, $jenis, 'export', true);

        $isAssetHolderArchive = $this->isAssetHolderArchive($config);
        abort_unless(
            Schema::hasTable('laporan_dokumen') && (! $isAssetHolderArchive || $this->assetHolderArchiveSchemaReady()),
            Response::HTTP_SERVICE_UNAVAILABLE,
            'Struktur database laporan dokumen belum tersedia.',
        );

        $year = $request->integer('tahun', now()->year);
        $search = $this->searchTerm($request);
        $documents = $this->documentsQuery($jenis, $config, $year, $search)->get();

        return compact('config', 'year', 'search', 'documents');
    }

    public function store(Request $request, string $jenis): RedirectResponse
    {
        $config = $this->reportConfig($jenis);
        if (isset($config['redirect_to'])) {
            return redirect()->route('laporan-dokumen.index', [
                'jenis' => $config['redirect_to'],
                'tahun' => $request->integer('tahun', now()->year),
            ]);
        }

        $this->authorizeReportAction($request, $jenis, 'upload', true);
        $isAssetHolderArchive = $this->isAssetHolderArchive($config);
        abort_unless(
            Schema::hasTable('laporan_dokumen') && (! $isAssetHolderArchive || $this->assetHolderArchiveSchemaReady()),
            Response::HTTP_SERVICE_UNAVAILABLE,
            $isAssetHolderArchive
                ? 'Struktur arsip Berita Acara belum tersedia. Jalankan migration Berita Acara terlebih dahulu.'
                : 'Struktur database laporan dokumen belum tersedia. Jalankan migration laporan dokumen terlebih dahulu.',
        );
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'periode' => ['nullable', 'string', Rule::in($this->allowedPeriods($config))],
            'judul' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
            ...($isAssetHolderArchive ? [
                'tanggal_berita_acara' => ['required', 'date'],
                'asset_holder_id' => ['required', 'integer', Rule::exists('pemegang_aset', 'id')],
            ] : []),
        ]);

        $assetHolder = null;
        if ($isAssetHolderArchive) {
            $assetHolder = AssetHolder::query()->findOrFail($validated['asset_holder_id']);
            $validated['tahun'] = (int) Str::before($validated['tanggal_berita_acara'], '-');
        }

        $period = $this->normalizedPeriod($config, (string) ($validated['periode'] ?? ''));
        $month = str_starts_with($period, 'bulan_') ? (int) Str::after($period, 'bulan_') : null;

        if (($config['single_document_per_period'] ?? false) === true) {
            $alreadyExists = ReportDocument::query()
                ->whereIn('jenis_laporan', $this->reportTypes($jenis, $config))
                ->where('tahun', $validated['tahun'])
                ->where('periode', $period)
                ->exists();

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'periode' => "Dokumen {$config['title']} untuk bulan dan tahun tersebut sudah tersedia. Pilih bulan lain atau selesaikan keputusan penggantian dokumen terlebih dahulu.",
                ]);
            }
        }

        $file = $request->file('file');
        $storageJenis = $this->storageReportType($jenis);
        $path = $file->store("laporan-dokumen/{$storageJenis}/{$validated['tahun']}", 'local');

        $payload = [
            'jenis_laporan' => $this->storedReportType($jenis),
            'tahun' => $validated['tahun'],
            'bulan' => $month,
            'periode' => $period,
            'judul' => filled($validated['judul'] ?? null)
                ? $validated['judul']
                : $this->defaultTitle($config, $period, (int) $validated['tahun']),
            'nama_file_asli' => $file->getClientOriginalName(),
            'path_file' => $path,
            'mime_type' => $file->getClientMimeType() ?: 'application/pdf',
            'ukuran_file' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
        ];

        if ($isAssetHolderArchive) {
            $payload += [
                'asset_holder_id' => $assetHolder?->id,
                'tanggal_berita_acara' => $validated['tanggal_berita_acara'],
                'nama_pegawai_snapshot' => $assetHolder?->nama_pegawai,
                'jabatan_snapshot' => $assetHolder?->jabatan,
                'status_pegawai_snapshot' => $assetHolder?->is_active,
                'unit_kerja_snapshot' => $assetHolder ? WorkUnit::labelFor($assetHolder->unit_kerja) : null,
            ];
        }

        ReportDocument::query()->create($payload);

        return redirect()
            ->route('laporan-dokumen.index', ['jenis' => $jenis, 'tahun' => $validated['tahun']])
            ->with('success', 'Dokumen laporan berhasil diunggah.');
    }

    public function completeBeritaAcara(Request $request, ReportDocument $document): RedirectResponse
    {
        abort_unless($this->isBeritaAcaraDocument($document), Response::HTTP_NOT_FOUND);
        $this->authorizeReportAction($request, $document->jenis_laporan, 'complete', true);
        abort_unless($this->assetHolderArchiveSchemaReady(), Response::HTTP_SERVICE_UNAVAILABLE, 'Struktur arsip Berita Acara belum tersedia. Jalankan migration Berita Acara terlebih dahulu.');

        $validated = $request->validate([
            'tanggal_berita_acara' => ['required', 'date'],
            'asset_holder_id' => ['required', 'integer', Rule::exists('pemegang_aset', 'id')],
        ]);
        $holder = AssetHolder::query()->findOrFail($validated['asset_holder_id']);

        $document->update([
            'tahun' => (int) Str::before($validated['tanggal_berita_acara'], '-'),
            'asset_holder_id' => $holder->id,
            'tanggal_berita_acara' => $validated['tanggal_berita_acara'],
            'nama_pegawai_snapshot' => $holder->nama_pegawai,
            'jabatan_snapshot' => $holder->jabatan,
            'status_pegawai_snapshot' => $holder->is_active,
            'unit_kerja_snapshot' => WorkUnit::labelFor($holder->unit_kerja),
        ]);

        return redirect()->route('laporan-dokumen.index', [
            'jenis' => 'berita-acara-bmd',
            'tahun' => $document->tahun,
        ])->with('success', 'Data Berita Acara berhasil dilengkapi.');
    }

    public function destroy(Request $request, ReportDocument $document): RedirectResponse
    {
        $this->authorizeReportAction($request, $document->jenis_laporan, 'delete', true);
        $this->ensureSafeStoragePath($document);

        $redirectRoute = route('laporan-dokumen.index', [
            'jenis' => $this->displayReportType($document->jenis_laporan),
            'tahun' => $document->tahun,
        ]);

        $deletedDocument = [
            'id' => $document->id,
            'judul' => $document->judul,
            'nama_file_asli' => $document->nama_file_asli,
            'jenis_laporan' => $document->jenis_laporan,
            'tahun' => $document->tahun,
            'periode' => $document->periode,
            'deleted_by' => $request->user()?->id,
            'deleted_at' => now()->toDateTimeString(),
        ];
        $path = $document->path_file;

        try {
            DB::transaction(function () use ($document): void {
                $document->delete();
            });

            if ($path && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }

            Log::info('Dokumen laporan dihapus permanen.', $deletedDocument);
        } catch (\Throwable $exception) {
            Log::error('Gagal menghapus dokumen laporan.', [
                ...$deletedDocument,
                'error' => $exception->getMessage(),
            ]);

            return redirect($redirectRoute)->withErrors([
                'file' => 'Dokumen gagal dihapus. Silakan coba lagi atau hubungi administrator.',
            ]);
        }

        return redirect($redirectRoute)->with('success', 'Dokumen laporan berhasil dihapus permanen.');
    }

    public function preview(Request $request, ReportDocument $document): Response
    {
        $this->authorizeReportAction($request, $document->jenis_laporan, 'view');
        $this->ensureFileExists($document);

        return response(Storage::disk('local')->get($document->path_file), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $document->nama_file_asli).'"',
        ]);
    }

    public function download(Request $request, ReportDocument $document): StreamedResponse
    {
        $this->authorizeReportAction(
            $request,
            $document->jenis_laporan,
            $document->jenis_laporan === 'rkbmd' ? 'download' : 'view',
            $document->jenis_laporan === 'rkbmd',
        );
        $this->ensureFileExists($document);

        return Storage::disk('local')->download($document->path_file, $document->nama_file_asli, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /** @return array<string, mixed> */
    private function reportConfig(string $jenis): array
    {
        if ($jenis === 'berita_acara_bmd') {
            return self::REPORTS['berita-acara-bmd'];
        }

        abort_unless(isset(self::REPORTS[$jenis]), Response::HTTP_NOT_FOUND);

        return self::REPORTS[$jenis];
    }

    /** @return list<string> */
    private function reportTypes(string $jenis, array $config): array
    {
        return collect([$jenis, $this->storedReportType($jenis), ...($config['aliases'] ?? [])])
            ->unique()
            ->values()
            ->all();
    }

    private function storedReportType(string $jenis): string
    {
        return $jenis === 'berita-acara-bmd' ? 'berita_acara_bmd' : $jenis;
    }

    private function storageReportType(string $jenis): string
    {
        return str_replace('_', '-', $this->storedReportType($jenis));
    }

    private function displayReportType(string $jenis): string
    {
        return in_array($jenis, ['berita_acara_bmd', 'berita-acara-pemakaian-bmd', 'berita-acara-serah-terima'], true)
            ? 'berita-acara-bmd'
            : $jenis;
    }

    private function authorizeReportView(Request $request, string $jenis, array $config): void
    {
        $this->authorizeReportAction($request, $jenis, 'view');
    }

    private function authorizeReportAction(Request $request, string $jenis, string $action, bool $requiresView = false): void
    {
        $permission = $this->reportPermission($jenis, $action);
        $viewPermission = $this->reportPermission($jenis, 'view');

        abort_unless(
            $permission
                && $request->user()?->hasPermission($permission)
                && (! $requiresView || $request->user()?->hasPermission($viewPermission)),
            Response::HTTP_FORBIDDEN,
            'Anda tidak memiliki akses ke dokumen laporan ini.',
        );
    }

    private function canManageReport(Request $request, string $jenis, string $action): bool
    {
        $permission = $this->reportPermission($jenis, $action);
        $viewPermission = $this->reportPermission($jenis, 'view');

        return $permission
            && $request->user()?->hasPermission($viewPermission)
            && $request->user()?->hasPermission($permission);
    }

    private function reportPermission(string $jenis, string $action): ?string
    {
        $module = match ($jenis) {
            'rekonsiliasi-bulanan' => 'laporan_rekonsiliasi_bulanan',
            'semester-1' => 'laporan_semester_1',
            'tahunan' => 'laporan_tahunan',
            'aset-unaudited' => 'laporan_aset_unaudited',
            'aset-audited' => 'laporan_aset_audited',
            'rkbmd' => 'laporan_rkbmd',
            'berita-acara-bmd', 'berita_acara_bmd', 'berita-acara-pemakaian-bmd', 'berita-acara-serah-terima' => 'berita_acara_bmd',
            'persediaan-bulanan' => 'laporan_persediaan_bulanan',
            'persediaan-semester-1' => 'laporan_persediaan_semester_1',
            'persediaan-tahunan' => 'laporan_persediaan_tahunan',
            default => null,
        };

        $allowedActions = $module === 'berita_acara_bmd'
            ? ['view', 'upload', 'complete', 'delete', 'download', 'export']
            : ['view', 'upload', 'delete', 'download', 'export'];

        return $module && in_array($action, $allowedActions, true)
            ? "{$module}.{$action}"
            : null;
    }

    private function isAssetHolderArchive(array $config): bool
    {
        return ($config['asset_holder_archive'] ?? false) === true;
    }

    private function assetHolderArchiveSchemaReady(): bool
    {
        return Schema::hasTable('pemegang_aset')
            && Schema::hasColumns('laporan_dokumen', [
                'asset_holder_id',
                'tanggal_berita_acara',
                'nama_pegawai_snapshot',
                'jabatan_snapshot',
                'status_pegawai_snapshot',
                'unit_kerja_snapshot',
            ]);
    }

    private function isBeritaAcaraDocument(ReportDocument $document): bool
    {
        return in_array($document->jenis_laporan, $this->reportTypes('berita-acara-bmd', self::REPORTS['berita-acara-bmd']), true);
    }

    /** @return list<string> */
    private function allowedPeriods(array $config): array
    {
        if (($config['type'] ?? null) === 'monthly') {
            return collect(array_keys(self::MONTHS))->map(fn (int $month): string => "bulan_{$month}")->all();
        }

        if (($config['type'] ?? null) === 'multi') {
            return $config['periods'] ?? [(string) ($config['period'] ?? '')];
        }

        return [(string) ($config['period'] ?? '')];
    }

    private function normalizedPeriod(array $config, string $period): string
    {
        if (in_array(($config['type'] ?? null), ['monthly', 'multi'], true)) {
            return $period;
        }

        return (string) ($config['period'] ?? $period);
    }

    /** @return list<array<string, mixed>> */
    private function slots(array $config, $documents): array
    {
        if (($config['type'] ?? null) === 'monthly') {
            $slots = collect(self::MONTHS)
                ->map(function (string $monthName, int $month) use ($documents): array {
                    $period = "bulan_{$month}";

                    return [
                        'month' => $month,
                        'periode' => $period,
                        'label' => $monthName,
                        'documents' => $documents->where('periode', $period)->values(),
                    ];
                });

            if (($config['table_by_month'] ?? false) === true) {
                $slots = $slots
                    ->filter(fn (array $slot): bool => $slot['documents']->isNotEmpty())
                    ->sortByDesc('month');
            }

            return $slots
                ->values()
                ->all();
        }

        if (($config['type'] ?? null) === 'single') {
            return [[
                'periode' => (string) $config['period'],
                'label' => (string) $config['period_label'],
                'documents' => $documents->where('periode', $config['period'])->values(),
            ]];
        }

        return [];
    }

    private function perPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }

    private function emptyPaginator(Request $request, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            collect(),
            0,
            $perPage,
            LengthAwarePaginator::resolveCurrentPage(),
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    /** @param Collection<int, mixed> $items */
    private function paginateCollection(Collection $items, Request $request, int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    private function defaultTitle(array $config, string $period, int $year): string
    {
        if (($config['type'] ?? null) === 'monthly') {
            $month = (int) Str::after($period, 'bulan_');

            return ($config['title'] ?? 'Laporan').' '.(self::MONTHS[$month] ?? $period).' '.$year;
        }

        if (($config['rkbmd'] ?? false) === true) {
            return ($config['title'] ?? 'Dokumen').' '.($config['period_labels'][$period] ?? $period).' '.$year;
        }

        return ($config['title'] ?? 'Dokumen').' '.$year;
    }

    /** @return list<int> */
    private function yearOptions(int $selectedYear): array
    {
        $current = now()->year;
        $years = range($current - 3, $current + 2);
        $years[] = $selectedYear;

        return collect($years)->unique()->sortDesc()->values()->all();
    }

    private function searchTerm(Request $request): string
    {
        return Str::substr(trim($request->string('search')->toString()), 0, 100);
    }

    /** @return Builder<ReportDocument> */
    private function documentsQuery(string $jenis, array $config, int $year, string $search): Builder
    {
        $isAssetHolderArchive = $this->isAssetHolderArchive($config);
        $query = ReportDocument::query()
            ->with('uploader')
            ->whereIn('jenis_laporan', $this->reportTypes($jenis, $config));

        if ($isAssetHolderArchive) {
            $query->with('assetHolder')->where(function (Builder $dateQuery) use ($year): void {
                $dateQuery->whereYear('tanggal_berita_acara', $year)
                    ->orWhere(function (Builder $legacyQuery) use ($year): void {
                        $legacyQuery->whereNull('tanggal_berita_acara')->where('tahun', $year);
                    });
            })->orderByDesc('tanggal_berita_acara')->orderByDesc('id');
        } else {
            $query->where('tahun', $year)->latest();
        }

        $this->applyDocumentSearch($query, $search);

        return $query;
    }

    /** @param Builder<ReportDocument> $query */
    private function applyDocumentSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';
        $normalizedSearch = Str::lower($search);
        $matchingMonths = collect(self::MONTHS)
            ->filter(fn (string $name): bool => Str::contains(Str::lower($name), $normalizedSearch))
            ->keys()
            ->map(fn (int $month): string => "bulan_{$month}")
            ->all();
        $matchingApbdPeriods = collect([
            'apbd_murni' => 'APBD Murni',
            'apbd_perubahan' => 'APBD Perubahan',
        ])->filter(fn (string $label): bool => Str::contains(Str::lower($label), $normalizedSearch))
            ->keys()
            ->all();

        $query->where(function (Builder $searchQuery) use ($search, $like, $matchingMonths, $matchingApbdPeriods): void {
            $searchQuery
                ->where('judul', 'like', $like)
                ->orWhere('nama_file_asli', 'like', $like)
                ->orWhere('periode', 'like', $like)
                ->orWhereHas('uploader', fn (Builder $uploaderQuery) => $uploaderQuery->where('name', 'like', $like));

            if (ctype_digit($search)) {
                $searchQuery->orWhere('tahun', (int) $search);
            }

            if ($matchingMonths !== []) {
                $searchQuery->orWhereIn('periode', $matchingMonths);
            }

            if ($matchingApbdPeriods !== []) {
                $searchQuery->orWhereIn('periode', $matchingApbdPeriods);
            }
        });
    }

    private function ensureFileExists(ReportDocument $document): void
    {
        abort_unless(
            $document->path_file && Storage::disk('local')->exists($document->path_file),
            Response::HTTP_NOT_FOUND,
            'File dokumen laporan tidak ditemukan.',
        );
    }

    private function ensureSafeStoragePath(ReportDocument $document): void
    {
        abort_unless(
            $document->path_file
                && ! str_contains($document->path_file, '..')
                && str_starts_with($document->path_file, 'laporan-dokumen/'),
            Response::HTTP_BAD_REQUEST,
            'Path dokumen laporan tidak valid.',
        );
    }
}
