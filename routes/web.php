<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\AssetDisposalRequestController;
use App\Http\Controllers\AssetHolderController;
use App\Http\Controllers\AssetReportController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetMaintenanceController;
use App\Http\Controllers\AssetQrController;
use App\Http\Controllers\KirController;
use App\Http\Controllers\KodefikasiController;
use App\Http\Controllers\KodefikasiInternalController;
use App\Http\Controllers\KategoriStockOpnameController;
use App\Http\Controllers\LoanRequestController;
use App\Http\Controllers\PermintaanPersediaanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ProcurementProposalController;
use App\Http\Controllers\PublicAssetController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ReportDocumentController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

Route::get('/aset/scan/{publicToken}', [PublicAssetController::class, 'show'])
    ->name('public.aset.show');

Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('web.login');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('web.logout');

Route::get('/export/{dataset}/{format}', DataExportController::class)
    ->middleware('auth')
    ->whereIn('format', ['word', 'pdf', 'excel'])
    ->name('exports.download');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
});

Route::middleware(['auth', 'permission:kodefikasi.view'])->group(function () {
    Route::get('/kodefikasi', [KodefikasiController::class, 'index'])->name('kodefikasi.index');
});

Route::get('/kodefikasi-internal', [KodefikasiInternalController::class, 'index'])
    ->middleware(['auth', 'permission:kodefikasi_internal.view'])
    ->name('kodefikasi-internal.index');

Route::get('/kodefikasi/{type}/template', [KodefikasiController::class, 'template'])
    ->middleware(['auth', 'role:admin,super_admin,tim_aset'])
    ->name('kodefikasi.template');

Route::middleware(['auth', 'role:admin,super_admin'])->group(function () {
    Route::post('/kodefikasi-internal/bulk-generate', [KodefikasiInternalController::class, 'bulkGenerate'])->name('kodefikasi-internal.bulk-generate');
    Route::post('/kodefikasi-internal/{asset}/generate', [KodefikasiInternalController::class, 'generate'])->whereNumber('asset')->name('kodefikasi-internal.generate');
    Route::patch('/kodefikasi-internal/{asset}', [KodefikasiInternalController::class, 'update'])->whereNumber('asset')->name('kodefikasi-internal.update');
    Route::patch('/kodefikasi-internal/{asset}/reset', [KodefikasiInternalController::class, 'reset'])->whereNumber('asset')->name('kodefikasi-internal.reset');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/kodefikasi/{type}', [KodefikasiController::class, 'store'])->name('kodefikasi.store');
    Route::put('/kodefikasi/{type}/{id}', [KodefikasiController::class, 'update'])->whereNumber('id')->name('kodefikasi.update');
    Route::delete('/kodefikasi/{type}/{id}', [KodefikasiController::class, 'destroy'])->whereNumber('id')->name('kodefikasi.destroy');
    Route::post('/kodefikasi/{type}/impor', [KodefikasiController::class, 'import'])->name('kodefikasi.import');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'permission:dashboard.view'])
    ->name('dashboard');

Route::middleware(['auth', 'role:admin,super_admin,tim_aset,timaset_persediaan,tim_aset_persediaan'])->group(function () {
    Route::get('/stock-opname', [StockOpnameController::class, 'index'])->name('stock-opname.index');
});

Route::middleware(['auth', 'permission:kategori_stock_opname.view'])->group(function () {
    Route::get('/kategori-stock-opname', [KategoriStockOpnameController::class, 'index'])->name('kategori-stock-opname.index');
});

