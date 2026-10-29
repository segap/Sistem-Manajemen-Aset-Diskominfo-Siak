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
    ->middleware(['auth', 'permission:kodefikasi.import'])
    ->where('type', 'kode-barang|kode-persediaan|kode-lokasi')
    ->name('kodefikasi.template');

Route::middleware(['auth'])->group(function () {
    Route::post('/kodefikasi-internal/bulk-generate', [KodefikasiInternalController::class, 'bulkGenerate'])
        ->middleware('permission:kodefikasi_internal.bulk_generate')
        ->name('kodefikasi-internal.bulk-generate');
    Route::post('/kodefikasi-internal/{asset}/generate', [KodefikasiInternalController::class, 'generate'])
        ->middleware('permission:kodefikasi_internal.generate')
        ->whereNumber('asset')
        ->name('kodefikasi-internal.generate');
});

Route::middleware(['auth'])->group(function () {
    Route::patch('/kodefikasi-internal/{asset}', [KodefikasiInternalController::class, 'update'])
        ->middleware('permission:kodefikasi_internal.update')
        ->whereNumber('asset')
        ->name('kodefikasi-internal.update');
    Route::patch('/kodefikasi-internal/{asset}/reset', [KodefikasiInternalController::class, 'reset'])
        ->middleware('permission:kodefikasi_internal.reset')
        ->whereNumber('asset')
        ->name('kodefikasi-internal.reset');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/kodefikasi/{type}', [KodefikasiController::class, 'store'])
        ->middleware('permission:kodefikasi.create')
        ->where('type', 'kode-barang|kode-persediaan|kode-lokasi')
        ->name('kodefikasi.store');
    Route::put('/kodefikasi/{type}/{id}', [KodefikasiController::class, 'update'])
        ->middleware('permission:kodefikasi.update')
        ->where('type', 'kode-barang|kode-persediaan|kode-lokasi')
        ->whereNumber('id')
        ->name('kodefikasi.update');
    Route::delete('/kodefikasi/{type}/{id}', [KodefikasiController::class, 'destroy'])
        ->middleware('permission:kodefikasi.delete')
        ->where('type', 'kode-barang|kode-persediaan|kode-lokasi')
        ->whereNumber('id')
        ->name('kodefikasi.destroy');
    Route::post('/kodefikasi/{type}/impor', [KodefikasiController::class, 'import'])
        ->middleware('permission:kodefikasi.import')
        ->where('type', 'kode-barang|kode-persediaan|kode-lokasi')
        ->name('kodefikasi.import');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'permission:dashboard.view'])
    ->name('dashboard');

Route::get('/stock-opname', [StockOpnameController::class, 'index'])
    ->middleware(['auth', 'permission:stock_opname.view'])
    ->name('stock-opname.index');

Route::middleware(['auth', 'permission:kategori_stock_opname.view'])->group(function () {
    Route::get('/kategori-stock-opname', [KategoriStockOpnameController::class, 'index'])->name('kategori-stock-opname.index');
});

Route::middleware(['auth', 'permission:roles.view'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index'])->name('web.roles.index');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/stock-opname', [StockOpnameController::class, 'store'])->middleware('permission:stock_opname.create')->name('stock-opname.store');
    Route::get('/stock-opname/template-impor', [StockOpnameController::class, 'importTemplate'])->middleware('permission:stock_opname.template.download')->name('stock-opname.import-template');
    Route::post('/stock-opname/impor/preview', [StockOpnameController::class, 'importPreview'])->middleware('permission:stock_opname.import')->name('stock-opname.import-preview');
    Route::post('/stock-opname/impor', [StockOpnameController::class, 'import'])->middleware('permission:stock_opname.import')->name('stock-opname.import');
    Route::put('/stock-opname/{stockOpnameItem}', [StockOpnameController::class, 'update'])->middleware('permission:stock_opname.update')->name('stock-opname.update')->whereNumber('stockOpnameItem');
    Route::patch('/stock-opname/{stockOpnameItem}/tambah-stok', [StockOpnameController::class, 'addStock'])->middleware('permission:stock_opname.stock.add')->name('stock-opname.add-stock')->whereNumber('stockOpnameItem');
    Route::patch('/stock-opname/{stockOpnameItem}/adjust', [StockOpnameController::class, 'adjust'])->middleware('permission:stock_opname.stock.adjust')->name('stock-opname.adjust')->whereNumber('stockOpnameItem');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/kategori-stock-opname', [KategoriStockOpnameController::class, 'store'])->middleware('permission:kategori_stock_opname.create')->name('kategori-stock-opname.store');
    Route::put('/kategori-stock-opname/{kategoriStockOpname}', [KategoriStockOpnameController::class, 'update'])->middleware('permission:kategori_stock_opname.update')->name('kategori-stock-opname.update')->whereNumber('kategoriStockOpname');
    Route::delete('/kategori-stock-opname/{kategoriStockOpname}', [KategoriStockOpnameController::class, 'destroy'])->middleware('permission:kategori_stock_opname.delete')->name('kategori-stock-opname.destroy')->whereNumber('kategoriStockOpname');
});

