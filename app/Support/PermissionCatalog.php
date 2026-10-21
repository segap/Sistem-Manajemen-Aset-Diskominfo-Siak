<?php

namespace App\Support;

/**
 * Stable permission catalogue and one-time legacy role mapping.
 *
 * This class is declarative only. It does not write to the database unless
 * PermissionInitializer is explicitly invoked after the migrations exist.
 */
final class PermissionCatalog
{
    /**
     * @return list<array{kode: string, nama: string, grup: string}>
     */
    public static function definitions(): array
    {
        return [
            ...self::module('dashboard', 'Dashboard', ['view']),
            ...self::module('aset', 'Master Barang', ['view', 'create', 'update', 'delete', 'import', 'condition.update', 'verify', 'export']),
            ...self::module('kategori_aset', 'Kategori Aset', ['view', 'create', 'update', 'delete', 'export']),
            ...self::module('kodefikasi', 'Kodefikasi', ['view', 'create', 'update', 'delete', 'import', 'export']),
            ...self::module('kodefikasi_internal', 'Kodefikasi Internal', ['view', 'generate', 'bulk_generate', 'update', 'reset', 'export']),
            ...self::module('ruangan', 'Manajemen Ruangan', ['view', 'create', 'update', 'delete', 'export']),
            ...self::module('kir', 'Kartu Inventaris Ruangan', ['view', 'create', 'update', 'delete', 'import', 'template.download', 'download', 'export']),
            ...self::module('stock_opname', 'Stock Opname', ['view', 'create', 'update', 'delete', 'import', 'template.download', 'stock.add', 'stock.adjust', 'verify', 'export']),
            ...self::module('kategori_stock_opname', 'Kategori Stock Opname', ['view', 'create', 'update', 'delete', 'export']),
            ...self::module('permintaan', 'Permintaan Barang', ['view', 'create', 'verify', 'handover', 'export']),
            ...self::module('peminjaman', 'Peminjaman Barang', ['view', 'create', 'verify', 'approve', 'handover', 'return', 'delete', 'export']),
            ...self::module('pemeliharaan', 'Pemeliharaan Aset', ['view', 'create', 'verify', 'update', 'delete', 'start', 'complete', 'report.update', 'download', 'export']),
            ...self::module('persetujuan', 'Persetujuan', ['view', 'delete', 'export']),
            ...self::module('usulan_pengadaan', 'Usulan Pengadaan', ['view', 'create', 'verify', 'approve', 'export']),
            ...self::module('pengadaan', 'Pengadaan Barang', ['view', 'create', 'receive', 'verify', 'export']),
            ...self::module('penghapusan', 'Penghapusan Aset', ['view', 'create', 'approve', 'download', 'export']),
            ...self::module('laporan_aset', 'Laporan Aset', ['view', 'export']),
            ...self::reportModule('laporan_rekonsiliasi_bulanan', 'Laporan Rekonsiliasi Bulanan'),
            ...self::reportModule('laporan_semester_1', 'Laporan Semester 1'),
            ...self::reportModule('laporan_tahunan', 'Laporan Tahunan'),
            ...self::reportModule('laporan_aset_unaudited', 'Laporan Aset Unaudited'),
            ...self::reportModule('laporan_aset_audited', 'Laporan Aset Audited'),
            ...self::module('berita_acara_bmd', 'Berita Acara BMD', ['view', 'upload', 'complete', 'delete', 'download']),
            ...self::reportModule('laporan_persediaan_bulanan', 'Laporan Persediaan Bulanan'),
            ...self::reportModule('laporan_persediaan_semester_1', 'Laporan Persediaan Semester 1'),
            ...self::reportModule('laporan_persediaan_tahunan', 'Laporan Persediaan Tahunan'),
            ...self::module('users', 'Data User', ['view', 'create', 'update', 'delete', 'password.reset', 'export']),
            ...self::module('roles', 'Manajemen Role', ['view', 'create', 'update', 'delete', 'export']),
            ...self::module('pemegang_aset', 'Pemegang Aset', ['view', 'create', 'update', 'delete', 'import', 'template.download', 'ktp.download', 'print', 'pdf']),
        ];
    }

