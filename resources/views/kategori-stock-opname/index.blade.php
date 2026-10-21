@extends('layouts.app')

@section('title', 'Kategori Stock Opname - SIMIVA')

@php
    $numberStart = ($categories->currentPage() - 1) * $categories->perPage();
    $number = fn ($value) => number_format((int) $value, 0, ',', '.');
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Kategori Stock Opname</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Kelola kategori barang persediaan dan barang habis pakai.</p>
        </div>
        @if ($canManage)
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f] max-[580px]:w-full" type="button" data-modal-open="create-stock-category">
                <span class="text-sm font-normal leading-none">+</span> Tambah Kategori
            </button>
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

    @if (session('error'))
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ session('error') }}</div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <div class="filter-toolbar flex min-h-[72px] items-center justify-between gap-5 border-b border-[#edf1f5] px-[18px] py-3.5 max-[700px]:flex-col max-[700px]:items-stretch">
            <form class="flex h-[42px] w-[min(430px,100%)] items-center gap-[9px] rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 focus-within:border-[#6ca9d3] focus-within:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" method="GET" action="{{ route('kategori-stock-opname.index') }}">
                <svg class="w-[18px] shrink-0 fill-none stroke-[#8493a5] stroke-[1.8] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" /></svg>
                <input class="min-w-0 flex-1 border-0 bg-transparent text-[10px] text-[#34445a] outline-0" name="search" value="{{ $search }}" placeholder="Cari kategori..." aria-label="Cari kategori stock opname">
                @if ($search !== '')
                    <a class="grid h-[25px] w-[25px] place-items-center rounded-md border-0 bg-[#edf2f6] text-[#8695a7] no-underline" href="{{ route('kategori-stock-opname.index') }}" aria-label="Hapus pencarian">
                        <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg>
                    </a>
                @endif
            </form>
            <div class="flex flex-wrap items-center gap-3">
                <x-export-dropdown dataset="kategori-stock-opname" />
            </div>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Nama Kategori</th>
                        <th>Jumlah Barang</th>
                        <th>Keterangan</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $index => $category)
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td>{{ $category->nama_kategori }}</td>
                            <td>{{ $number($category->items_count) }} Barang</td>
                            <td><span class="description-cell">{{ $category->keterangan ?: '-' }}</span></td>
                            <td class="action-column">
                                @if ($canManage)
                                    <div class="row-actions">
                                        <button type="button" data-modal-open="edit-stock-category-{{ $category->id }}" aria-label="Edit {{ $category->nama_kategori }}" title="Edit"><svg viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" /></svg></button>
                                        <button class="danger" type="button" data-modal-open="delete-stock-category-{{ $category->id }}" aria-label="Hapus {{ $category->nama_kategori }}" title="Hapus"><svg viewBox="0 0 24 24"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></button>
                                    </div>
                                @else
                                    <span class="text-[10px] text-[#8a98aa]">Lihat saja</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24"><path d="M4 7.5 12 3l8 4.5-8 4.5-8-4.5Z" /><path d="M4 12.5 12 17l8-4.5" /><path d="M4 17.5 12 22l8-4.5" /></svg></span>
                                    <strong>Belum ada kategori stock opname</strong>
                                    <p>{{ $search !== '' ? 'Tidak ada kategori yang cocok dengan pencarian.' : 'Tambahkan kategori untuk mengelompokkan barang persediaan.' }}</p>
                                    @if ($canManage)
                                        <button class="page-action-button mt-3 inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="button" data-modal-open="create-stock-category">+ Tambah Kategori</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route('kategori-stock-opname.index') }}">
                @if ($search !== '')
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif
                <span>Tampilkan</span>
                <select name="per_page" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100] as $option)
                        <option value="{{ $option }}" @selected($selectedPerPage === $option)>{{ $option }} data</option>
                    @endforeach
                </select>
            </form>
            <span>Halaman {{ $categories->currentPage() }} dari {{ $categories->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $categories->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $categories->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24"><path d="M15 18 9 12l6-6" /></svg></a>
                <a class="pagination-icon-button {{ $categories->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $categories->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" /></svg></a>
            </div>
        </div>
    </section>

    @if ($canManage)
        @include('kategori-stock-opname.partials.form-modal', ['modalId' => 'create-stock-category', 'title' => 'Tambah Kategori', 'action' => route('kategori-stock-opname.store'), 'method' => 'POST', 'category' => null])
        @foreach ($categories as $category)
            @include('kategori-stock-opname.partials.form-modal', ['modalId' => 'edit-stock-category-'.$category->id, 'title' => 'Edit Kategori', 'action' => route('kategori-stock-opname.update', ['kategoriStockOpname' => $category, 'search' => $search, 'page' => $categories->currentPage(), 'per_page' => $selectedPerPage]), 'method' => 'PUT', 'category' => $category])
            <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-stock-category-{{ $category->id }}" role="presentation">
                <section class="w-[min(410px,100%)] rounded-2xl bg-white p-[27px] text-center shadow-[0_25px_80px_rgb(2_19_34_/_28%)]" role="alertdialog" aria-modal="true">
                    <span class="mx-auto mb-3.5 grid h-12 w-12 place-items-center rounded-full bg-[#fff0ee] text-[21px] font-extrabold text-[#b4443d]">!</span>
                    <h2 class="m-0 mb-2 text-[19px] font-extrabold text-[#0b2b50]">Hapus kategori ini?</h2>
                    <p class="m-0 text-[11px] leading-relaxed text-[#718095]">Kategori <strong>{{ $category->nama_kategori }}</strong> akan dihapus jika belum digunakan barang persediaan. Tindakan ini tidak dapat dibatalkan.</p>
                    <form method="POST" action="{{ route('kategori-stock-opname.destroy', ['kategoriStockOpname' => $category, 'search' => $search, 'page' => $categories->currentPage(), 'per_page' => $selectedPerPage]) }}">
                        @csrf
                        @method('DELETE')
                        <footer class="mt-[22px] flex justify-center gap-2 border-t border-[#edf1f5] pt-[18px]">
                            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c44f47] px-3.5 text-[10px] font-bold text-white" type="submit">Ya, Hapus</button>
                        </footer>
                    </form>
                </section>
            </div>
        @endforeach
    @endif
</main>

@if ($errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const modal = document.querySelector('[data-modal="{{ old('modal_id', 'create-stock-category') }}"]')
            modal?.classList.remove('hidden')
            modal?.classList.add('grid')
        })
    </script>
@endif
@endsection