Route::delete('/stock-opname/{stockOpnameItem}', [StockOpnameController::class, 'destroy'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('stock-opname.destroy')
    ->whereNumber('stockOpnameItem');

Route::get('/permintaan-persediaan', [PermintaanPersediaanController::class, 'index'])
    ->middleware(['auth', 'permission:permintaan.view'])
    ->name('permintaan-persediaan.index');

Route::post('/permintaan-persediaan', [PermintaanPersediaanController::class, 'store'])
    ->middleware(['auth', 'permission:permintaan.create'])
    ->name('permintaan-persediaan.store');

Route::patch('/permintaan-persediaan/{permintaanPersediaan}/verifikasi', [PermintaanPersediaanController::class, 'verify'])
    ->middleware(['auth', 'permission:permintaan.verify'])
    ->name('permintaan-persediaan.verify')
    ->whereNumber('permintaanPersediaan');

Route::patch('/permintaan-persediaan/{permintaanPersediaan}/penyerahan', [PermintaanPersediaanController::class, 'handover'])
    ->middleware(['auth', 'permission:permintaan.handover'])
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

Route::middleware(['auth'])->group(function () {
    Route::post('/kategori-aset', [AssetCategoryController::class, 'store'])
        ->middleware('permission:kategori_aset.create')
        ->name('web.kategori-aset.store');
    Route::put('/kategori-aset/{asset_category}', [AssetCategoryController::class, 'update'])
        ->middleware('permission:kategori_aset.update')
        ->name('web.kategori-aset.update');
    Route::delete('/kategori-aset/{asset_category}', [AssetCategoryController::class, 'destroy'])
        ->middleware('permission:kategori_aset.delete')
        ->name('web.kategori-aset.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/ruangan', [RuanganController::class, 'store'])
        ->middleware('permission:ruangan.create')
        ->name('ruangan.store');
    Route::put('/ruangan/{ruangan}', [RuanganController::class, 'update'])
        ->middleware('permission:ruangan.update')
        ->whereNumber('ruangan')
        ->name('ruangan.update');
    Route::delete('/ruangan/{ruangan}', [RuanganController::class, 'destroy'])
        ->middleware('permission:ruangan.delete')
        ->whereNumber('ruangan')
        ->name('ruangan.destroy');
});

Route::middleware(['auth', 'permission:kir.view'])->group(function () {
    Route::get('/kir', [KirController::class, 'index'])->name('kir.index');
    Route::get('/kir/{ruangan}', [KirController::class, 'show'])->name('kir.show')->whereNumber('ruangan');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/kir/{ruangan}/pdf', [KirController::class, 'pdf'])->middleware('permission:kir.download')->name('kir.pdf')->whereNumber('ruangan');
    Route::get('/kir/{ruangan}/word', [KirController::class, 'word'])->middleware('permission:kir.download')->name('kir.word')->whereNumber('ruangan');
});

Route::middleware(['auth', 'permission:laporan_aset.export'])->group(function () {
    Route::post('/kir/sinkronisasi', [KirController::class, 'synchronize'])->name('kir.synchronize');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/kir/{ruangan}', [KirController::class, 'store'])->middleware('permission:kir.create')->name('kir.store')->whereNumber('ruangan');
    Route::post('/kir/{ruangan}/impor', [KirController::class, 'import'])->middleware('permission:kir.import')->name('kir.import')->whereNumber('ruangan');
    Route::get('/kir/{ruangan}/template', [KirController::class, 'template'])->middleware('permission:kir.template.download')->name('kir.template')->whereNumber('ruangan');
    Route::put('/kir/{ruangan}/{detailKir}', [KirController::class, 'update'])->middleware('permission:kir.update')->name('kir.update')->whereNumber('ruangan')->whereNumber('detailKir');
    Route::delete('/kir/{ruangan}/{detailKir}', [KirController::class, 'destroy'])->middleware('permission:kir.delete')->name('kir.destroy')->whereNumber('ruangan')->whereNumber('detailKir');
});

Route::middleware(['auth', 'permission:aset.view'])->group(function () {
    Route::get('/aset', [AssetController::class, 'index'])->name('web.aset.index');
    Route::get('/aset/{asset}', [AssetController::class, 'show'])->name('web.aset.show')->whereNumber('asset');
    Route::get('/aset/{asset}/qr.svg', [AssetQrController::class, 'svg'])->name('web.aset.qr.svg')->whereNumber('asset');
    Route::patch('/aset/{asset}/kondisi', [AssetController::class, 'updateCondition'])
        ->middleware('permission:aset.condition.update')
        ->name('web.aset.kondisi');

    Route::post('/aset', [AssetController::class, 'store'])
        ->middleware('permission:aset.create')
        ->name('web.aset.store');
    Route::put('/aset/{asset}', [AssetController::class, 'update'])
        ->middleware('permission:aset.update')
        ->name('web.aset.update')
        ->whereNumber('asset');

    Route::delete('/aset/{asset}', [AssetController::class, 'destroy'])
        ->middleware('permission:aset.delete')
        ->name('web.aset.destroy')
        ->whereNumber('asset');
    Route::get('/aset/template-impor', [AssetController::class, 'importTemplate'])
        ->middleware('permission:aset.import')
        ->name('web.aset.template-impor');
    Route::post('/aset/impor', [AssetController::class, 'import'])
        ->middleware('permission:aset.import')
        ->name('web.aset.impor');

    Route::middleware('role:tim_aset')->group(function () {
        Route::get('/verifikasi-barang', [AssetController::class, 'verification'])->name('web.aset.verifikasi.index');
        Route::patch('/aset/{asset}/verifikasi', [AssetController::class, 'verify'])->name('web.aset.verifikasi')->whereNumber('asset');
    });
});

Route::middleware(['auth', 'permission:peminjaman.view'])->group(function () {
    Route::get('/peminjaman', [LoanRequestController::class, 'index'])->name('web.peminjaman.index');
    Route::get('/peminjaman/{loanRequest}', [LoanRequestController::class, 'show'])->name('web.peminjaman.show')->whereNumber('loanRequest');
});

Route::get('/verifikasi-peminjaman', [LoanRequestController::class, 'verification'])
    ->middleware(['auth', 'permission:peminjaman.verify'])
    ->name('web.peminjaman.verifikasi.index');

Route::post('/peminjaman', [LoanRequestController::class, 'store'])
    ->middleware(['auth', 'permission:peminjaman.create'])
    ->name('web.peminjaman.store');

Route::patch('/peminjaman/{loanRequest}/keputusan', [LoanRequestController::class, 'decide'])
    ->middleware('auth')
    ->name('web.peminjaman.keputusan')
    ->whereNumber('loanRequest');

Route::patch('/peminjaman/{loanRequest}/penyerahan', [LoanRequestController::class, 'handOver'])
    ->middleware(['auth', 'permission:peminjaman.handover'])
    ->name('web.peminjaman.penyerahan')
    ->whereNumber('loanRequest');

Route::patch('/peminjaman/{loanRequest}/pengembalian', [LoanRequestController::class, 'receiveReturn'])
    ->middleware(['auth', 'permission:peminjaman.return'])
    ->name('web.peminjaman.pengembalian')
    ->whereNumber('loanRequest');

Route::delete('/peminjaman/{loanRequest}', [LoanRequestController::class, 'destroy'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('web.peminjaman.destroy')
    ->whereNumber('loanRequest');

Route::middleware(['auth', 'permission:pemeliharaan.download'])->group(function () {
    Route::get('/pemeliharaan-aset/{assetMaintenance}/berita-acara', [AssetMaintenanceController::class, 'pdf'])->name('pemeliharaan-aset.pdf')->whereNumber('assetMaintenance');
    Route::get('/pemeliharaan-aset/{assetMaintenance}/berita-acara/word', [AssetMaintenanceController::class, 'word'])->name('pemeliharaan-aset.word')->whereNumber('assetMaintenance');
    Route::get('/pemeliharaan-aset/dokumen/{document}', [AssetMaintenanceController::class, 'downloadDocument'])->name('pemeliharaan-aset.document')->whereNumber('document');
});

Route::get('/pemeliharaan-aset', [AssetMaintenanceController::class, 'index'])
    ->middleware(['auth', 'permission:pemeliharaan.view'])
    ->name('pemeliharaan-aset.index');

Route::post('/pemeliharaan-aset', [AssetMaintenanceController::class, 'store'])
    ->middleware(['auth', 'permission:pemeliharaan.create'])
    ->name('pemeliharaan-aset.store');

Route::patch('/pemeliharaan-aset/{assetMaintenance}/verifikasi', [AssetMaintenanceController::class, 'verify'])
    ->middleware(['auth', 'permission:pemeliharaan.verify'])
    ->name('pemeliharaan-aset.verify')
    ->whereNumber('assetMaintenance');

Route::patch('/pemeliharaan-aset/{assetMaintenance}', [AssetMaintenanceController::class, 'update'])
    ->middleware(['auth', 'permission:pemeliharaan.update'])
    ->name('pemeliharaan-aset.update')
    ->whereNumber('assetMaintenance');

Route::delete('/pemeliharaan-aset/{assetMaintenance}', [AssetMaintenanceController::class, 'destroy'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('pemeliharaan-aset.destroy')
    ->whereNumber('assetMaintenance');

Route::patch('/pemeliharaan-aset/{assetMaintenance}/mulai', [AssetMaintenanceController::class, 'start'])
    ->middleware(['auth', 'permission:pemeliharaan.start'])
    ->name('pemeliharaan-aset.start')
    ->whereNumber('assetMaintenance');

Route::patch('/pemeliharaan-aset/{assetMaintenance}/selesai', [AssetMaintenanceController::class, 'complete'])
    ->middleware(['auth', 'permission:pemeliharaan.complete'])
    ->name('pemeliharaan-aset.complete')
    ->whereNumber('assetMaintenance');

Route::patch('/pemeliharaan-aset/{assetMaintenance}/berita-acara', [AssetMaintenanceController::class, 'updateReportData'])
    ->middleware(['auth', 'permission:pemeliharaan.report.update'])
    ->name('pemeliharaan-aset.report-data')
    ->whereNumber('assetMaintenance');

Route::middleware(['auth', 'permission:usulan_pengadaan.view'])->group(function () {
    Route::get('/usulan-pengadaan', [ProcurementProposalController::class, 'index'])->name('web.usulan-pengadaan.index');
    Route::get('/usulan-pengadaan/{procurementProposal}', [ProcurementProposalController::class, 'show'])
        ->name('web.usulan-pengadaan.show')
        ->whereNumber('procurementProposal');
    Route::get('/usulan-pengadaan/{procurementProposal}/download-pdf', [ProcurementProposalController::class, 'pdf'])
        ->name('web.usulan-pengadaan.pdf')
        ->whereNumber('procurementProposal');
});

Route::post('/usulan-pengadaan', [ProcurementProposalController::class, 'store'])
    ->middleware(['auth', 'permission:usulan_pengadaan.create'])
    ->name('web.usulan-pengadaan.store');

Route::patch('/usulan-pengadaan/{procurementProposal}', [ProcurementProposalController::class, 'update'])
    ->middleware('auth')
    ->name('web.usulan-pengadaan.update')
    ->whereNumber('procurementProposal');

Route::delete('/usulan-pengadaan/{procurementProposal}', [ProcurementProposalController::class, 'destroy'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('web.usulan-pengadaan.destroy')
    ->whereNumber('procurementProposal');

Route::patch('/usulan-pengadaan/{procurementProposal}/keputusan-tim-aset', [ProcurementProposalController::class, 'assetTeamDecision'])
    ->middleware(['auth', 'permission:usulan_pengadaan.verify'])
    ->name('web.usulan-pengadaan.keputusan-tim-aset')
    ->whereNumber('procurementProposal');

Route::patch('/usulan-pengadaan/{procurementProposal}/keputusan-kasubbag', [ProcurementProposalController::class, 'supervisorDecision'])
    ->middleware(['auth', 'role:kasubbag_umum_perencanaan'])
    ->name('web.usulan-pengadaan.keputusan-kasubbag')
    ->whereNumber('procurementProposal');

Route::middleware(['auth', 'permission:penghapusan.view'])->group(function () {
    Route::get('/penghapusan-aset', [AssetDisposalRequestController::class, 'index'])->name('penghapusan-aset.index');
    Route::get('/penghapusan-aset/aset/{asset}/bukti', [AssetDisposalRequestController::class, 'assetEvidence'])
        ->name('penghapusan-aset.asset-evidence')
        ->whereNumber('asset');
    Route::get('/penghapusan-aset/bukti/{evidence}/preview', [AssetDisposalRequestController::class, 'previewEvidence'])
        ->name('penghapusan-aset.bukti.preview')
        ->whereNumber('evidence');
    Route::get('/penghapusan-aset/{assetDisposalRequest}', [AssetDisposalRequestController::class, 'show'])
        ->name('penghapusan-aset.show')
        ->whereNumber('assetDisposalRequest');
    Route::get('/penghapusan-aset/{assetDisposalRequest}/dokumen/preview', [AssetDisposalRequestController::class, 'previewDocument'])
        ->name('penghapusan-aset.dokumen.preview')
        ->whereNumber('assetDisposalRequest');
});

Route::middleware(['auth', 'permission:penghapusan.download'])->group(function () {
    Route::get('/penghapusan-aset/bukti/{evidence}', [AssetDisposalRequestController::class, 'downloadEvidence'])
        ->name('penghapusan-aset.bukti')
        ->whereNumber('evidence');
    Route::get('/penghapusan-aset/{assetDisposalRequest}/dokumen', [AssetDisposalRequestController::class, 'downloadDocument'])
        ->name('penghapusan-aset.dokumen')
        ->whereNumber('assetDisposalRequest');
});

Route::post('/penghapusan-aset', [AssetDisposalRequestController::class, 'store'])
    ->middleware(['auth', 'permission:penghapusan.create'])
    ->name('penghapusan-aset.store');

Route::patch('/penghapusan-aset/{assetDisposalRequest}/keputusan', [AssetDisposalRequestController::class, 'decide'])
    ->middleware(['auth', 'permission:penghapusan.approve'])
    ->name('penghapusan-aset.keputusan')
    ->whereNumber('assetDisposalRequest');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/laporan/aset/pdf', [AssetReportController::class, 'pdf'])->name('laporan.aset.pdf');
    Route::get('/laporan/aset/xlsx', [AssetReportController::class, 'xlsx'])->name('laporan.aset.xlsx');
});

Route::get('/laporan', [AssetReportController::class, 'index'])
    ->middleware(['auth', 'permission:laporan_aset.view'])
    ->name('laporan.index');

Route::middleware(['auth'])->group(function () {
    Route::get('/laporan-dokumen/{jenis}/export-word', [ReportDocumentController::class, 'exportWord'])
        ->name('laporan-dokumen.export-word');
    Route::get('/laporan-dokumen/{jenis}/export-pdf', [ReportDocumentController::class, 'exportPdf'])
        ->name('laporan-dokumen.export-pdf');
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

Route::get('/laporan-dokumen/{jenis}', [ReportDocumentController::class, 'index'])
    ->middleware('auth')
    ->name('laporan-dokumen.index');

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
    ->middleware(['auth', 'permission:persetujuan.view'])
    ->name('persetujuan.index');

Route::delete('/persetujuan/{type}/{id}', [ApprovalController::class, 'destroy'])
    ->middleware(['auth', 'role:super_admin'])
    ->whereIn('type', ['loan', 'procurement', 'disposal'])
    ->whereNumber('id')
    ->name('persetujuan.destroy');