    /**
     * Legacy mapping inferred from current backend access. It is intentionally
     * not applied automatically; see PermissionInitializer.
     *
     * @return list<string>
     */
    public static function legacyRolePermissions(string $roleCode): array
    {
        $assetReports = self::reportPermissions([
            'laporan_rekonsiliasi_bulanan',
            'laporan_semester_1',
            'laporan_tahunan',
            'laporan_aset_unaudited',
            'laporan_aset_audited',
            'berita_acara_bmd',
        ]);
        $assetReports[] = 'berita_acara_bmd.complete';
        $inventoryReports = self::reportPermissions([
            'laporan_persediaan_bulanan',
            'laporan_persediaan_semester_1',
            'laporan_persediaan_tahunan',
        ]);

        return match ($roleCode) {
            'admin' => array_merge(
                self::all('dashboard', ['view']),
                self::all('aset', ['view', 'create', 'update', 'delete', 'import', 'condition.update', 'export']),
                self::all('kategori_aset', ['view', 'create', 'update', 'delete', 'export']),
                self::all('kodefikasi', ['view', 'create', 'update', 'delete', 'import', 'export']),
                self::all('kodefikasi_internal', ['view', 'generate', 'bulk_generate', 'update', 'reset', 'export']),
                self::all('ruangan', ['view', 'create', 'update', 'delete', 'export']),
                self::all('kir', ['view', 'create', 'update', 'delete', 'import', 'template.download', 'download', 'export']),
                self::all('stock_opname', ['view', 'create', 'update', 'import', 'template.download', 'stock.add', 'stock.adjust', 'export']),
                self::all('kategori_stock_opname', ['view', 'create', 'update', 'delete', 'export']),
                self::all('peminjaman', ['view', 'export']),
                self::all('pemeliharaan', ['view', 'download', 'export']),
                self::all('persetujuan', ['view', 'export']),
                self::all('usulan_pengadaan', ['view', 'export']),
                self::all('pengadaan', ['view', 'create', 'receive', 'export']),
                self::all('penghapusan', ['view', 'download', 'export']),
                self::all('laporan_aset', ['view', 'export']),
                $assetReports,
                self::all('users', ['view', 'create', 'update', 'delete', 'password.reset', 'export']),
                self::all('roles', ['view', 'create', 'update', 'delete', 'export']),
                self::all('pemegang_aset', ['view', 'create', 'update', 'delete', 'import', 'template.download', 'ktp.download', 'print', 'pdf']),
            ),
            'admin_bidang' => array_merge(
                self::all('aset', ['view']),
                self::all('permintaan', ['view', 'create', 'export']),
                self::all('peminjaman', ['view', 'create', 'export']),
                self::all('pemeliharaan', ['view', 'create', 'export']),
                self::all('usulan_pengadaan', ['view', 'create', 'export']),
            ),
            'kasubbag_umum_perencanaan' => array_merge(
                self::all('dashboard', ['view']),
                self::all('peminjaman', ['approve']),
                self::all('persetujuan', ['view', 'export']),
                self::all('penghapusan', ['view', 'approve', 'download', 'export']),
                self::reportPermissions([
                    'laporan_rekonsiliasi_bulanan', 'laporan_semester_1', 'laporan_tahunan',
                    'laporan_aset_unaudited', 'laporan_aset_audited', 'berita_acara_bmd',
                ], ['view', 'download']),
                self::all('pemegang_aset', ['view', 'print', 'pdf']),
            ),
            'tim_aset' => array_merge(
                self::all('aset', ['view', 'condition.update', 'verify']),
                self::all('kodefikasi', ['view']),
                self::all('kodefikasi_internal', ['view']),
                self::all('stock_opname', ['view', 'verify']),
                self::all('kategori_stock_opname', ['view']),
                self::all('peminjaman', ['view', 'verify', 'handover', 'return', 'export']),
                self::all('pemeliharaan', ['view', 'create', 'verify', 'update', 'delete', 'start', 'complete', 'report.update', 'download', 'export']),
                self::all('usulan_pengadaan', ['view', 'verify', 'export']),
                self::all('pengadaan', ['view', 'create', 'receive', 'verify', 'export']),
                self::all('penghapusan', ['view', 'create', 'download', 'export']),
                $assetReports,
                self::all('pemegang_aset', ['view', 'create', 'update', 'delete', 'import', 'template.download', 'ktp.download', 'print', 'pdf']),
            ),
            'timaset_persediaan', 'tim_aset_persediaan', 'aset-persediaan' => array_merge(
                self::all('stock_opname', ['view', 'verify', 'export']),
                self::all('permintaan', ['view', 'verify', 'handover', 'export']),
                $inventoryReports,
            ),
            'pimpinan' => ['dashboard.view'],
            // Super Admin is deliberately not populated: PermissionService grants its bypass.
            default => [],
        };
    }

    /** @param list<string> $actions @return list<array{kode: string, nama: string, grup: string}> */
    private static function module(string $module, string $group, array $actions): array
    {
        return array_map(fn (string $action): array => [
            'kode' => "{$module}.{$action}",
            'nama' => "{$group} — ".str($action)->replace('.', ' ')->headline(),
            'grup' => $group,
        ], $actions);
    }

    /** @return list<array{kode: string, nama: string, grup: string}> */
    private static function reportModule(string $module, string $group): array
    {
        return self::module($module, $group, ['view', 'upload', 'delete', 'download']);
    }

    /** @param list<string> $actions @return list<string> */
    private static function all(string $module, array $actions): array
    {
        return array_map(fn (string $action): string => "{$module}.{$action}", $actions);
    }

    /** @param list<string> $modules @param list<string> $actions @return list<string> */
    private static function reportPermissions(array $modules, array $actions = ['view', 'upload', 'delete', 'download']): array
    {
        return array_merge(...array_map(fn (string $module): array => self::all($module, $actions), $modules));
    }
}
