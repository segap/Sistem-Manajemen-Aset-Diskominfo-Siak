<?php

namespace App\Http\Resources;

use App\Enums\AssetCondition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $borrowed = (int) ($this->borrowed_jumlah ?? 0);
        $reserved = (int) ($this->reserved_jumlah ?? 0);
        $damaged = $this->kondisi === AssetCondition::Good ? 0 : $this->jumlah;
        $isApproved = ($this->status_verifikasi ?? 'disetujui') === 'disetujui';
        $available = $damaged > 0 || $this->tanggal_penghapusan || ! $isApproved
            ? 0
            : max(0, $this->jumlah - $borrowed - $reserved);
        $verificationLabels = [
            'menunggu_verifikasi' => 'Menunggu Verifikasi',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];

        return [
            'id' => $this->id,
            'display_code' => $this->display_code,
            'kode_internal' => $this->kode_internal,
            'nama_aset' => $this->nama_aset,
            'kategori' => [
                'id' => $this->category->id,
                'kode_kategori' => $this->category->kode_kategori,
                'nama_kategori' => $this->category->nama_kategori,
            ],
            'merek_tipe' => $this->merek_tipe,
            'spesifikasi' => $this->spesifikasi,
            'jumlah' => $this->jumlah,
            'tahun_perolehan' => $this->tahun_perolehan,
            'kondisi' => $this->kondisi->value,
            'label_kondisi' => $this->kondisi->label(),
            'status_verifikasi' => $this->status_verifikasi ?? 'disetujui',
            'label_status_verifikasi' => $verificationLabels[$this->status_verifikasi ?? 'disetujui'] ?? 'Disetujui',
            'catatan_verifikasi' => $this->catatan_verifikasi,
            'tanggal_verifikasi' => $this->tanggal_verifikasi,
            'pemasok' => $this->pemasok,
            'lokasi_penyimpanan' => $this->lokasi_penyimpanan,
            'status_siklus' => $this->tanggal_penghapusan ? 'disposed' : 'active',
            'tanggal_penghapusan' => $this->tanggal_penghapusan,
            'qr' => $isApproved ? [
                'url_scan' => rtrim(config('app.frontend_url'), '/').'/aset/scan/'.$this->token_publik,
                'url_svg' => route('assets.qr.svg', $this->resource),
            ] : null,
            'stock' => [
                'total' => $this->jumlah,
                'tersedia' => $available,
                'direservasi' => $reserved,
                'dipinjam' => $borrowed,
                'rusak' => $damaged,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
