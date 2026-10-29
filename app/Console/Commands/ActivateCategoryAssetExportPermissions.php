<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Support\CategoryAssetExportPermissions;
use App\Support\PermissionCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

class ActivateCategoryAssetExportPermissions extends Command
{
    protected $signature = 'permissions:activate-category-asset-exports {--force : Daftarkan permission export kategori dan salin mapping legacy secara selektif}';

    protected $description = 'Aktivasi aman permission Export Excel/PDF Kategori Aset dari mapping export lama';

    public function handle(): int
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            throw new LogicException('Tabel permissions dan role_permissions harus tersedia terlebih dahulu.');
        }

        $legacyRoles = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
            ->where('permissions.kode', CategoryAssetExportPermissions::LEGACY_CODE)
            ->orderBy('roles.nama')
            ->get(['roles.id', 'roles.kode', 'roles.nama']);

        if (! $this->option('force')) {
            $this->info('Pratinjau read-only: tidak ada data yang diubah.');
            $this->line('Permission yang akan ditambahkan: '.implode(', ', CategoryAssetExportPermissions::codes()));
            $this->table(['ID', 'Kode Role', 'Nama Role'], $legacyRoles->map(fn ($role) => [$role->id, $role->kode, $role->nama])->all());
            $this->line('Jalankan ulang dengan --force hanya setelah menyetujui role di atas menerima kedua permission baru.');

            return self::SUCCESS;
        }

        $result = DB::transaction(function (): array {
            $definitions = collect(PermissionCatalog::definitions())->keyBy('kode');
            $created = 0;

            foreach (CategoryAssetExportPermissions::codes() as $code) {
                $definition = $definitions->get($code);
                $permission = Permission::query()->firstOrCreate(
                    ['kode' => $code],
                    ['nama' => $definition['nama'], 'grup' => $definition['grup']],
                );
                $created += $permission->wasRecentlyCreated ? 1 : 0;
            }

            $permissions = Permission::query()
                ->whereIn('kode', [CategoryAssetExportPermissions::LEGACY_CODE, ...CategoryAssetExportPermissions::codes()])
                ->pluck('id', 'kode');

            $roleIds = DB::table('role_permissions')
                ->where('permission_id', $permissions[CategoryAssetExportPermissions::LEGACY_CODE])
                ->pluck('role_id')
                ->unique()
                ->values();

            $timestamp = now();
            $rows = $roleIds->flatMap(fn (int $roleId) => collect(CategoryAssetExportPermissions::codes())
                ->map(fn (string $code) => [
                    'role_id' => $roleId,
                    'permission_id' => $permissions[$code],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]))
                ->all();

            if ($rows !== []) {
                DB::table('role_permissions')->insertOrIgnore($rows);
            }

            return ['created' => $created, 'affected_roles' => $roleIds->count()];
        });

        $this->info("Permission baru dibuat: {$result['created']}");
        $this->info("Role legacy yang dipetakan: {$result['affected_roles']}");

        return self::SUCCESS;
    }
}
