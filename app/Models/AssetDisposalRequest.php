<?php

namespace App\Models;

use App\Enums\AssetDisposalReason;
use App\Enums\AssetDisposalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetDisposalRequest extends Model
{
    protected $table = 'pengajuan_penghapusan_aset';

    /** @var list<string> */
    protected $fillable = [
        'pengusul_id', 'aset_id', 'asset_condition_mutation_id', 'alasan', 'catatan',
        'catatan_kondisi_snapshot', 'google_drive_url_snapshot', 'path_dokumen',
        'nama_dokumen', 'mime_dokumen', 'status', 'diperiksa_oleh',
        'tanggal_pemeriksaan', 'alasan_penolakan',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengusul_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'aset_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperiksa_oleh');
    }

    public function conditionMutation(): BelongsTo
    {
        return $this->belongsTo(AssetConditionMutation::class, 'asset_condition_mutation_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(AssetDisposalEvidence::class, 'pengajuan_penghapusan_aset_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'alasan' => AssetDisposalReason::class,
            'status' => AssetDisposalStatus::class,
            'tanggal_pemeriksaan' => 'datetime',
        ];
    }
}
