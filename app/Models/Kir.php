<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kir extends Model
{
    protected $table = 'kir';

    /** @var list<string> */
    protected $fillable = [
        'ruangan_id',
        'kode_lokasi_id',
        'pengguna_barang',
        'kode_lokasi',
        'penanggung_jawab_id',
    ];

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_id');
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab_id');
    }

    public function kodeLokasi(): BelongsTo
    {
        return $this->belongsTo(KodeLokasi::class, 'kode_lokasi_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailKir::class, 'kir_id');
    }
}
