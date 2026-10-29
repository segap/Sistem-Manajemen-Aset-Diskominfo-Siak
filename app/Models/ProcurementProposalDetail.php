<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementProposalDetail extends Model
{
    protected $table = 'detail_usulan_pengadaan';

    /** @var list<string> */
    protected $fillable = [
        'nomor_urut',
        'nama_barang',
        'spesifikasi',
        'jumlah',
        'estimasi_harga_satuan',
        'link_produk',
        'keterangan',
        'penanggung_jawab_id',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ProcurementProposal::class, 'usulan_pengadaan_id');
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'nomor_urut' => 'integer',
            'jumlah' => 'integer',
            'estimasi_harga_satuan' => 'decimal:2',
        ];
    }
}
