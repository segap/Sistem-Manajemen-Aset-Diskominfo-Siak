<?php

namespace App\Models;

use App\Enums\AssetCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class Asset extends Model
{
    protected $table = 'aset';

    /** @var list<string> */
    protected $fillable = [
        'kategori_aset_id',
        'kode_barang_id',
        'kode_internal',
        'nama_aset',
        'merek_tipe',
        'spesifikasi',
        'jumlah',
        'tahun_perolehan',
        'kondisi',
        'status_verifikasi',
        'tanggal_verifikasi',
        'diverifikasi_oleh',
        'catatan_verifikasi',
        'path_foto',
        'google_drive_url',
        'pemasok',
        'pemegang_aset_id',
        'lokasi_penyimpanan',
        'tanggal_penghapusan',
    ];

    protected static function booted(): void
    {
        static::creating(function (Asset $asset): void {
            $asset->token_publik ??= (string) Str::uuid();
        });
    }

    /** @return BelongsTo<AssetCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'kategori_aset_id');
    }

    public function kodeBarang(): BelongsTo
    {
        return $this->belongsTo(KodeBarang::class, 'kode_barang_id');
    }

    public function assetHolder(): BelongsTo
    {
        return $this->belongsTo(AssetHolder::class, 'pemegang_aset_id');
    }

    /** @return HasMany<AssetConditionStock, $this> */
    public function conditionStocks(): HasMany
    {
        return $this->hasMany(AssetConditionStock::class, 'asset_id');
    }

    /** @return HasMany<AssetConditionMutation, $this> */
    public function conditionMutations(): HasMany
    {
        return $this->hasMany(AssetConditionMutation::class, 'asset_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(AssetPhoto::class, 'asset_id')->orderByDesc('is_primary')->orderBy('urutan')->orderBy('id');
    }

    /** Foto utama, atau foto pertama bila aset belum memiliki penanda foto utama. */
    public function thumbnailPhoto(): HasOne
    {
        return $this->hasOne(AssetPhoto::class, 'asset_id')->ofMany([
            'is_primary' => 'max',
            'urutan' => 'min',
            'id' => 'min',
        ]);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class, 'asset_id');
    }

    /** @return Attribute<string, never> */
    protected function displayCode(): Attribute
    {
        return Attribute::get(fn (): string => $this->kode_internal ?: '-');
    }

    /** @return Attribute<array<string, int>, never> */
    protected function conditionStockSummary(): Attribute
    {
        return Attribute::get(function (): array {
            $summary = collect(AssetCondition::cases())
                ->mapWithKeys(fn (AssetCondition $condition) => [$condition->value => 0])
                ->all();

            $stocks = $this->relationLoaded('conditionStocks')
                ? $this->conditionStocks
                : collect();

            if ($stocks->isEmpty()) {
                $summary[$this->kondisi?->value ?? AssetCondition::Good->value] = (int) $this->jumlah;

                return $summary;
            }

            foreach ($stocks as $stock) {
                if (array_key_exists($stock->kondisi, $summary)) {
                    $summary[$stock->kondisi] = (int) $stock->jumlah;
                }
            }

            return $summary;
        });
    }

    /** @return HasMany<LoanRequest, $this> */
    public function loanRequests(): HasMany
    {
        return $this->hasMany(LoanRequest::class, 'aset_id');
    }

    /** @return HasMany<DetailPeminjaman, $this> */
    public function loanDetails(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'aset_id');
    }

    /** @return HasMany<AssetDisposalRequest, $this> */
    public function disposalRequests(): HasMany
    {
        return $this->hasMany(AssetDisposalRequest::class, 'aset_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tahun_perolehan' => 'integer',
            'kondisi' => AssetCondition::class,
            'tanggal_verifikasi' => 'datetime',
            'tanggal_penghapusan' => 'datetime',
        ];
    }
}
