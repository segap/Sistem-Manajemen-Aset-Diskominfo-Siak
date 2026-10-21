<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProcurementProposalResource extends JsonResource
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
            'pengusul' => $this->userData($this->requester),
            'nama_barang' => $this->nama_barang,
            'spesifikasi' => $this->spesifikasi,
            'jumlah' => $this->jumlah,
            'estimasi_harga_satuan' => $this->estimasi_harga_satuan,
            'estimasi_total_harga' => number_format((float) $this->estimasi_harga_satuan * $this->jumlah, 2, '.', ''),
            'alasan_kebutuhan' => $this->alasan_kebutuhan,
            'pengguna_barang' => $this->userData($this->intendedUser),
            'penanggung_jawab' => $this->userData($this->responsiblePerson),
            'url_referensi_gambar' => $this->url_referensi_gambar,
            'url_ekatalog' => $this->url_ekatalog,
            'url_foto' => Storage::disk('public')->url($this->path_foto),
            'status' => $this->status->value,
            'label_status' => $this->status->label(),
            'sudah_diproses' => $this->whenLoaded('procurement', fn () => $this->procurement !== null, false),
            'pemeriksa_tim_aset' => $this->whenLoaded(
                'assetTeamReviewer',
                fn () => $this->assetTeamReviewer ? $this->userData($this->assetTeamReviewer) : null,
            ),
            'tanggal_pemeriksaan_tim_aset' => $this->tanggal_pemeriksaan_tim_aset,
            'catatan_tim_aset' => $this->catatan_tim_aset,
            'pemeriksa_kasubbag' => $this->whenLoaded(
                'supervisorReviewer',
                fn () => $this->supervisorReviewer ? $this->userData($this->supervisorReviewer) : null,
            ),
            'tanggal_pemeriksaan_kasubbag' => $this->tanggal_pemeriksaan_kasubbag,
            'catatan_kasubbag' => $this->catatan_kasubbag,
            'tahap_penolakan' => $this->tahap_penolakan,
            'alasan_penolakan' => $this->alasan_penolakan,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /** @return array{id: int, name: string, nip: ?string} */
    private function userData(mixed $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'nip' => $user->nip,
        ];
    }
}