Route::middleware(['auth', 'permission:roles.view'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index'])->name('web.roles.index');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/stock-opname', [StockOpnameController::class, 'store'])->name('stock-opname.store');
    Route::get('/stock-opname/template-impor', [StockOpnameController::class, 'importTemplate'])->name('stock-opname.import-template');
    Route::post('/stock-opname/impor/preview', [StockOpnameController::class, 'importPreview'])->name('stock-opname.import-preview');
    Route::post('/stock-opname/impor', [StockOpnameController::class, 'import'])->name('stock-opname.import');
    Route::put('/stock-opname/{stockOpnameItem}', [StockOpnameController::class, 'update'])->name('stock-opname.update')->whereNumber('stockOpnameItem');
    Route::patch('/stock-opname/{stockOpnameItem}/tambah-stok', [StockOpnameController::class, 'addStock'])->name('stock-opname.add-stock')->whereNumber('stockOpnameItem');
    Route::patch('/stock-opname/{stockOpnameItem}/adjust', [StockOpnameController::class, 'adjust'])->name('stock-opname.adjust')->whereNumber('stockOpnameItem');
    Route::post('/kategori-stock-opname', [KategoriStockOpnameController::class, 'store'])->name('kategori-stock-opname.store');
    Route::put('/kategori-stock-opname/{kategoriStockOpname}', [KategoriStockOpnameController::class, 'update'])->name('kategori-stock-opname.update')->whereNumber('kategoriStockOpname');
    Route::delete('/kategori-stock-opname/{kategoriStockOpname}', [KategoriStockOpnameController::class, 'destroy'])->name('kategori-stock-opname.destroy')->whereNumber('kategoriStockOpname');
});

Route::patch('/stock-opname/{stockOpnameItem}/verify', [StockOpnameController::class, 'verify'])
    ->middleware(['auth', 'role:tim_aset,timaset_persediaan,tim_aset_persediaan'])
    ->name('stock-opname.verify')
    ->whereNumber('stockOpnameItem');

Route::delete('/stock-opname/{stockOpnameItem}', [StockOpnameController::class, 'destroy'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('stock-opname.destroy')
    ->whereNumber('stockOpnameItem');

Route::middleware(['auth', 'role:admin_bidang,timaset_persediaan,tim_aset_persediaan,super_admin'])->group(function () {
    Route::get('/permintaan-persediaan', [PermintaanPersediaanController::class, 'index'])->name('permintaan-persediaan.index');
});

Route::post('/permintaan-persediaan', [PermintaanPersediaanController::class, 'store'])
    ->middleware(['auth', 'role:admin_bidang,super_admin'])
    ->name('permintaan-persediaan.store');

Route::patch('/permintaan-persediaan/{permintaanPersediaan}/verifikasi', [PermintaanPersediaanController::class, 'verify'])
    ->middleware(['auth', 'role:timaset_persediaan,tim_aset_persediaan,super_admin'])
    ->name('permintaan-persediaan.verify')
    ->whereNumber('permintaanPersediaan');

Route::patch('/permintaan-persediaan/{permintaanPersediaan}/penyerahan', [PermintaanPersediaanController::class, 'handover'])
    ->middleware(['auth', 'role:timaset_persediaan,tim_aset_persediaan,super_admin'])
    ->name('permintaan-persediaan.handover')
    ->whereNumber('permintaanPersediaan');

Route::get('/super-admin', SuperAdminController::class)
    ->middleware(['auth', 'role:super_admin'])
    ->name('super-admin.index');

Route::get('/kategori-aset', [AssetCategoryController::class, 'index'])
    ->middleware(['auth', 'permission:kategori_aset.view'])
    ->name('web.kategori-aset.index');
Route::get('/ruangan', [RuanganController::class, 'index'])
    ->middleware(['auth', 'permission:ruangan.view'])
    ->name('ruangan.index');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/kategori-aset', [AssetCategoryController::class, 'store'])->name('web.kategori-aset.store');
    Route::put('/kategori-aset/{asset_category}', [AssetCategoryController::class, 'update'])->name('web.kategori-aset.update');
    Route::delete('/kategori-aset/{asset_category}', [AssetCategoryController::class, 'destroy'])->name('web.kategori-aset.destroy');

    Route::post('/ruangan', [RuanganController::class, 'store'])->name('ruangan.store');
    Route::put('/ruangan/{ruangan}', [RuanganController::class, 'update'])->name('ruangan.update')->whereNumber('ruangan');
    Route::delete('/ruangan/{ruangan}', [RuanganController::class, 'destroy'])->name('ruangan.destroy')->whereNumber('ruangan');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/kir', [KirController::class, 'index'])->name('kir.index');
    Route::get('/kir/{ruangan}/pdf', [KirController::class, 'pdf'])->name('kir.pdf')->whereNumber('ruangan');
    Route::get('/kir/{ruangan}/word', [KirController::class, 'word'])->name('kir.word')->whereNumber('ruangan');
    Route::get('/kir/{ruangan}', [KirController::class, 'show'])->name('kir.show')->whereNumber('ruangan');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/kir/{ruangan}', [KirController::class, 'store'])->name('kir.store')->whereNumber('ruangan');
    Route::post('/kir/{ruangan}/impor', [KirController::class, 'import'])->name('kir.import')->whereNumber('ruangan');
    Route::get('/kir/{ruangan}/template', [KirController::class, 'template'])->name('kir.template')->whereNumber('ruangan');
    Route::put('/kir/{ruangan}/{detailKir}', [KirController::class, 'update'])->name('kir.update')->whereNumber('ruangan')->whereNumber('detailKir');
    Route::delete('/kir/{ruangan}/{detailKir}', [KirController::class, 'destroy'])->name('kir.destroy')->whereNumber('ruangan')->whereNumber('detailKir');
});

