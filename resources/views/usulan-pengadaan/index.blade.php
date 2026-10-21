@extends('layouts.app')

@section('title', 'Usulan Pengadaan - SIMIVA')

@php
    use App\Enums\ProcurementProposalStatus;
    use App\Enums\UserRole;
    use App\Enums\WorkUnit;
    use Illuminate\Support\Facades\Storage;

    $role = auth()->user()?->role;
    $canCreateProposal = in_array($role, [UserRole::AdminBidang->value, UserRole::SuperAdmin->value], true);
    $canReviewAssetTeam = in_array($role, [UserRole::TimAset->value, UserRole::SuperAdmin->value], true);
    $canReviewSupervisor = in_array($role, [UserRole::KasubbagUmumPerencanaan->value, UserRole::SuperAdmin->value], true);
    $numberStart = ($proposals->currentPage() - 1) * $proposals->perPage();
    $selectedPerPage = (int) request('per_page', $proposals->perPage());
    $rupiah = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $formatDateTime = fn ($date) => $date ? $date->translatedFormat('d M Y H:i') : '-';
    $workUnitLabel = fn (?string $value) => WorkUnit::labelFor($value);
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Usulan Pengadaan</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Kelola usulan pengadaan barang sebelum masuk proses pengadaan.</p>
        </div>
        @if ($canCreateProposal)
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f] max-[580px]:w-full" type="button" data-modal-open="create-proposal">
                <span class="button-plus font-normal">+</span> Buat Usulan
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

    @if ($errors->any())
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <form class="filter-toolbar flex min-h-[72px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[780px]:flex-wrap" method="GET" action="{{ route('web.usulan-pengadaan.index') }}">
            <div class="flex h-[42px] w-[min(430px,100%)] items-center rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 focus-within:border-[#6ca9d3] focus-within:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]">
                <input class="min-w-0 flex-1 border-0 bg-transparent text-[10px] text-[#34445a] outline-0 placeholder:text-[#78879a]" name="search" value="{{ request('search') }}" placeholder="Cari nama barang...">
            </div>
            <select class="h-[42px] min-w-[160px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            @if (request()->hasAny(['search', 'status']))
                <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('web.usulan-pengadaan.index') }}">Reset</a>
            @endif
            <x-export-dropdown dataset="usulan-pengadaan" />
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Pengusul</th>
                        <th>Barang</th>
                        <th>Jumlah</th>
                        <th>Estimasi</th>
                        <th>Status</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($proposals as $index => $proposal)
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td>
                                <strong>{{ $proposal->requester?->name ?: '-' }}</strong>
                                <small class="cell-subtitle">{{ $workUnitLabel($proposal->requester?->work_unit) }}</small>
                            </td>
                            <td>{{ $proposal->nama_barang }}</td>
                            <td>{{ $proposal->jumlah }}</td>
                            <td>{{ $rupiah((float) $proposal->estimasi_harga_satuan * $proposal->jumlah) }}</td>
                            <td><span class="loan-status status-{{ $proposal->status->value }}">{{ $proposal->status->label() }}</span></td>
                            <td class="action-column">
                                <div class="row-actions">
                                    <button title="Detail" type="button" data-modal-open="detail-proposal-{{ $proposal->id }}" aria-label="Detail usulan {{ $proposal->nama_barang }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v12H4z" /><path d="M8 7V5h8v2" /><path d="M8 12h8" /></svg></span>
                                    <strong>Belum ada data</strong>
                                    <p>Belum ada usulan pengadaan pada filter ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route('web.usulan-pengadaan.index') }}">
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
            <span>Halaman {{ $proposals->currentPage() }} dari {{ $proposals->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $proposals->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $proposals->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18 9 12l6-6" /></svg></a>
                <a class="pagination-icon-button {{ $proposals->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $proposals->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg></a>
            </div>
        </div>
    </section>

    @if ($canCreateProposal)
        @include('usulan-pengadaan.partials.create-modal')
    @endif

    @foreach ($proposals as $proposal)
        @include('usulan-pengadaan.partials.detail-modal', compact('proposal', 'canReviewAssetTeam', 'canReviewSupervisor', 'rupiah', 'formatDateTime'))
    @endforeach
</main>
@endsection
