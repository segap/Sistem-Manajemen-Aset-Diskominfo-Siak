<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailKir extends Model
{
    protected $table = 'detail_kir';

    /** @var list<string> */
    protected $fillable = [
        'kir_id',
        'aset_id',
        'kode_barang_id',
        'nibar',
        'nomor_register',
        'kode_lokasi',
        'kode_barang',
        'nama_barang',
        'spesifikasi_nama_barang',
        'merek_tipe',
        'tahun_perolehan',
        'jumlah',
        'satuan',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tahun_perolehan' => 'integer',
            'jumlah' => 'integer',
        ];
    }

    public function kir(): BelongsTo
    {
        return $this->belongsTo(Kir::class, 'kir_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'aset_id');
    }

    public function kodeBarangMaster(): BelongsTo
    {
        return $this->belongsTo(KodeBarang::class, 'kode_barang_id');
    }
}
