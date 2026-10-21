<?php

namespace App\Http\Controllers;

use App\Http\Requests\KirDetailRequest;
use App\Enums\AssetCondition;
use App\Models\Asset;
use App\Models\DetailKir;
use App\Models\Kir;
use App\Models\KodeBarang;
use App\Models\KodeLokasi;
use App\Models\Ruangan;
use App\Services\KirImportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KirController extends Controller
{
    private const PENGGUNA_BARANG = 'H. Rozi Chandra';

    /** @var list<string> */
    private array $satuanOptions = ['Unit', 'Buah', 'Set'];

    public function index(Request $request)
    {
        $this->syncAllKirDetails();

        $perPage = (int) $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $search = trim((string) $request->query('search', ''));

        $ruangan = Ruangan::query()
            ->with(['penanggungJawab', 'kir'])
            ->withCount('kirDetails')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('kode_ruangan', 'like', "%{$search}%")
                        ->orWhere('nama_ruangan', 'like', "%{$search}%")
                        ->orWhereHas('penanggungJawab', function ($user) use ($search): void {
                            $user->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('nama_ruangan')
            ->paginate($perPage)
            ->withQueryString();

        return view('kir.index', compact('ruangan'));
    }

    public function show(Request $request, Ruangan $ruangan)
    {
        $kir = $this->ensureKir($ruangan);
        $this->syncKirDetails($kir);

        $perPage = (int) $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $search = trim((string) $request->query('search', ''));

        $details = $kir->details()
            ->with('asset')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('nibar', 'like', "%{$search}%")
                        ->orWhere('nomor_register', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%")
                        ->orWhere('nama_barang', 'like', "%{$search}%")
                        ->orWhere('merek_tipe', 'like', "%{$search}%")
                        ->orWhereHas('asset', fn ($asset) => $asset
                            ->where('kode_internal', 'like', "%{$search}%"));
                });
            })
            ->orderBy('nama_barang')
            ->paginate($perPage)
            ->withQueryString();

        $assets = Asset::query()
            ->with(['category', 'kodeBarang'])
            ->whereNull('tanggal_penghapusan')
            ->where('status_verifikasi', 'disetujui')
            ->orderBy('nama_aset')
            ->get();
        $kodeBarangOptions = KodeBarang::query()->orderBy('kode_barang')->limit(500)->get(['id', 'kode_barang', 'nama_barang']);
        $kodeLokasiOptions = KodeLokasi::query()->orderBy('kode_lokasi')->limit(500)->get(['id', 'kode_lokasi', 'nama_lokasi']);
        $satuanOptions = $this->satuanOptions;

        return view('kir.show', compact('ruangan', 'kir', 'details', 'assets', 'kodeBarangOptions', 'kodeLokasiOptions', 'satuanOptions'));
    }

    public function store(KirDetailRequest $request, Ruangan $ruangan): RedirectResponse
    {
        $kir = $this->ensureKir($ruangan);
        $validated = $this->validatedPayload($request, $kir);
        $this->ensureUniqueNibar($kir, $validated['nibar'] ?? null);

        $kir->details()->create($validated);

        return redirect()->route('kir.show', $ruangan)->with('success', 'Barang KIR berhasil ditambahkan.');
    }

    public function update(KirDetailRequest $request, Ruangan $ruangan, DetailKir $detailKir): RedirectResponse
    {
        $kir = $this->ensureKir($ruangan);
        $this->abortIfWrongKir($kir, $detailKir);

        $validated = $this->validatedPayload($request, $kir);
        $this->ensureUniqueNibar($kir, $validated['nibar'] ?? null, $detailKir);

        $detailKir->update($validated);

        return redirect()->route('kir.show', $ruangan)->with('success', 'Barang KIR berhasil diperbarui.');
    }

    public function destroy(Ruangan $ruangan, DetailKir $detailKir): RedirectResponse
    {
        $kir = $this->ensureKir($ruangan);
        $this->abortIfWrongKir($kir, $detailKir);

        $detailKir->delete();

        return redirect()->route('kir.show', $ruangan)->with('success', 'Barang KIR berhasil dihapus.');
    }

    public function import(Request $request, Ruangan $ruangan, KirImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], attributes: [
            'file' => 'file Excel',
        ]);

        $kir = $this->ensureKir($ruangan);
        $count = $importService->import($kir, $request->file('file'));

        return redirect()->route('kir.show', $ruangan)->with('success', "{$count} barang KIR berhasil diimpor.");
    }

    public function template(Ruangan $ruangan): StreamedResponse
    {
        $kir = $this->ensureKir($ruangan);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template KIR');

        $headers = [
            'NIBAR',
            'Nomor Register',
            'Kode Lokasi',
            'Kode Barang',
            'Nama Barang',
            'Spesifikasi Nama Barang',
            'Merek/Tipe',
            'Tahun Perolehan',
            'Jumlah',
            'Satuan',
            'Keterangan',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index).'1', $header);
            $sheet->getColumnDimension(chr(65 + $index))->setAutoSize(true);
        }

        $sheet->fromArray([
            'NBR-001',
            'REG-001',
            '',
            'KODE-BRG-001',
            'Nama Barang Contoh',
            'Spesifikasi barang',
            'Merek/Tipe',
            date('Y'),
            1,
            'Unit',
            'Keterangan tambahan',
        ], null, 'A2');

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'template-kir-'.$ruangan->kode_ruangan.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function pdf(Ruangan $ruangan): Response
    {
        $data = $this->exportData($ruangan);
        $options = new Options;
        $options->set('defaultFont', 'Times New Roman');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reports.kir', $data)->render());
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->exportFilename($ruangan, 'pdf').'"',
        ]);
    }

    public function word(Ruangan $ruangan): Response
    {
        $html = view('reports.kir', $this->exportData($ruangan))->render();

        return response($html, Response::HTTP_OK, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->exportFilename($ruangan, 'doc').'"',
        ]);
    }

    private function ensureKir(Ruangan $ruangan): Kir
    {
        return DB::transaction(function () use ($ruangan): Kir {
            $kir = $ruangan->kir()->firstOrCreate([], [
                'pengguna_barang' => self::PENGGUNA_BARANG,
                'kode_lokasi_id' => $ruangan->kode_lokasi_id,
                'kode_lokasi' => $ruangan->kodeLokasi?->kode_lokasi,
                'penanggung_jawab_id' => $ruangan->penanggung_jawab_id,
            ]);

            $updates = [];
            if ($kir->pengguna_barang !== self::PENGGUNA_BARANG) {
                $updates['pengguna_barang'] = self::PENGGUNA_BARANG;
            }
            if ($kir->penanggung_jawab_id !== $ruangan->penanggung_jawab_id) {
                $updates['penanggung_jawab_id'] = $ruangan->penanggung_jawab_id;
            }
            if ($kir->kode_lokasi_id !== $ruangan->kode_lokasi_id) {
                $updates['kode_lokasi_id'] = $ruangan->kode_lokasi_id;
                $updates['kode_lokasi'] = $ruangan->kodeLokasi?->kode_lokasi;
            }
            if ($updates !== []) {
                $kir->update($updates);
            }

            return $kir->refresh();
        });
    }

    /** @return array<string, mixed> */
    private function validatedPayload(KirDetailRequest $request, Kir $kir): array
    {
        $validated = $request->validated();
        $validated['kode_lokasi'] = filled($validated['kode_lokasi'] ?? null) ? trim((string) $validated['kode_lokasi']) : null;
        $validated['kode_barang_id'] = filled($validated['kode_barang_id'] ?? null) ? (int) $validated['kode_barang_id'] : null;
        $validated['nibar'] = filled($validated['nibar'] ?? null) ? trim((string) $validated['nibar']) : null;
        $validated['aset_id'] = filled($validated['aset_id'] ?? null) ? (int) $validated['aset_id'] : null;

        $asset = Asset::query()
            ->with('kodeBarang')
            ->whereNull('tanggal_penghapusan')
            ->where('status_verifikasi', 'disetujui')
            ->findOrFail($validated['aset_id']);

        $validated['kode_barang_id'] = $asset->kode_barang_id;
        $validated['kode_barang'] = $asset->kodeBarang?->kode_barang;
        $validated['nama_barang'] = $asset->nama_aset;
        $validated['spesifikasi_nama_barang'] = $asset->spesifikasi;
        $validated['merek_tipe'] = $asset->merek_tipe;
        $validated['tahun_perolehan'] = $asset->tahun_perolehan;
        $validated['jumlah'] = $asset->jumlah;

        if (blank($validated['keterangan'] ?? null)) {
            $validated['keterangan'] = $this->conditionCode($asset->kondisi);
        }

        return $validated;
    }

    private function ensureUniqueNibar(Kir $kir, ?string $nibar, ?DetailKir $except = null): void
    {
        if (! filled($nibar)) {
            return;
        }

        $exists = $kir->details()
            ->where('nibar', trim($nibar))
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'nibar' => 'NIBAR sudah digunakan pada KIR ruangan ini.',
            ]);
        }
    }

    private function conditionCode(AssetCondition|string|null $condition): ?string
    {
        $value = $condition instanceof AssetCondition ? $condition->value : $condition;

        return match ($value) {
            AssetCondition::Good->value, 'Baik', 'B' => 'B',
            AssetCondition::LightDamage->value, 'Rusak Ringan', 'RR' => 'RR',
            AssetCondition::MediumDamage->value, 'Rusak Sedang', 'RS' => 'RS',
            AssetCondition::HeavyDamage->value, 'Rusak Berat', 'RB' => 'RB',
            default => null,
        };
    }

    private function abortIfWrongKir(Kir $kir, DetailKir $detailKir): void
    {
        abort_unless($detailKir->kir_id === $kir->id, 404);
    }

    /** @return array<string, mixed> */
    private function exportData(Ruangan $ruangan): array
    {
        $kir = $this->ensureKir($ruangan);
        $this->syncKirDetails($kir);

        return [
            'ruangan' => $ruangan->loadMissing('penanggungJawab'),
            'kir' => $kir,
            'details' => $kir->details()->orderBy('nama_barang')->get(),
            'generatedAt' => now(),
        ];
    }

    private function exportFilename(Ruangan $ruangan, string $extension): string
    {
        return 'kir-'.$ruangan->kode_ruangan.'-'.now()->format('Ymd-His').'.'.$extension;
    }

    private function syncAllKirDetails(): void
    {
        Ruangan::query()
            ->with(['kir', 'kodeLokasi'])
            ->orderBy('id')
            ->chunkById(50, function ($rooms): void {
                foreach ($rooms as $room) {
                    $this->syncKirDetails($this->ensureKir($room));
                }
            });
    }

    private function syncKirDetails(Kir $kir): void
    {
        $kir->loadMissing(['ruangan.kodeLokasi']);
        $room = $kir->ruangan;

        if (! $room) {
            return;
        }

        DB::transaction(function () use ($kir, $room): void {
            $assets = Asset::query()
                ->with(['kodeBarang', 'category'])
                ->whereNull('tanggal_penghapusan')
                ->where('status_verifikasi', 'disetujui')
                ->where('lokasi_penyimpanan', $room->nama_ruangan)
                ->orderBy('nama_aset')
                ->get();

            $assetIds = $assets->pluck('id')->all();

            $kir->details()
                ->whereNotNull('aset_id')
                ->when(
                    $assetIds !== [],
                    fn ($query) => $query->whereNotIn('aset_id', $assetIds),
                    fn ($query) => $query
                )
                ->delete();

            foreach ($assets as $asset) {
                $kir->details()->updateOrCreate(
                    ['aset_id' => $asset->id],
                    [
                        'kode_barang_id' => $asset->kode_barang_id,
                        'kode_lokasi' => $kir->kode_lokasi ?: $room->kodeLokasi?->kode_lokasi,
                        'kode_barang' => $asset->kodeBarang?->kode_barang,
                        'nama_barang' => $asset->nama_aset,
                        'spesifikasi_nama_barang' => $asset->spesifikasi,
                        'merek_tipe' => $asset->merek_tipe,
                        'tahun_perolehan' => $asset->tahun_perolehan,
                        'jumlah' => $asset->jumlah,
                        'satuan' => 'Unit',
                        'keterangan' => $asset->kondisi?->label() ?? $asset->kondisi,
                    ]
                );
            }
        });
    }
}
