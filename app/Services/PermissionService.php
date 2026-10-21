<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class PermissionService
{
    private static ?bool $tablesReady = null;

    public function allows(?User $user, string $permission): bool
    {
        $permission = trim($permission);

        if (! $user || ! $user->is_active || $permission === '') {
            return false;
        }

        if ($user->role === UserRole::SuperAdmin->value) {
            return true;
        }

        if (! $this->tablesReady()) {
            return false;
        }

        if (! $user->relationLoaded('roleData')) {
            $user->load('roleData.permissions:id,kode');
        } elseif ($user->roleData && ! $user->roleData->relationLoaded('permissions')) {
            $user->roleData->load('permissions:id,kode');
        }

        return $user->roleData?->permissions->contains('kode', $permission) ?? false;
    }

    private function tablesReady(): bool
    {
        return self::$tablesReady ??= Schema::hasTable('permissions')
            && Schema::hasTable('role_permissions');
    }
}
