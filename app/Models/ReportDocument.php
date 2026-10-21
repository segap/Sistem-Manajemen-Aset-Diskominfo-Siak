<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportDocument extends Model
{
    protected $table = 'laporan_dokumen';

    /** @var list<string> */
    protected $fillable = [
        'jenis_laporan',
        'tahun',
        'bulan',
        'periode',
        'judul',
        'nama_file_asli',
        'path_file',
        'mime_type',
        'ukuran_file',
        'uploaded_by',
        'asset_holder_id',
        'tanggal_berita_acara',
        'nama_pegawai_snapshot',
        'jabatan_snapshot',
        'status_pegawai_snapshot',
        'unit_kerja_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'ukuran_file' => 'integer',
            'tanggal_berita_acara' => 'date',
            'status_pegawai_snapshot' => 'boolean',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function assetHolder(): BelongsTo
    {
        return $this->belongsTo(AssetHolder::class, 'asset_holder_id');
    }
}
