<?php

namespace App\Http\Controllers;

use App\Services\DataExportService;
use App\Support\CategoryAssetExportPermissions;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DataExportController extends Controller
{
    private const DATASET_PERMISSIONS = [
        'aset' => 'aset.export', 'stock-opname' => 'stock_opname.export',
        'permintaan-persediaan' => 'permintaan.export', 'peminjaman' => 'peminjaman.export',
        'pemeliharaan-aset' => 'pemeliharaan.export', 'usulan-pengadaan' => 'usulan_pengadaan.export',
        'pengadaan' => 'pengadaan.export', 'penghapusan-aset' => 'penghapusan.export',
        'persetujuan' => 'persetujuan.export', 'kategori-aset' => 'kategori_aset.export',
        'kategori-stock-opname' => 'kategori_stock_opname.export', 'ruangan' => 'ruangan.export',
        'kir' => 'kir.export', 'kir-detail' => 'kir.export', 'users' => 'users.export',
        'roles' => 'roles.export', 'kodefikasi-barang' => 'kodefikasi.export',
        'kodefikasi-persediaan' => 'kodefikasi.export', 'kodefikasi-lokasi' => 'kodefikasi.export',
        'kodefikasi-internal' => 'kodefikasi_internal.export', 'laporan-aset' => 'laporan_aset.export',
    ];

    public function __invoke(Request $request, DataExportService $service, string $dataset, string $format): Response
    {
        abort_unless(in_array($format, ['word', 'pdf', 'excel'], true), 404);
        $permission = $dataset === 'kategori-aset'
            ? CategoryAssetExportPermissions::permissionForFormat($format)
            : self::DATASET_PERMISSIONS[$dataset] ?? null;
        abort_unless($permission && $request->user()?->hasPermission($permission), 403, 'Anda tidak memiliki hak akses untuk ekspor ini.');

        if (in_array($dataset, ['users', 'roles'], true)) {
            abort_unless($request->user()?->role === 'super_admin', 403, 'Ekspor data sensitif hanya untuk Super Admin.');
        }

        return $service->download($request, $dataset, $format);
    }
}
