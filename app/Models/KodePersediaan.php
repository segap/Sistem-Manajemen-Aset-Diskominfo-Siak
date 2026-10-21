<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KodePersediaan extends Model
{
    protected $table = 'kode_persediaan';

    protected $fillable = [
        'parent_id',
        'kode_persediaan',
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
}
