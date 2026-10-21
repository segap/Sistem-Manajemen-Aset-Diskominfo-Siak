<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KodeBarang extends Model
{
    protected $table = 'kode_barang';

    protected $fillable = [
        'parent_id',
        'kode_barang',
        'akun',
        'kelompok',
        'jenis',
        'objek',
        'rincian_objek',
        'sub_rincian_objek',
        'sub_sub_rincian_objek',
        'nama_barang',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function aset(): HasMany
    {
        return $this->hasMany(Asset::class, 'kode_barang_id');
    }

    public function detailKir(): HasMany
    {
        return $this->hasMany(DetailKir::class, 'kode_barang_id');
    }
}
