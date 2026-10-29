@php
    $selectedPermissionCodes = collect($selectedPermissions ?? [])->filter()->unique()->flip();
    $viewPermissions = $permissions->filter(fn ($permission) => str_ends_with($permission->kode, '.view'))->keyBy('kode');
    $availablePermissionCodes = $permissions->pluck('kode')->flip();
    $menuGroups = [
        'Menu Utama' => [
            ['Dashboard', 'dashboard.view'], ['Master Barang', 'aset.view'], ['Kategori Aset', 'kategori_aset.view'],
            ['Manajemen Ruangan', 'ruangan.view'], ['Kartu Inventaris Ruangan', 'kir.view'], ['Stock Opname', 'stock_opname.view'],
            ['Kategori Stock Opname', 'kategori_stock_opname.view'], ['Permintaan', 'permintaan.view'], ['Peminjaman', 'peminjaman.view'],
            ['Pemeliharaan', 'pemeliharaan.view'], ['Persetujuan', 'persetujuan.view'], ['Usulan Pengadaan', 'usulan_pengadaan.view'],
            ['Pengadaan Barang', 'pengadaan.view'], ['Penghapusan Aset', 'penghapusan.view'],
        ],
        'Kodefikasi' => [
            ['Kode Barang/Lokasi', 'kodefikasi.view'], ['Kodefikasi Internal', 'kodefikasi_internal.view'],
        ],
        'Laporan' => [
            ['Laporan Aset', 'laporan_aset.view'], ['Rekonsiliasi Bulanan', 'laporan_rekonsiliasi_bulanan.view'],
            ['Semester 1', 'laporan_semester_1.view'], ['Tahunan', 'laporan_tahunan.view'], ['Aset Unaudited', 'laporan_aset_unaudited.view'],
            ['Aset Audited', 'laporan_aset_audited.view'], ['Berita Acara BMD', 'berita_acara_bmd.view'],
            ['Laporan RKBMD', 'laporan_rkbmd.view'],
            ['Laporan Persediaan Bulanan', 'laporan_persediaan_bulanan.view'], ['Laporan Persediaan Semester 1', 'laporan_persediaan_semester_1.view'],
            ['Laporan Persediaan Tahunan', 'laporan_persediaan_tahunan.view'],
        ],
        'Manajemen User' => [
            ['Data User', 'users.view'], ['Role', 'roles.view'], ['Data Pegawai', 'pemegang_aset.view'],
        ],
    ];
    $reportActionOptions = [
        'laporan_aset.view' => ['laporan_aset.export' => 'Export Laporan'],
        'laporan_rekonsiliasi_bulanan.view' => ['laporan_rekonsiliasi_bulanan.upload' => 'Upload Dokumen', 'laporan_rekonsiliasi_bulanan.delete' => 'Hapus Dokumen', 'laporan_rekonsiliasi_bulanan.export' => 'Export Laporan'],
        'laporan_semester_1.view' => ['laporan_semester_1.upload' => 'Upload Dokumen', 'laporan_semester_1.delete' => 'Hapus Dokumen', 'laporan_semester_1.export' => 'Export Laporan'],
        'laporan_tahunan.view' => ['laporan_tahunan.upload' => 'Upload Dokumen', 'laporan_tahunan.delete' => 'Hapus Dokumen', 'laporan_tahunan.export' => 'Export Laporan'],
        'laporan_aset_unaudited.view' => ['laporan_aset_unaudited.upload' => 'Upload Dokumen', 'laporan_aset_unaudited.delete' => 'Hapus Dokumen', 'laporan_aset_unaudited.export' => 'Export Laporan'],
        'laporan_aset_audited.view' => ['laporan_aset_audited.upload' => 'Upload Dokumen', 'laporan_aset_audited.delete' => 'Hapus Dokumen', 'laporan_aset_audited.export' => 'Export Laporan'],
        'laporan_rkbmd.view' => ['laporan_rkbmd.upload' => 'Upload Dokumen', 'laporan_rkbmd.download' => 'Download Dokumen', 'laporan_rkbmd.delete' => 'Hapus Dokumen', 'laporan_rkbmd.export' => 'Export Laporan'],
        'berita_acara_bmd.view' => ['berita_acara_bmd.upload' => 'Upload Dokumen', 'berita_acara_bmd.complete' => 'Lengkapi Berita Acara', 'berita_acara_bmd.delete' => 'Hapus Dokumen', 'berita_acara_bmd.export' => 'Export Laporan'],
        'laporan_persediaan_bulanan.view' => ['laporan_persediaan_bulanan.upload' => 'Upload Dokumen', 'laporan_persediaan_bulanan.delete' => 'Hapus Dokumen', 'laporan_persediaan_bulanan.export' => 'Export Laporan'],
        'laporan_persediaan_semester_1.view' => ['laporan_persediaan_semester_1.upload' => 'Upload Dokumen', 'laporan_persediaan_semester_1.delete' => 'Hapus Dokumen', 'laporan_persediaan_semester_1.export' => 'Export Laporan'],
        'laporan_persediaan_tahunan.view' => ['laporan_persediaan_tahunan.upload' => 'Upload Dokumen', 'laporan_persediaan_tahunan.delete' => 'Hapus Dokumen', 'laporan_persediaan_tahunan.export' => 'Export Laporan'],
    ];
