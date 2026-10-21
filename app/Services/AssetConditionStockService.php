<?php

namespace App\Services;

use App\Enums\AssetCondition;
use App\Models\Asset;
use App\Models\AssetConditionMutation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetConditionStockService
{
    /** @return array<string, int> */
    public function ensureStocks(Asset $asset): array
    {
        $asset->loadMissing('conditionStocks');
        $existing = $asset->conditionStocks->keyBy('kondisi');
        $total = 0;

        foreach (AssetCondition::cases() as $condition) {
            $stock = $existing->get($condition->value);

            if (! $stock) {
                $stock = $asset->conditionStocks()->create([
                    'kondisi' => $condition->value,
                    'jumlah' => $condition === $asset->kondisi ? (int) $asset->jumlah : 0,
                ]);
            }

            $total += (int) $stock->jumlah;
        }

        if ($total !== (int) $asset->jumlah) {
            $mainCondition = $asset->kondisi?->value ?? AssetCondition::Good->value;
            $mainStock = $asset->conditionStocks()->firstOrCreate(
                ['kondisi' => $mainCondition],
                ['jumlah' => 0],
            );
            $mainStock->increment('jumlah', (int) $asset->jumlah - $total);
        }

        return $asset->fresh('conditionStocks')->condition_stock_summary;
    }

    public function resetToMainCondition(Asset $asset): void
    {
        foreach (AssetCondition::cases() as $condition) {
            $asset->conditionStocks()->updateOrCreate(
                ['kondisi' => $condition->value],
                ['jumlah' => $condition === $asset->kondisi ? (int) $asset->jumlah : 0],
            );
        }
    }

    public function adjustTotal(Asset $asset, int $oldTotal, int $newTotal): void
    {
        $this->ensureStocks($asset);

        $difference = $newTotal - $oldTotal;
        if ($difference === 0) {
            return;
        }

        $goodStock = $asset->conditionStocks()->firstOrCreate(
            ['kondisi' => AssetCondition::Good->value],
            ['jumlah' => 0],
        );

        if ($difference > 0) {
            $goodStock->increment('jumlah', $difference);
            return;
        }

        if ($goodStock->jumlah < abs($difference)) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah aset tidak boleh lebih kecil dari stok kondisi selain Baik.',
            ]);
        }

        $goodStock->decrement('jumlah', abs($difference));
    }

    /**
     * @param  list<array{jenis: string, path_file: string, nama_file_asli?: ?string, mime_type?: ?string, ukuran_file?: ?int}>  $documents
     */
    public function transferSingleAssetCondition(Asset $asset, string $to, ?string $note, ?int $userId, ?string $googleDriveUrl = null, array $documents = []): AssetConditionMutation
    {
        $targetCondition = AssetCondition::tryFrom($to);

        if (! $targetCondition) {
            throw ValidationException::withMessages(['ke_kondisi' => 'Kondisi baru tidak valid.']);
        }

        return DB::transaction(function () use ($asset, $targetCondition, $note, $userId, $googleDriveUrl, $documents): AssetConditionMutation {
            $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);

            if ((int) $lockedAsset->jumlah !== 1) {
                throw ValidationException::withMessages([
                    'asset' => 'Perubahan kondisi satu unit hanya dapat dilakukan pada aset dengan jumlah 1.',
                ]);
            }

            $from = $lockedAsset->kondisi?->value ?? AssetCondition::Good->value;
            $to = $targetCondition->value;

            if ($from === $to) {
                throw ValidationException::withMessages(['ke_kondisi' => 'Kondisi baru harus berbeda dari kondisi saat ini.']);
            }

            $stocks = $lockedAsset->conditionStocks()
                ->whereIn('kondisi', collect(AssetCondition::cases())->pluck('value')->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('kondisi');

            $missingConditions = collect(AssetCondition::cases())
                ->map(fn (AssetCondition $condition) => $condition->value)
                ->reject(fn (string $condition) => $stocks->has($condition));

            if ($missingConditions->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'asset' => 'Stok kondisi aset belum lengkap. Sinkronkan data stok kondisi sebelum mengubah kondisi aset.',
                ]);
            }

            $totalStock = (int) $stocks->sum('jumlah');
            $fromStock = $stocks->get($from);
            $toStock = $stocks->get($to);

            if ($totalStock !== 1 || ! $fromStock || ! $toStock || (int) $fromStock->jumlah !== 1) {
                throw ValidationException::withMessages([
                    'asset' => 'Stok kondisi aset tidak konsisten untuk perubahan satu unit.',
                ]);
            }

            $fromStock->decrement('jumlah');
            $toStock->increment('jumlah');

            $mutation = AssetConditionMutation::query()->create([
                'asset_id' => $lockedAsset->id,
                'dari_kondisi' => $from,
                'ke_kondisi' => $to,
                'jumlah' => 1,
                'catatan' => $note,
                'google_drive_url' => $googleDriveUrl,
                'user_id' => $userId,
            ]);

            if ($documents !== []) {
                $mutation->documents()->createMany($documents);
            }

            $lockedAsset->update(['kondisi' => $to]);

            return $mutation->load('documents');
        });
    }

    /**
     * @param  list<array{jenis: string, path_file: string, nama_file_asli?: ?string, mime_type?: ?string, ukuran_file?: ?int}>  $documents
     */
    public function transfer(Asset $asset, string $from, string $to, int $quantity, ?string $note, ?int $userId, ?string $googleDriveUrl = null, array $documents = []): AssetConditionMutation
    {
        if ($from === $to) {
            throw ValidationException::withMessages(['ke_kondisi' => 'Kondisi tujuan harus berbeda dari kondisi asal.']);
        }

        return DB::transaction(function () use ($asset, $from, $to, $quantity, $note, $userId, $googleDriveUrl, $documents): AssetConditionMutation {
            $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $this->ensureStocks($lockedAsset);

            $fromStock = $lockedAsset->conditionStocks()->where('kondisi', $from)->lockForUpdate()->firstOrFail();
            $toStock = $lockedAsset->conditionStocks()->where('kondisi', $to)->lockForUpdate()->firstOrFail();

            if ($fromStock->jumlah < $quantity) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Jumlah yang dipindahkan melebihi stok kondisi asal.',
                ]);
            }

            $fromStock->decrement('jumlah', $quantity);
            $toStock->increment('jumlah', $quantity);

            $mutation = AssetConditionMutation::query()->create([
                'asset_id' => $lockedAsset->id,
                'dari_kondisi' => $from,
                'ke_kondisi' => $to,
                'jumlah' => $quantity,
                'catatan' => $note,
                'google_drive_url' => $googleDriveUrl,
                'user_id' => $userId,
            ]);

            if ($documents !== []) {
                $mutation->documents()->createMany($documents);
            }

            $summary = $lockedAsset->fresh('conditionStocks')->condition_stock_summary;
            $dominantCondition = collect($summary)
                ->filter(fn (int $amount) => $amount > 0)
                ->sortDesc()
                ->keys()
                ->first() ?? AssetCondition::Good->value;

            $lockedAsset->update(['kondisi' => $dominantCondition]);

            return $mutation->load('documents');
        });
    }
}
