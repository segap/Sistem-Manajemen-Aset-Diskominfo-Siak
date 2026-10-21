<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenanceDocument extends Model
{
    protected $table = 'dokumen_pemeliharaan';

    protected $fillable = [
        'pemeliharaan_aset_id',
        'jenis_dokumen',
        'path_file',
        'nama_file',
        'uploaded_by',
    ];

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(AssetMaintenance::class, 'pemeliharaan_aset_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
