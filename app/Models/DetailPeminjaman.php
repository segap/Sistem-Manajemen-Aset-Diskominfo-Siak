<?php

namespace App\Models;

use App\Enums\AssetCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPeminjaman extends Model
{
    protected $table = 'detail_peminjaman';

    protected $fillable = [
        'peminjaman_id',
        'aset_id',
        'kondisi_kembali',
        'catatan_pengembalian',
    ];

    public function loanRequest(): BelongsTo
    {
        return $this->belongsTo(LoanRequest::class, 'peminjaman_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'aset_id');
    }

    protected function casts(): array
    {
        return [
            'kondisi_kembali' => AssetCondition::class,
        ];
    }
}
