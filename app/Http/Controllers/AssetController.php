<?php

namespace App\Http\Controllers;

use App\Enums\LoanStatus;
use App\Http\Requests\AssetRequest;
use App\Http\Requests\UpdateAssetConditionRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetHolder;
use App\Models\KodeBarang;
use App\Models\Ruangan;
use App\Models\StockOpnameItem;
use App\Services\AssetConditionStockService;
use App\Services\KodeInternalService;
use App\Services\RiwayatPersetujuanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        return $this->renderList($request, 'aset.index');
    }

    public function verification(Request $request): View
    {
        $request->merge([
            'status_verifikasi' => $request->query('status_verifikasi', 'menunggu_verifikasi'),
        ]);

        return $this->renderList($request, 'aset.verifikasi', true);
    }

    public function show(Asset $asset): View
    {
        $this->ensureAssetIsActive($asset);

        return view('aset.show', [
            'asset' => $this->loadStockCounts($asset->load([
                'category',
                'assetHolder',
                'kodeBarang',
                'conditionStocks',
                'photos',
                'conditionMutations.user',
                'conditionMutations.documents',
                'maintenances.verifier',
                'maintenances.completedBy',
            ])),
        ]);
    }

    public function store(AssetRequest $request, KodeInternalService $kodeInternalService, AssetConditionStockService $conditionStockService): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['foto'], $validated['photos'], $validated['hapus_foto']);
        $unitCount = (int) $validated['jumlah'];

        if ($unitCount > 500) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah aset yang dibuat sekaligus maksimal 500 unit.',
            ]);
        }

        $validated['jumlah'] = 1;

        $storedPhotoPaths = [];
        $photoPayloads = $this->storeAssetPhotos($request, $storedPhotoPaths);

        try {
            DB::transaction(function () use ($request, $validated, $photoPayloads, $unitCount, $kodeInternalService, $conditionStockService): void {
                for ($unit = 1; $unit <= $unitCount; $unit++) {
                    $asset = Asset::query()->create([
                        ...$validated,
                        'path_foto' => $photoPayloads[0]['path'] ?? null,
                        'status_verifikasi' => 'menunggu_verifikasi',
                        'tanggal_verifikasi' => null,
                        'diverifikasi_oleh' => null,
                        'catatan_verifikasi' => null,
                    ]);

                    foreach ($photoPayloads as $index => $payload) {
                        $asset->photos()->create([
                            ...$payload,
                            'is_primary' => $index === 0,
                            'urutan' => $index + 1,
                        ]);
                    }

                    $conditionStockService->resetToMainCondition($asset);
                    app(RiwayatPersetujuanService::class)->record('asset_verification', $asset, 'pengajuan', 'dibuat', null, $request->user());
                    $kodeInternalService->generate($asset);
                }
            });
        } catch (Throwable $exception) {
            if ($storedPhotoPaths !== []) {
                Storage::disk('public')->delete($storedPhotoPaths);
            }

            throw $exception;
        }

        return back()->with('success', $unitCount.' unit aset berhasil ditambahkan, kode internal dibuat otomatis, dan menunggu verifikasi Tim Aset.');
    }

    public function update(AssetRequest $request, Asset $asset, AssetConditionStockService $conditionStockService): RedirectResponse
    {
        $this->ensureAssetIsActive($asset);

        if ((int) $asset->jumlah !== 1) {
            throw ValidationException::withMessages([
                'asset' => 'Aset legacy dengan jumlah lebih dari 1 harus diperbaiki melalui proses perbaikan data khusus sebelum dapat diedit.',
            ]);
        }

        $validated = $request->validated();
        unset($validated['foto'], $validated['photos'], $validated['hapus_foto']);

        $oldTotal = (int) $asset->jumlah;
        $oldCondition = $asset->kondisi?->value;
        $storedPhotoPaths = [];
        $photoPayloads = $this->storeAssetPhotos($request, $storedPhotoPaths);
        $deletedPaths = [];

        try {
            DB::transaction(function () use ($request, $asset, $validated, $photoPayloads, &$deletedPaths): void {
                $asset->update($validated);

                $deleteIds = array_map('intval', $request->input('hapus_foto', []));
                if ($deleteIds !== []) {
                    $photosToDelete = $asset->photos()->whereIn('id', $deleteIds)->get();
                    $deletedPaths = $photosToDelete->pluck('path')->filter()->values()->all();
                    $asset->photos()->whereIn('id', $photosToDelete->pluck('id'))->delete();
                }

                $existingCount = $asset->photos()->count();
                foreach ($photoPayloads as $index => $payload) {
                    $asset->photos()->create([
                        ...$payload,
                        'is_primary' => false,
                        'urutan' => $existingCount + $index + 1,
                    ]);
                }

                $this->syncPrimaryPhoto($asset);
            });

            $asset->refresh();

            if ($oldCondition !== $asset->kondisi?->value) {
                $conditionStockService->resetToMainCondition($asset);
            } else {
                $conditionStockService->adjustTotal($asset, $oldTotal, (int) $asset->jumlah);
            }
        } catch (Throwable $exception) {
            if ($storedPhotoPaths !== []) {
                Storage::disk('public')->delete($storedPhotoPaths);
            }

            throw $exception;
        }

        if ($deletedPaths !== []) {
            Storage::disk('public')->delete($deletedPaths);
        }

        return back()->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $this->ensureAssetIsActive($asset);
        $asset->update(['tanggal_penghapusan' => now()]);

        return back()->with('success', 'Aset berhasil dinonaktifkan dari daftar aset aktif.');
    }

    public function updateCondition(UpdateAssetConditionRequest $request, Asset $asset): RedirectResponse
    {
        $this->ensureAssetIsActive($asset);
        $this->ensureAssetIsApproved($asset);
        $data = $request->validated();
        $documentBatch = (string) Str::uuid();
        $documents = [];
        $storedPaths = [];

        foreach ($request->file('dokumentasi_foto', []) as $photo) {
            if (! $photo) {
                continue;
            }

            $path = $photo->store("asset-condition-mutations/{$documentBatch}/photos", 'public');
            $storedPaths[] = $path;
            $documents[] = [
                'jenis' => 'foto',
                'path_file' => $path,
                'nama_file_asli' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getMimeType(),
                'ukuran_file' => $photo->getSize(),
            ];
        }

        if ($request->hasFile('dokumentasi_video')) {
            $video = $request->file('dokumentasi_video');
            $path = $video->store("asset-condition-mutations/{$documentBatch}/videos", 'public');
            $storedPaths[] = $path;
            $documents[] = [
                'jenis' => 'video',
                'path_file' => $path,
                'nama_file_asli' => $video->getClientOriginalName(),
                'mime_type' => $video->getMimeType(),
                'ukuran_file' => $video->getSize(),
            ];
        }

        try {
            app(AssetConditionStockService::class)->transferSingleAssetCondition(
                $asset,
                $data['ke_kondisi'],
                $data['catatan'] ?? null,
                $request->user()?->id,
                $data['google_drive_url'] ?? null,
                $documents,
            );
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }

        return back()->with('success', 'Stok kondisi aset berhasil diperbarui.');
    }

    public function verify(Request $request, Asset $asset): RedirectResponse
    {
        $this->ensureAssetIsActive($asset);

        $data = $request->validate([
            'decision' => ['required', 'in:disetujui,ditolak'],
            'catatan_verifikasi' => ['nullable', 'string', 'max:1000'],
        ]);

        if (($asset->status_verifikasi ?? 'disetujui') !== 'menunggu_verifikasi') {
            throw ValidationException::withMessages([
                'asset' => 'Aset ini sudah diproses oleh Tim Aset.',
            ]);
        }

        $asset->update([
            'status_verifikasi' => $data['decision'],
            'tanggal_verifikasi' => now(),
            'diverifikasi_oleh' => $request->user()->id,
            'catatan_verifikasi' => $data['catatan_verifikasi'] ?? null,
        ]);

        app(RiwayatPersetujuanService::class)->record(
            'asset_verification',
            $asset,
            'verifikasi_tim_aset',
            $data['decision'] === 'disetujui' ? 'disetujui' : 'ditolak',
            $data['catatan_verifikasi'] ?? null,
            $request->user(),
        );

        return back()->with('success', $data['decision'] === 'disetujui'
            ? 'Aset disetujui Tim Aset.'
            : 'Aset ditolak Tim Aset.');
    }

    /**
     * @param  list<string>  $storedPaths
     * @return list<array{path: string, nama_file_asli: ?string, mime_type: ?string, ukuran_file: ?int}>
     */
    private function storeAssetPhotos(AssetRequest $request, array &$storedPaths): array
    {
        $files = array_filter($request->file('photos', []));

        if ($files === [] && $request->hasFile('foto')) {
            $files = [$request->file('foto')];
        }

        $payloads = [];

        foreach ($files as $file) {
            $path = $file->store('assets/photos', 'public');
            $storedPaths[] = $path;
            $payloads[] = [
                'path' => $path,
                'nama_file_asli' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'ukuran_file' => $file->getSize(),
            ];
        }

        return $payloads;
    }

    private function syncPrimaryPhoto(Asset $asset): void
    {
        $primary = $asset->photos()->where('is_primary', true)->orderBy('urutan')->first();

        if (! $primary) {
            $primary = $asset->photos()->orderBy('urutan')->orderBy('id')->first();
        }

        if ($primary) {
            $asset->photos()->whereKeyNot($primary->id)->update(['is_primary' => false]);
            $primary->update(['is_primary' => true]);
            $asset->update(['path_foto' => $primary->path]);

            return;
        }

        $asset->update(['path_foto' => null]);
    }

    public function import(Request $request, KodeInternalService $kodeInternalService, AssetConditionStockService $conditionStockService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:10240'],
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.extensions' => 'File harus berformat XLSX, XLS, atau CSV.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        try {
            $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'File tidak dapat dibaca. Pastikan format Excel/CSV valid.']);
        }

        if (count($rows) < 2) {
            throw ValidationException::withMessages(['file' => 'File minimal berisi header dan satu baris data.']);
        }

        $headers = array_map(fn ($value) => $this->normalizeImportHeader((string) $value), array_shift($rows));
        $missingHeaders = array_values(array_diff(['kategori', 'nama_aset', 'jumlah', 'kondisi'], $headers));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages(['file' => 'Header wajib belum lengkap: '.implode(', ', $missingHeaders).'.']);
        }

        $categories = collect();
        AssetCategory::query()->get()->each(function (AssetCategory $category) use ($categories): void {
            foreach ([$category->nama_kategori, $category->kode_kategori] as $value) {
                $key = $this->normalizeLookup($value);
                if ($key !== '') {
                    $categories[$key] = $category->id;
                }
            }
        });

        $kodeBarangLookup = collect();
        KodeBarang::query()->get(['id', 'kode_barang', 'nama_barang'])->each(function (KodeBarang $kodeBarang) use ($kodeBarangLookup): void {
            foreach ([$kodeBarang->id, $kodeBarang->kode_barang, $kodeBarang->nama_barang] as $value) {
                $key = $this->normalizeLookup((string) $value);
                if ($key !== '') {
                    $kodeBarangLookup[$key] = $kodeBarang->id;
                }
            }
        });

        $payloads = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $data = [];

            foreach ($headers as $column => $header) {
                if ($header !== '') {
                    $data[$header] = is_string($row[$column] ?? null) ? trim($row[$column]) : $row[$column] ?? null;
                }
            }

            if (collect($data)->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }

            $categoryValue = trim((string) ($data['kategori'] ?? ''));
            $kodeBarangValue = trim((string) ($data['kode_barang_id'] ?? ''));
            $payload = [
                'kategori_aset_id' => $this->resolveImportCategoryId($categoryValue, $categories),
                'kode_barang_id' => $this->resolveImportKodeBarangId($kodeBarangValue, $kodeBarangLookup),
                'kode_internal' => strtoupper(trim((string) ($data['kode_internal'] ?? ''))) ?: null,
                'nama_aset' => trim((string) ($data['nama_aset'] ?? '')),
                'merek_tipe' => $data['merek_tipe'] ?? null,
                'spesifikasi' => $data['spesifikasi'] ?? null,
                'jumlah' => $data['jumlah'] ?? null,
                'tahun_perolehan' => $data['tahun_perolehan'] ?? null,
                'kondisi' => $this->normalizeCondition((string) ($data['kondisi'] ?? '')),
                'pemasok' => $data['pemasok'] ?? null,
                'lokasi_penyimpanan' => $data['lokasi'] ?? null,
            ];

            $validator = Validator::make($payload, [
                'kategori_aset_id' => ['required', 'integer', 'exists:kategori_aset,id'],
                'kode_barang_id' => ['nullable', 'integer', 'exists:kode_barang,id'],
                'kode_internal' => ['nullable', 'string', 'max:30', 'regex:/^[A-Z]{2,5}\/[0-9]{3,}$/', 'unique:aset,kode_internal'],
                'nama_aset' => ['required', 'string', 'max:150'],
                'merek_tipe' => ['nullable', 'string', 'max:150'],
                'spesifikasi' => ['required_if:kondisi,rusak_ringan,rusak_sedang,rusak_berat,hilang', 'nullable', 'string', 'max:5000'],
                'jumlah' => ['required', 'integer', 'min:1', 'max:1000000'],
                'tahun_perolehan' => ['nullable', 'integer', 'digits:4', 'between:1900,'.(date('Y') + 1)],
                'kondisi' => ['required', 'in:baik,rusak_ringan,rusak_sedang,rusak_berat,hilang'],
                'pemasok' => ['nullable', 'string', 'max:150'],
                'lokasi_penyimpanan' => ['nullable', 'string', 'max:150'],
            ], [
                'kategori_aset_id.required' => 'Kolom kategori wajib diisi.',
                'kategori_aset_id.exists' => 'Kategori "'.$categoryValue.'" tidak ditemukan.',
                'kode_barang_id.exists' => 'Kode barang "'.$kodeBarangValue.'" tidak ditemukan di master Kode Barang.',
                'kode_internal.regex' => 'Format kode_internal harus seperti PRN/001.',
                'kode_internal.unique' => 'Kode internal sudah digunakan.',
                'nama_aset.required' => 'Kolom nama_aset wajib diisi.',
                'spesifikasi.required_if' => 'Kolom spesifikasi wajib diisi sebagai deskripsi kerusakan/alasan kehilangan.',
                'jumlah.required' => 'Kolom jumlah wajib diisi.',
                'kondisi.required' => 'Kolom kondisi wajib diisi.',
                'kondisi.in' => 'Kolom kondisi harus berisi Baik, Rusak Ringan, Rusak Sedang, Rusak Berat, atau Hilang.',
            ]);

            if ($payload['kode_internal'] && collect($payloads)->contains('kode_internal', $payload['kode_internal'])) {
                $validator->after(fn ($validator) => $validator->errors()->add('kode_internal', 'Kode internal duplikat di file Excel.'));
            }

            if ((int) ($payload['jumlah'] ?? 0) > 1 && $payload['kode_internal']) {
                $validator->after(fn ($validator) => $validator->errors()->add('kode_internal', 'kode_internal_opsional hanya boleh diisi untuk baris dengan jumlah 1. Kosongkan agar sistem membuat kode internal unik per unit.'));
            }

            if ($validator->fails()) {
                $errors[] = 'Baris '.$line.': '.collect($validator->errors()->all())->implode(' ');
                continue;
            }

            $payloads[] = $payload;
        }

        if ($payloads === []) {
            throw ValidationException::withMessages(['file' => $errors[0] ?? 'Tidak ada data valid yang dapat diimport.']);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => array_slice($errors, 0, 10)]);
        }

        $internalCodeReview = [];
        $totalUnits = collect($payloads)->sum(fn (array $payload): int => (int) $payload['jumlah']);

        DB::transaction(function () use ($payloads, $kodeInternalService, $conditionStockService, &$internalCodeReview): void {
            foreach ($payloads as $payload) {
                $unitCount = (int) $payload['jumlah'];
                $payload['jumlah'] = 1;

                for ($unit = 1; $unit <= $unitCount; $unit++) {
                    $asset = Asset::query()->create([
                        ...$payload,
                        'kode_internal' => $unit === 1 ? $payload['kode_internal'] : null,
                        'status_verifikasi' => 'menunggu_verifikasi',
                        'tanggal_verifikasi' => null,
                        'diverifikasi_oleh' => null,
                        'catatan_verifikasi' => null,
                    ]);

                    $conditionStockService->resetToMainCondition($asset);
                    app(RiwayatPersetujuanService::class)->record('asset_verification', $asset, 'pengajuan', 'dibuat', 'Dibuat dari import Excel aset.', request()->user());

                    if ($asset->kode_internal) {
                        $kodeInternalService->rememberExistingCode($asset);
                        continue;
                    }

                    $kodeInternalService->generate($asset);
                }
            }
        });

        $redirect = back()->with('success', $totalUnits.' unit aset dari '.count($payloads).' baris Excel berhasil diimport dan menunggu verifikasi Tim Aset.');

        return $internalCodeReview === []
            ? $redirect
            : $redirect->with('warning', implode(' ', array_slice($internalCodeReview, 0, 5)).' Silakan selesaikan melalui menu Kodefikasi Internal.');
    }

    public function importTemplate()
    {
        $headers = ['kategori', 'kode_barang_opsional', 'kode_internal_opsional', 'nama_aset', 'merek_tipe', 'spesifikasi', 'jumlah', 'tahun_perolehan', 'kondisi', 'pemasok', 'lokasi'];
        $categoryExample = AssetCategory::query()->orderBy('nama_kategori')->value('nama_kategori') ?? 'Isi sesuai kategori di sistem';
        $kodeBarangExample = KodeBarang::query()->orderBy('kode_barang')->value('kode_barang') ?? '1.3.2.10.01.02.002';
        $example = [$categoryExample, $kodeBarangExample, 'LPT/001', 'Laptop Operasional', 'Lenovo ThinkPad', 'RAM 8GB, SSD 512GB', 10, (int) date('Y'), 'Baik', 'Nama pemasok', 'Ruang Inventaris'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Aset');
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($example, null, 'A2');
        $sheet->freezePane('A2');

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFEAF2FF');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, 'template-import-aset.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function renderList(Request $request, string $view, bool $verificationMode = false): View
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $assets = $this->withStockCounts(Asset::query())
            ->with('category')
            ->with('assetHolder')
            ->with('kodeBarang')
            ->with('conditionStocks')
            ->with('photos')
            ->whereNull('tanggal_penghapusan')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(fn (Builder $query) => $query
                    ->where('kode_internal', 'like', "%{$search}%")
                    ->orWhere('nama_aset', 'like', "%{$search}%")
                    ->orWhere('merek_tipe', 'like', "%{$search}%")
                    ->orWhereHas('kodeBarang', fn (Builder $query) => $query
                        ->where('kode_barang', 'like', "%{$search}%")
                        ->orWhere('nama_barang', 'like', "%{$search}%")));
            })
            ->when($request->integer('kategori_id'), fn (Builder $query, int $categoryId) => $query->where('kategori_aset_id', $categoryId))
            ->when($request->filled('kondisi'), fn (Builder $query) => $query->whereHas('conditionStocks', fn (Builder $query) => $query
                ->where('kondisi', $request->string('kondisi'))
                ->where('jumlah', '>', 0)))
            ->when($request->filled('status_verifikasi'), fn (Builder $query) => $query->where('status_verifikasi', $request->string('status_verifikasi')))
            ->orderBy('nama_aset')
            ->paginate($perPage)
            ->withQueryString();

        $activeAssetHolders = AssetHolder::query()
            ->where('is_active', true)
            ->orderBy('nama_pegawai')
            ->get(['id', 'nama_pegawai', 'nip', 'is_active']);

        return view($view, [
            'assets' => $assets,
            'stockOpnameItems' => $verificationMode ? collect() : $this->masterStockOpnameItems($request),
            'categories' => AssetCategory::query()->orderBy('nama_kategori')->get(),
            'kodeBarangOptions' => KodeBarang::query()->orderBy('kode_barang')->limit(500)->get(['id', 'kode_barang', 'nama_barang']),
            'ruanganOptions' => Ruangan::query()->orderBy('nama_ruangan')->get(['id', 'kode_ruangan', 'nama_ruangan']),
            'assetHolderOptions' => $activeAssetHolders,
            'verificationMode' => $verificationMode,
            'conditions' => $this->conditionOptions(),
            'verificationOptions' => $this->verificationOptions(),
        ]);
    }

    private function masterStockOpnameItems(Request $request)
    {
        return StockOpnameItem::query()
            ->with(['category', 'kodeBarang', 'mutations'])
            ->where('is_active', true)
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(fn (Builder $query) => $query
                    ->where('kode_internal', 'like', "%{$search}%")
                    ->orWhere('kode_persediaan', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%")
                    ->orWhere('merek_tipe', 'like', "%{$search}%")
                    ->orWhere('lokasi_penyimpanan', 'like', "%{$search}%")
                    ->orWhereHas('kodeBarang', fn (Builder $query) => $query
                        ->where('kode_barang', 'like', "%{$search}%")
                        ->orWhere('nama_barang', 'like', "%{$search}%")));
            })
            ->orderBy('nama_barang')
            ->limit(100)
            ->get();
    }

    private function withStockCounts(Builder $query): Builder
    {
        return $query
            ->withCount([
                'loanDetails as borrowed_jumlah' => fn (Builder $query) => $query
                    ->whereHas('loanRequest', fn (Builder $loanQuery) => $loanQuery->where('status', LoanStatus::Borrowed->value)),
                'loanDetails as reserved_jumlah' => fn (Builder $query) => $query
                    ->whereHas('loanRequest', fn (Builder $loanQuery) => $loanQuery
                        ->whereIn('status', [LoanStatus::Approved->value, LoanStatus::LegacyApproved->value])
                        ->whereDate('tanggal_pinjam', '<=', today())
                        ->whereDate('tanggal_kembali', '>=', today())),
            ]);
    }

    private function loadStockCounts(Asset $asset): Asset
    {
        $asset->loadCount([
            'loanDetails as borrowed_jumlah' => fn (Builder $query) => $query
                ->whereHas('loanRequest', fn (Builder $loanQuery) => $loanQuery->where('status', LoanStatus::Borrowed->value)),
            'loanDetails as reserved_jumlah' => fn (Builder $query) => $query
                ->whereHas('loanRequest', fn (Builder $loanQuery) => $loanQuery
                    ->whereIn('status', [LoanStatus::Approved->value, LoanStatus::LegacyApproved->value])
                    ->whereDate('tanggal_pinjam', '<=', today())
                    ->whereDate('tanggal_kembali', '>=', today())),
        ]);

        return $asset;
    }

    private function ensureAssetIsActive(Asset $asset): void
    {
        if ($asset->tanggal_penghapusan) {
            throw ValidationException::withMessages(['asset' => 'Aset yang telah dihapus tidak dapat diubah.']);
        }
    }

    private function ensureAssetIsApproved(Asset $asset): void
    {
        if (($asset->status_verifikasi ?? 'disetujui') !== 'disetujui') {
            throw ValidationException::withMessages(['asset' => 'Aset belum disetujui oleh Tim Aset.']);
        }
    }

    private function conditionOptions(): array
    {
        return [
            'baik' => 'Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_sedang' => 'Rusak Sedang',
            'rusak_berat' => 'Rusak Berat',
            'hilang' => 'Hilang',
        ];
    }

    private function verificationOptions(): array
    {
        return [
            'menunggu_verifikasi' => 'Menunggu',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];
    }

    private function normalizeImportHeader(string $value): string
    {
        $header = str($value)->lower()->replace([' ', '-', '/', '.'], '_')->replaceMatches('/_+/', '_')->trim('_')->toString();

        return match ($header) {
            'kode_barang', 'kode_barang_opsional', 'kode_barang_id', 'kodefikasi_barang', 'kode_master_barang' => 'kode_barang_id',
            'kode_internal', 'kode_internal_opsional', 'kode_internal_aset', 'internal_code' => 'kode_internal',
            'nama', 'barang', 'nama_barang', 'nama_asset' => 'nama_aset',
            'merek', 'merk', 'tipe', 'type', 'merek_type', 'merk_tipe', 'merk_type' => 'merek_tipe',
            'spek', 'spesification', 'specification', 'spesifikasi' => 'spesifikasi',
            'qty', 'stok', 'stock', 'kuantitas' => 'jumlah',
            'tahun', 'tahun_beli', 'tahun_pengadaan' => 'tahun_perolehan',
            'pemasok', 'vendor' => 'pemasok',
            'tempat', 'ruangan', 'lokasi_penyimpanan' => 'lokasi',
            default => $header,
        };
    }

    private function normalizeLookup(?string $value): string
    {
        return str((string) $value)->lower()->squish()->toString();
    }

    private function normalizeCondition(string $value): string
    {
        return match ($this->normalizeLookup($value)) {
            'baik', 'good' => 'baik',
            'rusak ringan', 'rusak_ringan', 'light damage', 'light_damage' => 'rusak_ringan',
            'rusak sedang', 'rusak_sedang', 'medium damage', 'medium_damage' => 'rusak_sedang',
            'rusak berat', 'rusak_berat', 'heavy damage', 'heavy_damage' => 'rusak_berat',
            'hilang', 'lost' => 'hilang',
            default => $value,
        };
    }

    private function resolveImportCategoryId(string $value, $categories): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && AssetCategory::query()->whereKey((int) $value)->exists()) {
            return (int) $value;
        }

        $key = $this->normalizeLookup($value);
        if (isset($categories[$key])) {
            return (int) $categories[$key];
        }

        $baseCode = Str::of($value)->ascii()->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->substr(0, 24)->toString();
        $baseCode = $baseCode !== '' ? $baseCode : 'KATEGORI';
        $code = $baseCode;
        $counter = 1;

        while (AssetCategory::query()->where('kode_kategori', $code)->exists()) {
            $suffix = '_'.$counter++;
            $code = substr($baseCode, 0, 30 - strlen($suffix)).$suffix;
        }

        $category = AssetCategory::query()->create([
            'kode_kategori' => $code,
            'nama_kategori' => $value,
            'ikon' => null,
            'deskripsi' => 'Dibuat otomatis dari import Excel aset.',
        ]);

        $categories[$key] = $category->id;
        $categories[$this->normalizeLookup($category->kode_kategori)] = $category->id;

        return $category->id;
    }

    private function resolveImportKodeBarangId(string $value, $kodeBarangLookup): ?int
    {
        if ($value === '') {
            return null;
        }

        $key = $this->normalizeLookup($value);

        return isset($kodeBarangLookup[$key]) ? (int) $kodeBarangLookup[$key] : null;
    }
}