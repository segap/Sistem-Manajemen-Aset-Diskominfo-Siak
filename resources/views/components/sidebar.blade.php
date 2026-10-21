@php
    $user = auth()->user();
    $role = $user?->role ?? 'admin_bidang';
    $roleLabels = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'admin_bidang' => 'Admin Bidang',
        'kasubbag_umum_perencanaan' => 'Kepala Sub Bagian Perencanaan dan Umum',
        'tim_aset' => 'Tim Aset',
        'timaset_persediaan' => 'Tim Aset Persediaan',
        'tim_aset_persediaan' => 'Tim Aset Persediaan',
        'aset-persediaan' => 'Aset Persediaan',
        'pimpinan' => 'Pimpinan',
    ];
    $laporanSubmenus = [
        ['report', 'Laporan Aset', '/laporan'],
        ['report', 'Laporan Rekonsiliasi Bulanan', '/laporan-dokumen/rekonsiliasi-bulanan'],
        ['report', 'Laporan Semester 1', '/laporan-dokumen/semester-1'],
        ['report', 'Laporan Tahunan', '/laporan-dokumen/tahunan'],
        ['report', 'Laporan Aset Unaudited', '/laporan-dokumen/aset-unaudited'],
        ['report', 'Laporan Aset Audited', '/laporan-dokumen/aset-audited'],
        ['report', 'Berita Acara BMD', '/laporan-dokumen/berita-acara-bmd'],
    ];
    $laporanPersediaanSubmenus = [
        ['report', 'Laporan Persediaan', null, [
            ['report', 'Laporan Persediaan Bulanan', '/laporan-dokumen/persediaan-bulanan'],
            ['report', 'Laporan Persediaan Semester 1', '/laporan-dokumen/persediaan-semester-1'],
            ['report', 'Laporan Persediaan Tahunan', '/laporan-dokumen/persediaan-tahunan'],
        ]],
    ];
    $menus = [
        'pimpinan' => [
            ['dashboard', 'Dashboard', '/dashboard'],
        ],
        'super_admin' => [
            ['dashboard', 'Dashboard', '/dashboard'],
            ['box', 'Master Barang', '/aset'],
            ['layers', 'Kategori Aset', '/kategori-aset'],
            ['code', 'Kodefikasi', null, [
                ['code', 'Kode Barang/Lokasi', '/kodefikasi'],
                ['hash', 'Kodefikasi Internal', '/kodefikasi-internal'],
            ]],
            ['room', 'Manajemen Ruangan', '/ruangan'],
            ['kir', 'Kartu Inventaris Ruangan', '/kir'],
            ['stock', 'Stock Opname', '/stock-opname'],
            ['layers', 'Kategori Stock Opname', '/kategori-stock-opname'],
            ['proposal', 'Permintaan', '/permintaan-persediaan'],
            ['loan', 'Peminjaman', '/peminjaman'],
            ['tools', 'Pemeliharaan', '/pemeliharaan-aset'],
            ['check', 'Persetujuan', '/persetujuan'],
            ['proposal', 'Usulan Pengadaan', '/usulan-pengadaan'],
            ['cart', 'Pengadaan Barang', '/pengadaan'],
            ['trash', 'Penghapusan', '/penghapusan-aset'],
            ['report', 'Laporan', null, array_merge($laporanSubmenus, $laporanPersediaanSubmenus)],
            ['users', 'Manajemen User', null, [
                ['users', 'Data User', '/users'],
                ['users', 'Role', '/roles'],
                ['users', 'Pemegang Aset', '/pemegang-aset'],
            ]],
        ],
        'admin' => [
            ['dashboard', 'Dashboard', '/dashboard'],
            ['box', 'Master Barang', '/aset'],
            ['layers', 'Kategori Aset', '/kategori-aset'],
            ['code', 'Kodefikasi', null, [
                ['code', 'Kode Barang/Lokasi', '/kodefikasi'],
                ['hash', 'Kodefikasi Internal', '/kodefikasi-internal'],
            ]],
            ['room', 'Manajemen Ruangan', '/ruangan'],
            ['kir', 'Kartu Inventaris Ruangan', '/kir'],
            ['stock', 'Stock Opname', '/stock-opname'],
            ['layers', 'Kategori Stock Opname', '/kategori-stock-opname'],
            ['loan', 'Peminjaman', '/peminjaman'],
            ['tools', 'Pemeliharaan', '/pemeliharaan-aset'],
            ['check', 'Persetujuan', '/persetujuan'],
            ['cart', 'Pengadaan Barang', '/pengadaan'],
            ['trash', 'Penghapusan', '/penghapusan-aset'],
            ['report', 'Laporan', null, $laporanSubmenus],
            ['users', 'Manajemen User', null, [
                ['users', 'Data User', '/users'],
                ['users', 'Role', '/roles'],
                ['users', 'Pemegang Aset', '/pemegang-aset'],
            ]],
        ],
        'admin_bidang' => [
            ['loan', 'Peminjaman Barang', '/peminjaman'],
            ['tools', 'Pemeliharaan', '/pemeliharaan-aset'],
            ['proposal', 'Usulan Pengadaan', '/usulan-pengadaan'],
            ['stock', 'Permintaan Barang', '/permintaan-persediaan'],
        ],
        'kasubbag_umum_perencanaan' => [
            ['dashboard', 'Dashboard', '/dashboard'],
            ['check', 'Persetujuan', '/persetujuan'],
            ['trash', 'Penghapusan', '/penghapusan-aset'],
            ['report', 'Laporan', null, $laporanSubmenus],
            ['users', 'Manajemen User', null, [
                ['users', 'Pemegang Aset', '/pemegang-aset'],
            ]],
        ],
        'tim_aset' => [
            ['box', 'Master Barang', '/aset'],
            ['check', 'Verifikasi Barang', '/verifikasi-barang'],
            ['proposal', 'Verifikasi Usulan', '/usulan-pengadaan'],
            ['cart', 'Pengadaan Barang', '/pengadaan'],
            ['loan', 'Verifikasi Peminjaman', '/verifikasi-peminjaman'],
            ['tools', 'Pemeliharaan', '/pemeliharaan-aset'],
            ['stock', 'Verifikasi Stock Opname', '/stock-opname'],
            ['trash', 'Penghapusan', '/penghapusan-aset'],
            ['report', 'Laporan', null, $laporanSubmenus],
            ['users', 'Manajemen User', null, [
                ['users', 'Pemegang Aset', '/pemegang-aset'],
            ]],
        ],
        'timaset_persediaan' => [
            ['stock', 'Stock Opname', '/stock-opname'],
            ['proposal', 'Permintaan Barang', '/permintaan-persediaan'],
            ['report', 'Laporan', null, $laporanPersediaanSubmenus],
        ],
        'tim_aset_persediaan' => [
            ['stock', 'Stock Opname', '/stock-opname'],
            ['proposal', 'Permintaan Barang', '/permintaan-persediaan'],
            ['report', 'Laporan', null, $laporanPersediaanSubmenus],
        ],
        'aset-persediaan' => [
            ['stock', 'Stock Opname', '/stock-opname'],
            ['proposal', 'Permintaan Barang', '/permintaan-persediaan'],
            ['report', 'Laporan', null, $laporanPersediaanSubmenus],
        ],
    ];
    $paths = [
        'dashboard' => 'M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-13h6V4h-6v3Z',
        'box' => 'm4 7 8-4 8 4v10l-8 4-8-4V7Zm0 0 8 4 8-4m-8 4v10',
        'layers' => 'm12 3 9 5-9 5-9-5 9-5Zm-9 10 9 5 9-5M3 17l9 5 9-5',
        'loan' => 'M7 6h10M7 10h10M7 14h6M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z',
        'cart' => 'M3 4h2l2.4 10.2a2 2 0 0 0 2 1.5h7.8a2 2 0 0 0 2-1.6L21 7H6m4 13h.01M17 20h.01',
        'trash' => 'M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7',
        'tools' => 'M14.7 6.3a4 4 0 0 0-5-5L12 3.6 9.6 6 7.3 3.7a4 4 0 0 0 5 5L4 17l3 3 7.7-7.7a4 4 0 0 0 5-5L17.4 9.6 15 7.2l-.3-.9Z',
        'report' => 'M6 3h9l4 4v14H6V3Zm9 0v5h4M9 13h6m-6 4h6',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87m-1-11.96a4 4 0 0 1 0 7.75',
        'proposal' => 'M7 3h10v4h4v14H3V7h4V3Zm0 4h10V3H7v4Zm2 6h6m-6 4h4',
        'room' => 'M4 21V5a2 2 0 0 1 2-2h9l5 5v13M9 21v-6h6v6M8 8h2m4 0h2M8 12h2m4 0h2',
        'kir' => 'M6 3h12a2 2 0 0 1 2 2v16l-4-2-4 2-4-2-4 2V5a2 2 0 0 1 2-2Zm3 5h6M9 12h6M9 16h4',
        'check' => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11',
        'code' => 'M16 18l6-6-6-6M8 6l-6 6 6 6',
        'hash' => 'M5 9h14M4 15h14M10 3 8 21M16 3l-2 18',
        'stock' => 'M4 7h16M7 7v12h10V7M9 11h6M9 15h6',
    ];
    $initials = collect(explode(' ', $user?->name ?? 'SA'))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
    $isMenuActive = fn (?string $url) => $url
        ? request()->path() === trim($url, '/') || request()->is(trim($url, '/').'/*')
        : false;
    $viewPermissions = [
        '/dashboard' => 'dashboard.view', '/aset' => 'aset.view', '/kategori-aset' => 'kategori_aset.view',
        '/kodefikasi' => 'kodefikasi.view', '/kodefikasi-internal' => 'kodefikasi_internal.view',
        '/ruangan' => 'ruangan.view', '/kategori-stock-opname' => 'kategori_stock_opname.view',
    ];
    $canView = fn (string $permission): bool => $user?->hasPermission($permission) ?? false;
    $usesDynamicSource = ! array_key_exists($role, $menus) || $role === 'pimpinan';
    $filterMenu = function (array $menu) use (&$filterMenu, $viewPermissions, $canView, $usesDynamicSource): ?array {
        $children = $menu[3] ?? null;
        if (is_array($children)) {
            $children = collect($children)->map(fn (array $child) => $filterMenu($child))->filter()->values()->all();
            if ($children === []) return null;
            $menu[3] = $children;
            return $menu;
        }

        $permission = $viewPermissions[$menu[2] ?? ''] ?? null;
        if ($permission) return $canView($permission) ? $menu : null;

        return $usesDynamicSource ? null : $menu;
    };
    $menuSource = $usesDynamicSource ? $menus['super_admin'] : ($menus[$role] ?? []);
    $visibleMenus = collect($menuSource)->map(fn (array $menu) => $filterMenu($menu))->filter()->values();
