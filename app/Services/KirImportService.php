<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Kir;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KirImportService
{
    /**
     * @throws ValidationException
     */
    public function import(Kir $kir, UploadedFile $file): int
    {
        $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $headers = $this->headers($rows[1] ?? []);

        if (! isset($headers['nama_barang'], $headers['jumlah'])) {
            throw ValidationException::withMessages([
                'file' => 'Header wajib minimal memuat Nama Barang dan Jumlah.',
            ]);
        }

        $errors = [];
        $payload = [];
        $existingNibars = $kir->details()
            ->whereNotNull('nibar')
            ->where('nibar', '!=', '')
            ->pluck('nibar')
            ->map(fn ($value) => Str::lower(trim((string) $value)))
            ->all();
        $seenNibars = array_fill_keys($existingNibars, true);

        foreach (array_slice($rows, 1, null, true) as $rowNumber => $row) {
            $data = $this->rowData($headers, $row);

            if ($this->isBlankRow($data)) {
                continue;
            }

            $namaBarang = $this->nullableString($data['nama_barang'] ?? null) ?? '';
            $jumlah = $data['jumlah'] ?? null;
            $tahun = $this->nullableString($data['tahun_perolehan'] ?? null);
            $tahunPerolehan = $this->normalizeYear($tahun);
            $nibar = $this->nullableString($data['nibar'] ?? null);
            $nomorRegister = $this->nullableString($data['nomor_register'] ?? null);
            $kodeBarang = $this->nullableString($data['kode_barang'] ?? null);
            $asset = $this->resolveAsset($nomorRegister, $kodeBarang, $namaBarang);

            if ($namaBarang === '') {
                $errors[] = "Baris {$rowNumber}: Nama Barang wajib diisi.";
            }

            if (! $asset) {
                $errors[] = "Baris {$rowNumber}: Barang harus sesuai dengan aset terverifikasi di Master Data.";
            }

            if (! is_numeric($jumlah) || (int) $jumlah < 1) {
                $errors[] = "Baris {$rowNumber}: Jumlah wajib berupa angka minimal 1.";
            }

            if ($tahun !== null && $tahunPerolehan === null) {
                $errors[] = "Baris {$rowNumber}: Tahun Perolehan tidak valid.";
            }

            if ($nibar !== null) {
                $nibarKey = Str::lower($nibar);
                if (isset($seenNibars[$nibarKey])) {
                    $errors[] = "Baris {$rowNumber}: NIBAR {$nibar} sudah digunakan pada KIR ruangan ini.";
                }
                $seenNibars[$nibarKey] = true;
            }

            $payload[] = [
                'kir_id' => $kir->id,
                'aset_id' => $asset?->id,
                'nibar' => $nibar,
                'nomor_register' => $asset?->display_code,
                'kode_lokasi' => $this->nullableString($data['kode_lokasi'] ?? null),
                'kode_barang_id' => $asset?->kode_barang_id,
                'kode_barang' => $asset?->kodeBarang?->kode_barang,
                'nama_barang' => $asset?->nama_aset ?: $namaBarang,
                'spesifikasi_nama_barang' => $asset?->spesifikasi,
                'merek_tipe' => $asset?->merek_tipe,
                'tahun_perolehan' => $asset?->tahun_perolehan ?: $tahunPerolehan,
                'jumlah' => $asset?->jumlah ?: (int) $jumlah,
                'satuan' => $this->nullableString($data['satuan'] ?? null) ?? 'Unit',
                'keterangan' => $this->nullableString($data['keterangan'] ?? null) ?: ($asset?->kondisi?->label() ?? $asset?->kondisi),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        if ($payload === []) {
            throw ValidationException::withMessages(['file' => 'Tidak ada baris data yang dapat diimpor.']);
        }

        DB::transaction(fn () => DB::table('detail_kir')->insert($payload));

        return count($payload);
    }

    /** @param array<string, mixed> $row */
    private function headers(array $row): array
    {
        $headers = [];

        foreach ($row as $column => $label) {
            $key = $this->canonicalHeader((string) $label);
            if ($key !== null) {
                $headers[$key] = $column;
            }
        }

        return $headers;
    }

    /** @param array<string, string> $headers @param array<string, mixed> $row */
    private function rowData(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $key => $column) {
            $data[$key] = $row[$column] ?? null;
        }

        return $data;
    }

    private function canonicalHeader(string $label): ?string
    {
        $key = Str::of($label)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();

        return match ($key) {
            'nibar' => 'nibar',
            'nomor_register', 'no_register', 'register' => 'nomor_register',
            'kode_lokasi', 'kode_lok' => 'kode_lokasi',
            'kode_barang', 'kode_brg' => 'kode_barang',
            'nama_barang', 'barang', 'nama' => 'nama_barang',
            'spesifikasi_nama_barang', 'spesifikasi', 'spek' => 'spesifikasi_nama_barang',
            'merek_tipe', 'merek_type', 'merk_tipe', 'merek', 'merk', 'tipe' => 'merek_tipe',
            'tahun_perolehan', 'tahun' => 'tahun_perolehan',
            'jumlah', 'qty' => 'jumlah',
            'satuan' => 'satuan',
            'keterangan', 'ket' => 'keterangan',
            default => null,
        };
    }

    /** @param array<string, mixed> $data */
    private function isBlankRow(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return in_array($value, ['', '-', '—'], true) ? null : $value;
    }

    private function normalizeYear(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $year = (int) $value;

        if ($year < 1900 || $year > ((int) date('Y') + 1)) {
            return null;
        }

        return $year;
    }

    private function resolveAsset(?string $nomorRegister, ?string $kodeBarang, string $namaBarang): ?Asset
    {
        $query = Asset::query()
            ->with('kodeBarang')
            ->whereNull('tanggal_penghapusan')
            ->where('status_verifikasi', 'disetujui');

        if ($nomorRegister !== null) {
            $asset = (clone $query)
                ->where(fn ($assetQuery) => $assetQuery
                    ->where('kode_internal', $nomorRegister)
                    ->orWhere('kode_internal', $nomorRegister))
                ->first();

            if ($asset) {
                return $asset;
            }
        }

        if ($kodeBarang !== null && $namaBarang !== '') {
            $asset = (clone $query)
                ->where('nama_aset', $namaBarang)
                ->whereHas('kodeBarang', fn ($kodeQuery) => $kodeQuery->where('kode_barang', $kodeBarang))
                ->first();

            if ($asset) {
                return $asset;
            }
        }

        if ($namaBarang !== '') {
            $matches = (clone $query)->where('nama_aset', $namaBarang)->limit(2)->get();

            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        return null;
    }
}
