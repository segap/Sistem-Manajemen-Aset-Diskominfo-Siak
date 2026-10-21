<?php

namespace App\Models;

use App\Enums\RuanganStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ruangan extends Model
{
    protected $table = 'ruangan';

    /** @var list<string> */
    protected $fillable = [
        'kode_ruangan',
        'kode_lokasi_id',
        'nama_ruangan',
        'penanggung_jawab_id',
        'keterangan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => RuanganStatus::class,
        ];
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab_id');
    }

    public function kodeLokasi(): BelongsTo
    {
        return $this->belongsTo(KodeLokasi::class, 'kode_lokasi_id');
    }

    public function kir(): HasOne
    {
        return $this->hasOne(Kir::class, 'ruangan_id');
    }

    public function kirDetails(): HasManyThrough
    {
        return $this->hasManyThrough(
            DetailKir::class,
            Kir::class,
            'ruangan_id',
            'kir_id',
            'id',
            'id'
        );
    }
}
