<?php

namespace App\Http\Controllers;

use App\Enums\WorkUnit;
use App\Http\Requests\AssetHolder\StoreAssetHolderRequest;
use App\Http\Requests\AssetHolder\UpdateAssetHolderRequest;
use App\Models\AssetHolder;
use App\Services\AssetHolderImportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AssetHolderController extends Controller
{
    /** @var list<string> */
    private array $managerRoles = ['admin', 'super_admin', 'tim_aset'];

    public function index(Request $request): View
    {
        $hasAssetHolderColumn = Schema::hasColumn('aset', 'pemegang_aset_id');
        $search = $request->string('search')->toString();
        $workUnit = $request->string('unit_kerja')->toString();
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $matchingWorkUnits = collect(WorkUnit::options())
            ->filter(fn (string $label, string $value): bool => str_contains(strtolower($label), strtolower($search)) || str_contains(strtolower($value), strtolower($search)))
            ->keys()
            ->all();

        $assetHoldersQuery = AssetHolder::query()
            ->when($hasAssetHolderColumn, fn (Builder $query) => $query
                ->withCount('assets')
                ->with([
                    'assets' => fn ($assetQuery) => $assetQuery
                        ->with('category')
                        ->orderBy('kode_internal')
                        ->orderBy('nama_aset'),
                ]))
            ->when($request->filled('search'), function (Builder $query) use ($matchingWorkUnits, $search): void {
                $query->where(function (Builder $query) use ($matchingWorkUnits, $search): void {
                    $query->where('nama_pegawai', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('unit_kerja', 'like', "%{$search}%")
                        ->when($matchingWorkUnits !== [], fn (Builder $unitQuery) => $unitQuery->orWhereIn('unit_kerja', $matchingWorkUnits));
                });
            })
            ->when($workUnit !== '', fn (Builder $query) => $query->where('unit_kerja', $workUnit))
            ->when($request->has('is_active') && $request->string('is_active')->toString() !== '', fn (Builder $query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('nama_pegawai');

        $assetHolders = $assetHoldersQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('pemegang-aset.index', [
            'assetHolders' => $assetHolders,
            'workUnits' => WorkUnit::options(),
            'canManageAssetHolders' => $this->canManage($request),
            'canViewSensitiveAssetHolderData' => $this->canViewSensitive($request),
            'hasAssetHolderColumn' => $hasAssetHolderColumn,
            'importPreview' => session('asset_holder_import_preview'),
            'importToken' => session('asset_holder_import_token'),
        ]);
    }

    public function store(StoreAssetHolderRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['foto', 'dokumen_ktp']);
        $data['is_active'] = $request->boolean('is_active', true);

        $storedPaths = [];

        try {
            if ($request->hasFile('foto')) {
                $data['foto_path'] = $request->file('foto')->store('asset-holders/photos', 'public');
                $storedPaths[] = ['disk' => 'public', 'path' => $data['foto_path']];
            }

            if ($request->hasFile('dokumen_ktp')) {
                $ktp = $request->file('dokumen_ktp');
                $data['ktp_path'] = $ktp->store('asset-holders/ktp', 'local');
                $data['ktp_nama_file'] = $ktp->getClientOriginalName();
                $data['ktp_mime'] = $ktp->getClientMimeType();
                $storedPaths[] = ['disk' => 'local', 'path' => $data['ktp_path']];
            }

            AssetHolder::query()->create($data);
        } catch (Throwable $exception) {
            foreach ($storedPaths as $file) {
                Storage::disk($file['disk'])->delete($file['path']);
            }

            throw $exception;
        }

        return back()->with('success', 'Pemegang aset berhasil ditambahkan.');
    }

    public function update(UpdateAssetHolderRequest $request, AssetHolder $assetHolder): RedirectResponse
    {
        $data = $request->safe()->except(['foto', 'dokumen_ktp']);
        $data['is_active'] = $request->boolean('is_active', false);
        $newPaths = [];
        $oldPaths = [];

        try {
            if ($request->hasFile('foto')) {
                $data['foto_path'] = $request->file('foto')->store('asset-holders/photos', 'public');
                $newPaths[] = ['disk' => 'public', 'path' => $data['foto_path']];
                $oldPaths[] = ['disk' => 'public', 'path' => $assetHolder->foto_path];
            }

            if ($request->hasFile('dokumen_ktp')) {
                $ktp = $request->file('dokumen_ktp');
                $data['ktp_path'] = $ktp->store('asset-holders/ktp', 'local');
                $data['ktp_nama_file'] = $ktp->getClientOriginalName();
                $data['ktp_mime'] = $ktp->getClientMimeType();
                $newPaths[] = ['disk' => 'local', 'path' => $data['ktp_path']];
                $oldPaths[] = ['disk' => 'local', 'path' => $assetHolder->ktp_path];
            }

            $assetHolder->update($data);
        } catch (Throwable $exception) {
            foreach ($newPaths as $file) {
                Storage::disk($file['disk'])->delete($file['path']);
            }

            throw $exception;
        }

        foreach ($oldPaths as $file) {
            if ($file['path']) {
                Storage::disk($file['disk'])->delete($file['path']);
            }
        }

        return back()->with('success', 'Pemegang aset berhasil diperbarui.');
    }

    public function destroy(Request $request, AssetHolder $assetHolder): RedirectResponse
    {
        abort_unless($this->canManage($request), 403, 'Anda tidak memiliki akses untuk menghapus pemegang aset.');

        if (Schema::hasColumn('aset', 'pemegang_aset_id')
            && DB::table('aset')->where('pemegang_aset_id', $assetHolder->id)->exists()) {
            throw ValidationException::withMessages([
                'pemegang_aset' => 'Pemegang aset tidak dapat dihapus karena masih memiliki aset yang tercatat. Pindahkan pemegang aset pada Master Data terlebih dahulu.',
            ]);
        }

        $photoPath = $assetHolder->foto_path;
        $ktpPath = $assetHolder->ktp_path;

        $assetHolder->delete();

        if ($photoPath && ! AssetHolder::query()->where('foto_path', $photoPath)->exists()) {
            Storage::disk('public')->delete($photoPath);
        }

        if ($ktpPath && ! AssetHolder::query()->where('ktp_path', $ktpPath)->exists()) {
            Storage::disk('local')->delete($ktpPath);
        }

        return back()->with('success', 'Pemegang aset berhasil dihapus permanen.');
    }

    public function downloadKtp(Request $request, AssetHolder $assetHolder): StreamedResponse
    {
        abort_unless($this->canViewSensitive($request), 403, 'Anda tidak memiliki akses ke dokumen KTP.');
        abort_unless($assetHolder->ktp_path && Storage::disk('local')->exists($assetHolder->ktp_path), JsonResponse::HTTP_NOT_FOUND, 'Dokumen KTP tidak ditemukan.');

        return Storage::disk('local')->download(
            $assetHolder->ktp_path,
            $assetHolder->ktp_nama_file ?: 'dokumen-ktp',
            ['Content-Type' => $assetHolder->ktp_mime ?: 'application/octet-stream'],
        );
    }

    public function print(Request $request, AssetHolder $assetHolder): View
    {
        $holder = $this->loadForDocument($assetHolder);

        return view('pemegang-aset.print', $this->documentViewData($holder));
    }

    public function pdf(Request $request, AssetHolder $assetHolder): Response
    {
        $holder = $this->loadForDocument($assetHolder);
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('pemegang-aset.pdf', $this->documentViewData($holder))->render());
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->documentFilename($holder).'"',
        ]);
    }

    public function importTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pemegang Aset');

        $headers = ['Nama Pegawai', 'NIP', 'Email', 'Unit Kerja', 'Jabatan', 'Nomor HP', 'NIK'];
        $notes = [
            'Wajib, maksimal 150 karakter',
            'Opsional, angka, maksimal 30 digit',
            'Opsional, format email',
            'Wajib, pilih salah satu unit kerja valid',
            'Wajib, maksimal 150 karakter',
            'Opsional, angka/+/-/spasi',
            'Opsional, tepat 16 digit',
        ];

        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue("{$column}1", $header);
            $sheet->setCellValue("{$column}2", $notes[$index]);
        }

        $sheet->setCellValue('A3', 'CONTOH - Jangan lupa hapus/ubah baris ini sebelum import');
        $sheet->setCellValueExplicit('B3', '198001012010011001', DataType::TYPE_STRING);
        $sheet->setCellValue('C3', 'contoh.pegawai@siakkab.go.id');
        $sheet->setCellValue('D3', WorkUnit::Aptika->value);
        $sheet->setCellValue('E3', 'Pranata Komputer');
        $sheet->setCellValueExplicit('F3', '081234567890', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('G3', '1401010101010001', DataType::TYPE_STRING);

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F3FF');
        $sheet->getStyle('A2:G2')->getFont()->setItalic(true)->getColor()->setRGB('66758A');
        $sheet->getStyle('A3:G3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF7ED');

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        foreach (['B', 'F', 'G'] as $column) {
            $sheet->getStyle("{$column}:{$column}")->getNumberFormat()->setFormatCode('@');
        }

        $unitValues = implode(',', array_keys(WorkUnit::options()));
        for ($row = 3; $row <= 502; $row++) {
            $validation = $sheet->getCell("D{$row}")->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowDropDown(true);
            $validation->setFormula1('"'.$unitValues.'"');
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(fn () => $writer->save('php://output'), 'template-pemegang-aset.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importPreview(Request $request, AssetHolderImportService $service): RedirectResponse
    {
        abort_unless($this->canManage($request), 403, 'Anda tidak memiliki akses import pemegang aset.');

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.mimes' => 'File harus berformat .xlsx.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $path = $request->file('file')->store('asset-holder-imports', 'local');
        $preview = $service->preview($request->file('file'));
        $token = (string) Str::uuid();

        if ($preview['total'] === 0 && $preview['invalid'] === 0) {
            Storage::disk('local')->delete($path);
            throw ValidationException::withMessages(['file' => 'Tidak ada baris data yang dapat diperiksa.']);
        }

        $imports = $request->session()->get('asset_holder_imports', []);
        $imports[$token] = $path;
        $request->session()->put('asset_holder_imports', $imports);

        return back()
            ->with('asset_holder_import_preview', $preview)
            ->with('asset_holder_import_token', $token)
            ->with('_modal', 'import');
    }

    public function importConfirm(Request $request, AssetHolderImportService $service): RedirectResponse
    {
        abort_unless($this->canManage($request), 403, 'Anda tidak memiliki akses import pemegang aset.');

        $request->validate(['token' => ['required', 'string']]);

        $imports = $request->session()->get('asset_holder_imports', []);
        $path = $imports[$request->input('token')] ?? null;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            throw ValidationException::withMessages(['file' => 'Sesi import sudah berakhir. Silakan periksa ulang file Excel.']);
        }

        try {
            $count = $service->import(Storage::disk('local')->path($path));
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'Import gagal diproses. Periksa kembali file Excel dan coba lagi.']);
        } finally {
            Storage::disk('local')->delete($path);
            unset($imports[$request->input('token')]);
            $request->session()->put('asset_holder_imports', $imports);
        }

        return back()->with('success', "{$count} data pemegang aset berhasil diimpor.");
    }

    private function canManage(Request $request): bool
    {
        return in_array($request->user()?->role, $this->managerRoles, true);
    }

    private function canViewSensitive(Request $request): bool
    {
        return $this->canManage($request);
    }

    private function loadForDocument(AssetHolder $assetHolder): AssetHolder
    {
        if (! Schema::hasColumn('aset', 'pemegang_aset_id')) {
            return $assetHolder->setRelation('assets', collect());
        }

        return $assetHolder->load([
            'assets' => fn ($query) => $query
                ->with('category')
                ->orderBy('kode_internal')
                ->orderBy('nama_aset'),
        ]);
    }

    /** @return array{holder: AssetHolder, holderAssets: \Illuminate\Support\Collection<int, \App\Models\Asset>, photoDataUri: ?string, photoDimensions: ?array{width: int, height: int}, generatedAt: \Illuminate\Support\Carbon} */
    private function documentViewData(AssetHolder $holder): array
    {
        $photoDataUri = null;
        $photoDimensions = null;

        if ($holder->foto_path && Storage::disk('public')->exists($holder->foto_path)) {
            $disk = Storage::disk('public');
            $dimensions = getimagesize($disk->path($holder->foto_path));

            if ($dimensions !== false && $dimensions[0] > 0 && $dimensions[1] > 0) {
                $maxSide = 76;
                $scale = min($maxSide / $dimensions[0], $maxSide / $dimensions[1], 1);
                $photoDimensions = [
                    'width' => max(1, (int) round($dimensions[0] * $scale)),
                    'height' => max(1, (int) round($dimensions[1] * $scale)),
                ];
                $mime = $disk->mimeType($holder->foto_path) ?: 'image/jpeg';
                $photoDataUri = 'data:'.$mime.';base64,'.base64_encode($disk->get($holder->foto_path));
            }
        }

        return [
            'holder' => $holder,
            'holderAssets' => $holder->assets ?? collect(),
            'photoDataUri' => $photoDataUri,
            'photoDimensions' => $photoDimensions,
            'generatedAt' => now(),
        ];
    }

    private function documentFilename(AssetHolder $holder): string
    {
        return 'Pemegang-Aset-'.(Str::slug($holder->nama_pegawai) ?: 'pegawai').'.pdf';
    }
}
