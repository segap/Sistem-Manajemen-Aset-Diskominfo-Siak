<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

class PermissionInitializer
{
    /**
     * Insert the catalogue and legacy mappings once without overwriting an
     * existing role configuration. This method is never called automatically.
     *
     * @return array{permissions_created: int, roles_initialized: list<string>, roles_skipped: list<string>}
     */
    public function initializeEmptyRoles(): array
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            throw new LogicException('Tabel permissions dan role_permissions harus tersedia terlebih dahulu.');
        }

        return DB::transaction(function (): array {
            $created = 0;
            foreach (PermissionCatalog::definitions() as $definition) {
                $permission = Permission::query()->firstOrCreate(
                    ['kode' => $definition['kode']],
                    ['nama' => $definition['nama'], 'grup' => $definition['grup']],
                );

                $created += $permission->wasRecentlyCreated ? 1 : 0;
            }

            $permissionIds = Permission::query()->pluck('id', 'kode');
            $initialized = [];
            $skipped = [];

            foreach (Role::query()->orderBy('id')->get() as $role) {
                $codes = PermissionCatalog::legacyRolePermissions($role->kode);

                if ($codes === []) {
                    $skipped[] = $role->kode;
                    continue;
                }

                if ($role->permissions()->exists()) {
                    $skipped[] = $role->kode;
                    continue;
                }

                $rows = collect($codes)
                    ->unique()
                    ->map(fn (string $code): ?array => isset($permissionIds[$code]) ? [
                        'role_id' => $role->id,
                        'permission_id' => $permissionIds[$code],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ] : null)
                    ->filter()
                    ->values()
                    ->all();

                if ($rows !== []) {
                    DB::table('role_permissions')->insertOrIgnore($rows);
                }

                $initialized[] = $role->kode;
            }

            return [
                'permissions_created' => $created,
                'roles_initialized' => $initialized,
                'roles_skipped' => $skipped,
            ];
        });
    }
}
