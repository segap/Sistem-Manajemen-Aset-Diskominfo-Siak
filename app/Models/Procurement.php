<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\ProcurementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Procurement extends Model
{
    protected $table = 'pengadaan';

    /** @var list<string> */
    protected $fillable = [
        'usulan_pengadaan_id', 'kategori_aset_id', 'dibuat_oleh',
        'nama_barang', 'merek_tipe', 'spesifikasi', 'jumlah_dipesan', 'harga_satuan',
        'pemasok', 'tanggal_pesan', 'perkiraan_tanggal_tiba', 'alasan_pengadaan', 'status',
        'jumlah_diterima', 'tanggal_penerimaan', 'diterima_oleh', 'catatan_penerimaan', 'bukti_penerimaan',
        'diverifikasi_oleh', 'tanggal_verifikasi', 'kondisi', 'lokasi_penyimpanan',
        'catatan_verifikasi', 'alasan_penolakan', 'aset_id',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ProcurementProposal::class, 'usulan_pengadaan_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'kategori_aset_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'aset_id');
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'pengadaan_aset', 'pengadaan_id', 'aset_id')
            ->withTimestamps();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jumlah_dipesan' => 'integer',
            'jumlah_diterima' => 'integer',
            'harga_satuan' => 'decimal:2',
            'tanggal_pesan' => 'date',
            'perkiraan_tanggal_tiba' => 'date',
            'status' => ProcurementStatus::class,
            'tanggal_penerimaan' => 'datetime',
            'tanggal_verifikasi' => 'datetime',
            'kondisi' => AssetCondition::class,
        ];
    }
}
