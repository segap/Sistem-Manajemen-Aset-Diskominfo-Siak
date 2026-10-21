@extends('layouts.app')

@section('title', 'Manajemen Ruangan - SIMIVA')

@php
    use App\Enums\RuanganStatus;

    $numberStart = ($ruangan->currentPage() - 1) * $ruangan->perPage();
    $selectedPerPage = (int) request('per_page', $ruangan->perPage());
    $openModal = old('_modal');
    $statusClass = fn ($status) => ($status instanceof RuanganStatus ? $status->value : $status) === RuanganStatus::Active->value
        ? 'status-approved'
        : 'status-rejected';
    $userLabel = fn ($user) => trim($user->name.' · '.$user->email);
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Manajemen Ruangan</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Kelola master ruang/unit bidang untuk penanggung jawab aset.</p>
        </div>
        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f] max-[580px]:w-full" type="button" data-modal-open="create-ruangan">
            <span class="text-sm font-normal leading-none">+</span> Tambah Ruangan
        </button>
    </div>

    @if (session('success'))
        <div class="fixed right-6 top-[88px] z-70 flex items-center gap-[9px] rounded-[10px] border border-[#a9e2c9] bg-[#effcf6] px-4 py-3 text-[11px] font-semibold text-[#08714e] shadow-[0_12px_30px_rgb(23_91_65_/_12%)] max-[580px]:left-3 max-[580px]:right-3" role="status" data-auto-dismiss>
            <span class="grid h-[19px] w-[19px] place-items-center rounded-full bg-[#24a576] text-white">
                <svg class="h-3 w-3 fill-none stroke-current stroke-[3] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7" /></svg>
            </span>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any() && ! $openModal)
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <form class="filter-toolbar flex min-h-[72px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[760px]:flex-wrap" method="GET" action="{{ route('ruangan.index') }}">
            <div class="flex h-[42px] w-[min(430px,100%)] items-center rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 focus-within:border-[#6ca9d3] focus-within:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]">
                <input class="min-w-0 flex-1 border-0 bg-transparent text-[10px] text-[#34445a] outline-0 placeholder:text-[#78879a]" name="search" value="{{ request('search') }}" placeholder="Cari kode, nama ruangan, atau penanggung jawab...">
            </div>
            <select class="h-[42px] min-w-[170px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('ruangan.index') }}">Reset</a>
            <x-export-dropdown dataset="ruangan" />
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Kode Ruangan</th>
                        <th>Nama Ruangan</th>
                        <th>Penanggung Jawab</th>
                        <th class="status-column">Status</th>
                        <th>Keterangan</th>
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
                                @if ($item->penanggungJawab)
                                    <strong>{{ $item->penanggungJawab->name }}</strong>
                                    <small class="mt-1 block text-[10px] text-[#7f8ea1]">{{ $item->penanggungJawab->email }}</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="status-column"><span class="loan-status {{ $statusClass($item->status) }}">{{ $item->status->label() }}</span></td>
                            <td>{{ $item->keterangan ?: '-' }}</td>
                            <td class="action-column">
                                <div class="row-actions">
                                    <button title="Edit" type="button" data-modal-open="edit-ruangan-{{ $item->id }}" aria-label="Edit {{ $item->nama_ruangan }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" /></svg></button>
                                    <button class="danger" title="Hapus" type="button" data-modal-open="delete-ruangan-{{ $item->id }}" aria-label="Hapus {{ $item->nama_ruangan }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4z" /><path d="M8 8h8M8 12h5M8 16h6" /></svg></span>
                                    <strong>Ruangan tidak ditemukan</strong>
                                    <p>Tambahkan ruangan baru atau ubah filter pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route('ruangan.index') }}">
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

    <div class="fixed inset-0 z-80 {{ $openModal === 'create' ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="create-ruangan" role="presentation">
        <section class="w-[min(680px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="create-ruangan-title">
            <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                <div><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="create-ruangan-title">Tambah Ruangan</h2></div>
                <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
            </header>
            <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('ruangan.store') }}" data-submit-loading>
                @csrf
                <input type="hidden" name="_modal" value="create">
                @if ($openModal === 'create' && $errors->any())
                    <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
                @endif
                @include('ruangan.partials.form-fields', ['item' => null])
                @include('users.partials.form-footer', ['submitLabel' => 'Simpan'])
            </form>
        </section>
    </div>

    @foreach ($ruangan as $item)
        @php
            $editOpen = $openModal === 'edit-'.$item->id;
            $deleteOpen = $openModal === 'delete-'.$item->id;
        @endphp

        <div class="fixed inset-0 z-80 {{ $editOpen ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="edit-ruangan-{{ $item->id }}" role="presentation">
            <section class="w-[min(680px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
                <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]"><div><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]">Edit Ruangan</h2></div><button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button></header>
                <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('ruangan.update', $item) }}" data-submit-loading>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_modal" value="edit-{{ $item->id }}">
                    @if ($editOpen && $errors->any())
                        <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
                    @endif
                    @include('ruangan.partials.form-fields', ['item' => $item])
                    @include('users.partials.form-footer', ['submitLabel' => 'Simpan'])
                </form>
            </section>
        </div>

        <div class="fixed inset-0 z-80 {{ $deleteOpen ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-ruangan-{{ $item->id }}" role="presentation">
            <section class="w-[min(520px,100%)] rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
                <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]"><div><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]">Hapus Ruangan</h2></div><button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button></header>
                <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('ruangan.destroy', $item) }}" data-submit-loading>
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="_modal" value="delete-{{ $item->id }}">
                    @if ($deleteOpen && $errors->any())
                        <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <div class="rounded-[12px] bg-[#f7f9fc] p-4 text-center text-[11px] leading-relaxed text-[#6e7d91]">
                        <strong class="mb-1.5 block text-[14px] text-[#0b2b50]">{{ $item->nama_ruangan }}</strong>
                        <p class="m-0">Ruangan dengan kode <strong>{{ $item->kode_ruangan }}</strong> akan dihapus dari master ruangan.</p>
                    </div>
                    <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c44f47] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#ad433c]" type="submit" data-loading-button data-loading-text="Menghapus..."><span data-loading-label>Hapus Ruangan</span></button>
                    </footer>
                </form>
            </section>
        </div>
    @endforeach
</main>
@endsection
