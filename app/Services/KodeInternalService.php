<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\KodeInternalPrefix;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KodeInternalService
{
    private const FORMAT = '/^[A-Z]{2,5}\/[0-9]{3,}$/';

    /** @var array<string, string> */
    private array $knownPrefixes = [
        'printer' => 'PRN',
        'laptop' => 'LPT',
        'monitor' => 'MNT',
        'scanner' => 'SCN',
        'pemindai' => 'SCN',
        'proyektor' => 'PRY',
        'projector' => 'PRY',
        'lemari besi' => 'LB',
        'kursi kerja' => 'KK',
        'kamera conference' => 'KC',
        'kamera konferensi' => 'KC',
        'air conditioner' => 'AC',
        'ac' => 'AC',
        'unit power supply' => 'UPS',
        'ups' => 'UPS',
    ];

    /** @return array{prefix: string, nomor: int, kode: string, collision: bool, collision_name: ?string} */
    public function preview(Asset $asset, ?string $requestedPrefix = null): array
    {
        $prefix = $this->normalizePrefix($requestedPrefix ?: $this->suggestPrefix($asset->nama_aset));
        $existing = KodeInternalPrefix::query()->where('prefix', $prefix)->first();
        $collision = $existing && ! $this->sameKind($existing->nama_barang, $asset->nama_aset);
        $nextNumber = $existing ? ((int) $existing->nomor_terakhir + 1) : 1;

        return [
            'prefix' => $prefix,
            'nomor' => $nextNumber,
            'kode' => $this->formatCode($prefix, $nextNumber),
            'collision' => $collision,
            'collision_name' => $collision ? $existing->nama_barang : null,
        ];
    }

    public function generate(Asset $asset, ?string $requestedPrefix = null): string
    {
        if ($asset->kode_internal) {
            throw ValidationException::withMessages([
                'kode_internal' => 'Aset ini sudah memiliki kode internal.',
            ]);
        }

        return DB::transaction(function () use ($asset, $requestedPrefix): string {
            $asset = Asset::query()->whereKey($asset->id)->lockForUpdate()->firstOrFail();

            if ($asset->kode_internal) {
                throw ValidationException::withMessages([
                    'kode_internal' => 'Aset ini sudah memiliki kode internal.',
                ]);
            }

            $prefix = $this->normalizePrefix($requestedPrefix ?: $this->suggestPrefix($asset->nama_aset));
            $prefixRow = KodeInternalPrefix::query()->where('prefix', $prefix)->lockForUpdate()->first();

            if ($prefixRow && ! $this->sameKind($prefixRow->nama_barang, $asset->nama_aset)) {
                throw ValidationException::withMessages([
                    'prefix' => 'Prefix '.$prefix.' sudah digunakan oleh '.$prefixRow->nama_barang.'. Tentukan prefix lain.',
                ]);
            }

            if (! $prefixRow) {
                $prefixRow = KodeInternalPrefix::query()->create([
                    'nama_barang' => $asset->nama_aset,
                    'prefix' => $prefix,
                    'nomor_terakhir' => $this->highestExistingNumber($prefix),
                ]);
                $prefixRow = KodeInternalPrefix::query()->whereKey($prefixRow->id)->lockForUpdate()->firstOrFail();
            }

            $number = max((int) $prefixRow->nomor_terakhir, $this->highestExistingNumber($prefix)) + 1;
            $code = $this->formatCode($prefix, $number);

            while (Asset::query()->where('kode_internal', $code)->exists()) {
                $number++;
                $code = $this->formatCode($prefix, $number);
            }

            $asset->update(['kode_internal' => $code]);
            $prefixRow->update(['nomor_terakhir' => $number]);

            return $code;
        });
    }

    public function update(Asset $asset, string $code): string
    {
        $code = $this->normalizeCode($code);
        $prefix = Str::before($code, '/');
        $number = (int) Str::after($code, '/');

        return DB::transaction(function () use ($asset, $code, $prefix, $number): string {
            $exists = Asset::query()
                ->where('kode_internal', $code)
                ->whereKeyNot($asset->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'kode_internal' => 'Kode internal sudah digunakan oleh aset lain.',
                ]);
            }

            $asset->update(['kode_internal' => $code]);
            $prefixRow = KodeInternalPrefix::query()->where('prefix', $prefix)->lockForUpdate()->first();

            if ($prefixRow) {
                if (! $this->sameKind($prefixRow->nama_barang, $asset->nama_aset)) {
                    throw ValidationException::withMessages([
                        'kode_internal' => 'Prefix '.$prefix.' sudah digunakan oleh '.$prefixRow->nama_barang.'.',
                    ]);
                }

                if ((int) $prefixRow->nomor_terakhir < $number) {
                    $prefixRow->update(['nomor_terakhir' => $number]);
                }
            } else {
                KodeInternalPrefix::query()->create([
                    'nama_barang' => $asset->nama_aset,
                    'prefix' => $prefix,
                    'nomor_terakhir' => $number,
                ]);
            }

            return $code;
        });
    }

    public function reset(Asset $asset): void
    {
        $asset->update(['kode_internal' => null]);
    }

    public function rememberExistingCode(Asset $asset): void
    {
        if (! $asset->kode_internal) {
            return;
        }

        $code = $this->normalizeCode($asset->kode_internal);
        $prefix = Str::before($code, '/');
        $number = (int) Str::after($code, '/');

        DB::transaction(function () use ($asset, $prefix, $number): void {
            $prefixRow = KodeInternalPrefix::query()->where('prefix', $prefix)->lockForUpdate()->first();

            if ($prefixRow) {
                if ((int) $prefixRow->nomor_terakhir < $number) {
                    $prefixRow->update(['nomor_terakhir' => $number]);
                }

                return;
            }

            KodeInternalPrefix::query()->create([
                'nama_barang' => $asset->nama_aset,
                'prefix' => $prefix,
                'nomor_terakhir' => $number,
            ]);
        });
    }

    /** @return array{success: int, skipped: int, review: list<array{name: string, reason: string}>} */
    public function bulkGenerate(): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'review' => []];

        Asset::query()
            ->whereNull('kode_internal')
            ->whereNull('tanggal_penghapusan')
            ->orderBy('id')
            ->chunkById(50, function ($assets) use (&$result): void {
                foreach ($assets as $asset) {
                    try {
                        $this->generate($asset);
                        $result['success']++;
                    } catch (ValidationException $exception) {
                        $result['review'][] = [
                            'name' => $asset->nama_aset,
                            'reason' => collect($exception->errors())->flatten()->first() ?? 'Perlu ditinjau.',
                        ];
                    }
                }
            });

        return $result;
    }

    public function suggestPrefix(string $name): string
    {
        $normalized = $this->normalizeName($name);

        if (isset($this->knownPrefixes[$normalized])) {
            return $this->knownPrefixes[$normalized];
        }

        foreach ($this->knownPrefixes as $keyword => $prefix) {
            if (str_contains($normalized, $keyword)) {
                return $prefix;
            }
        }

        $words = collect(preg_split('/\s+/', strtoupper(Str::ascii($normalized))) ?: [])
            ->filter(fn ($word) => $word !== '')
            ->values();

        if ($words->count() >= 2) {
            return $words->map(fn ($word) => $word[0])->take(5)->implode('');
        }

        $word = $words->first() ?: 'AST';
        $withoutVowels = preg_replace('/[AIUEO]/', '', $word) ?: $word;
        $prefix = $word[0].substr($withoutVowels, 0, 2);

        return substr(str_pad($prefix, 3, substr($word, 1, 2)), 0, 5);
    }

    public function normalizePrefix(string $prefix): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z]/', '', Str::ascii($prefix)) ?? '');

        if (! preg_match('/^[A-Z]{2,5}$/', $prefix)) {
            throw ValidationException::withMessages([
                'prefix' => 'Prefix harus berisi 2 sampai 5 huruf A-Z.',
            ]);
        }

        return $prefix;
    }

    public function normalizeCode(string $code): string
    {
        $code = strtoupper(trim($code));

        if (! preg_match(self::FORMAT, $code)) {
            throw ValidationException::withMessages([
                'kode_internal' => 'Format kode internal harus seperti PRN/001.',
            ]);
        }

        return $code;
    }

    private function highestExistingNumber(string $prefix): int
    {
        return Asset::query()
            ->where('kode_internal', 'like', $prefix.'/%')
            ->pluck('kode_internal')
            ->map(fn ($code) => (int) Str::after($code, '/'))
            ->max() ?? 0;
    }

    private function formatCode(string $prefix, int $number): string
    {
        return $prefix.'/'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    private function sameKind(string $left, string $right): bool
    {
        $left = $this->normalizeName($left);
        $right = $this->normalizeName($right);

        if ($left === $right || str_contains($left, $right) || str_contains($right, $left)) {
            return true;
        }

        return $this->firstWord($left) === $this->firstWord($right);
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }

    private function firstWord(string $name): string
    {
        return Str::before($name, ' ');
    }
}