@endphp

<button class="pointer-events-none fixed inset-0 z-25 border-0 bg-[rgb(5_20_35_/_55%)] opacity-0 transition-opacity duration-200 lg:hidden" type="button" data-sidebar-backdrop aria-label="Tutup menu"></button>

<aside class="fixed inset-y-0 left-0 z-30 flex w-[255px] -translate-x-full flex-col bg-white text-[#516176] shadow-[10px_0_32px_rgb(43_61_84_/_8%)] transition-transform duration-200 lg:translate-x-0" data-sidebar>

    <nav class="flex-1 overflow-y-auto px-5 py-6 [scrollbar-color:#d8e0ec_transparent] [scrollbar-gutter:stable] [scrollbar-width:thin]" aria-label="Navigasi utama" data-sidebar-scroll>
        @foreach ($visibleMenus as $menu)
            @php
                [$icon, $label, $url] = array_pad($menu, 4, null);
                $children = $menu[3] ?? null;
                $hasChildren = is_array($children);
                $childrenActive = $hasChildren && collect($children)->contains(function ($child) use ($isMenuActive) {
                    $grandChildren = $child[3] ?? null;

                    return $isMenuActive($child[2] ?? null)
                        || (is_array($grandChildren) && collect($grandChildren)->contains(fn ($grandChild) => $isMenuActive($grandChild[2] ?? null)));
                });
                $active = $hasChildren
                    ? $childrenActive
                    : ($url === route('super-admin.index', [], false)
                        ? request()->routeIs('super-admin.*')
                        : $isMenuActive($url));
                $class = $active
                    ? 'translate-x-px bg-[#2f55df] text-white shadow-[0_9px_18px_rgb(47_85_223_/_28%)]'
                    : 'text-[#4d5f76] hover:bg-[#f2f6ff] hover:text-[#2556d9]';
            @endphp
            @if ($hasChildren)
                <details class="group my-[5px]" @open($childrenActive) data-sidebar-details>
                    <summary class="flex min-h-[42px] cursor-pointer list-none items-center gap-3 rounded-[7px] px-3 text-[11px] font-bold transition duration-200 ease-out [&::-webkit-details-marker]:hidden {{ $class }}">
                        <svg class="h-[18px] w-[18px] shrink-0 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $paths[$icon] }}" /></svg>
                        <span class="min-w-0 flex-1">{{ $label }}</span>
                        <svg class="h-3.5 w-3.5 shrink-0 fill-none stroke-current stroke-[2.2] transition group-open:rotate-180 [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                    </summary>
                    <div class="mt-1 grid gap-1 pl-[30px]">
                        @foreach ($children as $child)
                            @php
                                [$childIcon, $childLabel, $childUrl] = array_pad($child, 4, null);
                                $grandChildren = $child[3] ?? null;
                                $hasGrandChildren = is_array($grandChildren);
                                $grandChildrenActive = $hasGrandChildren && collect($grandChildren)->contains(fn ($grandChild) => $isMenuActive($grandChild[2] ?? null));
                                $childActive = $isMenuActive($childUrl);
                                $childClass = ($childActive || $grandChildrenActive)
                                    ? 'bg-[#eef3ff] text-[#2556d9]'
                                    : 'text-[#65748a] hover:bg-[#f6f8fc] hover:text-[#2556d9]';
                            @endphp
                            @if ($hasGrandChildren)
                                <details class="group/sub" @open($grandChildrenActive) data-sidebar-details>
                                    <summary class="flex min-h-[34px] cursor-pointer list-none items-center gap-2 rounded-[7px] px-3 text-[10px] font-bold transition [&::-webkit-details-marker]:hidden {{ $childClass }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $grandChildrenActive ? 'bg-[#2f55df]' : 'bg-[#c7d0dc]' }}"></span>
                                        <span class="min-w-0 flex-1">{{ $childLabel }}</span>
                                        <svg class="h-3 w-3 shrink-0 fill-none stroke-current stroke-[2.2] transition group-open/sub:rotate-180 [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                                    </summary>
                                    <div class="mt-1 grid gap-1 pl-4">
                                        @foreach ($grandChildren as $grandChild)
                                            @php
                                                [$grandIcon, $grandLabel, $grandUrl] = array_pad($grandChild, 3, null);
                                                $grandActive = $isMenuActive($grandUrl);
                                                $grandClass = $grandActive
                                                    ? 'bg-[#eef3ff] text-[#2556d9]'
                                                    : 'text-[#65748a] hover:bg-[#f6f8fc] hover:text-[#2556d9]';
                                            @endphp
                                            <a href="{{ $grandUrl }}" class="flex min-h-[32px] items-center gap-2 rounded-[7px] px-3 text-[9px] font-bold no-underline transition {{ $grandClass }}" @if($grandActive) data-sidebar-active @endif>
                                                <span class="h-1.5 w-1.5 rounded-full {{ $grandActive ? 'bg-[#2f55df]' : 'bg-[#c7d0dc]' }}"></span>
                                                <span>{{ $grandLabel }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <a href="{{ $childUrl }}" class="flex min-h-[34px] items-center gap-2 rounded-[7px] px-3 text-[10px] font-bold no-underline transition {{ $childClass }}" @if($childActive) data-sidebar-active @endif>
                                    <span class="h-1.5 w-1.5 rounded-full {{ $childActive ? 'bg-[#2f55df]' : 'bg-[#c7d0dc]' }}"></span>
                                    <span>{{ $childLabel }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </details>
            @else
                <a href="{{ $url }}" class="my-[5px] flex min-h-[42px] items-center gap-3 rounded-[7px] px-3 text-[11px] font-bold no-underline transition duration-200 ease-out {{ $class }}" @if($active) data-sidebar-active @endif>
                    <svg class="h-[18px] w-[18px] shrink-0 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $paths[$icon] }}" /></svg>
                    <span>{{ $label }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    <div class="flex min-h-[76px] items-center gap-2.5 border-t border-[#eef2f6] bg-white px-5 py-3.5">
        @if ($role === 'pimpinan')
        <div class="flex min-w-0 flex-1 items-center gap-2.5">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-[10px] bg-[#2f55df] text-[11px] font-extrabold uppercase text-white">{{ $initials }}</span>
            <span class="grid min-w-0 flex-1">
                <strong class="truncate text-[11px] text-[#2f3d51]">{{ $user?->name }}</strong>
                <small class="truncate text-[8px] text-[#8a98aa]">{{ $roleLabels[$role] ?? $role }}</small>
            </span>
        </div>
        @else
        <a class="flex min-w-0 flex-1 items-center gap-2.5 text-inherit no-underline" href="{{ route('profile.index') }}" title="Buka profil">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-[10px] bg-[#2f55df] text-[11px] font-extrabold uppercase text-white">{{ $initials }}</span>
            <span class="grid min-w-0 flex-1">
                <strong class="truncate text-[11px] text-[#2f3d51]">{{ $user?->name }}</strong>
                <small class="truncate text-[8px] text-[#8a98aa]">{{ $roleLabels[$role] ?? $role }}</small>
            </span>
        </a>
        @endif
        <form method="POST" action="{{ route('web.logout') }}">
            @csrf
            <button class="grid h-8 w-8 place-items-center rounded-lg border-0 bg-transparent text-[#7e8da1] hover:bg-[#eef3ff] hover:text-[#2f55df]" type="submit" aria-label="Keluar dari aplikasi" title="Keluar">
                <svg class="h-[19px] w-[19px] fill-none stroke-current stroke-[1.8] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3m10-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" /></svg>
            </button>
        </form>
    </div>
</aside>
