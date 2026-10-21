@extends('layouts.app')

@section('title', 'Laporan Aset - SIMIVA')

@php
    $summary = $report['summary'] ?? [];
    $rows = $report['rows'];
    $type = $report['type'] ?? 'asset';
    $query = request()->except('page');
    $tabs = ['asset' => 'Aset Tetap', 'stock_opname' => 'Stock Opname', 'all' => 'Semua Barang'];
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div><h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Laporan Aset</h1><p class="m-0 text-[13px] text-[#66758a]">Pratinjau dan ekspor data aset tetap maupun barang persediaan.</p></div>
        <div class="relative inline-flex" data-export-dropdown><button class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] border border-[#d6e0eb] bg-white px-4 text-[12px] font-extrabold text-[#405267]" type="button" data-export-toggle>Export <svg class="h-4 w-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></button><div class="absolute right-0 top-[calc(100%+8px)] z-50 hidden min-w-[130px] overflow-hidden rounded-[12px] border border-[#dce4ee] bg-white py-1 shadow-lg" data-export-menu><a class="block px-4 py-2.5 text-[11px] font-bold text-[#405267] no-underline hover:bg-[#f4f8fc]" href="{{ route('laporan.aset.pdf', $query) }}" target="_blank">Export PDF</a><a class="block px-4 py-2.5 text-[11px] font-bold text-[#405267] no-underline hover:bg-[#f4f8fc]" href="{{ route('laporan.aset.xlsx', $query) }}">Export Excel</a></div></div>
    </div>

    <nav class="mb-5 inline-flex flex-wrap gap-1 rounded-[13px] bg-[#eef4fa] p-1" aria-label="Jenis laporan">
        @foreach ($tabs as $tab => $label)<a href="{{ route('laporan.index', ['report_type' => $tab]) }}" class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[10px] px-4 text-[10px] font-bold no-underline transition {{ $type === $tab ? 'bg-[#146bb5] text-white shadow-[0_8px_18px_rgb(20_107_181_/_18%)]' : 'text-[#53657a] hover:bg-white' }}">{{ $label }}</a>@endforeach
    </nav>

    @if ($errors->any())<div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]">{{ $errors->first() }}</div>@endif

    <section class="mb-5 min-w-0 rounded-[14px] border border-[#e3e9f0] bg-white p-[22px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]">
        <form method="GET" action="{{ route('laporan.index') }}"><input type="hidden" name="report_type" value="{{ $type }}">
            <div class="grid grid-cols-4 gap-4 max-[1100px]:grid-cols-2 max-[580px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Pencarian</span><input class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" name="search" value="{{ request('search') }}" placeholder="Kode internal atau nama barang"></label>
                @if ($type !== 'stock_opname')
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Kategori Aset</span><select class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" name="kategori_id"><option value="">Semua</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)request('kategori_id') === (string)$category->id)>{{ $category->nama_kategori }}</option>@endforeach</select></label>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Kondisi</span><select class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" name="kondisi"><option value="">Semua</option>@foreach($conditions as $condition)<option value="{{ $condition->value }}" @selected(request('kondisi') === $condition->value)>{{ $condition->label() }}</option>@endforeach</select></label>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Status Aset</span><select class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" name="lifecycle"><option value="all">Semua</option><option value="active" @selected(request('lifecycle') === 'active')>Aktif</option><option value="disposed" @selected(request('lifecycle') === 'disposed')>Dihapus</option></select></label>
                @endif
                @if ($type !== 'asset')
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Kategori Stock Opname</span><select class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" name="stock_category_id"><option value="">Semua</option>@foreach($stockCategories as $category)<option value="{{ $category->id }}" @selected((string)request('stock_category_id') === (string)$category->id)>{{ $category->nama_kategori }}</option>@endforeach</select></label>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Status Stok</span><select class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" name="stock_status"><option value="">Semua</option><option value="tersedia" @selected(request('stock_status') === 'tersedia')>Tersedia</option><option value="menipis" @selected(request('stock_status') === 'menipis')>Stok Menipis</option><option value="habis" @selected(request('stock_status') === 'habis')>Stok Habis</option></select></label>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Status Verifikasi</span><select class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" name="verification"><option value="">Semua</option><option value="diajukan" @selected(request('verification') === 'diajukan')>Diajukan</option><option value="disetujui_tim_aset" @selected(request('verification') === 'disetujui_tim_aset')>Disetujui Tim Aset</option><option value="ditolak_tim_aset" @selected(request('verification') === 'ditolak_tim_aset')>Ditolak Tim Aset</option></select></label>
                @endif
            </div>
            <div class="mt-[18px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]"><a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline" href="{{ route('laporan.index', ['report_type' => $type]) }}">Reset</a><button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit">Terapkan Filter</button></div>
        </form>
    </section>

    @if ($type === 'stock_opname')
        <div class="mb-5 grid grid-cols-4 gap-3 max-[700px]:grid-cols-2 max-[480px]:grid-cols-1">@foreach ([['Total Jenis Barang',$summary['total_jenis_barang'] ?? 0],['Total Stok',$summary['total_stok'] ?? 0],['Stok Menipis',$summary['stok_menipis'] ?? 0],['Stok Habis',$summary['stok_habis'] ?? 0]] as [$label,$value])<article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[18px]"><span class="block text-[9px] uppercase text-[#8190a2]">{{ $label }}</span><strong class="mt-2 block text-2xl font-extrabold text-[#0b2b50]">{{ $value }}</strong></article>@endforeach</div>
    @elseif ($type === 'asset')
        <div class="mb-5 grid grid-cols-5 gap-3 max-[700px]:grid-cols-2">@foreach ([['Jenis Aset',$summary['total_jenis_aset'] ?? 0],['Total Unit',$summary['total_unit'] ?? 0],['Tersedia',$summary['unit_tersedia'] ?? 0],['Reservasi',$summary['unit_direservasi'] ?? 0],['Dipinjam',$summary['unit_dipinjam'] ?? 0]] as [$label,$value])<article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[18px]"><span class="block text-[9px] uppercase text-[#8190a2]">{{ $label }}</span><strong class="mt-2 block text-2xl font-extrabold text-[#0b2b50]">{{ $value }}</strong></article>@endforeach</div>
    @else
        <div class="mb-5 grid grid-cols-4 gap-3 max-[700px]:grid-cols-2"><article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[18px]"><span class="block text-[9px] uppercase text-[#8190a2]">Jenis Aset Tetap</span><strong class="mt-2 block text-2xl font-extrabold text-[#0b2b50]">{{ $report['asset_summary']['total_jenis_aset'] ?? 0 }}</strong></article><article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[18px]"><span class="block text-[9px] uppercase text-[#8190a2]">Unit Aset Tetap</span><strong class="mt-2 block text-2xl font-extrabold text-[#0b2b50]">{{ $report['asset_summary']['total_unit'] ?? 0 }}</strong></article><article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[18px]"><span class="block text-[9px] uppercase text-[#8190a2]">Jenis Stock Opname</span><strong class="mt-2 block text-2xl font-extrabold text-[#0b2b50]">{{ $report['stock_summary']['total_jenis_barang'] ?? 0 }}</strong></article><article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[18px]"><span class="block text-[9px] uppercase text-[#8190a2]">Total Stok Persediaan</span><strong class="mt-2 block text-2xl font-extrabold text-[#0b2b50]">{{ $report['stock_summary']['total_stok'] ?? 0 }}</strong></article></div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]"><div class="table-scroll"><table class="data-table"><thead><tr><th class="number-column">No</th>@if($type === 'all')<th>Sumber Data</th>@endif<th>Kode Internal</th><th>Nama Barang</th><th>Kategori</th>@if($type === 'stock_opname')<th>Satuan</th><th>Total Stok</th><th>Stok Tersedia</th><th>Status Stok</th><th>Status Verifikasi</th>@elseif($type === 'all')<th>Jumlah</th><th>Satuan</th><th>Keterangan Status</th>@else<th>Jumlah</th><th>Tersedia</th><th>Dipinjam</th><th>Kondisi</th><th>Status</th>@endif</tr></thead><tbody>
        @forelse($rows as $index => $row)<tr><td class="number-column">{{ $rows->firstItem() + $index }}</td>@if($type === 'all')<td>{{ $row['source'] }}</td>@endif<td><span class="code-badge">{{ $row['display_code'] }}</span></td><td><strong>{{ $row['nama_barang'] ?? $row['nama_aset'] }}</strong></td><td>{{ $row['kategori'] }}</td>@if($type === 'stock_opname')<td>{{ $row['satuan'] }}</td><td>{{ $row['stok'] }}</td><td>{{ $row['tersedia'] }}</td><td>{{ $row['label_stock_status'] }}</td><td>{{ $row['label_verification'] }}</td>@elseif($type === 'all')<td>{{ $row['jumlah'] }}</td><td>{{ $row['satuan'] ?? '-' }}</td><td>{{ $row['status'] ?? $row['label_kondisi'] }}</td>@else<td>{{ $row['jumlah'] }}</td><td>{{ $row['tersedia'] }}</td><td>{{ $row['dipinjam'] }}</td><td>{{ $row['label_kondisi'] }}</td><td>{{ $row['label_status_siklus'] }}</td>@endif</tr>
        @empty<tr><td colspan="{{ $type === 'stock_opname' ? 9 : ($type === 'all' ? 8 : 9) }}"><div class="table-empty"><strong>Data tidak ditemukan</strong><p>Tidak ada data yang sesuai dengan filter laporan ini.</p></div></td></tr>@endforelse
    </tbody></table></div>
        @if ($rows->total() > 10)
            <footer class="grid grid-cols-3 items-center gap-3 border-t border-[#edf1f5] px-5 py-4 text-[10px] text-[#66758a] max-[580px]:grid-cols-1">
                <form class="flex items-center gap-3 max-[580px]:justify-center" method="GET" action="{{ route('laporan.index') }}">
                    @foreach (request()->except(['per_page', 'page']) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                    <span>Tampilkan</span>
                    <select class="h-[40px] min-w-[118px] rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-extrabold text-[#405267] outline-0" name="per_page" aria-label="Jumlah data per halaman" onchange="this.form.submit()">@foreach ([10, 25, 50, 100] as $perPage)<option value="{{ $perPage }}" @selected($rows->perPage() === $perPage)>{{ $perPage }} data</option>@endforeach</select>
                </form>
                <span class="text-center">Halaman {{ $rows->currentPage() }} dari {{ $rows->lastPage() }}</span>
                <nav class="flex justify-end gap-2 max-[580px]:justify-center" aria-label="Pagination laporan">
                    @if ($rows->onFirstPage())
                        <span class="inline-flex h-[40px] w-[40px] items-center justify-center rounded-[9px] border border-[#e7edf3] text-[#b4c1ce]"><svg class="h-5 w-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    @else
                        <a class="inline-flex h-[40px] w-[40px] items-center justify-center rounded-[9px] border border-[#dce4ec] bg-white text-[#6e849a] no-underline transition hover:border-[#b8c9dc] hover:bg-[#f4f8fc]" href="{{ $rows->previousPageUrl() }}" aria-label="Halaman sebelumnya"><svg class="h-5 w-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                    @endif
                    @if ($rows->hasMorePages())
                        <a class="inline-flex h-[40px] w-[40px] items-center justify-center rounded-[9px] border border-[#dce4ec] bg-white text-[#486781] no-underline transition hover:border-[#b8c9dc] hover:bg-[#f4f8fc]" href="{{ $rows->nextPageUrl() }}" aria-label="Halaman berikutnya"><svg class="h-5 w-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                    @else
                        <span class="inline-flex h-[40px] w-[40px] items-center justify-center rounded-[9px] border border-[#e7edf3] text-[#b4c1ce]"><svg class="h-5 w-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    @endif
                </nav>
            </footer>
        @endif
    </section>
</main>
<script>document.addEventListener('click', e => document.querySelectorAll('[data-export-dropdown]').forEach(d => { const menu = d.querySelector('[data-export-menu]'); if (d.contains(e.target) && e.target.closest('[data-export-toggle]')) { menu.classList.toggle('hidden'); } else if (!d.contains(e.target)) { menu.classList.add('hidden'); } }));</script>
@endsection
