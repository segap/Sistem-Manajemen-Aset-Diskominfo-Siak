<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposalEvidence extends Model
{
    protected $table = 'pengajuan_penghapusan_bukti';

    /** @var list<string> */
    protected $fillable = [
        'pengajuan_penghapusan_aset_id',
        'asset_condition_mutation_document_id',
        'jenis',
        'path_file',
        'nama_file',
        'mime_type',
        'ukuran_file',
    ];

    protected function casts(): array
    {
        return [
            'ukuran_file' => 'integer',
        ];
    }

    public function disposalRequest(): BelongsTo
    {
        return $this->belongsTo(AssetDisposalRequest::class, 'pengajuan_penghapusan_aset_id');
    }

    public function mutationDocument(): BelongsTo
    {
        return $this->belongsTo(AssetConditionMutationDocument::class, 'asset_condition_mutation_document_id');
    }
}
