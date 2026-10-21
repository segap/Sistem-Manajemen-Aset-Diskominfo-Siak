<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetPhoto extends Model
{
    protected $fillable = [
        'asset_id',
        'path',
        'nama_file_asli',
        'mime_type',
        'ukuran_file',
        'is_primary',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'ukuran_file' => 'integer',
            'urutan' => 'integer',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
