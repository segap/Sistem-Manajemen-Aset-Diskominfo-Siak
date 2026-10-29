@extends('layouts.app')

@section('title', 'Usulan Pengadaan - SIMIVA')

@php
    use App\Enums\ProcurementProposalStatus;
    use App\Enums\UserRole;
    use App\Enums\WorkUnit;
    use Illuminate\Support\Facades\Storage;

    $role = auth()->user()?->role;
    $canCreateProposal = auth()->user()?->hasPermission('usulan_pengadaan.create') ?? false;
    $canReviewAssetTeam = auth()->user()?->hasPermission('usulan_pengadaan.verify') ?? false;
    $canReviewSupervisor = $role === UserRole::KasubbagUmumPerencanaan->value;
    $canExportProposal = auth()->user()?->hasPermission('usulan_pengadaan.export') ?? false;
    $canEditProposal = fn ($proposal) => $proposal->status === ProcurementProposalStatus::Submitted
        && ($role === UserRole::SuperAdmin->value || ($role === UserRole::AdminBidang->value && $proposal->pengusul_id === auth()->id()));
    $canDeleteProposal = fn ($proposal) => $role === UserRole::SuperAdmin->value && $proposal->procurement === null;
    $isDeleteBlockedByLegacyProcurement = fn ($proposal) => $role === UserRole::SuperAdmin->value && $proposal->procurement !== null;
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
            <p class="m-0 text-[13px] text-[#66758a]">Kelola usulan kebutuhan barang dari setiap bidang, mulai dari pengajuan, verifikasi, persetujuan, hingga unduh PDF.</p>
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
            <select class="h-[42px] min-w-[180px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="jenis_belanja" onchange="this.form.submit()">
                <option value="">Semua Jenis Belanja</option>
                @foreach ($spendingTypes as $value => $label)
                    <option value="{{ $value }}" @selected($selectedSpendingType === $value)>{{ $label }}</option>
                @endforeach
                <option value="unclassified" @selected($selectedSpendingType === 'unclassified')>Belum diklasifikasikan</option>
            </select>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            @if (request()->hasAny(['search', 'status', 'jenis_belanja']))
                <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('web.usulan-pengadaan.index') }}">Reset</a>
            @endif
            @if ($canExportProposal)
                <x-export-dropdown dataset="usulan-pengadaan" />
            @endif
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Pengusul</th>
                        <th>Bidang</th>
                        <th>Tahun</th>
                        <th>Jenis Belanja</th>
                        <th>Jenis Barang</th>
                        <th>Tanggal</th>
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
                            <td>{{ $workUnitLabel($proposal->unit_kerja_snapshot ?: $proposal->requester?->work_unit) }}</td>
                            <td>{{ $proposal->tahun_anggaran ?: '-' }}</td>
                            <td>{{ $proposal->jenis_belanja_label }}</td>
                            <td>{{ $proposal->details->isNotEmpty() ? $proposal->details->count().' jenis' : '1 jenis' }}</td>
                            <td>{{ $formatDateTime($proposal->created_at) }}</td>
                            <td><span class="loan-status status-{{ $proposal->status->value }}">{{ $proposal->status->label() }}</span></td>
                            <td class="action-column">
                                <div class="row-actions">
                                    <button title="Detail" type="button" data-modal-open="detail-proposal-{{ $proposal->id }}" aria-label="Detail usulan #{{ $proposal->id }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                    </button>
                                    @if ($proposal->status === ProcurementProposalStatus::Approved)
                                        <a title="Download PDF" href="{{ route('web.usulan-pengadaan.pdf', $proposal) }}" aria-label="Download PDF kebutuhan Sarpras usulan #{{ $proposal->id }}" target="_blank" rel="noopener">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14" /></svg>
                                        </a>
                                    @endif
                                    @if ($canEditProposal($proposal))
                                        <button title="Edit" type="button" data-modal-open="edit-proposal-{{ $proposal->id }}" aria-label="Edit usulan #{{ $proposal->id }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 16.5-.8 4.3 4.3-.8L19 8.5 15.5 5 4 16.5ZM14.8 5.7l3.5 3.5 1.3-1.3a2.5 2.5 0 0 0 0-3.5 2.5 2.5 0 0 0-3.5 0l-1.3 1.3Z" /></svg>
                                        </button>
                                    @endif
                                    @if ($canDeleteProposal($proposal))
                                        <button class="danger" title="Hapus" type="button" data-modal-open="delete-proposal-{{ $proposal->id }}" aria-label="Hapus usulan #{{ $proposal->id }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>
                                        </button>
                                    @elseif ($isDeleteBlockedByLegacyProcurement($proposal))
                                        <button class="danger cursor-not-allowed opacity-45" title="Tidak dapat dihapus karena masih terhubung dengan pengadaan lama" type="button" disabled aria-label="Usulan #{{ $proposal->id }} tidak dapat dihapus karena masih terhubung dengan pengadaan lama">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
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
        @include('usulan-pengadaan.partials.detail-modal', compact('proposal', 'canReviewAssetTeam', 'canReviewSupervisor', 'rupiah', 'formatDateTime') + ['timeline' => $proposalTimelines[$proposal->id] ?? []])
        @if ($canEditProposal($proposal))
            @if ($proposal->details->isNotEmpty())
                @include('usulan-pengadaan.partials.edit-multi-modal', compact('proposal', 'users'))
            @else
                @include('usulan-pengadaan.partials.edit-modal', compact('proposal', 'users'))
            @endif
        @endif
        @if ($canDeleteProposal($proposal))
            @include('usulan-pengadaan.partials.delete-modal', compact('proposal'))
        @endif
    @endforeach
</main>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 })

    document.querySelectorAll('[data-edit-proposal-form]').forEach((form) => {
        const quantity = form.querySelector('[data-edit-proposal-qty]')
        const price = form.querySelector('[data-edit-proposal-price]')
        const total = form.querySelector('[data-edit-proposal-total]')

        const updateTotal = () => {
            total.textContent = currency.format((Number(quantity.value) || 0) * (Number(price.value) || 0))
        }

        quantity.addEventListener('input', updateTotal)
        price.addEventListener('input', updateTotal)
        updateTotal()
    })
})
</script>
@endsection
