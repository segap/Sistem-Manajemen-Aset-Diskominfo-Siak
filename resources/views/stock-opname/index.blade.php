@extends('layouts.app')

@section('title', 'Stock Opname - SIMIVA')

@php
    use App\Enums\StockOpnameVerificationStatus;
    use App\Enums\UserRole;

    $role = auth()->user()?->role;
    $canManage = in_array($role, [UserRole::Admin->value, UserRole::SuperAdmin->value], true);
    $canDelete = $role === UserRole::SuperAdmin->value;
    $canVerify = in_array($role, [UserRole::TimAset->value, UserRole::TimAsetPersediaan->value, UserRole::SuperAdmin->value], true);
    $numberStart = ($items->currentPage() - 1) * $items->perPage();
    $number = fn ($value) => number_format((int) $value, 0, ',', '.');
    $formatDateTime = fn ($date) => $date ? ($date instanceof \Carbon\CarbonInterface ? $date : \Carbon\Carbon::parse($date))->translatedFormat('d M Y H:i') : '-';
    $stockStatusLabel = fn ($stock) => $stock === 0 ? 'Stok Habis' : ($stock < 5 ? 'Stok Menipis' : 'Tersedia');
    $stockStatusClass = fn ($stock) => $stock === 0 ? 'status-ditolak' : ($stock < 5 ? 'status-stok-menipis' : 'status-disetujui');
    $stockTotal = fn ($item) => max(
        (int) $item->stok,
        (int) ($item->mutations->max('stok_sebelum') ?? 0),
        (int) ($item->mutations->max('stok_sesudah') ?? 0),
    );
    $verificationClass = fn ($status) => match ($status instanceof StockOpnameVerificationStatus ? $status->value : $status) {
        'disetujui_tim_aset' => 'status-disetujui',
        'ditolak_tim_aset' => 'status-ditolak',
        default => 'status-diajukan',
    };
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Stock Opname</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Kelola barang persediaan, verifikasi Tim Aset, dan penyesuaian stok fisik.</p>
        </div>
        @if ($canManage)
            <div class="flex flex-wrap justify-end gap-2 max-[580px]:w-full max-[580px]:flex-col">
                <button class="page-action-button import-excel-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 px-3.5 text-[10px] font-bold text-white transition" type="button" data-modal-open="import-stock-opname">Import Excel</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="create-stock-opname"><span class="text-sm font-normal leading-none">+</span> Tambah Barang</button>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="fixed right-6 top-[88px] z-70 flex items-center gap-[9px] rounded-[10px] border border-[#a9e2c9] bg-[#effcf6] px-4 py-3 text-[11px] font-semibold text-[#08714e] shadow-[0_12px_30px_rgb(23_91_65_/_12%)] max-[580px]:left-3 max-[580px]:right-3" role="status" data-auto-dismiss>
            <span class="grid h-[19px] w-[19px] place-items-center rounded-full bg-[#24a576] text-white"><svg class="h-3 w-3 fill-none stroke-current stroke-[3] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" /></svg></span>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <form class="filter-toolbar flex min-h-[72px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[920px]:flex-wrap" method="GET" action="{{ route('stock-opname.index') }}">
            <input class="h-[42px] w-[min(320px,100%)] rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 text-[10px] text-[#34445a] outline-0 placeholder:text-[#78879a]" name="search" value="{{ request('search') }}" placeholder="Cari kode, nama, merek, lokasi...">
            <select class="h-[42px] min-w-[185px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="category" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->nama_kategori }}</option>
                @endforeach
            </select>
            <select class="h-[42px] min-w-[165px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="stock_status" onchange="this.form.submit()">
                <option value="">Semua Stok</option>
                <option value="rendah" @selected(request('stock_status') === 'rendah')>Stok Rendah</option>
                <option value="tersedia" @selected(request('stock_status') === 'tersedia')>Tersedia</option>
                <option value="menipis" @selected(request('stock_status') === 'menipis')>Stok Menipis</option>
                <option value="habis" @selected(request('stock_status') === 'habis')>Stok Habis</option>
            </select>
            <select class="h-[42px] min-w-[175px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="verification" onchange="this.form.submit()">
                <option value="">Semua Verifikasi</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('verification') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            @if (request()->hasAny(['search', 'stock_status', 'verification', 'category']))
                <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('stock-opname.index') }}">Reset</a>
            @endif
            <x-export-dropdown dataset="stock-opname" />
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Kode Internal</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th>Stok</th>
                        <th>Status Stok</th>
                        <th>Verifikasi</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $index => $item)
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td>
                                <span class="code-badge">{{ $item->kode_internal ?: '-' }}</span>
                            </td>
                            <td><strong>{{ $item->nama_barang }}</strong><small class="cell-subtitle">{{ $item->merek_tipe ?: 'Merek/tipe belum dicatat' }}</small></td>
                            <td>{{ $item->category?->nama_kategori }}</td>
                            <td>{{ $item->satuan }}</td>
                            <td>
                                <strong>{{ $number($item->stok) }}</strong>
                                <small class="cell-subtitle">dari {{ $number($stockTotal($item)) }} tersedia</small>
                            </td>
                            <td><span class="loan-status {{ $stockStatusClass($item->stok) }}">{{ $stockStatusLabel($item->stok) }}</span></td>
                            <td><span class="loan-status {{ $verificationClass($item->status_verifikasi) }}">{{ $item->status_verifikasi->label() }}</span></td>
                            <td class="action-column">
                                <div class="row-actions">
                                    <button type="button" data-modal-open="detail-stock-opname-{{ $item->id }}" title="Detail" aria-label="Detail {{ $item->nama_barang }}"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg></button>
                                    @if ($canManage)
                                        <button type="button" data-modal-open="edit-stock-opname-{{ $item->id }}" title="Edit" aria-label="Edit {{ $item->nama_barang }}"><svg viewBox="0 0 24 24"><path d="m4 20 4.5-1 9.8-9.8a2.1 2.1 0 0 0-3-3L5.5 16 4 20Z" /></svg></button>
                                        <button type="button" data-modal-open="add-stock-opname-{{ $item->id }}" title="Tambah Stok" aria-label="Tambah stok {{ $item->nama_barang }}"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" /></svg></button>
                                        @if ($item->status_verifikasi === StockOpnameVerificationStatus::Approved)
                                            <button type="button" data-modal-open="adjust-stock-opname-{{ $item->id }}" title="Stock opname fisik" aria-label="Stock opname {{ $item->nama_barang }}"><svg viewBox="0 0 24 24"><path d="M4 7h16M7 7v12h10V7M9 11h6m-6 4h6" /></svg></button>
                                        @endif
                                    @endif
                                    @if ($canVerify && $item->status_verifikasi === StockOpnameVerificationStatus::Submitted)
                                        <button type="button" data-modal-open="verify-stock-opname-{{ $item->id }}" title="Setujui" aria-label="Setujui {{ $item->nama_barang }}"><svg viewBox="0 0 24 24"><path d="M5 12l4 4L19 6" /></svg></button>
                                        <button class="danger" type="button" data-modal-open="verify-stock-opname-{{ $item->id }}" title="Tolak" aria-label="Tolak {{ $item->nama_barang }}"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                                    @endif
                                    @if ($canDelete)
                                        <button class="danger" type="button" data-modal-open="delete-stock-opname-{{ $item->id }}" title="Hapus Barang" aria-label="Hapus {{ $item->nama_barang }}"><svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24"><path d="M5 5h14v14H5z" /><path d="M8 9h8M8 13h5" /></svg></span>
                                    <strong>Belum ada barang persediaan</strong>
                                    <p>Data stock opname belum tersedia pada filter ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route('stock-opname.index') }}">
                @foreach (request()->except(['per_page', 'page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <span>Tampilkan</span>
                <select name="per_page" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100] as $option)
                        <option value="{{ $option }}" @selected($selectedPerPage === $option)>{{ $option }} data</option>
                    @endforeach
                </select>
            </form>
            <span>Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $items->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $items->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24"><path d="M15 18 9 12l6-6" /></svg></a>
                <a class="pagination-icon-button {{ $items->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $items->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" /></svg></a>
            </div>
        </div>
    </section>

    @if ($canManage)
        @include('stock-opname.partials.form-modal', ['modalId' => 'create-stock-opname', 'title' => 'Tambah Barang Persediaan', 'action' => route('stock-opname.store'), 'method' => 'POST', 'item' => null])
        @include('stock-opname.partials.import-modal')
    @endif

    @foreach ($items as $item)
        @include('stock-opname.partials.detail-modal', compact('item', 'formatDateTime', 'stockStatusLabel', 'stockStatusClass', 'verificationClass'))
        @if ($canManage)
            @include('stock-opname.partials.form-modal', ['modalId' => 'edit-stock-opname-'.$item->id, 'title' => 'Edit Barang Persediaan', 'action' => route('stock-opname.update', $item), 'method' => 'PUT', 'item' => $item])
            @include('stock-opname.partials.add-stock-modal', compact('item'))
            @include('stock-opname.partials.adjust-modal', compact('item'))
        @endif
        @if ($canDelete)
            @include('stock-opname.partials.delete-modal', compact('item'))
        @endif
        @if ($canVerify && $item->status_verifikasi === StockOpnameVerificationStatus::Submitted)
            @include('stock-opname.partials.verify-modal', compact('item'))
        @endif
    @endforeach
</main>
@endsection
