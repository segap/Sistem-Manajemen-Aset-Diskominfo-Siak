<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    protected $table = 'kategori_aset';

    /** @var list<string> */
    protected $fillable = ['kode_kategori', 'nama_kategori', 'ikon', 'deskripsi'];

    /** @return HasMany<Asset, $this> */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'kategori_aset_id');
    }
}
