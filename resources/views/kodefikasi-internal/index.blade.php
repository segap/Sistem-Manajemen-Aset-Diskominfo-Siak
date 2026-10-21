@extends('layouts.app')

@section('title', 'Kodefikasi Internal - SIMIVA')

@php
    $canManage = in_array(auth()->user()?->role, ['admin', 'super_admin'], true);
    $numberStart = ($assets->currentPage() - 1) * $assets->perPage();
    $selectedPerPage = (int) request('per_page', $assets->perPage());
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Kodefikasi Internal</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Pengelolaan kode identifikasi internal setiap unit aset.</p>
        </div>
        @if ($canManage)
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="bulk-generate-kode-internal">
                Generate Kode Belum Terdaftar
            </button>
        @endif
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[10px] border border-[#a9e2c9] bg-[#effcf6] px-[15px] py-[13px] text-[11px] font-semibold text-[#08714e]" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @if (session('bulk_review'))
        <div class="mb-4 rounded-[10px] border border-[#fed7aa] bg-[#fff7ed] px-[15px] py-[13px] text-[11px] text-[#9a3412]" role="alert">
            <strong class="mb-2 block text-[#7c2d12]">Perlu ditinjau</strong>
            @foreach (session('bulk_review') as $review)
                <div>{{ $review['name'] }} — {{ $review['reason'] }}</div>
            @endforeach
        </div>
    @endif

    <section class="mb-5 grid grid-cols-3 gap-4 max-[780px]:grid-cols-1">
        <article class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
            <span class="text-[10px] uppercase text-[#7e8fa4]">Total Aset</span>
            <strong class="mt-2 block text-[28px] font-extrabold text-[#0b2b50]">{{ $totalAssets }}</strong>
        </article>
        <article class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
            <span class="text-[10px] uppercase text-[#7e8fa4]">Sudah Dikodefikasi</span>
            <strong class="mt-2 block text-[28px] font-extrabold text-[#166534]">{{ $codedAssets }}</strong>
        </article>
        <article class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
            <span class="text-[10px] uppercase text-[#7e8fa4]">Belum Dikodefikasi</span>
            <strong class="mt-2 block text-[28px] font-extrabold text-[#b45309]">{{ $uncodedAssets }}</strong>
        </article>
    </section>

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <form class="filter-toolbar flex min-h-[86px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-4 max-[1100px]:flex-wrap" method="GET" action="{{ route('kodefikasi-internal.index') }}">
            <input class="h-[42px] min-w-[250px] flex-1 rounded-[9px] border border-[#dce4ec] bg-[#fbfcfe] px-3 text-[10px] outline-0 focus:border-[#6ca9d3] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="search" value="{{ request('search') }}" placeholder="Cari kode internal, aset, atau kode barang...">
            <select class="h-[42px] min-w-[160px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="status_kode" onchange="this.form.submit()">
                <option value="">Semua kode</option>
                <option value="sudah" @selected(request('status_kode') === 'sudah')>Sudah memiliki kode</option>
                <option value="belum" @selected(request('status_kode') === 'belum')>Belum memiliki kode</option>
            </select>
            <select class="h-[42px] min-w-[155px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="kategori_id" onchange="this.form.submit()">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('kategori_id') === (string) $category->id)>{{ $category->nama_kategori }}</option>
                @endforeach
            </select>
            <select class="h-[42px] min-w-[155px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="lokasi" onchange="this.form.submit()">
                <option value="">Semua lokasi</option>
                @foreach ($locations as $location)
                    <option value="{{ $location }}" @selected(request('lokasi') === $location)>{{ $location }}</option>
                @endforeach
            </select>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('kodefikasi-internal.index') }}">Reset</a>
            <x-export-dropdown dataset="kodefikasi-internal" />
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Kode Internal</th>
                        <th>Nama Barang</th>
                        <th>Kode Barang</th>
                        <th>Ruangan/Lokasi</th>
                        <th>Kondisi</th>
                        @if ($canManage)
                            <th class="action-column">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $index => $asset)
                        @php($preview = $previews[$asset->id] ?? null)
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td>
                                @if ($asset->kode_internal)
                                    <span class="code-badge">{{ $asset->kode_internal }}</span>
                                @else
                                    <span class="loan-status status-diajukan">Belum Dibuat</span>
                                @endif
                            </td>
                            <td><strong>{{ $asset->nama_aset }}</strong><small class="cell-subtitle">{{ $asset->merek_tipe ?: 'Merek/tipe belum dicatat' }}</small></td>
                            <td>{{ $asset->kodeBarang ? $asset->kodeBarang->kode_barang : '-' }}</td>
                            <td>{{ $asset->lokasi_penyimpanan ?: '-' }}</td>
                            <td><span class="kondisi-badge kondisi-{{ $asset->kondisi->value }}">{{ $asset->kondisi->label() }}</span></td>
                            @if ($canManage)
                                <td class="action-column">
                                    <div class="row-actions">
                                        @if (! $asset->kode_internal)
                                            <button type="button" data-modal-open="generate-kode-internal-{{ $asset->id }}" title="Buat Kode" aria-label="Buat kode {{ $asset->nama_aset }}"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" /></svg></button>
                                        @else
                                            <button type="button" data-modal-open="edit-kode-internal-{{ $asset->id }}" title="Edit" aria-label="Edit kode {{ $asset->nama_aset }}"><svg viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" /></svg></button>
                                            <button class="danger" type="button" data-modal-open="reset-kode-internal-{{ $asset->id }}" title="Reset" aria-label="Reset kode {{ $asset->nama_aset }}"><svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></button>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 7 : 6 }}">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4z" /><path d="M8 8h8M8 12h5M8 16h6" /></svg></span>
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
            <form class="per-page-control" method="GET" action="{{ route('kodefikasi-internal.index') }}">
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

    @if ($canManage)
        <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6" data-modal="bulk-generate-kode-internal" role="presentation">
            <section class="w-[min(540px,100%)] rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)]" role="dialog" aria-modal="true">
                <header class="flex min-h-[76px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                    <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Generate Kode Belum Terdaftar</h2>
                    <button class="grid h-[34px] w-[34px] place-items-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close>×</button>
                </header>
                <form class="grid gap-4 p-[22px]" method="POST" action="{{ route('kodefikasi-internal.bulk-generate') }}" data-submit-loading>
                    @csrf
                    <p class="m-0 rounded-[12px] bg-[#f8fafc] p-4 text-[12px] leading-relaxed text-[#52657a]">Sistem akan membuat kode hanya untuk <strong>{{ $uncodedAssets }} aset</strong> yang belum memiliki kode internal. Kode yang sudah ada tidak akan ditimpa.</p>
                    <footer class="flex justify-end gap-2 border-t border-[#edf1f5] pt-4">
                        <button class="page-action-button border border-[#dbe3eb] bg-white text-[#5e7084]" type="button" data-modal-close>Batal</button>
                        <button class="page-action-button border-0 bg-[#146bb5] text-white" type="submit">Generate</button>
                    </footer>
                </form>
            </section>
        </div>

        @foreach ($assets as $asset)
            @php($preview = $previews[$asset->id] ?? null)
            @if (! $asset->kode_internal && $preview)
                <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6" data-modal="generate-kode-internal-{{ $asset->id }}" role="presentation">
                    <section class="w-[min(620px,100%)] rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)]" role="dialog" aria-modal="true">
                        <header class="flex min-h-[76px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                            <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Buat Kode Internal</h2>
                            <button class="grid h-[34px] w-[34px] place-items-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close>×</button>
                        </header>
                        <form class="grid gap-4 p-[22px]" method="POST" action="{{ route('kodefikasi-internal.generate', $asset) }}" data-submit-loading>
                            @csrf
                            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
                                <div class="grid gap-[5px] bg-white p-[13px]"><span class="text-[9px] text-[#8996a6]">Nama Barang</span><strong class="text-[11px] text-[#33485d]">{{ $asset->nama_aset }}</strong></div>
                                <div class="grid gap-[5px] bg-white p-[13px]"><span class="text-[9px] text-[#8996a6]">Nomor Berikutnya</span><strong class="text-[11px] text-[#33485d]">{{ str_pad((string) $preview['nomor'], 3, '0', STR_PAD_LEFT) }}</strong></div>
                            </div>
                            @if ($preview['collision'])
                                <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]">Prefix {{ $preview['prefix'] }} sudah digunakan oleh {{ $preview['collision_name'] }}. Tentukan prefix lain sebelum menyimpan.</div>
                            @endif
                            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                                <span>Prefix</span>
                                <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[11px] font-bold uppercase text-[#34465a] outline-0" name="prefix" value="{{ $preview['prefix'] }}" maxlength="5" required>
                            </label>
                            <p class="m-0 rounded-[12px] bg-[#f8fafc] p-4 text-[12px] text-[#52657a]">Preview kode internal: <strong class="text-[#0b2b50]">{{ $preview['kode'] }}</strong></p>
                            <footer class="flex justify-end gap-2 border-t border-[#edf1f5] pt-4">
                                <button class="page-action-button border border-[#dbe3eb] bg-white text-[#5e7084]" type="button" data-modal-close>Batal</button>
                                <button class="page-action-button border-0 bg-[#146bb5] text-white" type="submit">Simpan</button>
                            </footer>
                        </form>
                    </section>
                </div>
            @endif

            @if ($asset->kode_internal)
                <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6" data-modal="edit-kode-internal-{{ $asset->id }}" role="presentation">
                    <section class="w-[min(560px,100%)] rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)]" role="dialog" aria-modal="true">
                        <header class="flex min-h-[76px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                            <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Edit Kode Internal</h2>
                            <button class="grid h-[34px] w-[34px] place-items-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close>×</button>
                        </header>
                        <form class="grid gap-4 p-[22px]" method="POST" action="{{ route('kodefikasi-internal.update', $asset) }}" data-submit-loading>
                            @csrf
                            @method('PATCH')
                            <div class="rounded-[10px] border border-[#fed7aa] bg-[#fff7ed] px-[15px] py-[13px] text-[11px] text-[#9a3412]">Perubahan kode internal dapat memengaruhi identifikasi aset.</div>
                            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                                <span>Kode Internal</span>
                                <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[11px] font-bold uppercase text-[#34465a] outline-0" name="kode_internal" value="{{ $asset->kode_internal }}" pattern="[A-Z]{2,5}/[0-9]{3,}" required>
                            </label>
                            <footer class="flex justify-end gap-2 border-t border-[#edf1f5] pt-4">
                                <button class="page-action-button border border-[#dbe3eb] bg-white text-[#5e7084]" type="button" data-modal-close>Batal</button>
                                <button class="page-action-button border-0 bg-[#146bb5] text-white" type="submit">Simpan</button>
                            </footer>
                        </form>
                    </section>
                </div>

                <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6" data-modal="reset-kode-internal-{{ $asset->id }}" role="presentation">
                    <section class="w-[min(520px,100%)] rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)]" role="dialog" aria-modal="true">
                        <header class="flex min-h-[76px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                            <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Reset Kode Internal</h2>
                            <button class="grid h-[34px] w-[34px] place-items-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close>×</button>
                        </header>
                        <form class="grid gap-4 p-[22px]" method="POST" action="{{ route('kodefikasi-internal.reset', $asset) }}" data-submit-loading>
                            @csrf
                            @method('PATCH')
                            <p class="m-0 text-[12px] leading-relaxed text-[#52657a]">Kode <strong>{{ $asset->kode_internal }}</strong> pada aset <strong>{{ $asset->nama_aset }}</strong> akan dikosongkan. Nomor yang pernah digunakan tidak akan dipakai ulang.</p>
                            <footer class="flex justify-end gap-2 border-t border-[#edf1f5] pt-4">
                                <button class="page-action-button border border-[#dbe3eb] bg-white text-[#5e7084]" type="button" data-modal-close>Batal</button>
                                <button class="page-action-button border-0 bg-[#b42318] text-white" type="submit">Reset</button>
                            </footer>
                        </form>
                    </section>
                </div>
            @endif
        @endforeach
    @endif
</main>
@endsection