@endphp

<section class="grid gap-2.5" data-permission-form>
    <div class="flex items-center justify-between gap-3">
        <div>
            <h3 class="m-0 text-[12px] font-extrabold text-[#0b2b50]">Pengaturan Hak Akses Menu</h3>
            <p class="mt-0.5 text-[10px] text-[#718095]">Pilih menu yang dapat dibuka. Hak tindakan dikelola terpisah.</p>
        </div>
        @if (! $superAdminRole)
            <div class="flex shrink-0 items-center gap-2">
                <span class="whitespace-nowrap text-[10px] font-bold text-[#146bb5]"><span data-permission-selected-count>0</span> menu dipilih</span>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c44f47] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#ad433c]" type="button" data-permission-clear>Hapus Pilihan</button>
            </div>
        @endif
    </div>

    @if ($superAdminRole)
        <div class="rounded-[9px] border border-[#bcdcf4] bg-[#eef7ff] px-3 py-2.5 text-[10px] leading-relaxed text-[#28618c]" role="note">Super Admin memiliki akses penuh melalui bypass sistem. Hak aksesnya tidak dapat dibatasi melalui checkbox.</div>
    @else
        <div class="w-full overflow-y-auto overscroll-contain rounded-[9px] border border-[#dce4ec] bg-white px-3 py-1 [scrollbar-color:#cbd5e1_transparent] [scrollbar-width:thin]" style="height: 260px; max-height: 260px;" data-permission-groups>
            @foreach ($menuGroups as $groupName => $items)
                @php $availableItems = collect($items)->filter(fn (array $item) => $viewPermissions->has($item[1])); @endphp
                @if ($availableItems->isNotEmpty())
                    <section class="border-b border-[#edf1f5] py-2.5 last:border-b-0" aria-labelledby="permission-group-{{ $permissionModalKey }}-{{ $loop->index }}">
                        <h4 class="m-0 mb-1 text-[9px] font-extrabold uppercase tracking-[.06em] text-[#718095]" id="permission-group-{{ $permissionModalKey }}-{{ $loop->index }}">{{ $groupName }}</h4>
                        <div class="grid gap-0.5">
                            @foreach ($availableItems as [$label, $permissionCode])
                                <div class="rounded-[7px] px-1.5 transition hover:bg-[#f6f9fc]">
                                    <div class="flex min-h-[38px] w-full items-center gap-2.5 text-[10px] font-medium leading-normal text-[#405267]">
                                        <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-2.5">
                                            <input class="m-0 h-4 w-4 shrink-0 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $permissionCode }}" data-permission-checkbox @if ($permissionCode === 'aset.view') data-asset-view-checkbox @endif @if ($permissionCode === 'kategori_aset.view') data-category-asset-view-checkbox @endif @if ($permissionCode === 'kodefikasi.view') data-kodefikasi-view-checkbox @endif @if ($permissionCode === 'kodefikasi_internal.view') data-kodefikasi-internal-view-checkbox @endif @if ($permissionCode === 'ruangan.view') data-ruangan-view-checkbox @endif @if ($permissionCode === 'kir.view') data-kir-view-checkbox @endif @if ($permissionCode === 'stock_opname.view') data-stock-opname-view-checkbox @endif @if ($permissionCode === 'kategori_stock_opname.view') data-stock-opname-category-view-checkbox @endif @if ($permissionCode === 'permintaan.view') data-supply-request-view-checkbox @endif @if ($permissionCode === 'peminjaman.view') data-loan-view-checkbox @endif @if ($permissionCode === 'pemeliharaan.view') data-maintenance-view-checkbox @endif @if ($permissionCode === 'usulan_pengadaan.view') data-procurement-proposal-view-checkbox @endif @if ($permissionCode === 'pengadaan.view') data-procurement-view-checkbox @endif @if ($permissionCode === 'penghapusan.view') data-disposal-view-checkbox @endif @if (isset($reportActionOptions[$permissionCode])) data-report-view-checkbox="{{ $permissionCode }}" @endif @checked($selectedPermissionCodes->has($permissionCode))>
                                            <span>{{ $label }}</span>
                                        </label>
                                        @if (in_array($permissionCode, ['aset.view', 'kategori_aset.view', 'kodefikasi.view', 'kodefikasi_internal.view', 'ruangan.view', 'kir.view', 'stock_opname.view', 'kategori_stock_opname.view', 'permintaan.view', 'peminjaman.view', 'pemeliharaan.view', 'usulan_pengadaan.view', 'pengadaan.view', 'penghapusan.view'], true) || isset($reportActionOptions[$permissionCode]))
                                            <button class="rounded-[7px] border border-[#dbe3eb] bg-white px-2 py-1 text-[9px] font-bold text-[#146bb5] transition hover:bg-[#eef7ff]" type="button" @if ($permissionCode === 'aset.view') data-asset-actions-toggle @elseif ($permissionCode === 'kategori_aset.view') data-category-asset-actions-toggle @elseif ($permissionCode === 'kodefikasi.view') data-kodefikasi-actions-toggle @elseif ($permissionCode === 'kodefikasi_internal.view') data-kodefikasi-internal-actions-toggle @elseif ($permissionCode === 'ruangan.view') data-ruangan-actions-toggle @elseif ($permissionCode === 'kir.view') data-kir-actions-toggle @elseif ($permissionCode === 'stock_opname.view') data-stock-opname-actions-toggle @elseif ($permissionCode === 'kategori_stock_opname.view') data-stock-opname-category-actions-toggle @elseif ($permissionCode === 'permintaan.view') data-supply-request-actions-toggle @elseif ($permissionCode === 'peminjaman.view') data-loan-actions-toggle @elseif ($permissionCode === 'pemeliharaan.view') data-maintenance-actions-toggle @elseif ($permissionCode === 'usulan_pengadaan.view') data-procurement-proposal-actions-toggle @elseif ($permissionCode === 'pengadaan.view') data-procurement-actions-toggle @elseif ($permissionCode === 'penghapusan.view') data-disposal-actions-toggle @else data-report-actions-toggle="{{ $permissionCode }}" @endif>Atur Aksi</button>
                                        @endif
                                    </div>
                                    @if ($permissionCode === 'aset.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-asset-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Master Barang</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'aset.create' => 'Tambah Barang',
                                                    'aset.update' => 'Edit Barang',
                                                    'aset.condition.update' => 'Edit Kondisi Barang',
                                                    'aset.export' => 'Export Excel',
                                                    'aset.import' => 'Import Excel',
                                                    'aset.delete' => 'Hapus Barang',
                                                ] as $actionCode => $actionLabel)
                                                    <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                        <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-asset-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                        <span>{{ $actionLabel }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'kategori_aset.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-category-asset-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Kategori Aset</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'kategori_aset.create' => 'Tambah Kategori',
                                                    'kategori_aset.update' => 'Edit Kategori',
                                                    'kategori_aset.delete' => 'Hapus Kategori',
                                                    'kategori_aset.export.excel' => 'Export Excel',
                                                    'kategori_aset.export.pdf' => 'Export PDF',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-category-asset-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'kodefikasi.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-kodefikasi-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Kodefikasi</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'kodefikasi.create' => 'Tambah Kodefikasi',
                                                    'kodefikasi.update' => 'Edit Kodefikasi',
                                                    'kodefikasi.delete' => 'Hapus Kodefikasi',
                                                    'kodefikasi.import' => 'Import Excel/CSV',
                                                    'kodefikasi.export' => 'Export Data',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-kodefikasi-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'kodefikasi_internal.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-kodefikasi-internal-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Kodefikasi Internal</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'kodefikasi_internal.generate' => 'Generate Kode Internal',
                                                    'kodefikasi_internal.bulk_generate' => 'Generate Massal Kode Internal',
                                                    'kodefikasi_internal.update' => 'Edit Kode Internal',
                                                    'kodefikasi_internal.reset' => 'Reset Kode Internal',
                                                    'kodefikasi_internal.export' => 'Export Data',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-kodefikasi-internal-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'ruangan.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-ruangan-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Manajemen Ruangan</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'ruangan.create' => 'Tambah Ruangan',
                                                    'ruangan.update' => 'Edit Ruangan',
                                                    'ruangan.delete' => 'Hapus Ruangan',
                                                    'ruangan.export' => 'Export Data',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-ruangan-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'kir.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-kir-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Kartu Inventaris Ruangan</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'kir.create' => 'Tambah Detail KIR',
                                                    'kir.update' => 'Edit Detail KIR',
                                                    'kir.delete' => 'Hapus Detail KIR',
                                                    'kir.import' => 'Import Excel',
                                                    'kir.template.download' => 'Unduh Template',
                                                    'kir.download' => 'Unduh PDF/Word',
                                                    'kir.export' => 'Export Excel',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-kir-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'stock_opname.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-stock-opname-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Stock Opname</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'stock_opname.create' => 'Tambah Barang',
                                                    'stock_opname.update' => 'Edit Barang',
                                                    'stock_opname.import' => 'Import Excel',
                                                    'stock_opname.template.download' => 'Unduh Template',
                                                    'stock_opname.export' => 'Export Data',
                                                    'stock_opname.stock.add' => 'Tambah Stok',
                                                    'stock_opname.stock.adjust' => 'Catat Stock Opname Fisik',
                                                    'stock_opname.verify' => 'Verifikasi Stock Opname',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-stock-opname-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'kategori_stock_opname.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-stock-opname-category-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Kategori Stock Opname</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'kategori_stock_opname.create' => 'Tambah Kategori',
                                                    'kategori_stock_opname.update' => 'Edit Kategori',
                                                    'kategori_stock_opname.delete' => 'Hapus Kategori',
                                                    'kategori_stock_opname.export' => 'Export Data',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-stock-opname-category-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'permintaan.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-supply-request-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Permintaan Barang</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'permintaan.create' => 'Buat Permintaan',
                                                    'permintaan.export' => 'Export Data',
                                                    'permintaan.verify' => 'Verifikasi Permintaan',
                                                    'permintaan.handover' => 'Penyerahan Barang',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-supply-request-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'peminjaman.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-loan-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Peminjaman Barang</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'peminjaman.create' => 'Ajukan Peminjaman',
                                                    'peminjaman.verify' => 'Verifikasi Peminjaman',
                                                    'peminjaman.handover' => 'Serah Terima Barang',
                                                    'peminjaman.return' => 'Catat Pengembalian',
                                                    'peminjaman.export' => 'Export Data',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-loan-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'pemeliharaan.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-maintenance-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Pemeliharaan Aset</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'pemeliharaan.create' => 'Ajukan Pemeliharaan',
                                                    'pemeliharaan.update' => 'Edit Pemeliharaan',
                                                    'pemeliharaan.download' => 'Download Dokumen',
                                                    'pemeliharaan.export' => 'Export Data',
                                                    'pemeliharaan.verify' => 'Verifikasi Pemeliharaan',
                                                    'pemeliharaan.start' => 'Mulai Pemeliharaan',
                                                    'pemeliharaan.complete' => 'Selesaikan Pemeliharaan',
                                                    'pemeliharaan.report.update' => 'Lengkapi Berita Acara',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-maintenance-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'usulan_pengadaan.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-procurement-proposal-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Usulan Pengadaan</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'usulan_pengadaan.create' => 'Buat Usulan',
                                                    'usulan_pengadaan.update' => 'Edit Usulan',
                                                    'usulan_pengadaan.verify' => 'Verifikasi Tim Aset',
                                                    'usulan_pengadaan.export' => 'Export Usulan',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-procurement-proposal-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'pengadaan.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-procurement-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Pengadaan Barang</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'pengadaan.create' => 'Catat Pengadaan',
                                                    'pengadaan.update' => 'Edit Pengadaan',
                                                    'pengadaan.receive' => 'Penerimaan Barang',
                                                    'pengadaan.verify' => 'Verifikasi Pengadaan',
                                                    'pengadaan.export' => 'Export Pengadaan',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-procurement-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($permissionCode === 'penghapusan.view')
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-disposal-action-options>
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan Penghapusan Aset</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ([
                                                    'penghapusan.create' => 'Ajukan Penghapusan',
                                                    'penghapusan.approve' => 'Persetujuan Penghapusan',
                                                    'penghapusan.download' => 'Download Dokumen',
                                                    'penghapusan.export' => 'Export Penghapusan',
                                                ] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-disposal-action-checkbox @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if (isset($reportActionOptions[$permissionCode]))
                                        <div class="ml-6 mb-1 hidden rounded-[8px] border border-[#e2eaf2] bg-[#f8fafc] p-2" data-report-action-options="{{ $permissionCode }}">
                                            <p class="m-0 mb-1.5 text-[9px] font-semibold text-[#718095]">Hak tindakan {{ $label }}</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                                @foreach ($reportActionOptions[$permissionCode] as $actionCode => $actionLabel)
                                                    @if ($availablePermissionCodes->has($actionCode))
                                                        <label class="flex cursor-pointer items-center gap-1.5 text-[9px] font-medium text-[#405267]">
                                                            <input class="m-0 h-3.5 w-3.5 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $actionCode }}" data-report-action-checkbox="{{ $permissionCode }}" @checked($selectedPermissionCodes->has($actionCode))>
                                                            <span>{{ $actionLabel }}</span>
                                                        </label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    @endif
</section>
