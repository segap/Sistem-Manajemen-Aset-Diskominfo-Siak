<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetConditionMutation extends Model
{
    protected $fillable = [
        'asset_id',
        'dari_kondisi',
        'ke_kondisi',
        'jumlah',
        'catatan',
        'google_drive_url',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetConditionMutationDocument::class, 'asset_condition_mutation_id');
    }
}
