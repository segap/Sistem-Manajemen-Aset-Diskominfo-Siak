<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KodeInternalPrefix extends Model
{
    protected $table = 'kode_internal_prefixes';

    /** @var list<string> */
    protected $fillable = [
        'nama_barang',
        'prefix',
        'nomor_terakhir',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'nomor_terakhir' => 'integer',
        ];
    }
}
