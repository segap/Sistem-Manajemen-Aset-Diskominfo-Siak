<?php

namespace App\Models;

use App\Enums\PermintaanPersediaanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanPersediaan extends Model
{
    protected $table = 'permintaan_persediaan';

    protected $fillable = [
        'nomor_permintaan',
        'pemohon_id',
        'asal_permintaan',
        'pemegang_aset_id',
        'dicatat_oleh',
        'nama_pemohon_snapshot',
        'nip_pemohon_snapshot',
        'unit_kerja_snapshot',
        'tanggal_permintaan',
        'keperluan',
        'status',
        'catatan',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'diserahkan_oleh',
        'diserahkan_pada',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(DetailPermintaanPersediaan::class, 'permintaan_persediaan_id');
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemohon_id');
    }

    public function pemegangAset(): BelongsTo
    {
        return $this->belongsTo(AssetHolder::class, 'pemegang_aset_id');
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function handoverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diserahkan_oleh');
    }

    protected function casts(): array
    {
        return [
            'tanggal_permintaan' => 'datetime',
            'diverifikasi_pada' => 'datetime',
            'diserahkan_pada' => 'datetime',
            'status' => PermintaanPersediaanStatus::class,
        ];
    }
}
