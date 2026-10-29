<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AssetMaintenancePriority;
use App\Enums\AssetMaintenanceStatus;
use App\Enums\AssetMaintenanceType;
use App\Enums\MaintenanceFinalResult;
use App\Enums\MaintenanceMainFunctionStatus;
use App\Enums\MaintenanceTestResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetMaintenance extends Model
{
    protected $table = 'pemeliharaan_aset';

    private const INITIAL_DOCUMENT_TYPES = ['foto_sebelum', 'video_sebelum'];

    protected $fillable = [
        'asset_id',
        'pemohon_id',
        'tanggal_pengajuan',
        'kondisi_sebelum',
        'jumlah_dipelihara',
        'keluhan',
        'catatan_pengajuan',
        'foto_sebelum',
        'prioritas',
        'status',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'catatan_verifikasi',
        'tanggal_mulai',
        'tanggal_selesai',
        'penyedia_servis',
        'tindakan_servis',
        'biaya_servis',
        'kondisi_setelah',
        'catatan_hasil',
        'foto_setelah',
        'link_drive_dokumentasi',
        'selesai_oleh',
        'nomor_berita_acara',
        'tanggal_berita_acara',
        'lokasi_pelaksanaan',
        'dasar_pelaksanaan',
        'jenis_pemeliharaan',
        'jenis_pemeliharaan_lainnya',
        'nama_pelaksana',
        'identitas_pelaksana',
        'komponen_material',
        'fungsi_utama',
        'hasil_pengujian',
        'hasil_akhir_pemeliharaan',
        'tempat_ttd',
        'jabatan_penanggung_jawab',
        'nama_penanggung_jawab',
        'nip_penanggung_jawab',
        'berita_acara_dibuat_pada',
        'berita_acara_dibuat_oleh',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemohon_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selesai_oleh');
    }

    public function reportCreatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'berita_acara_dibuat_oleh');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetMaintenanceDocument::class, 'pemeliharaan_aset_id');
    }

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(RiwayatPersetujuan::class, 'pengajuan_id')
            ->where('jenis_pengajuan', 'maintenance');
    }

    /**
     * Returns the reason deletion is unsafe, or null when a submitted request
     * only has its optional initial administrative history.
     */
    public function deletionBlockReason(): ?string
    {
        if ($this->status !== AssetMaintenanceStatus::Submitted) {
            return 'Hanya pengajuan yang masih menunggu verifikasi dapat dihapus.';
        }

        $documents = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->get();

        if (filled($this->foto_setelah)) {
            return 'Data pemeliharaan tidak dapat dihapus karena memiliki dokumentasi hasil pemeliharaan yang harus dipertahankan.';
        }

        if ($documents->contains(fn (AssetMaintenanceDocument $document): bool => ! in_array($document->jenis_dokumen, self::INITIAL_DOCUMENT_TYPES, true))) {
            return 'Data pemeliharaan tidak dapat dihapus karena memiliki dokumen proses yang harus dipertahankan.';
        }

        if (filled($this->foto_sebelum) && ! $documents->contains(
            fn (AssetMaintenanceDocument $document): bool => $document->jenis_dokumen === 'foto_sebelum'
                && $document->path_file === $this->foto_sebelum,
        )) {
            return 'Data pemeliharaan tidak dapat dihapus karena dokumentasi pengajuan tidak dapat dipastikan keamanannya.';
        }

        $histories = $this->relationLoaded('approvalHistories')
            ? $this->approvalHistories
            : $this->approvalHistories()->get();

        if ($histories->isEmpty()) {
            return null;
        }

        if ($histories->count() === 1
            && $histories->first()->tahap === 'pengajuan'
            && $histories->first()->status === 'dibuat') {
            return null;
        }

        return 'Data pemeliharaan tidak dapat dihapus karena memiliki riwayat proses yang harus dipertahankan.';
    }

    public function isSafelyDeletable(): bool
    {
        return $this->deletionBlockReason() === null;
    }

    public function hasCompleteReportData(): bool
    {
        return $this->missingReportDataFields() === [];
    }

    /** @return list<string> */
    public function missingReportDataFields(): array
    {
        $fields = [
            'nomor_berita_acara',
            'tanggal_berita_acara',
            'lokasi_pelaksanaan',
            'jenis_pemeliharaan',
            'nama_pelaksana',
            'fungsi_utama',
            'hasil_pengujian',
            'hasil_akhir_pemeliharaan',
            'tempat_ttd',
            'jabatan_penanggung_jawab',
            'nama_penanggung_jawab',
            'nip_penanggung_jawab',
        ];

        if ($this->jenis_pemeliharaan === AssetMaintenanceType::Other) {
            $fields[] = 'jenis_pemeliharaan_lainnya';
        }

        return collect($fields)
            ->filter(fn (string $field): bool => blank($this->{$field}))
            ->values()
            ->all();
    }

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'datetime',
            'diverifikasi_pada' => 'datetime',
            'tanggal_mulai' => 'datetime',
            'tanggal_selesai' => 'datetime',
            'tanggal_berita_acara' => 'date',
            'berita_acara_dibuat_pada' => 'datetime',
            'jumlah_dipelihara' => 'integer',
            'biaya_servis' => 'decimal:2',
            'kondisi_sebelum' => AssetCondition::class,
            'kondisi_setelah' => AssetCondition::class,
            'prioritas' => AssetMaintenancePriority::class,
            'status' => AssetMaintenanceStatus::class,
            'jenis_pemeliharaan' => AssetMaintenanceType::class,
            'fungsi_utama' => MaintenanceMainFunctionStatus::class,
            'hasil_pengujian' => MaintenanceTestResult::class,
            'hasil_akhir_pemeliharaan' => MaintenanceFinalResult::class,
        ];
    }
}
