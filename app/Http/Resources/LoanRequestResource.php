<?php

namespace App\Http\Resources;

use App\Enums\LoanStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanRequestResource extends JsonResource
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
            'pemohon' => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
                'nip' => $this->requester->nip,
            ],
            'aset' => [
                'id' => $this->asset->id,
                'display_code' => $this->asset->display_code,
                'kode_internal' => $this->asset->kode_internal,
                'nama_aset' => $this->asset->nama_aset,
                'merek_tipe' => $this->asset->merek_tipe,
                'spesifikasi' => $this->asset->spesifikasi,
                'jumlah' => $this->asset->jumlah,
                'kategori' => $this->whenLoaded('asset', fn () => $this->asset->relationLoaded('category') ? [
                    'id' => $this->asset->category?->id,
                    'kode_kategori' => $this->asset->category?->kode_kategori,
                    'nama_kategori' => $this->asset->category?->nama_kategori,
                ] : null),
                'kondisi' => $this->asset->kondisi?->value,
                'label_kondisi' => $this->asset->kondisi?->label(),
                'lokasi_penyimpanan' => $this->asset->lokasi_penyimpanan,
                'tahun_perolehan' => $this->asset->tahun_perolehan,
            ],
            'items' => $this->whenLoaded('details', fn () => $this->details->map(fn ($detail) => [
                'id' => $detail->id,
                'aset' => [
                    'id' => $detail->asset?->id,
                    'display_code' => $detail->asset?->display_code,
                    'kode_internal' => $detail->asset?->kode_internal,
                    'nama_aset' => $detail->asset?->nama_aset,
                    'merek_tipe' => $detail->asset?->merek_tipe,
                    'kondisi' => $detail->asset?->kondisi?->value,
                    'label_kondisi' => $detail->asset?->kondisi?->label(),
                ],
                'kondisi_kembali' => $detail->kondisi_kembali?->value,
                'label_kondisi_kembali' => $detail->kondisi_kembali?->label(),
                'catatan_pengembalian' => $detail->catatan_pengembalian,
            ])->values()),
            'jumlah' => $this->relationLoaded('details') && $this->details->isNotEmpty() ? $this->details->count() : $this->jumlah,
            'jumlah_legacy' => $this->jumlah,
            'tanggal_pinjam' => $this->tanggal_pinjam->toDateString(),
            'tanggal_kembali' => $this->tanggal_kembali->toDateString(),
            'alasan' => $this->alasan,
            'status' => $this->status->value,
            'label_status' => $this->status->label(),
            'pemeriksa' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null),
            'tanggal_pemeriksaan' => $this->tanggal_pemeriksaan,
            'alasan_penolakan' => $this->alasan_penolakan,
            'diserahkan_oleh' => $this->whenLoaded('handedOverBy', fn () => $this->handedOverBy ? [
                'id' => $this->handedOverBy->id,
                'name' => $this->handedOverBy->name,
            ] : null),
            'tanggal_penyerahan' => $this->tanggal_penyerahan,
            'diterima_kembali_oleh' => $this->whenLoaded('returnedBy', fn () => $this->returnedBy ? [
                'id' => $this->returnedBy->id,
                'name' => $this->returnedBy->name,
            ] : null),
            'tanggal_pengembalian' => $this->tanggal_pengembalian,
            'kondisi_kembali' => $this->kondisi_kembali?->value,
            'label_kondisi_kembali' => $this->kondisi_kembali?->label(),
            'catatan_pengembalian' => $this->catatan_pengembalian,
            'is_overdue' => $this->status === LoanStatus::Borrowed
                && today()->isAfter($this->tanggal_kembali),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
