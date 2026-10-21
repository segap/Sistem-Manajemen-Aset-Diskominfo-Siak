<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpnameCategory extends Model
{
    protected $fillable = ['nama_kategori', 'keterangan'];

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
