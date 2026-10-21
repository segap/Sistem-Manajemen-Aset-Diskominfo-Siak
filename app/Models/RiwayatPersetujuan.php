<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPersetujuan extends Model
{
    protected $table = 'riwayat_persetujuan';

    protected $fillable = [
        'jenis_pengajuan',
        'pengajuan_id',
        'tahap',
        'status',
        'user_id',
        'nama_user_snapshot',
        'catatan',
        'diproses_pada',
    ];

    protected function casts(): array
    {
        return [
            'diproses_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
