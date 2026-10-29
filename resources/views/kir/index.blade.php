@extends('layouts.app')

@section('title', 'Kartu Inventaris Ruangan - SIMIVA')

@php
    $numberStart = ($ruangan->currentPage() - 1) * $ruangan->perPage();
    $selectedPerPage = (int) request('per_page', $ruangan->perPage());
    $canExportKir = auth()->user()?->hasPermission('kir.export') ?? false;
    $canSynchronizeKir = in_array(auth()->user()?->role, ['admin', 'super_admin'], true);
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6">
        <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Kartu Inventaris Ruangan</h1>
        <p class="m-0 text-[13px] text-[#66758a]">Pantau daftar inventaris barang berdasarkan ruangan.</p>
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
        <form class="filter-toolbar flex min-h-[72px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[760px]:flex-wrap" method="GET" action="{{ route('kir.index') }}">
            <div class="flex h-[42px] w-[min(430px,100%)] items-center rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 focus-within:border-[#6ca9d3] focus-within:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]">
                <input class="min-w-0 flex-1 border-0 bg-transparent text-[10px] text-[#34445a] outline-0 placeholder:text-[#78879a]" name="search" value="{{ request('search') }}" placeholder="Cari kode, nama ruangan, atau penanggung jawab...">
            </div>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('kir.index') }}">Reset</a>
            @if ($canExportKir)
                <x-export-dropdown dataset="kir" />
            @endif
            @if ($canSynchronizeKir)
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="submit" form="kir-sync-form">Sinkronkan KIR</button>
            @endif
        </form>
        @if ($canSynchronizeKir)
            <form id="kir-sync-form" method="POST" action="{{ route('kir.synchronize') }}" class="hidden">
                @csrf
            </form>
        @endif

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Kode Ruangan</th>
                        <th>Nama Ruangan</th>
                        <th>Penanggung Jawab</th>
                        <th>Jumlah Barang</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ruangan as $index => $item)
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td><span class="inline-flex rounded-md bg-[#eaf6ff] px-2.5 py-1 text-[10px] font-extrabold text-[#0868aa]">{{ $item->kode_ruangan }}</span></td>
                            <td>{{ $item->nama_ruangan }}</td>
                            <td>
                                @if ($item->pemegangAset)
                                    <strong>{{ $item->pemegangAset->nama_pegawai }}</strong>
                                    <small class="mt-1 block text-[10px] text-[#7f8ea1]">{{ \App\Enums\WorkUnit::labelFor($item->pemegangAset->unit_kerja) }}</small>
                                @elseif ($item->penanggungJawab)
                                    <strong>{{ $item->penanggungJawab->name }}</strong>
                                    <small class="mt-1 block text-[10px] text-[#7f8ea1]">{{ $item->penanggungJawab->email }}</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $item->kir_details_count }} barang</td>
                            <td class="action-column">
                                <div class="row-actions">
                                    <a href="{{ route('kir.show', $item) }}" title="Lihat KIR" aria-label="Lihat KIR {{ $item->nama_ruangan }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4z" /><path d="M8 9h8M8 13h8M8 17h5" /></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12a2 2 0 0 1 2 2v16l-4-2-4 2-4-2-4 2V5a2 2 0 0 1 2-2Z" /><path d="M9 8h6M9 12h6M9 16h4" /></svg></span>
                                    <strong>KIR tidak ditemukan</strong>
                                    <p>Tambahkan ruangan terlebih dahulu atau ubah filter pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route('kir.index') }}">
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
            <span>Halaman {{ $ruangan->currentPage() }} dari {{ $ruangan->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $ruangan->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $ruangan->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18 9 12l6-6" /></svg></a>
                <a class="pagination-icon-button {{ $ruangan->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $ruangan->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg></a>
            </div>
        </div>
    </section>
</main>
@endsection