Route::middleware(['auth', 'permission:aset.view'])->group(function () {
    Route::get('/aset', [AssetController::class, 'index'])->name('web.aset.index');
    Route::get('/aset/{asset}', [AssetController::class, 'show'])->name('web.aset.show')->whereNumber('asset');
    Route::get('/aset/{asset}/qr.svg', [AssetQrController::class, 'svg'])->name('web.aset.qr.svg')->whereNumber('asset');
    Route::patch('/aset/{asset}/kondisi', [AssetController::class, 'updateCondition'])
        ->middleware('role:admin,tim_aset')
        ->name('web.aset.kondisi');

    Route::middleware('role:admin')->group(function () {
        Route::post('/aset', [AssetController::class, 'store'])->name('web.aset.store');
        Route::put('/aset/{asset}', [AssetController::class, 'update'])->name('web.aset.update')->whereNumber('asset');
        Route::delete('/aset/{asset}', [AssetController::class, 'destroy'])->name('web.aset.destroy')->whereNumber('asset');
        Route::get('/aset/template-impor', [AssetController::class, 'importTemplate'])->name('web.aset.template-impor');
        Route::post('/aset/impor', [AssetController::class, 'import'])->name('web.aset.impor');
    });

    Route::middleware('role:tim_aset')->group(function () {
        Route::get('/verifikasi-barang', [AssetController::class, 'verification'])->name('web.aset.verifikasi.index');
        Route::patch('/aset/{asset}/verifikasi', [AssetController::class, 'verify'])->name('web.aset.verifikasi')->whereNumber('asset');
    });
});

Route::middleware(['auth', 'role:admin,admin_bidang'])->group(function () {
    Route::get('/peminjaman', [LoanRequestController::class, 'index'])->name('web.peminjaman.index');
    Route::get('/peminjaman/{loanRequest}', [LoanRequestController::class, 'show'])->name('web.peminjaman.show')->whereNumber('loanRequest');
});

Route::get('/verifikasi-peminjaman', [LoanRequestController::class, 'verification'])
    ->middleware(['auth', 'role:tim_aset'])
    ->name('web.peminjaman.verifikasi.index');

Route::post('/peminjaman', [LoanRequestController::class, 'store'])
    ->middleware(['auth', 'role:admin_bidang'])
    ->name('web.peminjaman.store');

Route::patch('/peminjaman/{loanRequest}/keputusan', [LoanRequestController::class, 'decide'])
    ->middleware(['auth', 'role:tim_aset,kasubbag_umum_perencanaan'])
    ->name('web.peminjaman.keputusan')
    ->whereNumber('loanRequest');

Route::patch('/peminjaman/{loanRequest}/penyerahan', [LoanRequestController::class, 'handOver'])
    ->middleware(['auth', 'role:tim_aset'])
    ->name('web.peminjaman.penyerahan')
    ->whereNumber('loanRequest');

