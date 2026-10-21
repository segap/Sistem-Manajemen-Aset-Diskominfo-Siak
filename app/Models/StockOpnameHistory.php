<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameHistory extends Model
{
    protected $fillable = [
        'stock_opname_item_id',
        'user_id',
        'aksi',
        'status_sebelum',
        'status_sesudah',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'hasil_opname',
        'catatan',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockOpnameItem::class, 'stock_opname_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
