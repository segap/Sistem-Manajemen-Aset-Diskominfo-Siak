<?php

namespace App\Models;

use App\Enums\StockOpnameVerificationStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpnameItem extends Model
{
    protected $fillable = [
        'stock_opname_category_id',
        'kode_persediaan_id',
        'kode_barang_id',
        'kode_internal',
        'pengusul_id',
        'diverifikasi_oleh',
        'kode_persediaan',
        'nama_barang',
        'merek_tipe',
        'satuan',
        'stok',
        'lokasi_penyimpanan',
        'keterangan',
        'status_verifikasi',
        'tanggal_verifikasi',
        'catatan_verifikasi',
        'is_active',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(StockOpnameCategory::class, 'stock_opname_category_id');
    }

    public function kodePersediaan(): BelongsTo
    {
        return $this->belongsTo(KodePersediaan::class, 'kode_persediaan_id');
    }

    public function kodeBarang(): BelongsTo
    {
        return $this->belongsTo(KodeBarang::class, 'kode_barang_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengusul_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(StockOpnameHistory::class);
    }

    public function mutations(): HasMany
    {
        return $this->hasMany(StockOpnameMutation::class);
    }

    public function requestDetails(): HasMany
    {
        return $this->hasMany(DetailPermintaanPersediaan::class, 'persediaan_id');
    }

    protected function stockStatus(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            $this->stok === 0 => 'habis',
            $this->stok < 5 => 'menipis',
            default => 'tersedia',
        });
    }

    protected function casts(): array
    {
        return [
            'stok' => 'integer',
            'is_active' => 'boolean',
            'tanggal_verifikasi' => 'datetime',
            'status_verifikasi' => StockOpnameVerificationStatus::class,
        ];
    }
}
