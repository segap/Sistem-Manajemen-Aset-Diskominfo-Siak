@extends('layouts.app')

@section('title', 'Kategori Aset - SIMIVA')

@php
    $numberStart = ($categories->currentPage() - 1) * $categories->perPage();
    $canCreateCategories = auth()->user()?->hasPermission('kategori_aset.create') ?? false;
    $canUpdateCategories = auth()->user()?->hasPermission('kategori_aset.update') ?? false;
    $canDeleteCategories = auth()->user()?->hasPermission('kategori_aset.delete') ?? false;
    $canManageCategories = $canUpdateCategories || $canDeleteCategories;
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <span class="hidden">MASTER DATA</span>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Kategori Aset</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Atur pengelompokan barang agar data inventaris lebih terstruktur.</p>
        </div>
        @if ($canCreateCategories)
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f] max-[580px]:w-full" type="button" data-modal-open="create-category">
                <span class="text-sm font-normal leading-none">+</span> Tambah Kategori
            </button>
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

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <div class="filter-toolbar flex min-h-[72px] items-center justify-between gap-5 border-b border-[#edf1f5] px-[18px] py-3.5 max-[580px]:flex-col max-[580px]:items-stretch">
            <form class="flex h-[42px] w-[min(430px,100%)] items-center gap-[9px] rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 focus-within:border-[#6ca9d3] focus-within:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" method="GET" action="{{ route('web.kategori-aset.index') }}">
                <svg class="w-[18px] shrink-0 fill-none stroke-[#8493a5] stroke-[1.8] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" /></svg>
                <input class="min-w-0 flex-1 border-0 bg-transparent text-[10px] text-[#34445a] outline-0" name="search" value="{{ $search }}" placeholder="Cari kode atau nama kategori..." aria-label="Cari kategori">
                @if ($search !== '')
                    <a class="grid h-[25px] w-[25px] place-items-center rounded-md border-0 bg-[#edf2f6] text-[#8695a7] no-underline" href="{{ route('web.kategori-aset.index') }}" aria-label="Hapus pencarian">
                        <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                    </a>
                @endif
            </form>
            @if ($exportFormats !== [])
                <div class="flex flex-wrap items-center gap-3">
                    <x-export-dropdown dataset="kategori-aset" :formats="$exportFormats" />
                </div>
            @endif
        </div>

        @if (session('error'))
            <div class="mx-4 mt-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ session('error') }}</div>
        @endif

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Kode</th>
                        <th>Nama Kategori</th>
                        <th>Ikon</th>
                        <th>Deskripsi</th>
                        @if ($canManageCategories)<th class="action-column">Aksi</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $index => $category)
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td><span class="code-badge">{{ $category->kode_kategori }}</span></td>
                            <td>{{ $category->nama_kategori }}</td>
                            <td>
                                @if ($category->ikon)
                                    <span class="icon-value">{{ $category->ikon }}</span>
                                @else
                                    <span class="empty-value">-</span>
                                @endif
                            </td>
                            <td><span class="description-cell">{{ $category->deskripsi ?: 'Belum ada deskripsi' }}</span></td>
                            @if ($canManageCategories)
                                <td class="action-column">
                                    <div class="row-actions">
                                    @if ($canUpdateCategories)
                                    <button type="button" data-modal-open="edit-category-{{ $category->id }}" aria-label="Edit {{ $category->nama_kategori }}" title="Edit">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" /></svg>
                                    </button>
                                    @endif
                                    @if ($canDeleteCategories)
                                    <button class="danger" type="button" data-modal-open="delete-category-{{ $category->id }}" aria-label="Hapus {{ $category->nama_kategori }}" title="Hapus">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg>
                                    </button>
                                    @endif
                                </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageCategories ? 6 : 5 }}">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5-8 4.5-8-4.5Z" /><path d="M4 12.5 12 17l8-4.5" /><path d="M4 17.5 12 22l8-4.5" /></svg></span>
                                    <strong>Belum ada kategori</strong>
                                    <p>{{ $search !== '' ? 'Tidak ada kategori yang cocok dengan pencarian.' : 'Tambahkan kategori pertama untuk mulai mengelompokkan aset.' }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <span>Halaman {{ $categories->currentPage() }} dari {{ $categories->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $categories->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $categories->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18 9 12l6-6" /></svg>
                </a>
                <a class="pagination-icon-button {{ $categories->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $categories->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                </a>
            </div>
        </div>
    </section>

    @if ($canCreateCategories)
    <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="create-category" role="presentation">
        <section class="w-[min(620px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="create-category-title">
            <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                <div><span class="hidden">DATA BARU</span><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="create-category-title">Tambah Kategori</h2></div>
                <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
            </header>
            <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('web.kategori-aset.store') }}" data-submit-loading>
                @csrf
                <input type="hidden" name="modal_id" value="create-category">
                <div class="grid grid-cols-[1fr_1.4fr] gap-4 max-[580px]:grid-cols-1">
                    @include('kategori-aset.partials.field', ['label' => 'Kode kategori', 'name' => 'kode_kategori', 'value' => old('kode_kategori'), 'placeholder' => 'Contoh: LPT', 'required' => true])
                    @include('kategori-aset.partials.field', ['label' => 'Nama kategori', 'name' => 'nama_kategori', 'value' => old('nama_kategori'), 'placeholder' => 'Contoh: Laptop', 'required' => true])
                </div>
                @include('kategori-aset.partials.field', ['label' => 'Nama ikon', 'name' => 'ikon', 'value' => old('ikon'), 'placeholder' => 'Contoh: laptop', 'hint' => 'Opsional, digunakan oleh tampilan aplikasi.'])
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Deskripsi</span>
                    <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)] @error('deskripsi') border-[#e08a84] @enderror" name="deskripsi" rows="4" placeholder="Jelaskan jenis aset dalam kategori ini..." maxlength="1000">{{ old('deskripsi') }}</textarea>
                    @error('deskripsi') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
                <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Tambah Kategori</span></button>
                </footer>
            </form>
        </section>
    </div>
    @endif

    @foreach ($categories as $category)
        @if ($canUpdateCategories)
        <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="edit-category-{{ $category->id }}" role="presentation">
            <section class="w-[min(620px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="edit-category-title-{{ $category->id }}">
                <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                    <div><span class="hidden">PERBARUI DATA</span><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="edit-category-title-{{ $category->id }}">Edit Kategori</h2></div>
                    <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                </header>
                <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('web.kategori-aset.update', ['asset_category' => $category, 'search' => $search, 'page' => $categories->currentPage()]) }}" data-submit-loading>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="modal_id" value="edit-category-{{ $category->id }}">
                    <div class="grid grid-cols-[1fr_1.4fr] gap-4 max-[580px]:grid-cols-1">
                        @include('kategori-aset.partials.field', ['label' => 'Kode kategori', 'name' => 'kode_kategori', 'value' => old('kode_kategori', $category->kode_kategori), 'placeholder' => 'Contoh: LPT', 'required' => true, 'hint' => 'Huruf, angka, - atau _'])
                        @include('kategori-aset.partials.field', ['label' => 'Nama kategori', 'name' => 'nama_kategori', 'value' => old('nama_kategori', $category->nama_kategori), 'placeholder' => 'Contoh: Laptop', 'required' => true])
                    </div>
                    @include('kategori-aset.partials.field', ['label' => 'Nama ikon', 'name' => 'ikon', 'value' => old('ikon', $category->ikon), 'placeholder' => 'Contoh: laptop', 'hint' => 'Opsional, digunakan oleh tampilan aplikasi.'])
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Deskripsi</span>
                        <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)] @error('deskripsi') border-[#e08a84] @enderror" name="deskripsi" rows="4" placeholder="Jelaskan jenis aset dalam kategori ini..." maxlength="1000">{{ old('deskripsi', $category->deskripsi) }}</textarea>
                        @error('deskripsi') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </label>
                    <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Simpan Perubahan</span></button>
                    </footer>
                </form>
            </section>
        </div>
        @endif

        @if ($canDeleteCategories)
        <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-category-{{ $category->id }}" role="presentation">
            <section class="w-[min(410px,100%)] rounded-2xl bg-white p-[27px] text-center shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="alertdialog" aria-modal="true" aria-labelledby="delete-title-{{ $category->id }}">
                <span class="mx-auto mb-3.5 grid h-12 w-12 place-items-center rounded-full bg-[#fff0ee] text-[21px] font-extrabold text-[#b4443d]">!</span>
                <h2 class="m-0 mb-2 text-[19px] font-extrabold text-[#0b2b50]" id="delete-title-{{ $category->id }}">Hapus kategori?</h2>
                <p class="m-0 text-[11px] leading-relaxed text-[#718095]">Kategori <strong>{{ $category->nama_kategori }}</strong> akan dihapus. Tindakan ini tidak dapat dibatalkan.</p>
                <form method="POST" action="{{ route('web.kategori-aset.destroy', ['asset_category' => $category, 'search' => $search, 'page' => $categories->currentPage()]) }}">
                    @csrf
                    @method('DELETE')
                    <footer class="mt-[22px] flex justify-center gap-2 border-t border-[#edf1f5] pt-[18px]">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c44f47] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#ad433c]" type="submit">Ya, Hapus</button>
                    </footer>
                </form>
            </section>
        </div>
        @endif
    @endforeach
</main>

@if ($errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const modal = document.querySelector('[data-modal="{{ old('modal_id', 'create-category') }}"]')
            modal?.classList.remove('hidden')
            modal?.classList.add('grid')
        })
    </script>
@endif
@endsection
