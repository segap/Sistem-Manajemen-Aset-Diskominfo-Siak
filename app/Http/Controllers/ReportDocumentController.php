<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\WorkUnit;
use App\Models\AssetHolder;
use App\Models\ReportDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
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

        $this->authorizeReport($request, $config);

        $year = $request->integer('tahun', now()->year);
        $isAssetHolderArchive = $this->isAssetHolderArchive($config);
        $schemaReady = Schema::hasTable('laporan_dokumen')
            && (! $isAssetHolderArchive || $this->assetHolderArchiveSchemaReady());
        $reportTypes = $this->reportTypes($jenis, $config);
        $yearOptions = $this->yearOptions($year);
        $documents = $schemaReady
            ? ReportDocument::query()
                ->with('uploader')
                ->whereIn('jenis_laporan', $reportTypes)
                ->when($isAssetHolderArchive, function (Builder $query) use ($year): void {
                    $query->where(function (Builder $dateQuery) use ($year): void {
                        $dateQuery->whereYear('tanggal_berita_acara', $year)
                            ->orWhere(function (Builder $legacyQuery) use ($year): void {
                                $legacyQuery->whereNull('tanggal_berita_acara')->where('tahun', $year);
                            });
                    })->orderByDesc('tanggal_berita_acara')->orderByDesc('id');
                }, fn (Builder $query) => $query->latest())
                ->get()
            : collect();
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
            'slots' => $this->slots($config, $documents),
            'canUpload' => $this->canUpload($request, $config),
            'months' => self::MONTHS,
            'schemaReady' => $schemaReady,
            'occupiedPeriodsByYear' => $occupiedPeriodsByYear,
            'assetHolders' => $isAssetHolderArchive && $schemaReady
                ? AssetHolder::query()->orderBy('nama_pegawai')->get()
                : collect(),
        ]);
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

        $this->authorizeReport($request, $config);

        abort_unless($this->canUpload($request, $config), Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin upload dokumen laporan.');
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
        $config = self::REPORTS['berita-acara-bmd'];
        $this->authorizeReport($request, $config);
        abort_unless($this->canUpload($request, $config), Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin melengkapi berita acara.');
        abort_unless($this->isBeritaAcaraDocument($document), Response::HTTP_NOT_FOUND);
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
        $config = $this->reportConfig($document->jenis_laporan);
        $this->authorizeReport($request, $config);

        abort_unless($this->canUpload($request, $config), Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin menghapus dokumen laporan.');
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
        $config = $this->reportConfig($document->jenis_laporan);
        $this->authorizeReport($request, $config);
        $this->ensureFileExists($document);

        return response(Storage::disk('local')->get($document->path_file), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $document->nama_file_asli).'"',
        ]);
    }

    public function download(Request $request, ReportDocument $document): StreamedResponse
    {
        $config = $this->reportConfig($document->jenis_laporan);
        $this->authorizeReport($request, $config);
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

    private function authorizeReport(Request $request, array $config): void
    {
        $role = $this->roleValue($request->user()?->role);
        $allowed = $config['inventory']
            ? [UserRole::SuperAdmin->value, UserRole::TimAsetPersediaan->value, 'tim_aset_persediaan', 'aset-persediaan']
            : [UserRole::SuperAdmin->value, UserRole::Admin->value, UserRole::TimAset->value, UserRole::KasubbagUmumPerencanaan->value];

        abort_unless(in_array($role, $allowed, true), Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke dokumen laporan ini.');
    }

    private function canUpload(Request $request, array $config): bool
    {
        $role = $this->roleValue($request->user()?->role);

        if ($role === UserRole::SuperAdmin->value) {
            return true;
        }

        if ($config['inventory']) {
            return in_array($role, [UserRole::TimAsetPersediaan->value, 'tim_aset_persediaan', 'aset-persediaan'], true);
        }

        return in_array($role, [UserRole::Admin->value, UserRole::TimAset->value], true);
    }

    private function roleValue(mixed $role): ?string
    {
        return $role instanceof UserRole ? $role->value : $role;
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

        return [(string) ($config['period'] ?? '')];
    }

    private function normalizedPeriod(array $config, string $period): string
    {
        if (($config['type'] ?? null) === 'monthly') {
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

    private function defaultTitle(array $config, string $period, int $year): string
    {
        if (($config['type'] ?? null) === 'monthly') {
            $month = (int) Str::after($period, 'bulan_');

            return ($config['title'] ?? 'Laporan').' '.(self::MONTHS[$month] ?? $period).' '.$year;
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
