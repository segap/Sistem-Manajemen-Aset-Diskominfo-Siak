<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanRequest extends Model
{
    protected $table = 'peminjaman';

    /** @var list<string> */
    protected $fillable = [
        'pemohon_id', 'aset_id', 'jumlah', 'tanggal_pinjam', 'tanggal_kembali',
        'alasan', 'surat_peminjaman', 'nama_surat_peminjaman', 'status', 'diperiksa_oleh', 'tanggal_pemeriksaan', 'alasan_penolakan',
        'diserahkan_oleh', 'tanggal_penyerahan', 'diterima_kembali_oleh', 'tanggal_pengembalian',
        'kondisi_kembali', 'catatan_pengembalian',
    ];

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemohon_id');
    }

    /** @return BelongsTo<Asset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'aset_id');
    }

    /** @return HasMany<DetailPeminjaman, $this> */
    public function details(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'peminjaman_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperiksa_oleh');
    }

    /** @return BelongsTo<User, $this> */
    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diserahkan_oleh');
    }

    /** @return BelongsTo<User, $this> */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_kembali_oleh');
    }

    public function itemCount(): int
    {
        return $this->relationLoaded('details') && $this->details->isNotEmpty()
            ? $this->details->count()
            : max(1, (int) $this->jumlah);
    }

    public function itemSummary(): string
    {
        if (! $this->relationLoaded('details') || $this->details->isEmpty()) {
            return trim(($this->asset?->display_code ?: '-').' - '.($this->asset?->nama_aset ?: '-'));
        }

        if ($this->details->count() === 1) {
            $asset = $this->details->first()->asset;

            return trim(($asset?->display_code ?: '-').' - '.($asset?->nama_aset ?: '-'));
        }

        return $this->details->count().' Barang';
    }

    public function itemPreview(): string
    {
        if (! $this->relationLoaded('details') || $this->details->isEmpty()) {
            return $this->asset?->display_code ?: '-';
        }

        return $this->details
            ->take(3)
            ->map(fn (DetailPeminjaman $detail): string => $detail->asset?->display_code ?: '-')
            ->implode(', ');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal_pinjam' => 'date',
            'tanggal_kembali' => 'date',
            'status' => LoanStatus::class,
            'tanggal_pemeriksaan' => 'datetime',
            'tanggal_penyerahan' => 'datetime',
            'tanggal_pengembalian' => 'datetime',
            'kondisi_kembali' => AssetCondition::class,
        ];
    }
}
