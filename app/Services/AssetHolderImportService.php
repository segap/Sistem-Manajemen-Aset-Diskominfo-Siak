<?php

namespace App\Services;

use App\Enums\WorkUnit;
use App\Models\AssetHolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class AssetHolderImportService
{
    /** @return array{total:int,valid:int,duplicate:int,invalid:int,errors:list<string>,rows:list<array<string, mixed>>} */
    public function preview(UploadedFile|string $file): array
    {
        try {
            $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
            $sheet = IOFactory::load($path)->getActiveSheet();
            $rows = $sheet->toArray(null, false, false, true);
        } catch (Throwable) {
            return [
                'total' => 0,
                'valid' => 0,
                'duplicate' => 0,
                'invalid' => 1,
                'errors' => ['File Excel tidak dapat dibaca. Pastikan file berformat .xlsx dan tidak rusak.'],
                'rows' => [],
            ];
        }

        $headers = $this->headers($rows[1] ?? []);
        $requiredHeaders = ['nama_pegawai', 'nip', 'email', 'unit_kerja', 'jabatan', 'nomor_hp', 'nik'];
        $missing = array_values(array_diff($requiredHeaders, array_keys($headers)));

        if ($missing !== []) {
            return [
                'total' => 0,
                'valid' => 0,
                'duplicate' => 0,
                'invalid' => 1,
                'errors' => ['Header Excel tidak sesuai. Kolom wajib: Nama Pegawai, NIP, Email, Unit Kerja, Jabatan, Nomor HP, NIK.'],
                'rows' => [],
            ];
        }

        $existingNips = AssetHolder::query()
            ->whereNotNull('nip')
            ->pluck('nip')
            ->mapWithKeys(fn ($value): array => [(string) $value => true])
            ->all();
        $existingEmails = AssetHolder::query()
            ->whereNotNull('email')
            ->pluck('email')
            ->mapWithKeys(fn ($value): array => [strtolower((string) $value) => true])
            ->all();

        $seenNips = [];
        $seenEmails = [];
        $validRows = [];
        $errors = [];
        $total = 0;
        $duplicate = 0;
        $invalid = 0;

        foreach (array_slice($rows, 1, null, true) as $rowNumber => $row) {
            $data = $this->normalizeRow($this->rowData($headers, $row));

            if ($this->isBlankRow($data) || $this->isExampleRow($data)) {
                continue;
            }

            $total++;
            $rowErrors = $this->validateRow($data, $rowNumber);
            $duplicateErrors = [];

            if (($data['nip'] ?? null) !== null) {
                if (isset($existingNips[$data['nip']])) {
                    $duplicateErrors[] = "Baris {$rowNumber}: NIP sudah terdaftar di database.";
                }
                if (isset($seenNips[$data['nip']])) {
                    $duplicateErrors[] = "Baris {$rowNumber}: NIP duplikat dengan baris {$seenNips[$data['nip']]}.";
                }
            }

            if (($data['email'] ?? null) !== null) {
                $emailKey = strtolower($data['email']);
                if (isset($existingEmails[$emailKey])) {
                    $duplicateErrors[] = "Baris {$rowNumber}: Email sudah terdaftar di database.";
                }
                if (isset($seenEmails[$emailKey])) {
                    $duplicateErrors[] = "Baris {$rowNumber}: Email duplikat dengan baris {$seenEmails[$emailKey]}.";
                }
            }

            if (($data['nip'] ?? null) !== null && ! isset($seenNips[$data['nip']])) {
                $seenNips[$data['nip']] = $rowNumber;
            }
            if (($data['email'] ?? null) !== null) {
                $emailKey = strtolower($data['email']);
                if (! isset($seenEmails[$emailKey])) {
                    $seenEmails[$emailKey] = $rowNumber;
                }
            }

            if ($rowErrors !== []) {
                $invalid++;
                array_push($errors, ...$rowErrors);
                continue;
            }

            if ($duplicateErrors !== []) {
                $duplicate++;
                array_push($errors, ...$duplicateErrors);
                continue;
            }

            $validRows[] = $data;
        }

        return [
            'total' => $total,
            'valid' => count($validRows),
            'duplicate' => $duplicate,
            'invalid' => $invalid,
            'errors' => array_slice($errors, 0, 50),
            'rows' => $validRows,
        ];
    }

    public function import(string $path): int
    {
        $preview = $this->preview($path);

        if ($preview['valid'] < 1) {
            return 0;
        }

        $now = now();
        $hasAddressColumn = Schema::hasColumn('pemegang_aset', 'alamat');
        $payload = collect($preview['rows'])
            ->map(function (array $row) use ($now, $hasAddressColumn): array {
                $data = [
                    'nama_pegawai' => $row['nama_pegawai'],
                    'nip' => $row['nip'],
                    'email' => $row['email'],
                    'unit_kerja' => $row['unit_kerja'],
                    'jabatan' => $row['jabatan'],
                    'nomor_hp' => $row['nomor_hp'],
                    'nik' => $row['nik'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasAddressColumn) {
                    $data['alamat'] = $row['alamat'];
                }

                return $data;
            })
            ->all();

        DB::transaction(fn () => AssetHolder::query()->insert($payload));

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
            'nama_pegawai', 'nama', 'pegawai' => 'nama_pegawai',
            'nip' => 'nip',
            'email', 'e_mail' => 'email',
            'unit_kerja', 'bidang' => 'unit_kerja',
            'jabatan' => 'jabatan',
            'nomor_hp', 'no_hp', 'hp', 'telepon', 'nomor_telepon' => 'nomor_hp',
            'nik', 'nomor_ktp', 'ktp' => 'nik',
            'alamat', 'alamat_pegawai', 'alamat_lengkap' => 'alamat',
            default => null,
        };
    }

    /** @param array<string, mixed> $data */
    private function normalizeRow(array $data): array
    {
        return [
            'nama_pegawai' => $this->nullableString($data['nama_pegawai'] ?? null),
            'nip' => $this->nullableString($data['nip'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null) ? strtolower($this->nullableString($data['email'])) : null,
            'unit_kerja' => $this->normalizeWorkUnit($this->nullableString($data['unit_kerja'] ?? null)),
            'jabatan' => $this->nullableString($data['jabatan'] ?? null),
            'nomor_hp' => $this->nullableString($data['nomor_hp'] ?? null),
            'nik' => $this->nullableString($data['nik'] ?? null),
            'alamat' => $this->nullableString($data['alamat'] ?? null),
        ];
    }

    /** @param array<string, mixed> $data */
    private function validateRow(array $data, int $rowNumber): array
    {
        $validator = Validator::make($data, [
            'nama_pegawai' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'string', 'regex:/^[0-9]+$/', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'unit_kerja' => ['required', Rule::enum(WorkUnit::class)],
            'jabatan' => ['required', 'string', 'max:150'],
            'nomor_hp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\\-\\s]+$/'],
            'nik' => ['nullable', 'digits:16'],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ]);

        return collect($validator->errors()->all())
            ->map(fn (string $message): string => "Baris {$rowNumber}: {$message}")
            ->all();
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

    /** @param array<string, mixed> $data */
    private function isExampleRow(array $data): bool
    {
        return str_starts_with(strtolower((string) ($data['nama_pegawai'] ?? '')), 'contoh');
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value !== '' && str_starts_with($value, '=')) {
            $value = "'".$value;
        }

        return in_array($value, ['', '-', '—'], true) ? null : $value;
    }

    private function normalizeWorkUnit(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $options = WorkUnit::options();
        if (array_key_exists($value, $options)) {
            return $value;
        }

        $normalized = Str::of($value)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();

        foreach ($options as $unitValue => $label) {
            $labelKey = Str::of($label)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
            if ($normalized === $unitValue || $normalized === $labelKey) {
                return $unitValue;
            }
        }

        return $value;
    }
}
