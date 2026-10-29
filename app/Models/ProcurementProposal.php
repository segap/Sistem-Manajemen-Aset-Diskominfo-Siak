<?php

namespace App\Models;

use App\Enums\ProcurementProposalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProcurementProposal extends Model
{
    protected $table = 'usulan_pengadaan';

    /** @var list<string> */
    protected $fillable = [
        'pengusul_id', 'nama_barang', 'spesifikasi', 'jumlah', 'estimasi_harga_satuan',
        'alasan_kebutuhan', 'pengguna_barang_id', 'penanggung_jawab_id',
        'url_referensi_gambar', 'url_ekatalog', 'path_foto', 'status',
        'tahun_anggaran', 'jenis_belanja', 'tanggal_pengajuan', 'unit_kerja_snapshot', 'keterangan_umum',
        'pemeriksa_tim_aset_id', 'tanggal_pemeriksaan_tim_aset', 'catatan_tim_aset',
        'pemeriksa_kasubbag_id', 'tanggal_pemeriksaan_kasubbag', 'catatan_kasubbag',
        'tahap_penolakan', 'alasan_penolakan',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengusul_id');
    }

    public function intendedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_barang_id');
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab_id');
    }

    public function assetTeamReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemeriksa_tim_aset_id');
    }

    public function supervisorReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemeriksa_kasubbag_id');
    }

    public function procurement(): HasOne
    {
        return $this->hasOne(Procurement::class, 'usulan_pengadaan_id');
    }

    /** @return HasMany<ProcurementProposalDetail, $this> */
    public function details(): HasMany
    {
        return $this->hasMany(ProcurementProposalDetail::class, 'usulan_pengadaan_id')
            ->orderBy('nomor_urut');
    }

    /** @return HasMany<RiwayatPersetujuan, $this> */
    public function approvalHistories(): HasMany
    {
        return $this->hasMany(RiwayatPersetujuan::class, 'pengajuan_id')
            ->where('jenis_pengajuan', 'procurement')
            ->orderBy('diproses_pada')
            ->orderBy('id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'estimasi_harga_satuan' => 'decimal:2',
            'tahun_anggaran' => 'integer',
            'tanggal_pengajuan' => 'datetime',
            'status' => ProcurementProposalStatus::class,
            'tanggal_pemeriksaan_tim_aset' => 'datetime',
            'tanggal_pemeriksaan_kasubbag' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function spendingTypes(): array
    {
        return [
            'modal' => 'Belanja Modal',
            'non_modal' => 'Non-Belanja Modal (Rutin)',
        ];
    }

    public function getJenisBelanjaLabelAttribute(): string
    {
        return self::spendingTypes()[$this->jenis_belanja] ?? 'Belum diklasifikasikan';
    }
}
