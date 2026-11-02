<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetImportBatch extends Model
{
    protected $fillable = ['file_hash', 'source_filename', 'user_id', 'status', 'asset_unit_count', 'imported_at'];

    protected function casts(): array
    {
        return ['asset_unit_count' => 'integer', 'imported_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'asset_import_batch_id');
    }
}
