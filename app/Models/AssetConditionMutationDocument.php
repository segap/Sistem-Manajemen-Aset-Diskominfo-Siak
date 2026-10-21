<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetConditionMutationDocument extends Model
{
    protected $fillable = [
        'asset_condition_mutation_id',
        'jenis',
        'path_file',
        'nama_file_asli',
        'mime_type',
        'ukuran_file',
    ];

    protected function casts(): array
    {
        return [
            'ukuran_file' => 'integer',
        ];
    }

    public function mutation(): BelongsTo
    {
        return $this->belongsTo(AssetConditionMutation::class, 'asset_condition_mutation_id');
    }
}