Route::patch('/peminjaman/{loanRequest}/pengembalian', [LoanRequestController::class, 'receiveReturn'])
    ->middleware(['auth', 'role:tim_aset'])
    ->name('web.peminjaman.pengembalian')
    ->whereNumber('loanRequest');

Route::delete('/peminjaman/{loanRequest}', [LoanRequestController::class, 'destroy'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('web.peminjaman.destroy')
    ->whereNumber('loanRequest');

Route::middleware(['auth', 'role:admin,admin_bidang,tim_aset,super_admin'])->group(function () {
    Route::get('/pemeliharaan-aset', [AssetMaintenanceController::class, 'index'])->name('pemeliharaan-aset.index');
    Route::get('/pemeliharaan-aset/{assetMaintenance}/berita-acara', [AssetMaintenanceController::class, 'pdf'])->name('pemeliharaan-aset.pdf')->whereNumber('assetMaintenance');
    Route::get('/pemeliharaan-aset/{assetMaintenance}/berita-acara/word', [AssetMaintenanceController::class, 'word'])->name('pemeliharaan-aset.word')->whereNumber('assetMaintenance');
    Route::get('/pemeliharaan-aset/dokumen/{document}', [AssetMaintenanceController::class, 'downloadDocument'])->name('pemeliharaan-aset.document')->whereNumber('document');
});

Route::post('/pemeliharaan-aset', [AssetMaintenanceController::class, 'store'])
    ->middleware(['auth', 'role:admin_bidang,tim_aset,super_admin'])
    ->name('pemeliharaan-aset.store');

Route::patch('/pemeliharaan-aset/{assetMaintenance}/verifikasi', [AssetMaintenanceController::class, 'verify'])
    ->middleware(['auth', 'role:tim_aset,super_admin'])
    ->name('pemeliharaan-aset.verify')
    ->whereNumber('assetMaintenance');

Route::patch('/pemeliharaan-aset/{assetMaintenance}', [AssetMaintenanceController::class, 'update'])
    ->middleware(['auth', 'role:tim_aset,super_admin'])
    ->name('pemeliharaan-aset.update')
    ->whereNumber('assetMaintenance');

Route::delete('/pemeliharaan-aset/{assetMaintenance}', [AssetMaintenanceController::class, 'destroy'])
    ->middleware(['auth', 'role:tim_aset,super_admin'])
    ->name('pemeliharaan-aset.destroy')
    ->whereNumber('assetMaintenance');

Route::patch('/pemeliharaan-aset/{assetMaintenance}/mulai', [AssetMaintenanceController::class, 'start'])
    ->middleware(['auth', 'role:tim_aset,super_admin'])
    ->name('pemeliharaan-aset.start')
    ->whereNumber('assetMaintenance');

Route::patch('/pemeliharaan-aset/{assetMaintenance}/selesai', [AssetMaintenanceController::class, 'complete'])
    ->middleware(['auth', 'role:tim_aset,super_admin'])
    ->name('pemeliharaan-aset.complete')
    ->whereNumber('assetMaintenance');

Route::patch('/pemeliharaan-aset/{assetMaintenance}/berita-acara', [AssetMaintenanceController::class, 'updateReportData'])
    ->middleware(['auth', 'role:tim_aset,super_admin'])
    ->name('pemeliharaan-aset.report-data')
    ->whereNumber('assetMaintenance');

Route::middleware(['auth', 'role:admin,admin_bidang,tim_aset'])->group(function () {
    Route::get('/usulan-pengadaan', [ProcurementProposalController::class, 'index'])->name('web.usulan-pengadaan.index');
    Route::get('/usulan-pengadaan/{procurementProposal}', [ProcurementProposalController::class, 'show'])
        ->name('web.usulan-pengadaan.show')
        ->whereNumber('procurementProposal');
});

Route::post('/usulan-pengadaan', [ProcurementProposalController::class, 'store'])
    ->middleware(['auth', 'role:admin_bidang'])
    ->name('web.usulan-pengadaan.store');

Route::patch('/usulan-pengadaan/{procurementProposal}/keputusan-tim-aset', [ProcurementProposalController::class, 'assetTeamDecision'])
    ->middleware(['auth', 'role:tim_aset'])
    ->name('web.usulan-pengadaan.keputusan-tim-aset')
    ->whereNumber('procurementProposal');

Route::patch('/usulan-pengadaan/{procurementProposal}/keputusan-kasubbag', [ProcurementProposalController::class, 'supervisorDecision'])
    ->middleware(['auth', 'role:kasubbag_umum_perencanaan'])
    ->name('web.usulan-pengadaan.keputusan-kasubbag')
    ->whereNumber('procurementProposal');

Route::middleware(['auth', 'role:admin,tim_aset'])->group(function () {
    Route::get('/pengadaan', [ProcurementController::class, 'index'])->name('pengadaan.index');
    Route::get('/pengadaan/{procurement}', [ProcurementController::class, 'show'])->name('pengadaan.show')->whereNumber('procurement');
});

Route::post('/pengadaan', [ProcurementController::class, 'store'])
    ->middleware(['auth', 'role:admin,tim_aset'])
    ->name('pengadaan.store');

Route::patch('/pengadaan/{procurement}/penerimaan', [ProcurementController::class, 'receive'])
    ->middleware(['auth', 'role:admin,tim_aset'])
    ->name('pengadaan.penerimaan')
    ->whereNumber('procurement');

Route::patch('/pengadaan/{procurement}/verifikasi', [ProcurementController::class, 'verify'])
    ->middleware(['auth', 'role:tim_aset'])
    ->name('pengadaan.verifikasi')
    ->whereNumber('procurement');

Route::middleware(['auth', 'role:admin,tim_aset,kasubbag_umum_perencanaan'])->group(function () {
    Route::get('/penghapusan-aset', [AssetDisposalRequestController::class, 'index'])->name('penghapusan-aset.index');
    Route::get('/penghapusan-aset/aset/{asset}/bukti', [AssetDisposalRequestController::class, 'assetEvidence'])
        ->name('penghapusan-aset.asset-evidence')
        ->whereNumber('asset');
    Route::get('/penghapusan-aset/bukti/{evidence}', [AssetDisposalRequestController::class, 'downloadEvidence'])
        ->name('penghapusan-aset.bukti')
        ->whereNumber('evidence');
    Route::get('/penghapusan-aset/bukti/{evidence}/preview', [AssetDisposalRequestController::class, 'previewEvidence'])
        ->name('penghapusan-aset.bukti.preview')
        ->whereNumber('evidence');
    Route::get('/penghapusan-aset/{assetDisposalRequest}', [AssetDisposalRequestController::class, 'show'])
        ->name('penghapusan-aset.show')
        ->whereNumber('assetDisposalRequest');
    Route::get('/penghapusan-aset/{assetDisposalRequest}/dokumen', [AssetDisposalRequestController::class, 'downloadDocument'])
        ->name('penghapusan-aset.dokumen')
        ->whereNumber('assetDisposalRequest');
    Route::get('/penghapusan-aset/{assetDisposalRequest}/dokumen/preview', [AssetDisposalRequestController::class, 'previewDocument'])
        ->name('penghapusan-aset.dokumen.preview')
        ->whereNumber('assetDisposalRequest');
});

Route::post('/penghapusan-aset', [AssetDisposalRequestController::class, 'store'])
    ->middleware(['auth', 'role:tim_aset'])
    ->name('penghapusan-aset.store');

Route::patch('/penghapusan-aset/{assetDisposalRequest}/keputusan', [AssetDisposalRequestController::class, 'decide'])
    ->middleware(['auth', 'role:kasubbag_umum_perencanaan'])
    ->name('penghapusan-aset.keputusan')
    ->whereNumber('assetDisposalRequest');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/laporan', [AssetReportController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/aset/pdf', [AssetReportController::class, 'pdf'])->name('laporan.aset.pdf');
    Route::get('/laporan/aset/xlsx', [AssetReportController::class, 'xlsx'])->name('laporan.aset.xlsx');
});

