@extends('layouts.app')

@section('title', 'Detail KIR - SIMIVA')

@php
    $canManageKir = in_array(auth()->user()?->role, ['admin', 'super_admin'], true);
    $canExportKir = auth()->user()?->hasPermission('kir.export') ?? false;
    $numberStart = ($details->currentPage() - 1) * $details->perPage();
    $selectedPerPage = (int) request('per_page', $details->perPage());
    $openModal = old('_modal');
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[680px]:flex-col max-[680px]:items-stretch">
        <div>
            <a class="mb-4 inline-flex items-center text-[11px] font-bold text-[#146bb5] no-underline" href="{{ route('kir.index') }}">← Kembali ke KIR</a>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">KIR {{ $ruangan->nama_ruangan }}</h1>
            <p class="m-0 text-[13px] text-[#66758a]">{{ $ruangan->kode_ruangan }} · {{ $kir->kode_lokasi ?: 'Kode lokasi belum diisi' }}</p>
        </div>
        @if ($canManageKir)
            <div class="flex flex-wrap justify-end gap-2 max-[680px]:grid max-[680px]:grid-cols-2">
                @if ($canExportKir)
                    <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('kir.pdf', $ruangan) }}">Unduh PDF</a>
                    <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('kir.word', $ruangan) }}">Unduh Word</a>
                @endif
                <button class="page-action-button import-excel-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 px-3.5 text-[10px] font-bold text-white transition" type="button" data-modal-open="import-kir">Import Excel</button>
            </div>
        @elseif ($canExportKir)
            <div class="flex flex-wrap justify-end gap-2 max-[680px]:grid max-[680px]:grid-cols-2">
                <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('kir.pdf', $ruangan) }}">Unduh PDF</a>
                <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('kir.word', $ruangan) }}">Unduh Word</a>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="fixed right-6 top-[88px] z-70 flex items-center gap-[9px] rounded-[10px] border border-[#a9e2c9] bg-[#effcf6] px-4 py-3 text-[11px] font-semibold text-[#08714e] shadow-[0_12px_30px_rgb(23_91_65_/_12%)] max-[580px]:left-3 max-[580px]:right-3" role="status" data-auto-dismiss>
            <span class="grid h-[19px] w-[19px] place-items-center rounded-full bg-[#24a576] text-white"><svg class="h-3 w-3 fill-none stroke-current stroke-[3] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7" /></svg></span>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any() && ! $openModal)
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="mb-5 overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <div class="border-b border-[#edf1f5] px-[18px] py-5 text-center">
            <h2 class="m-0 text-[18px] font-extrabold uppercase tracking-[.05em] text-[#0b2b50]">Kartu Inventaris Ruangan (KIR)</h2>
            <p class="m-0 mt-2 text-[11px] font-semibold text-[#66758a]">{{ $kir->pengguna_barang }}</p>
        </div>
        <div class="grid gap-0 md:grid-cols-2">
            <div class="border-b border-r border-[#edf1f5] px-5 py-4 max-md:border-r-0">
                <span class="block text-[9px] font-extrabold uppercase tracking-[.08em] text-[#8997aa]">Pengguna Barang</span>
                <strong class="mt-1 block text-[12px] text-[#0b2b50]">{{ $kir->pengguna_barang }}</strong>
            </div>
            <div class="border-b border-[#edf1f5] px-5 py-4">
                <span class="block text-[9px] font-extrabold uppercase tracking-[.08em] text-[#8997aa]">Kode Lokasi</span>
                <strong class="mt-1 block text-[12px] text-[#0b2b50]">{{ $kir->kode_lokasi ?: '-' }}</strong>
            </div>
            <div class="border-r border-[#edf1f5] px-5 py-4 max-md:border-b max-md:border-r-0">
                <span class="block text-[9px] font-extrabold uppercase tracking-[.08em] text-[#8997aa]">Nama Ruangan</span>
                <strong class="mt-1 block text-[12px] text-[#0b2b50]">{{ $ruangan->nama_ruangan }}</strong>
            </div>
            <div class="px-5 py-4">
                <span class="block text-[9px] font-extrabold uppercase tracking-[.08em] text-[#8997aa]">Penanggung Jawab Ruangan</span>
                <strong class="mt-1 block text-[12px] text-[#0b2b50]">{{ $ruangan->penanggungJawab?->name ?? '-' }}</strong>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <form class="filter-toolbar flex min-h-[72px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[760px]:flex-wrap" method="GET" action="{{ route('kir.show', $ruangan) }}">
            <div class="flex h-[42px] w-[min(430px,100%)] items-center rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 focus-within:border-[#6ca9d3] focus-within:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]">
                <input class="min-w-0 flex-1 border-0 bg-transparent text-[10px] text-[#34445a] outline-0 placeholder:text-[#78879a]" name="search" value="{{ request('search') }}" placeholder="Cari NIBAR, register, kode, nama, atau merek...">
            </div>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('kir.show', $ruangan) }}">Reset</a>
            <div class="ml-auto flex items-center gap-3 max-[760px]:ml-0 max-[760px]:w-full max-[760px]:justify-between">
                @if ($canManageKir)
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="create-kir-detail">
                        <span class="text-sm font-normal leading-none">+</span> Tambah Barang
                    </button>
                @endif
            </div>
        </form>

        <div class="table-scroll">
            <table class="data-table min-w-[1280px]">
                <thead>
                    <tr>
                        <th rowspan="2" class="number-column">No</th>
                        <th colspan="4" class="text-center">Identitas Barang</th>
                        <th colspan="3" class="text-center">Uraian Barang</th>
                        <th colspan="4" class="text-center">Jumlah dan Keterangan</th>
                        @if ($canManageKir)
                            <th rowspan="2" class="action-column">Aksi</th>
                        @endif
                    </tr>
                    <tr>
                        <th>NIBAR</th>
                        <th>Nomor Register</th>
                        <th>Kode Lokasi</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Spesifikasi Nama Barang</th>
                        <th>Merek/Tipe</th>
                        <th>Tahun Perolehan</th>
                        <th>Jumlah</th>
                        <th>Satuan</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($details as $index => $detail)
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td>{{ $detail->nibar ?: '-' }}</td>
                            <td>{{ $detail->nomor_register ?: '-' }}</td>
                            <td>{{ $detail->kode_lokasi ?: '-' }}</td>
                            <td>{{ $detail->kode_barang ?: '-' }}</td>
                            <td><strong>{{ $detail->nama_barang }}</strong></td>
                            <td>{{ $detail->spesifikasi_nama_barang ?: '-' }}</td>
                            <td>{{ $detail->merek_tipe ?: '-' }}</td>
                            <td>{{ $detail->tahun_perolehan ?: '-' }}</td>
                            <td><strong>{{ $detail->jumlah }}</strong></td>
                            <td>{{ $detail->satuan }}</td>
                            <td>{{ $detail->keterangan ?: '-' }}</td>
                            @if ($canManageKir)
                                <td class="action-column">
                                    <div class="row-actions">
                                        <button type="button" title="Edit" data-modal-open="edit-kir-detail-{{ $detail->id }}" aria-label="Edit {{ $detail->nama_barang }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" /></svg></button>
                                        <button class="danger" type="button" title="Hapus" data-modal-open="delete-kir-detail-{{ $detail->id }}" aria-label="Hapus {{ $detail->nama_barang }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageKir ? 13 : 12 }}">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12a2 2 0 0 1 2 2v16l-4-2-4 2-4-2-4 2V5a2 2 0 0 1 2-2Z" /><path d="M9 8h6M9 12h6M9 16h4" /></svg></span>
                                    <strong>Barang KIR belum ada</strong>
                                    <p>Tambahkan barang secara manual atau impor dari Excel.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route('kir.show', $ruangan) }}">
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
            <span>Halaman {{ $details->currentPage() }} dari {{ $details->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $details->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $details->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18 9 12l6-6" /></svg></a>
                <a class="pagination-icon-button {{ $details->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $details->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg></a>
            </div>
        </div>
    </section>

    @if ($canManageKir)
        <div class="fixed inset-0 z-80 {{ $openModal === 'create' ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="create-kir-detail" role="presentation">
            <section class="w-[min(900px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
                <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                    <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Tambah Barang KIR</h2>
                    <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                </header>
                <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('kir.store', $ruangan) }}" data-submit-loading>
                    @csrf
                    <input type="hidden" name="_modal" value="create">
                    @if ($openModal === 'create' && $errors->any())
                        <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
                    @endif
                    @include('kir.partials.form-fields', ['detail' => null])
                    @include('users.partials.form-footer', ['submitLabel' => 'Simpan'])
                </form>
            </section>
        </div>

        <div class="fixed inset-0 z-80 {{ $openModal === 'import' ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="import-kir" role="presentation">
            <section class="w-[min(620px,100%)] rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
                <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]"><h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Import KIR dari Excel</h2><button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button></header>
                <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('kir.import', $ruangan) }}" enctype="multipart/form-data" data-submit-loading>
                    @csrf
                    <input type="hidden" name="_modal" value="import">
                    @if ($openModal === 'import' && $errors->any())
                        <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                    <div class="rounded-[12px] bg-[#f3f7fb] p-4 text-[11px] leading-relaxed text-[#65748a]">
                        <strong class="mb-1 block text-[#34445a]">Format kolom Excel</strong>
                        NIBAR, Nomor Register, Kode Lokasi, Kode Barang, Nama Barang, Spesifikasi Nama Barang, Merek/Tipe, Tahun Perolehan, Jumlah, Satuan, Keterangan.
                    </div>
                    <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('kir.template', $ruangan) }}">Unduh Template</a>
                    <label class="grid gap-2 text-[11px] font-extrabold text-[#34445a]">
                        File Excel / CSV (maks. 10 MB)
                        <input class="w-full rounded-[9px] border border-[#dce4ee] bg-white px-3 py-3 text-[10px] font-semibold text-[#34445a] file:mr-3 file:rounded-md file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[10px] file:font-extrabold file:text-[#146bb5]" type="file" name="file" accept=".xlsx,.xls,.csv" required>
                    </label>
                    @include('users.partials.form-footer', ['submitLabel' => 'Import Data'])
                </form>
            </section>
        </div>

        @foreach ($details as $detail)
            @php
                $editOpen = $openModal === 'edit-'.$detail->id;
                $deleteOpen = $openModal === 'delete-'.$detail->id;
            @endphp
            <div class="fixed inset-0 z-80 {{ $editOpen ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="edit-kir-detail-{{ $detail->id }}" role="presentation">
                <section class="w-[min(900px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
                    <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]"><h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Edit Barang KIR</h2><button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button></header>
                    <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('kir.update', [$ruangan, $detail]) }}" data-submit-loading>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_modal" value="edit-{{ $detail->id }}">
                        @if ($editOpen && $errors->any())
                            <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
                        @endif
                        @include('kir.partials.form-fields', ['detail' => $detail])
                        @include('users.partials.form-footer', ['submitLabel' => 'Simpan'])
                    </form>
                </section>
            </div>

            <div class="fixed inset-0 z-80 {{ $deleteOpen ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-kir-detail-{{ $detail->id }}" role="presentation">
                <section class="w-[min(460px,100%)] rounded-2xl bg-white p-[27px] text-center shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="alertdialog" aria-modal="true">
                    <span class="mx-auto mb-5 grid h-[54px] w-[54px] place-items-center rounded-full bg-[#fff1f0] text-[#c44f47]"><svg class="h-7 w-7 fill-none stroke-current stroke-[1.9] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></span>
                    <h2 class="m-0 mb-2 text-xl font-extrabold text-[#0b2b50]">Hapus barang KIR?</h2>
                    <p class="m-0 text-[12px] leading-relaxed text-[#6b7a90]">Barang <strong>{{ $detail->nama_barang }}</strong> hanya akan dihapus dari daftar KIR ruangan ini. Data aset utama tidak dihapus.</p>
                    <form class="mt-6 flex justify-center gap-2 border-t border-[#edf1f5] pt-5" method="POST" action="{{ route('kir.destroy', [$ruangan, $detail]) }}" data-submit-loading>
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="_modal" value="delete-{{ $detail->id }}">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c44f47] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#ad433c]" type="submit" data-loading-button data-loading-text="Menghapus..."><span data-loading-label>Hapus</span></button>
                    </form>
                </section>
            </div>
        @endforeach
    @endif

    <script>
        document.querySelectorAll('.kir-asset-picker').forEach((select) => {
            select.addEventListener('change', () => {
                const option = select.selectedOptions[0]
                if (!option || !option.value) return
                const form = select.closest('form')
                ;['kode_barang_id', 'kode_barang', 'nama_barang', 'spesifikasi_nama_barang', 'merek_tipe', 'tahun_perolehan', 'jumlah', 'keterangan'].forEach((name) => {
                    const field = form?.querySelector(`[name="${name}"]`)
                    const datasetKey = name.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase())
                    if (field && option.dataset[datasetKey] !== undefined) {
                        field.value = option.dataset[datasetKey] || ''
                    }
                })
            })
        })
    </script>
</main>
@endsection
