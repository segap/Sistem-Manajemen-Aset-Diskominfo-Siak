@php
    $user = auth()->user();
    $roleLabels = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'admin_bidang' => 'Admin Bidang',
        'kasubbag_umum_perencanaan' => 'Kepala Sub Bagian Perencanaan dan Umum',
        'tim_aset' => 'Tim Aset',
        'timaset_persediaan' => 'Tim Aset Persediaan',
        'tim_aset_persediaan' => 'Tim Aset Persediaan',
        'pimpinan' => 'Pimpinan',
    ];
    $initials = collect(explode(' ', $user?->name ?? 'SA'))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
    $breadcrumbLabels = [
        'dashboard' => 'Dashboard',
        'aset' => 'Master Data',
        'kategori-aset' => 'Kategori Aset',
        'kodefikasi' => 'Kodefikasi',
        'kodefikasi-internal' => 'Kodefikasi Internal',
        'ruangan' => 'Manajemen Ruangan',
        'kir' => 'Kartu Inventaris Ruangan',
        'stock-opname' => 'Stock Opname',
        'kategori-stock-opname' => 'Kategori Stock Opname',
        'permintaan-persediaan' => 'Permintaan Barang',
        'peminjaman' => 'Peminjaman',
        'pemeliharaan-aset' => 'Pemeliharaan Aset',
        'persetujuan' => 'Persetujuan',
        'usulan-pengadaan' => 'Usulan Pengadaan',
        'pengadaan' => 'Pengadaan Barang',
        'penghapusan-aset' => 'Penghapusan Aset',
        'laporan' => 'Laporan',
        'users' => 'Manajemen User',
        'pemegang-aset' => 'Data Pegawai',
        'roles' => 'Manajemen Role',
        'profile' => 'Profil',
        'super-admin' => 'Kontrol Sistem',
        'verifikasi-barang' => 'Verifikasi Barang',
        'verifikasi-peminjaman' => 'Verifikasi Peminjaman',
    ];
    $segments = collect(request()->segments());
    $breadcrumbs = collect([['label' => 'SIMIVA', 'url' => route('dashboard')]])
        ->concat($segments->map(function (string $segment, int $index) use ($segments, $breadcrumbLabels): array {
            $path = $segments->take($index + 1)->implode('/');
            $label = $breadcrumbLabels[$path] ?? $breadcrumbLabels[$segment] ?? str($segment)->replace('-', ' ')->title()->toString();

            return [
                'label' => is_numeric($segment) ? 'Detail' : $label,
                'url' => $index === $segments->count() - 1 ? null : url($path),
            ];
        }))
        ->unique('label')
        ->values();
@endphp

<header class="sticky top-0 z-20 flex h-[72px] items-center gap-4 border-b border-slate-200 bg-white/90 px-[18px] backdrop-blur-md lg:px-[30px]">
    <button class="grid h-10 w-10 place-items-center rounded-[10px] border border-[#dce4ee] bg-white text-[#0b2b50] lg:hidden" type="button" data-sidebar-open aria-label="Buka menu">
        <svg class="h-[19px] w-[19px] fill-none stroke-current stroke-[1.8] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
    </button>
    <nav class="min-w-0" aria-label="Breadcrumb">
        <ol class="flex min-w-0 flex-wrap items-center gap-1.5 text-[11px] font-semibold text-[#8391a4]">
            @foreach ($breadcrumbs as $index => $breadcrumb)
                <li class="min-w-0">
                    @if ($breadcrumb['url'] && $index !== $breadcrumbs->count() - 1)
                        <a class="text-[#5f7085] no-underline transition hover:text-[#146bb5]" href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                    @else
                        <span class="text-[#0b2b50]">{{ $breadcrumb['label'] }}</span>
                    @endif
                </li>
                @if ($index !== $breadcrumbs->count() - 1)
                    <li class="text-[#b2bfce]" aria-hidden="true">/</li>
                @endif
            @endforeach
        </ol>
    </nav>
</header>
