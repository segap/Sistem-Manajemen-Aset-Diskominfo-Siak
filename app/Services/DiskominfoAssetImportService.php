<?php

namespace App\Services;

use App\Enums\WorkUnit;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetHolder;
use App\Models\AssetImportBatch;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DiskominfoAssetImportService
{
    private const SHEETS = ['Meja', 'Kursi', 'Personal Computer', 'Laptop', 'Printer', 'Scanner', 'Kamera'];
    private const MAX_ROWS = 1500;
    private const MAX_UNITS = 1000;

    /** @return array{rows: list<array<string, mixed>>, summary: array<string, int>, warnings: list<string>, hash: string} */
    public function preview(string $path, array $conditionOverrides = [], array $holderOverrides = []): array
    {
        try {
            $book = IOFactory::load($path);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'Workbook tidak dapat dibaca.']);
        }

        if ($book->getSheetCount() > 20) {
            throw ValidationException::withMessages(['file' => 'Jumlah worksheet melebihi batas aman.']);
        }

        $holders = $this->holderLookup();
        $rows = [];
        $warnings = [];

        foreach ($book->getWorksheetIterator() as $sheet) {
            $name = trim($sheet->getTitle());
            if (mb_strtolower($name) === 'ringkasan') {
                continue;
            }
            if (! in_array($name, self::SHEETS, true)) {
                $warnings[] = "Worksheet {$name} tidak dikenal dan dilewati.";
                continue;
            }
            if ($sheet->getHighestRow() > 200) {
                throw ValidationException::withMessages(['file' => "Worksheet {$name} melebihi batas baris yang didukung."]);
            }
            $headers = $this->row($sheet, 2);
            if ($this->normalize($headers['A'] ?? '') !== 'nama lengkap' || $this->normalize($headers['B'] ?? '') !== 'unit kerja' || $this->normalize($headers['C'] ?? '') !== 'jumlah' || $this->normalize($headers['D'] ?? '') !== 'kondisi' || $this->normalize($headers['E'] ?? '') !== 'catatan kerusakan') {
                throw ValidationException::withMessages(['file' => "Header worksheet {$name} tidak sesuai format Diskominfo."]);
            }
            for ($row = 3; $row <= $sheet->getHighestRow(); $row++) {
                if (count($rows) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages(['file' => 'Jumlah baris workbook melebihi batas aman.']);
                }
                $data = $this->row($sheet, $row);
                if (collect($data)->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                    continue;
                }
                $rows[] = $this->parseRow($name, $row, $data, $holders, $conditionOverrides, $holderOverrides);
            }
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'Tidak ada data inventaris yang ditemukan pada worksheet yang didukung.']);
        }

        $summary = ['total' => count($rows), 'valid' => 0, 'corrected' => 0, 'warning' => 0, 'error' => 0, 'skipped' => 0, 'units' => 0];
        foreach ($rows as $row) {
            $summary[$row['status']]++;
            if (in_array($row['status'], ['valid', 'corrected', 'warning'], true)) {
                $summary['units'] += $row['jumlah'];
            }
        }
        if ($summary['units'] > self::MAX_UNITS) {
            throw ValidationException::withMessages(['file' => 'Total unit aset melebihi batas aman import.']);
        }

        return ['rows' => $rows, 'summary' => $summary, 'warnings' => $warnings, 'hash' => hash_file('sha256', $path)];
    }

    /** @param array<string, int|string> $categoryMappings @param array<string, int|string> $holderOverrides @param array<string, string> $conditionOverrides */
    public function confirm(string $path, string $filename, User $user, array $categoryMappings, array $holderOverrides, array $conditionOverrides, AssetConditionStockService $stockService, KodeInternalService $codeService): int
    {
        $preview = $this->preview($path, $conditionOverrides, $holderOverrides);
        $hash = $preview['hash'];
        if ($preview['summary']['error'] > 0) {
            throw ValidationException::withMessages(['file' => 'Masih terdapat baris error. Perbaiki data sebelum konfirmasi import.']);
        }

        $rows = $preview['rows'];
        $categories = AssetCategory::query()->whereIn('id', collect($categoryMappings)->filter()->map(fn ($id) => (int) $id))->pluck('id')->all();
        $holderIds = collect($rows)->pluck('pemegang_aset_id')
            ->merge(collect($holderOverrides)->filter())
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $holders = AssetHolder::query()->where('is_active', true)->whereIn('id', $holderIds)->pluck('id')->all();
        $total = 0;

        foreach ($rows as &$row) {
            if ($row['status'] === 'skipped') {
                continue;
            }
            $categoryId = $categoryMappings[$row['sheet']] ?? null;
            if (! $categoryId || ! in_array((int) $categoryId, $categories, true)) {
                throw ValidationException::withMessages(['kategori' => "Kategori untuk sheet {$row['sheet']} wajib dipilih dari master kategori."]);
            }
            $holderId = $holderOverrides[$row['key']] ?? $row['pemegang_aset_id'];
            if (! $holderId || ! in_array((int) $holderId, $holders, true)) {
                throw ValidationException::withMessages(['pegawai' => "Pegawai sheet {$row['sheet']} baris {$row['row']} belum dipetakan secara valid."]);
            }
            $row['kategori_aset_id'] = (int) $categoryId;
            $row['pemegang_aset_id'] = (int) $holderId;
            $total += $row['jumlah'];
        }
        unset($row);

        try {
            return DB::transaction(function () use ($hash, $filename, $user, $rows, $total, $stockService, $codeService): int {
                $batch = AssetImportBatch::query()->where('file_hash', $hash)->lockForUpdate()->first();
            if ($batch?->status === 'completed') {
                throw ValidationException::withMessages(['file' => 'Workbook identik sudah berhasil diimport sebelumnya.']);
            }
            if ($batch?->status === 'processing') {
                throw ValidationException::withMessages(['file' => 'Import workbook ini sedang diproses.']);
            }
            $batch ??= new AssetImportBatch(['file_hash' => $hash]);
            $batch->fill(['source_filename' => $filename, 'user_id' => $user->id, 'status' => 'processing', 'asset_unit_count' => $total, 'imported_at' => null])->save();

            foreach ($rows as $row) {
                if ($row['status'] === 'skipped') {
                    continue;
                }
                for ($unit = 1; $unit <= $row['jumlah']; $unit++) {
                    $asset = Asset::query()->create([
                        'kategori_aset_id' => $row['kategori_aset_id'],
                        'nama_aset' => $row['asset_name'],
                        'jumlah' => 1,
                        'kondisi' => $row['kondisi'],
                        'spesifikasi' => null,
                        'catatan_kondisi' => $row['catatan'] ?: null,
                        'pemegang_aset_id' => $row['pemegang_aset_id'],
                        'lokasi_penyimpanan' => null,
                        'asset_import_batch_id' => $batch->id,
                        'status_verifikasi' => 'disetujui',
                        'tanggal_verifikasi' => now(),
                        'diverifikasi_oleh' => null,
                        'catatan_verifikasi' => 'Disetujui otomatis melalui Import Inventaris Diskominfo.',
                    ]);
                    $stockService->resetToMainCondition($asset);
                    app(RiwayatPersetujuanService::class)->record('asset_verification', $asset, 'pengajuan', 'dibuat', "Dibuat melalui Import Inventaris Diskominfo: {$row['sheet']} baris {$row['row']}.", $user);
                    app(RiwayatPersetujuanService::class)->record('asset_verification', $asset, 'import_excel', 'disetujui_otomatis', 'Disetujui otomatis berdasarkan metode Import Excel Diskominfo.');
                    $codeService->generate($asset);
                }
            }
                $batch->update(['status' => 'completed', 'imported_at' => now()]);
                return $total;
            });
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'duplicate')) {
                throw ValidationException::withMessages(['file' => 'Workbook identik sedang atau sudah diproses. Periksa daftar aset sebelum mencoba ulang.']);
            }

            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    private function parseRow(string $sheet, int $row, array $data, array $holders, array $conditionOverrides, array $holderOverrides): array
    {
        $name = trim((string) ($data['A'] ?? ''));
        $unit = trim((string) ($data['B'] ?? ''));
        $quantity = $this->quantity($data['C'] ?? null);
        $key = sha1($sheet.'|'.$row);
        $condition = $this->condition($data['D'] ?? null);
        $conditionOverride = $conditionOverrides[$key] ?? null;
        if (is_string($conditionOverride) && array_key_exists($conditionOverride, $this->conditionOptions())) {
            $condition = $conditionOverride;
        }
        $note = trim((string) ($data['E'] ?? ''));
        if ($note === '-') {
            $note = '';
        }
        $messages = [];
        $status = 'valid';
        $hasQuantityError = false;
        $hasInconsistentNoAsset = false;
        if ($quantity === 0) $status = 'skipped';
        if ($quantity === null) { $status = 'error'; $hasQuantityError = true; $messages[] = 'Jumlah kosong atau tidak dikenali.'; }
        $requiresConditionCorrection = $condition === null && $status !== 'skipped';
        if ($requiresConditionCorrection) { $status = 'error'; $messages[] = 'Kondisi tidak dikenali; pilih koreksi pada preview.'; }
        if ($quantity && $this->normalize($data['D'] ?? '') === 'tidak ada') { $status = 'error'; $hasInconsistentNoAsset = true; $messages[] = 'Jumlah positif tidak boleh memiliki kondisi Tidak ada.'; }
        $holderId = null;
        $requiresHolderMapping = false;
        if ($status !== 'skipped') {
            $comparisonName = $this->comparisonName($name);
            $candidates = $holders['by_name'][$comparisonName] ?? [];
            $excelUnit = $this->normalizeWorkUnit($unit);
            $holderMatch = $this->resolveHolderMatch($candidates, $excelUnit);
            if ($holderMatch['id'] !== null) {
                $holderId = $holderMatch['id'];
                if ($holderMatch['warning'] !== null && $status === 'valid') {
                    $status = 'warning';
                    $messages[] = $holderMatch['warning'];
                }
            } elseif (isset($holderOverrides[$key]) && filter_var($holderOverrides[$key], FILTER_VALIDATE_INT)) {
                $holderId = (int) $holderOverrides[$key];
                if ($status === 'valid') {
                    $status = 'corrected';
                    $messages[] = 'Pegawai dipilih secara manual; pilihan akan divalidasi ulang saat konfirmasi.';
                }
            } else {
                $status = 'error';
                $requiresHolderMapping = true;
                $messages[] = $candidates === []
                    ? 'Pegawai tidak ditemukan.'
                    : ($holderMatch['ambiguous']
                        ? 'Pegawai ambigu; pilih secara manual.'
                        : 'Nama pegawai ditemukan, tetapi Unit Kerja tidak setara. Pilih pegawai secara manual.');
            }
        }
        if ($status === 'valid' && $condition === 'baik' && $note !== '' && $note !== '-') { $status = 'warning'; $messages[] = 'Kondisi Baik memiliki catatan; periksa sebelum konfirmasi.'; }
        $blockingError = $status === 'error' && ($hasQuantityError || $hasInconsistentNoAsset);
        return ['key' => $key, 'sheet' => $sheet, 'row' => $row, 'asset_name' => $sheet, 'nama_pegawai' => $name, 'unit_kerja' => $unit, 'jumlah' => $quantity ?? 0, 'kondisi' => $condition, 'catatan' => $note, 'pemegang_aset_id' => $holderId, 'status' => $status, 'messages' => $messages, 'requires_condition_correction' => $requiresConditionCorrection, 'requires_holder_mapping' => $requiresHolderMapping, 'manual_holder_id' => $status === 'corrected' ? $holderId : null, 'suggestions' => $requiresHolderMapping ? $this->suggestHolderCandidates($comparisonName, $holders['all']) : [], 'blocking_error' => $blockingError];
    }

    private function quantity(mixed $value): ?int
    {
        $rawValue = mb_strtolower(trim((string) $value));
        if ($this->normalize($rawValue) === 'tidak ada') return 0;
        if (preg_match('/^([1-9][0-9]*)(?:\s*\([^)]*\))?$/u', $rawValue, $match)) return (int) $match[1];
        return null;
    }

    private function condition(mixed $value): ?string
    {
        return match ($this->normalize($value)) {
            'baik' => 'baik', 'rusak ringan' => 'rusak_ringan', 'rusak sedang' => 'rusak_sedang', 'rusak parah', 'rusak parah besar' => 'rusak_berat', default => null,
        };
    }

    /** @return array<string, string> */
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

    private function normalize(mixed $value): string
    {
        return mb_strtolower(trim(preg_replace('/[^\pL\pN]+/u', ' ', (string) $value)));
    }

    private function normalizeName(mixed $value): string
    {
        return mb_strtolower(trim(preg_replace('/[^\pL\pN]+/u', ' ', (string) $value)));
    }

    private function comparisonName(mixed $value): string
    {
        $name = $this->normalizeName($value);
        $titles = '(?:a md|a ma|s k m|s km|s kom|s k o m|s si|s s i|s t|s e|s sos|s s o s|s ip|s i p|m kom|m k o m|m si|m s i|m t|m m|mba|dr|dra|drs|prof)';

        return trim((string) preg_replace('/(?:\s+'.$titles.')+$/u', '', $name));
    }

    private function normalizeWorkUnit(mixed $value): string
    {
        $normalized = $this->normalize($value);

        foreach (WorkUnit::cases() as $unit) {
            if ($normalized === $this->normalize($unit->value) || $normalized === $this->normalize($unit->label())) {
                return $unit->value;
            }
        }

        return $normalized;
    }

    /** @param list<array{id: int, unit: string}> $candidates @return array{id: ?int, warning: ?string, ambiguous: bool} */
    private function resolveHolderMatch(array $candidates, string $excelUnit): array
    {
        $exactMatches = array_values(array_filter($candidates, fn (array $holder): bool => $holder['unit'] === $excelUnit));
        if (count($exactMatches) === 1) {
            return ['id' => $exactMatches[0]['id'], 'warning' => null, 'ambiguous' => false];
        }
        if (count($exactMatches) > 1) {
            return ['id' => null, 'warning' => null, 'ambiguous' => true];
        }

        if ($excelUnit === WorkUnit::Secretariat->value && count($candidates) === 1 && $this->isSecretariatSubunit($candidates[0]['unit'])) {
            return [
                'id' => $candidates[0]['id'],
                'warning' => 'Nama pegawai cocok unik; subbagian Sekretariat menggunakan data Data Pegawai SIMIVA.',
                'ambiguous' => false,
            ];
        }

        return ['id' => null, 'warning' => null, 'ambiguous' => count($candidates) > 1];
    }

    private function isSecretariatSubunit(string $unit): bool
    {
        return in_array($unit, [
            WorkUnit::SecretariatPlanningGeneral->value,
            WorkUnit::SecretariatFinanceStaffing->value,
        ], true);
    }

    /** @param list<array{id: int, name: string, comparison_name: string, unit: string}> $holders @return list<array{id: int, name: string}> */
    private function suggestHolderCandidates(string $comparisonName, array $holders): array
    {
        if ($comparisonName === '') {
            return [];
        }

        $firstWord = explode(' ', $comparisonName)[0] ?? '';

        return collect($holders)
            ->filter(fn (array $holder): bool => ($firstWord !== '' && str_starts_with($holder['comparison_name'], $firstWord.' '))
                && levenshtein($comparisonName, $holder['comparison_name']) <= 2)
            ->sortBy(fn (array $holder): int => levenshtein($comparisonName, $holder['comparison_name']))
            ->take(3)
            ->map(fn (array $holder): array => ['id' => $holder['id'], 'name' => $holder['name']])
            ->values()
            ->all();
    }

    private function row($sheet, int $row): array
    {
        return array_values($sheet->rangeToArray("A{$row}:E{$row}", null, true, true, true))[0];
    }

    private function holderLookup(): array
    {
        $lookup = ['by_name' => [], 'all' => []];
        AssetHolder::query()->where('is_active', true)->get(['id', 'nama_pegawai', 'unit_kerja'])->each(function (AssetHolder $holder) use (&$lookup): void {
            $entry = [
                'id' => $holder->id,
                'name' => $holder->nama_pegawai,
                'comparison_name' => $this->comparisonName($holder->nama_pegawai),
                'unit' => $this->normalizeWorkUnit($holder->unit_kerja),
            ];
            $lookup['by_name'][$entry['comparison_name']][] = $entry;
            $lookup['all'][] = $entry;
        });
        return $lookup;
    }
}
