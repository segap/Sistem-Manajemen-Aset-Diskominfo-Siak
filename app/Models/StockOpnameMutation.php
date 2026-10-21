<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameMutation extends Model
{
    protected $fillable = [
        'stock_opname_item_id',
        'user_id',
        'jenis',
        'referensi_type',
        'referensi_id',
        'jumlah',
        'stok_sebelum',
        'stok_sesudah',
        'catatan',
        'waktu_transaksi',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockOpnameItem::class, 'stock_opname_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'waktu_transaksi' => 'datetime',
            'jumlah' => 'integer',
            'stok_sebelum' => 'integer',
            'stok_sesudah' => 'integer',
        ];
    }
}
