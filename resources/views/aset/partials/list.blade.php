@php
    $userRole = auth()->user()?->role;
    $canManageAssets = in_array($userRole, ['admin', 'super_admin'], true);
    $canVerifyAssets = in_array($userRole, ['tim_aset', 'super_admin'], true);
    $canSeeAssetActions = in_array($userRole, ['admin', 'tim_aset', 'super_admin'], true);
    $numberStart = ($assets->currentPage() - 1) * $assets->perPage();
    $selectedPerPage = (int) request('per_page', $assets->perPage());
    $statusLabels = ['menunggu_verifikasi' => 'Menunggu Verifikasi', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
    $statusClass = fn ($status) => match ($status) {
        'menunggu_verifikasi' => 'status-diajukan',
        'ditolak' => 'status-ditolak',
        default => 'status-disetujui',
    };
    $conditionTone = fn (string $condition) => match ($condition) {
        'baik' => 'bg-[#dcfce7] text-[#08714e]',
        'rusak_ringan' => 'bg-[#fef3c7] text-[#92400e]',
        'rusak_sedang' => 'bg-[#ffedd5] text-[#c2410c]',
        'rusak_berat' => 'bg-[#fee2e2] text-[#b42318]',
        'hilang' => 'bg-[#e5e7eb] text-[#374151]',
        default => 'bg-[#eef4fb] text-[#52657a]',
    };
    $conditionValueFor = fn ($asset): string => $asset->kondisi instanceof \App\Enums\AssetCondition
        ? $asset->kondisi->value
        : (string) $asset->kondisi;
    $conditionLabelFor = fn ($asset): string => $asset->kondisi instanceof \App\Enums\AssetCondition
        ? $asset->kondisi->label()
        : ($conditions[$conditionValueFor($asset)] ?? '-');
@endphp

<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">{{ $title }}</h1>
            <p class="m-0 text-[13px] text-[#66758a]">{{ $description }}</p>
        </div>
        @if (! $verificationMode && $canManageAssets)
            <div class="flex flex-wrap justify-end gap-2 max-[580px]:w-full max-[580px]:flex-col">
                <button class="page-action-button import-excel-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 px-3.5 text-[10px] font-bold text-white transition" type="button" data-modal-open="import-assets">Import Excel</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="create-asset">
                    <span class="text-sm font-normal leading-none">+</span> Tambah Aset
                </button>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="fixed right-6 top-[88px] z-70 flex items-center gap-[9px] rounded-[10px] border border-[#a9e2c9] bg-[#effcf6] px-4 py-3 text-[11px] font-semibold text-[#08714e] shadow-[0_12px_30px_rgb(23_91_65_/_12%)] max-[580px]:left-3 max-[580px]:right-3" role="status" data-auto-dismiss>
            <span class="grid h-[19px] w-[19px] place-items-center rounded-full bg-[#24a576] text-white">
                <svg class="h-3 w-3 fill-none stroke-current stroke-[3] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7" /></svg>
            </span>
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="mb-4 rounded-[10px] border border-[#fed7aa] bg-[#fff7ed] px-[15px] py-[13px] text-[11px] font-semibold text-[#9a3412]" role="alert">
            {{ session('warning') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <form class="filter-toolbar flex min-h-[86px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-4 max-[1100px]:flex-wrap" method="GET" action="{{ route($routeName) }}">
            <div class="flex h-[42px] w-[min(520px,100%)] items-center gap-[9px] rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 focus-within:border-[#6ca9d3] focus-within:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]">
                <svg class="w-[18px] shrink-0 fill-none stroke-[#8493a5] stroke-[1.8] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" /></svg>
                <input class="min-w-0 flex-1 border-0 bg-transparent text-[10px] text-[#34445a] outline-0 placeholder:text-[#78879a]" name="search" value="{{ request('search') }}" placeholder="Cari kode, nama, atau merek aset..." aria-label="Cari aset">
            </div>
            <select class="h-[42px] min-w-[155px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="kategori_id" aria-label="Filter kategori" onchange="this.form.submit()">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('kategori_id') === (string) $category->id)>{{ $category->nama_kategori }}</option>
                @endforeach
            </select>
            <select class="h-[42px] min-w-[145px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="kondisi" aria-label="Filter kondisi" onchange="this.form.submit()">
                <option value="">Semua kondisi</option>
                @foreach ($conditions as $value => $label)
                    <option value="{{ $value }}" @selected(request('kondisi') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="h-[42px] min-w-[150px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="status_verifikasi" aria-label="Filter verifikasi" onchange="this.form.submit()">
                <option value="">Semua verifikasi</option>
                @foreach ($verificationOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status_verifikasi') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            @if (request()->hasAny(['search', 'kategori_id', 'kondisi', 'status_verifikasi']))
                <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route($routeName) }}">Reset</a>
            @endif
            <x-export-dropdown dataset="aset" />
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Kode Internal</th>
                        <th>Nama Aset</th>
                        @if (! $verificationMode)
                            <th>Penanggung Jawab</th>
                        @else
                            <th>Kode Barang</th>
                        @endif
                        <th>Kondisi</th>
                        <th>Verifikasi</th>
                        <th>Lokasi</th>
                        @if ($canSeeAssetActions)
                            <th class="action-column">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $index => $asset)
                        @php
                            $status = $asset->status_verifikasi ?? 'disetujui';
                        @endphp
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td>
                                @if ($asset->kode_internal)
                                    <span class="code-badge">{{ $asset->kode_internal }}</span>
                                @else
                                    <span class="loan-status status-dikembalikan">Belum Dibuat</span>
                                @endif
                                @if (! $verificationMode)
                                    <small class="cell-subtitle">{{ $asset->kodeBarang?->kode_barang ?: '-' }}</small>
                                @endif
                            </td>
                            <td><strong>{{ $asset->nama_aset }}</strong> ({{ $asset->category->nama_kategori }})<small class="cell-subtitle">{{ $asset->merek_tipe ?: 'Merek/tipe belum dicatat' }}</small></td>
                            @if (! $verificationMode)
                                <td>
                                    @if ($asset->assetHolder)
                                        <strong>{{ $asset->assetHolder->nama_pegawai }}</strong>
                                        <small class="cell-subtitle">{{ \App\Enums\WorkUnit::labelFor($asset->assetHolder->unit_kerja) }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                            @else
                                <td>{{ $asset->kodeBarang ? $asset->kodeBarang->kode_barang : '-' }}</td>
                            @endif
                            <td class="asset-condition-cell">
                                @php($conditionValue = $conditionValueFor($asset))
                                <span class="asset-condition-pill {{ $conditionTone($conditionValue) }}" title="{{ $conditionLabelFor($asset) }}">
                                    <span>{{ $conditionLabelFor($asset) }}</span>
                                </span>
                            </td>
                            <td><span class="loan-status {{ $statusClass($status) }}">{{ $statusLabels[$status] ?? 'Disetujui' }}</span></td>
                            <td>{{ $asset->lokasi_penyimpanan ?: '-' }}</td>
                            @if ($canSeeAssetActions)
                                <td class="action-column">
                                    <div class="row-actions">
                                        <a href="{{ route('web.aset.show', $asset) }}" aria-label="Lihat {{ $asset->nama_aset }}" title="Detail dan QR">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                        </a>
                                        @if ($canManageAssets && ! $verificationMode)
                                            <button type="button" data-modal-open="edit-asset-{{ $asset->id }}" aria-label="Edit {{ $asset->nama_aset }}" title="Edit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" /></svg></button>
                                            <button class="danger" type="button" data-modal-open="delete-asset-{{ $asset->id }}" aria-label="Hapus {{ $asset->nama_aset }}" title="Hapus"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></button>
                                        @endif
                                        @if ($status === 'disetujui' && ($canManageAssets || $userRole === 'tim_aset'))
                                            <button type="button" data-modal-open="condition-asset-{{ $asset->id }}" title="Ubah kondisi" aria-label="Ubah kondisi {{ $asset->nama_aset }}"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5-5L12 3.6 9.6 6 7.3 3.7a4 4 0 0 0 5 5L4 17l3 3 7.7-7.7a4 4 0 0 0 5-5L17.4 9.6 15 7.2l-.3-.9Z" /></svg></button>
                                        @endif
                                        @if ($canVerifyAssets && $status === 'menunggu_verifikasi')
                                            <form method="POST" action="{{ route('web.aset.verifikasi', $asset) }}" data-submit-loading>
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="decision" value="disetujui">
                                                <button type="submit" title="Setujui aset" aria-label="Setujui {{ $asset->nama_aset }}"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5" /></svg></button>
                                            </form>
                                            <form method="POST" action="{{ route('web.aset.verifikasi', $asset) }}" data-submit-loading>
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="decision" value="ditolak">
                                                <button class="danger" type="submit" title="Tolak aset" aria-label="Tolak {{ $asset->nama_aset }}"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canSeeAssetActions ? 8 : 7 }}">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 7 8-4 8 4v10l-8 4-8-4V7Zm0 0 8 4 8-4m-8 4v10" /></svg></span>
                                    <strong>Aset tidak ditemukan</strong>
                                    <p>Ubah filter atau pencarian untuk melihat data lainnya.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route($routeName) }}">
                @foreach (request()->except(['per_page', 'page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <span>Tampilkan</span>
                <select name="per_page" aria-label="Jumlah data per halaman" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100] as $option)
                        <option value="{{ $option }}" @selected($selectedPerPage === $option)>{{ $option }} data</option>
                    @endforeach
                </select>
            </form>
            <span>Halaman {{ $assets->currentPage() }} dari {{ $assets->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $assets->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $assets->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18 9 12l6-6" /></svg></a>
                <a class="pagination-icon-button {{ $assets->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $assets->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg></a>
            </div>
        </div>
    </section>

    @if (! $verificationMode)
        @include('aset.partials.stock-opname-master', ['items' => $stockOpnameItems ?? collect()])
    @endif

    @if ($canManageAssets && ! $verificationMode)
        @include('aset.partials.modals', ['asset' => null, 'mode' => 'create'])
        @include('aset.partials.import-modal')
        @foreach ($assets as $asset)
            @include('aset.partials.modals', ['asset' => $asset, 'mode' => 'edit'])
            @include('aset.partials.delete-modal', ['asset' => $asset])
        @endforeach
    @endif

    @foreach ($assets as $asset)
        @if (($asset->status_verifikasi ?? 'disetujui') === 'disetujui' && ($canManageAssets || $userRole === 'tim_aset'))
            @include('aset.partials.condition-modal', ['asset' => $asset])
        @endif
    @endforeach

    @include('aset.partials.condition-guide-modal')
</main>
