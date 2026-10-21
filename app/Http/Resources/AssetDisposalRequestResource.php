<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetDisposalRequestResource extends JsonResource
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
            'aset' => [
                'id' => $this->asset->id,
                'display_code' => $this->asset->display_code,
                'kode_internal' => $this->asset->kode_internal,
                'nama_aset' => $this->asset->nama_aset,
                'tanggal_penghapusan' => $this->asset->tanggal_penghapusan,
            ],
            'alasan' => $this->alasan->value,
            'label_alasan' => $this->alasan->label(),
            'catatan' => $this->catatan,
            'nama_dokumen' => $this->nama_dokumen,
            'dokumen_adalah_gambar' => in_array(strtolower(pathinfo((string) $this->nama_dokumen, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true),
            'status' => $this->status->value,
            'label_status' => $this->status->label(),
            'pemeriksa' => $this->whenLoaded('reviewer', fn () => $this->userData($this->reviewer)),
            'tanggal_pemeriksaan' => $this->tanggal_pemeriksaan,
            'alasan_penolakan' => $this->alasan_penolakan,
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
