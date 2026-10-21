<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KodeLokasi extends Model
{
    protected $table = 'kode_lokasi';

    protected $fillable = [
        'parent_id',
        'kode_lokasi',
        'nama_lokasi',
        'keterangan',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function ruangan(): HasMany
    {
        return $this->hasMany(Ruangan::class, 'kode_lokasi_id');
    }

    public function kir(): HasMany
    {
        return $this->hasMany(Kir::class, 'kode_lokasi_id');
    }
}
