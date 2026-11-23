@extends('layouts.app')

@section('title', 'Hasil Pendataan Inventaris - SIMIVA')

@section('content')
@php
    $numberStart = ($surveys->currentPage() - 1) * $surveys->perPage();
@endphp
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-4 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Hasil Pendataan Inventaris</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Catatan jumlah nol dari import inventaris; tidak dihitung sebagai aset fisik atau stok.</p>
        </div>
        <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('web.aset.index') }}">Kembali ke Master Barang</a>
    </div>
    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <form class="filter-toolbar flex min-h-[86px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-4 max-[1100px]:flex-wrap" method="GET">
            <input class="h-[42px] w-[min(480px,100%)] rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 text-[10px] text-[#34445a] outline-0" name="search" value="{{ request('search') }}" placeholder="Cari nama barang atau pegawai...">
            <select class="h-[42px] min-w-[155px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="kategori_id" onchange="this.form.submit()"><option value="">Semua kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) request('kategori_id') === (string) $category->id)>{{ $category->nama_kategori }}</option>@endforeach</select>
            <select class="h-[42px] min-w-[170px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="unit_kerja" onchange="this.form.submit()"><option value="">Semua unit kerja</option>@foreach ($units as $unit)<option value="{{ $unit }}" @selected(request('unit_kerja') === $unit)>{{ $unit }}</option>@endforeach</select>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit">Cari</button>
        </form>
        <div class="table-scroll"><table class="data-table"><thead><tr><th class="number-column">No</th><th>Nama Barang</th><th>Nama Pegawai</th><th>Unit Kerja</th><th>Jumlah</th><th>Kondisi Excel</th><th>Catatan</th><th>Sumber</th></tr></thead><tbody>
            @forelse ($surveys as $index => $survey)
                <tr><td class="number-column">{{ $numberStart + $index + 1 }}</td><td><strong>{{ $survey->nama_barang }}</strong></td><td>{{ $survey->assetHolder?->nama_pegawai ?: $survey->nama_pegawai_excel }}</td><td>{{ $survey->unit_kerja_excel ?: '-' }}</td><td class="text-center">0 unit<small class="cell-subtitle">Tidak Ada</small></td><td>{{ $survey->kondisi_excel ?: '-' }}</td><td class="max-w-[280px] whitespace-normal break-words">{{ $survey->catatan_kerusakan ?: '-' }}</td><td>{{ $survey->nama_sheet }}, baris {{ $survey->baris_excel }}</td></tr>
            @empty
                <tr><td colspan="8"><div class="table-empty"><strong>Belum ada hasil pendataan</strong><p>Baris jumlah nol dari Import Inventaris Diskominfo akan muncul di sini.</p></div></td></tr>
            @endforelse
        </tbody></table></div>
        <div class="table-pagination"><span>Tampilkan {{ $surveys->perPage() }} data</span><span>Halaman {{ $surveys->currentPage() }} dari {{ $surveys->lastPage() }}</span><div><a class="pagination-icon-button {{ $surveys->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $surveys->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya">‹</a><a class="pagination-icon-button {{ $surveys->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $surveys->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya">›</a></div></div>
    </section>
</main>
@endsection