Route::middleware(['auth', 'role:admin,super_admin,tim_aset,kasubbag_umum_perencanaan,timaset_persediaan,tim_aset_persediaan,aset-persediaan'])->group(function () {
    Route::get('/laporan-dokumen/{jenis}', [ReportDocumentController::class, 'index'])
        ->name('laporan-dokumen.index');
    Route::post('/laporan-dokumen/{jenis}', [ReportDocumentController::class, 'store'])
        ->name('laporan-dokumen.store');
    Route::patch('/laporan-dokumen/file/{document}/berita-acara', [ReportDocumentController::class, 'completeBeritaAcara'])
        ->name('laporan-dokumen.berita-acara.complete')
        ->whereNumber('document');
    Route::get('/laporan-dokumen/file/{document}/preview', [ReportDocumentController::class, 'preview'])
        ->name('laporan-dokumen.preview')
        ->whereNumber('document');
    Route::get('/laporan-dokumen/file/{document}/download', [ReportDocumentController::class, 'download'])
        ->name('laporan-dokumen.download')
        ->whereNumber('document');
    Route::delete('/laporan-dokumen/file/{document}', [ReportDocumentController::class, 'destroy'])
        ->name('laporan-dokumen.destroy')
        ->whereNumber('document');
});

Route::middleware(['auth', 'role:admin,super_admin,tim_aset,kasubbag_umum_perencanaan'])->group(function () {
    Route::get('/pemegang-aset', [AssetHolderController::class, 'index'])->name('pemegang-aset.index');
    Route::get('/pemegang-aset/template-impor', [AssetHolderController::class, 'importTemplate'])
        ->middleware('role:admin,super_admin,tim_aset')
        ->name('pemegang-aset.template-impor');
    Route::get('/pemegang-aset/{assetHolder}/ktp', [AssetHolderController::class, 'downloadKtp'])
        ->middleware('role:admin,super_admin,tim_aset')
        ->name('pemegang-aset.ktp')
        ->whereNumber('assetHolder');
    Route::get('/pemegang-aset/{assetHolder}/cetak', [AssetHolderController::class, 'print'])
        ->name('pemegang-aset.print')
        ->whereNumber('assetHolder');
    Route::get('/pemegang-aset/{assetHolder}/pdf', [AssetHolderController::class, 'pdf'])
        ->name('pemegang-aset.pdf')
        ->whereNumber('assetHolder');

    Route::middleware('role:admin,super_admin,tim_aset')->group(function () {
        Route::post('/pemegang-aset', [AssetHolderController::class, 'store'])->name('pemegang-aset.store');
        Route::post('/pemegang-aset/impor/preview', [AssetHolderController::class, 'importPreview'])->name('pemegang-aset.import.preview');
        Route::post('/pemegang-aset/impor/confirm', [AssetHolderController::class, 'importConfirm'])->name('pemegang-aset.import.confirm');
        Route::put('/pemegang-aset/{assetHolder}', [AssetHolderController::class, 'update'])->name('pemegang-aset.update')->whereNumber('assetHolder');
        Route::delete('/pemegang-aset/{assetHolder}', [AssetHolderController::class, 'destroy'])->name('pemegang-aset.destroy')->whereNumber('assetHolder');
    });
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('web.users.index');
    Route::post('/users', [UserController::class, 'store'])->name('web.users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('web.users.update')->whereNumber('user');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('web.users.destroy')->whereNumber('user');
    Route::patch('/users/{user}/password', [UserController::class, 'resetPassword'])->name('web.users.password')->whereNumber('user');

});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::post('/roles', [RoleController::class, 'store'])->name('web.roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('web.roles.update')->whereNumber('role');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('web.roles.destroy')->whereNumber('role');
});

Route::get('/persetujuan', ApprovalController::class)
    ->middleware(['auth', 'role:admin,kasubbag_umum_perencanaan'])
    ->name('persetujuan.index');

Route::delete('/persetujuan/{type}/{id}', [ApprovalController::class, 'destroy'])
    ->middleware(['auth', 'role:super_admin'])
    ->whereIn('type', ['loan', 'procurement', 'disposal'])
    ->whereNumber('id')
    ->name('persetujuan.destroy');
