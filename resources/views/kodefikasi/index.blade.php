@php
    $canCreate = auth()->user()?->hasPermission('kodefikasi.create') ?? false;
    $canUpdate = auth()->user()?->hasPermission('kodefikasi.update') ?? false;
    $canDelete = auth()->user()?->hasPermission('kodefikasi.delete') ?? false;
    $canImport = auth()->user()?->hasPermission('kodefikasi.import') ?? false;
    $canExport = auth()->user()?->hasPermission('kodefikasi.export') ?? false;
    $tabs = [
        'kode-barang' => ['label' => 'Kode Barang', 'type' => 'kode-barang', 'items' => $kodeBarang, 'code' => 'kode_barang', 'name' => 'nama_barang', 'options' => $kodeBarangOptions],
        'kode-persediaan' => ['label' => 'Kode Persediaan', 'type' => 'kode-persediaan', 'items' => $kodePersediaan, 'code' => 'kode_persediaan', 'name' => 'nama_barang', 'options' => $kodePersediaanOptions],
        'kode-lokasi' => ['label' => 'Kode Lokasi', 'type' => 'kode-lokasi', 'items' => $kodeLokasi, 'code' => 'kode_lokasi', 'name' => 'nama_lokasi', 'options' => $kodeLokasiOptions],
    ];
@endphp

@extends('layouts.app')

@section('title', 'Kodefikasi - SIMIVA')

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Kodefikasi</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Kelola master kode barang, persediaan, dan lokasi.</p>
        </div>
    </div>

    @if (session('import_errors'))
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
            @foreach (session('import_errors') as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="mb-5 inline-flex flex-wrap gap-1 rounded-[13px] bg-[#eef4fa] p-1">
        @foreach (collect($tabs)->map(fn ($tab, $key) => [$key, $tab['label']]) as [$key, $label])
            <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[10px] px-4 text-[10px] font-bold no-underline transition {{ $activeTab === $key ? 'bg-[#146bb5] text-white shadow-[0_8px_18px_rgb(20_107_181_/_18%)]' : 'text-[#53657a] hover:bg-white' }}" href="{{ route('kodefikasi.index', ['tab' => $key]) }}">{{ $label }}</a>
        @endforeach
    </div>

    @php($config = $tabs[$activeTab])
    @php($items = $config['items'])

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
            <div class="filter-toolbar flex min-h-[72px] items-center justify-between gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[1100px]:flex-wrap">
                <form class="flex flex-1 flex-wrap items-center gap-3" method="GET" action="{{ route('kodefikasi.index') }}">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <input class="h-[42px] min-w-[260px] flex-1 rounded-[9px] border border-[#dce4ec] bg-[#fbfcfe] px-3 text-[10px] outline-0 focus:border-[#6ca9d3] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama...">
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
                    <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('kodefikasi.index', ['tab' => $activeTab]) }}">Reset</a>
                    @if ($canExport)
                        <x-export-dropdown :dataset="$activeTab === 'kode-persediaan' ? 'kodefikasi-persediaan' : ($activeTab === 'kode-lokasi' ? 'kodefikasi-lokasi' : 'kodefikasi-barang')" />
                    @endif
                </form>

                @if ($canImport || $canCreate)
                    <div class="flex flex-wrap gap-2 max-[760px]:w-full">
                        @if ($canImport)
                            <button class="page-action-button import-excel-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 px-3.5 text-[10px] font-bold text-white transition" type="button" data-modal-open="import-{{ $activeTab }}">Import Excel</button>
                        @endif
                        @if ($canCreate)
                            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="create-{{ $activeTab }}"><span class="button-plus">+</span> Tambah</button>
                        @endif
                    </div>
                @endif
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="number-column">No</th>
                            <th>{{ $activeTab === 'kode-persediaan' ? 'Kode Barang' : $config['label'] }}</th>
                            <th>{{ $activeTab === 'kode-lokasi' ? 'Nama Lokasi / Unit Kerja' : 'Nama Barang' }}</th>
                            @if ($activeTab === 'kode-lokasi')
                                <th>Keterangan</th>
                            @endif
                            <th class="action-column">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="number-column">{{ $items->firstItem() + $loop->index }}</td>
                                <td><span class="code-badge">{{ $item->{$config['code']} }}</span></td>
                                <td>{{ $item->{$config['name']} }}</td>
                                @if ($activeTab === 'kode-lokasi')
                                    <td><span class="description-cell">{{ $item->keterangan ?: '-' }}</span></td>
                                @endif
                                <td class="action-column">
                                    <div class="row-actions">
                                        <button type="button" data-modal-open="detail-{{ $activeTab }}-{{ $item->id }}" title="Detail" aria-label="Detail {{ $item->{$config['name']} }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" /><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg></button>
                                        @if ($canUpdate)
                                            <button type="button" data-modal-open="edit-{{ $activeTab }}-{{ $item->id }}" title="Edit" aria-label="Edit {{ $item->{$config['name']} }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" /></svg></button>
                                        @endif
                                        @if ($canDelete)
                                            <button class="danger" type="button" data-modal-open="delete-{{ $activeTab }}-{{ $item->id }}" title="Hapus" aria-label="Hapus {{ $item->{$config['name']} }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $activeTab === 'kode-lokasi' ? 5 : 4 }}">
                                    <div class="table-empty">
                                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4z" /><path d="M8 8h8M8 12h5M8 16h6" /></svg></span>
                                        <strong>Belum ada data</strong>
                                        <p>Data kodefikasi belum tersedia pada filter ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-pagination">
                <form class="per-page-control" method="GET" action="{{ route('kodefikasi.index') }}">
                    @foreach (request()->except(['per_page', 'page']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <span>Tampilkan</span>
                    <select name="per_page" aria-label="Jumlah data per halaman" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $option)
                            <option value="{{ $option }}" @selected((int) request('per_page', $items->perPage()) === $option)>{{ $option }} data</option>
                        @endforeach
                    </select>
                </form>
                <span>Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}</span>
                <div>
                    <a class="pagination-icon-button {{ $items->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $items->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18 9 12l6-6" /></svg></a>
                    <a class="pagination-icon-button {{ $items->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $items->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg></a>
                </div>
            </div>
    </section>

    @include('kodefikasi.partials.modals', compact('config', 'items', 'activeTab', 'canCreate', 'canUpdate', 'canDelete', 'canImport'))
</main>
@endsection
