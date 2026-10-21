<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'nip' => $this->nip,
            'email' => $this->email,
            'role' => $this->role,
            'role_label' => $this->roleData?->nama
                ?? Role::query()->where('kode', $this->role)->value('nama')
                ?? UserRole::tryFrom((string) $this->role)?->label()
                ?? str($this->role)->replace('_', ' ')->title()->toString(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
