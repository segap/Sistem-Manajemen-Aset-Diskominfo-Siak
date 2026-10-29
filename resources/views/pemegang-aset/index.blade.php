@extends('layouts.app')

@section('title', 'Data Pegawai - SIMIVA')

@php
    use App\Enums\WorkUnit;
    use Illuminate\Support\Facades\Storage;

    $numberStart = ($assetHolders->currentPage() - 1) * $assetHolders->perPage();
    $selectedPerPage = (int) request('per_page', $assetHolders->perPage());
    $openModal = old('_modal', session('_modal'));
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Data Pegawai</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Kelola data pegawai dan penanggung jawab aset Dinas Komunikasi dan Informatika Kabupaten Siak.</p>
        </div>
        @if ($canManageAssetHolders)
            <div class="flex gap-2 max-[580px]:grid max-[580px]:w-full">
                <button class="page-action-button import-excel-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 px-3.5 text-[10px] font-bold text-white transition" type="button" data-modal-open="import-asset-holder">Import Excel</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="create-asset-holder">
                    <span class="text-sm font-normal leading-none">+</span> Tambah Pegawai
                </button>
            </div>
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

    @if ($errors->any() && ! $openModal)
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <form class="filter-toolbar flex min-h-[72px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[760px]:flex-wrap" method="GET" action="{{ route('pemegang-aset.index') }}">
            <div class="flex h-[42px] w-[min(430px,100%)] items-center rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 focus-within:border-[#6ca9d3] focus-within:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]">
                <input class="min-w-0 flex-1 border-0 bg-transparent text-[10px] text-[#34445a] outline-0 placeholder:text-[#78879a]" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIP, atau email...">
            </div>
            <select class="h-[42px] min-w-[250px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="unit_kerja" onchange="this.form.submit()">
                <option value="">Semua unit kerja</option>
                @foreach ($workUnits as $unitValue => $unitLabel)
                    <option value="{{ $unitValue }}" @selected(request('unit_kerja') === $unitValue)>{{ $unitLabel }}</option>
                @endforeach
            </select>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('pemegang-aset.index') }}">Reset</a>
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th class="text-center">Foto Pegawai</th>
                        <th>Nama Pegawai</th>
                        <th>NIP</th>
                        <th>Unit Kerja</th>
                        <th>Jabatan</th>
                        <th>Email</th>
                        <th>No. HP</th>
                        <th class="min-w-[190px]">Alamat</th>
                        <th class="status-column">Jumlah Aset</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assetHolders as $index => $holder)
                        @php
                            $photoUrl = $holder->foto_path ? Storage::disk('public')->url($holder->foto_path) : null;
                        @endphp
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td class="text-center">
                                <div class="mx-auto grid h-20 w-20 place-items-center overflow-hidden rounded-[14px] border border-[#dce4ec] bg-[#f7fafc]">
                                    @if ($photoUrl)
                                        <img class="h-full w-full object-cover" src="{{ $photoUrl }}" alt="Foto {{ $holder->nama_pegawai }}">
                                    @else
                                        <span class="text-[13px] font-extrabold text-[#8aa0b8]">{{ mb_substr($holder->nama_pegawai, 0, 1) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $holder->nama_pegawai }}</td>
                            <td>{{ $holder->nip ?: '-' }}</td>
                            <td>{{ WorkUnit::labelFor($holder->unit_kerja) }}</td>
                            <td>{{ $holder->jabatan }}</td>
                            <td>{{ $holder->email ?: '-' }}</td>
                            <td>{{ $holder->nomor_hp ?: '-' }}</td>
                            <td class="min-w-[190px]">{{ $holder->alamat ?: '-' }}</td>
                            <td class="status-column"><span class="loan-status status-diajukan">{{ $hasAssetHolderColumn ? ($holder->assets_count ?? 0) : 0 }}</span></td>
                            <td class="action-column">
                                <div class="row-actions">
                                    <button title="Detail" type="button" data-modal-open="detail-asset-holder-{{ $holder->id }}" aria-label="Detail {{ $holder->nama_pegawai }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg></button>
                                    @if ($canManageAssetHolders)
                                        <button title="Edit" type="button" data-modal-open="edit-asset-holder-{{ $holder->id }}" aria-label="Edit {{ $holder->nama_pegawai }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" /></svg></button>
                                        <button class="danger" title="Hapus" type="button" data-modal-open="delete-asset-holder-{{ $holder->id }}" aria-label="Hapus {{ $holder->nama_pegawai }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87m-1-11.96a4 4 0 0 1 0 7.75" /></svg></span>
                                    <strong>Data pegawai tidak ditemukan</strong>
                                    <p>Ubah filter atau pencarian untuk melihat data lainnya.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route('pemegang-aset.index') }}">
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
            <span>Halaman {{ $assetHolders->currentPage() }} dari {{ $assetHolders->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $assetHolders->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $assetHolders->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18 9 12l6-6" /></svg></a>
                <a class="pagination-icon-button {{ $assetHolders->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $assetHolders->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg></a>
            </div>
        </div>
    </section>

    @if ($canManageAssetHolders)
        @include('pemegang-aset.partials.form-modal', ['mode' => 'create', 'holder' => null])
        @include('pemegang-aset.partials.import-modal')
    @endif

    @foreach ($assetHolders as $holder)
        @include('pemegang-aset.partials.detail-modal', ['holder' => $holder])

        @if ($canManageAssetHolders)
            @include('pemegang-aset.partials.form-modal', ['mode' => 'edit', 'holder' => $holder])

            @php $deleteOpen = $openModal === 'delete-'.$holder->id; @endphp
            <div class="fixed inset-0 z-80 {{ $deleteOpen ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-asset-holder-{{ $holder->id }}" role="presentation">
                <section class="w-[min(460px,100%)] rounded-2xl bg-white p-[27px] text-center shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="alertdialog" aria-modal="true">
                    <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Hapus data pegawai?</h2>
                    <p class="m-0 mt-3 text-[11px] leading-relaxed text-[#718095]">
                        Data <strong>{{ $holder->nama_pegawai }}</strong> akan dihapus secara permanen dan tidak dapat dipulihkan. Apakah Anda yakin ingin melanjutkan?
                    </p>
                    <form class="mt-5 flex justify-center gap-2" method="POST" action="{{ route('pemegang-aset.destroy', $holder) }}" data-submit-loading>
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="_modal" value="delete-{{ $holder->id }}">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c44f47] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#ad433c]" type="submit" data-loading-button data-loading-text="Menghapus...">
                            <span data-loading-label>Hapus Permanen</span>
                        </button>
                    </form>
                </section>
            </div>
        @endif
    @endforeach
</main>

<script>
document.querySelectorAll('[data-asset-holder-form]').forEach((form) => {
    const input = form.querySelector('[data-photo-input]')
    const preview = form.querySelector('[data-photo-preview]')
    const placeholder = form.querySelector('[data-photo-placeholder]')

    input?.addEventListener('change', () => {
        const file = input.files?.[0]
        if (!file || !preview) return

        preview.src = URL.createObjectURL(file)
        preview.classList.remove('hidden')
        placeholder?.classList.add('hidden')
    })
})
</script>
@endsection
