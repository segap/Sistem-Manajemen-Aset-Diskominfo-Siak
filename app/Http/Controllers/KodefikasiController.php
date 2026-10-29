<?php

namespace App\Http\Controllers;

use App\Models\KodeBarang;
use App\Models\KodeLokasi;
use App\Models\KodePersediaan;
use App\Models\StockOpnameItem;
use App\Services\KodefikasiImportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class KodefikasiController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $this->normalizeTab($request->query('tab', 'kode-barang'));
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        return view('kodefikasi.index', [
            'activeTab' => $tab,
            'perPage' => $perPage,
            'kodeBarang' => $this->paginateHierarchical(KodeBarang::query(), 'kode_barang', $request, 'barang_page', $perPage),
            'kodePersediaan' => $this->paginateHierarchical(KodePersediaan::query(), 'kode_persediaan', $request, 'persediaan_page', $perPage),
            'kodeLokasi' => $this->paginateLokasi($request, $perPage),
            'kodeBarangOptions' => KodeBarang::query()->orderBy('kode_barang')->limit(500)->get(['id', 'kode_barang', 'nama_barang']),
            'kodePersediaanOptions' => KodePersediaan::query()->orderBy('kode_persediaan')->limit(500)->get(['id', 'kode_persediaan', 'nama_barang']),
            'kodeLokasiOptions' => KodeLokasi::query()->orderBy('kode_lokasi')->limit(500)->get(['id', 'kode_lokasi', 'nama_lokasi']),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $type = $this->resolveType($type);
        $model = $this->modelClass($type);

        $model::query()->create($this->validatedPayload($request, $type));

        return back()->with('success', 'Data kodefikasi berhasil ditambahkan.');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $type = $this->resolveType($type);
        $model = $this->modelClass($type);
        $item = $model::query()->findOrFail($id);

        $item->update($this->validatedPayload($request, $type, $id));

        return back()->with('success', 'Data kodefikasi berhasil diperbarui.');
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        $type = $this->resolveType($type);
        $model = $this->modelClass($type);
        $item = $model::query()->findOrFail($id);

        if ($message = $this->deletionBlockedMessage($type, $item)) {
            return back()->with('error', $message);
        }

        $item->delete();

        return back()->with('success', 'Data kodefikasi berhasil dihapus.');
    }

    public function import(Request $request, string $type, KodefikasiImportService $service): RedirectResponse
    {
        $type = $this->resolveType($type);
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:10240'],
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.extensions' => 'File harus berformat XLSX, XLS, atau CSV.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $result = $service->import($type, $request->file('file'));
        $message = "Import selesai. Berhasil: {$result['berhasil']}, Dilewati: {$result['dilewati']}, Gagal: {$result['gagal']}.";

        return back()
            ->with($result['gagal'] > 0 ? 'error' : 'success', $message)
            ->with('import_errors', $result['errors']);
    }

    public function template(string $type)
    {
        $type = $this->resolveType($type);
        $headers = match ($type) {
            'kode_lokasi' => ['kode_lokasi', 'nama_lokasi', 'keterangan'],
            'kode_persediaan' => ['kode_barang', 'akun', 'kelompok', 'jenis', 'objek', 'rincian_objek', 'sub_rincian_objek', 'sub_sub_rincian_objek', 'nama_barang'],
            default => [$type, 'akun', 'kelompok', 'jenis', 'objek', 'rincian_objek', 'sub_rincian_objek', 'sub_sub_rincian_objek', 'nama_barang'],
        };
        $example = match ($type) {
            'kode_lokasi' => ['12.14.08.021001.00002.00001', 'Ruang Bidang TIK', 'Contoh kode lokasi'],
            'kode_persediaan' => ['1.1.7.01.01', '1', '1', '7', '01', '01', '', '', 'BAHAN'],
            default => ['1.3.2.10.02.03.003', 'Aset Tetap', 'Peralatan dan Mesin', 'Komputer', 'Printer', 'Printer Laser', 'Printer Operasional', 'Printer Jaringan', 'Printer'],
        };

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template '.str($type)->replace('_', ' ')->title());
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($example, null, 'A2');
        $sheet->freezePane('A2');

        foreach (range('A', chr(64 + count($headers))) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $lastColumn = chr(64 + count($headers));
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEAF2FF');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(fn () => $writer->save('php://output'), 'template-'.$type.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function paginateHierarchical(Builder $query, string $codeColumn, Request $request, string $pageName, int $perPage)
    {
        return $query->with('parent')
            ->when($request->filled('search'), fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where($codeColumn, 'like', '%'.$request->string('search')->toString().'%')
                ->orWhere('nama_barang', 'like', '%'.$request->string('search')->toString().'%')))
            ->when($request->filled('akun'), fn (Builder $query) => $query->where('akun', 'like', '%'.$request->string('akun')->toString().'%'))
            ->when($request->filled('kelompok'), fn (Builder $query) => $query->where('kelompok', 'like', '%'.$request->string('kelompok')->toString().'%'))
            ->when($request->filled('jenis'), fn (Builder $query) => $query->where('jenis', 'like', '%'.$request->string('jenis')->toString().'%'))
            ->orderBy($codeColumn)
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
    }

    private function paginateLokasi(Request $request, int $perPage)
    {
        return KodeLokasi::query()->with('parent')
            ->when($request->filled('search'), fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('kode_lokasi', 'like', '%'.$request->string('search')->toString().'%')
                ->orWhere('nama_lokasi', 'like', '%'.$request->string('search')->toString().'%')))
            ->orderBy('kode_lokasi')
            ->paginate($perPage, ['*'], 'lokasi_page')
            ->withQueryString();
    }

    /** @return array<string, mixed> */
    private function validatedPayload(Request $request, string $type, ?int $ignoreId = null): array
    {
        $codeColumn = $type;
        $rules = match ($type) {
            'kode_lokasi' => [
                'parent_id' => ['nullable', 'integer', 'exists:kode_lokasi,id'],
                'kode_lokasi' => ['required', 'string', 'max:100', Rule::unique('kode_lokasi', 'kode_lokasi')->ignore($ignoreId)],
                'nama_lokasi' => ['required', 'string', 'max:200'],
                'keterangan' => ['nullable', 'string', 'max:5000'],
            ],
            'kode_persediaan' => [
                'kode_persediaan' => ['required', 'string', 'max:100', Rule::unique('kode_persediaan', 'kode_persediaan')->ignore($ignoreId)],
                'akun' => ['nullable', 'string', 'max:150'],
                'kelompok' => ['nullable', 'string', 'max:150'],
                'jenis' => ['nullable', 'string', 'max:150'],
                'objek' => ['nullable', 'string', 'max:150'],
                'rincian_objek' => ['nullable', 'string', 'max:150'],
                'sub_rincian_objek' => ['nullable', 'string', 'max:150'],
                'sub_sub_rincian_objek' => ['nullable', 'string', 'max:150'],
                'nama_barang' => ['required', 'string', 'max:200'],
            ],
            default => [
                'parent_id' => ['nullable', 'integer', 'exists:'.$type.',id'],
                $codeColumn => ['required', 'string', 'max:100', Rule::unique($type, $codeColumn)->ignore($ignoreId)],
                'akun' => ['nullable', 'string', 'max:150'],
                'kelompok' => ['nullable', 'string', 'max:150'],
                'jenis' => ['nullable', 'string', 'max:150'],
                'objek' => ['nullable', 'string', 'max:150'],
                'rincian_objek' => ['nullable', 'string', 'max:150'],
                'sub_rincian_objek' => ['nullable', 'string', 'max:150'],
                'sub_sub_rincian_objek' => ['nullable', 'string', 'max:150'],
                'nama_barang' => ['required', 'string', 'max:200'],
            ],
        };

        return $request->validate($rules, [], [
            $codeColumn => 'kode',
            'nama_barang' => 'nama barang',
            'nama_lokasi' => 'nama lokasi',
        ]);
    }

    private function normalizeTab(string $tab): string
    {
        return in_array($tab, ['kode-barang', 'kode-persediaan', 'kode-lokasi'], true) ? $tab : 'kode-barang';
    }

    private function resolveType(string $type): string
    {
        return match ($type) {
            'kode-persediaan', 'kode_persediaan' => 'kode_persediaan',
            'kode-lokasi', 'kode_lokasi' => 'kode_lokasi',
            'kode-barang', 'kode_barang' => 'kode_barang',
            default => abort(404),
        };
    }

    private function deletionBlockedMessage(string $type, Model $item): ?string
    {
        return match ($type) {
            'kode_barang' => match (true) {
                $item->children()->exists() => 'Kode Barang tidak dapat dihapus karena masih memiliki kode turunan.',
                $item->aset()->exists() => 'Kode Barang tidak dapat dihapus karena masih digunakan oleh data aset.',
                $item->detailKir()->exists() => 'Kode Barang tidak dapat dihapus karena masih digunakan oleh Detail KIR.',
                StockOpnameItem::query()->where('kode_barang_id', $item->id)->exists() => 'Kode Barang tidak dapat dihapus karena masih digunakan oleh Stock Opname.',
                default => null,
            },
            'kode_persediaan' => match (true) {
                $item->children()->exists() => 'Kode Persediaan tidak dapat dihapus karena masih memiliki kode turunan.',
                StockOpnameItem::query()->where('kode_persediaan_id', $item->id)->exists() => 'Kode Persediaan tidak dapat dihapus karena masih digunakan oleh Stock Opname.',
                default => null,
            },
            'kode_lokasi' => match (true) {
                $item->children()->exists() => 'Kode Lokasi tidak dapat dihapus karena masih memiliki kode turunan.',
                $item->ruangan()->exists() => 'Kode Lokasi tidak dapat dihapus karena masih digunakan oleh data ruangan.',
                $item->kir()->exists() => 'Kode Lokasi tidak dapat dihapus karena masih digunakan oleh KIR.',
                default => null,
            },
        };
    }

    /** @return class-string<Model> */
    private function modelClass(string $type): string
    {
        return match ($type) {
            'kode_persediaan' => KodePersediaan::class,
            'kode_lokasi' => KodeLokasi::class,
            default => KodeBarang::class,
        };
    }
}
