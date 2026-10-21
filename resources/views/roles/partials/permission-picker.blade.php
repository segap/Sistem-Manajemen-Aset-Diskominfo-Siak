@php
    $selectedPermissionCodes = collect($selectedPermissions ?? [])->filter()->unique()->flip();
    $viewPermissions = $permissions->filter(fn ($permission) => str_ends_with($permission->kode, '.view'))->keyBy('kode');
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
            ['Laporan Persediaan Bulanan', 'laporan_persediaan_bulanan.view'], ['Laporan Persediaan Semester 1', 'laporan_persediaan_semester_1.view'],
            ['Laporan Persediaan Tahunan', 'laporan_persediaan_tahunan.view'],
        ],
        'Manajemen User' => [
            ['Data User', 'users.view'], ['Role', 'roles.view'], ['Pemegang Aset', 'pemegang_aset.view'],
        ],
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
                                <label class="flex min-h-[38px] w-full cursor-pointer items-center gap-2.5 rounded-[7px] px-1.5 text-[10px] font-medium leading-normal text-[#405267] transition hover:bg-[#f6f9fc]">
                                    <input class="m-0 h-4 w-4 shrink-0 rounded border-[#9eb0c4] text-[#146bb5] focus:ring-[#146bb5]" type="checkbox" name="permissions[]" value="{{ $permissionCode }}" data-permission-checkbox @checked($selectedPermissionCodes->has($permissionCode))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    @endif
</section>
