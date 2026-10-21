<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcurementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usulan_id' => $this->usulan_pengadaan_id,
            'kategori' => [
                'id' => $this->category->id,
                'kode_kategori' => $this->category->kode_kategori,
                'nama_kategori' => $this->category->nama_kategori,
            ],
            'dibuat_oleh' => $this->userData($this->creator),
            'nama_barang' => $this->nama_barang,
            'merek_tipe' => $this->merek_tipe,
            'spesifikasi' => $this->spesifikasi,
            'jumlah_dipesan' => $this->jumlah_dipesan,
            'harga_satuan' => $this->harga_satuan,
            'total_harga' => number_format((float) $this->harga_satuan * $this->jumlah_dipesan, 2, '.', ''),
            'pemasok' => $this->pemasok,
            'tanggal_pesan' => $this->tanggal_pesan->toDateString(),
            'perkiraan_tanggal_tiba' => $this->perkiraan_tanggal_tiba?->toDateString(),
            'alasan_pengadaan' => $this->alasan_pengadaan,
            'status' => $this->status->value,
            'label_status' => $this->status->label(),
            'jumlah_diterima' => $this->jumlah_diterima,
            'tanggal_penerimaan' => $this->tanggal_penerimaan,
            'diterima_oleh' => $this->whenLoaded('receiver', fn () => $this->userData($this->receiver)),
            'catatan_penerimaan' => $this->catatan_penerimaan,
            'diverifikasi_oleh' => $this->whenLoaded('verifier', fn () => $this->userData($this->verifier)),
            'tanggal_verifikasi' => $this->tanggal_verifikasi,
            'kondisi' => $this->kondisi?->value,
            'label_kondisi' => $this->kondisi?->label(),
            'lokasi_penyimpanan' => $this->lokasi_penyimpanan,
            'catatan_verifikasi' => $this->catatan_verifikasi,
            'alasan_penolakan' => $this->alasan_penolakan,
            'aset_id' => $this->aset_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /** @return array{id: int, name: string, nip: ?string}|null */
    private function userData(mixed $user): ?array
    {
        return $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'nip' => $user->nip,
        ] : null;
    }
}
