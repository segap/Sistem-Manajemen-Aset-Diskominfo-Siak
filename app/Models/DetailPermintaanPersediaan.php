<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPermintaanPersediaan extends Model
{
    protected $table = 'detail_permintaan_persediaan';

    protected $fillable = [
        'permintaan_persediaan_id',
        'persediaan_id',
        'nama_barang_snapshot',
        'satuan_snapshot',
        'stok_saat_pengajuan',
        'jumlah_diminta',
        'justifikasi_kebutuhan',
        'jumlah_disetujui',
        'jumlah_diserahkan',
        'catatan',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(PermintaanPersediaan::class, 'permintaan_persediaan_id');
    }

    public function persediaan(): BelongsTo
    {
        return $this->belongsTo(StockOpnameItem::class, 'persediaan_id');
    }

    protected function casts(): array
    {
        return [
            'stok_saat_pengajuan' => 'integer',
            'jumlah_diminta' => 'integer',
            'jumlah_disetujui' => 'integer',
            'jumlah_diserahkan' => 'integer',
        ];
    }
}
