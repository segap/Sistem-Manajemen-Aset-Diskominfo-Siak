<?php

namespace App\Services;

use App\Models\KodeBarang;
use App\Models\KodeLokasi;
use App\Models\KodePersediaan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class KodefikasiImportService
{
    /** @return array{berhasil:int,dilewati:int,gagal:int,errors:array<int,string>} */
    public function import(string $type, UploadedFile $file): array
    {
        try {
            $rows = IOFactory::load($file->getRealPath())->getActiveSheet()->toArray(null, true, true, true);
        } catch (Throwable) {
            return ['berhasil' => 0, 'dilewati' => 0, 'gagal' => 1, 'errors' => ['File tidak dapat dibaca. Pastikan format Excel/CSV valid.']];
        }

        if (count($rows) < 2) {
            return ['berhasil' => 0, 'dilewati' => 0, 'gagal' => 1, 'errors' => ['File minimal berisi header dan satu baris data.']];
        }

        $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), array_shift($rows));
        if ($type === 'kode_persediaan') {
            $headers = array_map(fn (string $header) => $header === 'kode_barang' ? 'kode_persediaan' : $header, $headers);
        }
        $required = $type === 'kode_lokasi' ? ['kode_lokasi', 'nama_lokasi'] : [$type, 'nama_barang'];
        $missing = array_values(array_diff($required, $headers));

        if ($missing !== []) {
            return ['berhasil' => 0, 'dilewati' => 0, 'gagal' => 1, 'errors' => ['Header wajib belum lengkap: '.implode(', ', $missing).'.']];
        }

        $modelClass = $this->modelClass($type);
        $codeColumn = $type;
        $existingCodes = $modelClass::query()->pluck($codeColumn)->mapWithKeys(fn ($code) => [(string) $code => true])->all();

        $payloads = [];
        $seen = [];
        $errors = [];
        $skipped = 0;

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

            $code = trim((string) ($data[$codeColumn] ?? ''));
            $nameColumn = $type === 'kode_lokasi' ? 'nama_lokasi' : 'nama_barang';
            $name = trim((string) ($data[$nameColumn] ?? ''));

            if ($code === '') {
                $errors[] = "Baris {$line}: Kode wajib diisi.";
                continue;
            }

            if ($name === '') {
                $errors[] = "Baris {$line}: Nama wajib diisi.";
                continue;
            }

            if (isset($existingCodes[$code]) || isset($seen[$code])) {
                $skipped++;
                continue;
            }

            $seen[$code] = true;
            $payloads[] = $this->payload($type, $data, $code, $name);
        }

        if ($payloads !== []) {
            DB::transaction(fn () => $modelClass::query()->insert($payloads));
        }

        return [
            'berhasil' => count($payloads),
            'dilewati' => $skipped,
            'gagal' => count($errors),
            'errors' => array_slice($errors, 0, 20),
        ];
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

    /** @return array<string, mixed> */
    private function payload(string $type, array $data, string $code, string $name): array
    {
        $now = now();

        if ($type === 'kode_lokasi') {
            return [
                'kode_lokasi' => $code,
                'nama_lokasi' => $name,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($type === 'kode_persediaan') {
            return [
                'kode_persediaan' => $code,
                'akun' => $data['akun'] ?? null,
                'kelompok' => $data['kelompok'] ?? null,
                'jenis' => $data['jenis'] ?? null,
                'objek' => $data['objek'] ?? null,
                'rincian_objek' => $data['rincian_objek'] ?? null,
                'sub_rincian_objek' => $data['sub_rincian_objek'] ?? null,
                'sub_sub_rincian_objek' => $data['sub_sub_rincian_objek'] ?? null,
                'nama_barang' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return [
            $type => $code,
            'akun' => $data['akun'] ?? null,
            'kelompok' => $data['kelompok'] ?? null,
            'jenis' => $data['jenis'] ?? null,
            'objek' => $data['objek'] ?? null,
            'rincian_objek' => $data['rincian_objek'] ?? null,
            'sub_rincian_objek' => $data['sub_rincian_objek'] ?? null,
            'sub_sub_rincian_objek' => $data['sub_sub_rincian_objek'] ?? null,
            'nama_barang' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function normalizeHeader(string $value): string
    {
        $header = str($value)->lower()->replace([' ', '-', '/', '.'], '_')->replaceMatches('/_+/', '_')->trim('_')->toString();

        return match ($header) {
            'kode', 'kode_barang' => 'kode_barang',
            'kode_persediaan', 'kode_persediaan_barang' => 'kode_persediaan',
            'kode_lokasi', 'kode_unit' => 'kode_lokasi',
            'nama', 'barang' => 'nama_barang',
            'nama_lokasi', 'unit_kerja', 'lokasi' => 'nama_lokasi',
            default => $header,
        };
    }
}
