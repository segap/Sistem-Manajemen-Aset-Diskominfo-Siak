@extends('layouts.app')

@section('title', ($pageTitle ?? 'Peminjaman Barang').' - SIMIVA')

@php
    use App\Enums\LoanStatus;
    use App\Enums\UserRole;
    use App\Enums\WorkUnit;

    $role = auth()->user()?->role;
    $canCreateLoan = in_array($role, [UserRole::AdminBidang->value, UserRole::SuperAdmin->value], true);
    $canApproveLoan = in_array($role, [UserRole::TimAset->value, UserRole::KasubbagUmumPerencanaan->value], true);
    $canHandOverLoan = $role === UserRole::TimAset->value;
    $canReturnLoan = $role === UserRole::TimAset->value;
    $canDeleteLoan = $role === UserRole::SuperAdmin->value;
    $numberStart = ($loans->currentPage() - 1) * $loans->perPage();
    $selectedPerPage = (int) request('per_page', $loans->perPage());
    $number = fn ($value) => number_format((float) $value, 0, ',', '.');
    $statusClass = fn ($status) => match ($status instanceof LoanStatus ? $status->value : $status) {
        'diajukan' => 'status-diajukan',
        'terverifikasi_tim_aset' => 'status-terverifikasi',
        'disetujui_kasubbag', 'disetujui' => 'status-disetujui',
        'ditolak' => 'status-ditolak',
        'dipinjam' => 'status-dipinjam',
        'dikembalikan' => 'status-dikembalikan',
        default => 'status-diajukan',
    };
    $conditionOptions = collect($conditions)->mapWithKeys(fn ($condition) => [$condition->value => $condition->label()]);
    $formatDate = fn ($date) => $date ? $date->translatedFormat('d M Y') : '-';
    $formatDateTime = fn ($date) => $date ? $date->translatedFormat('d M Y H:i') : '-';
    $workUnitLabel = fn (?string $value) => WorkUnit::labelFor($value);
    $nipLabel = fn (?string $value) => $value ? 'NIP. '.$value : 'NIP belum dicatat';
    $historyService = app(\App\Services\RiwayatPersetujuanService::class);
    $isVerificationLoanPage = ($indexRouteName ?? 'web.peminjaman.index') === 'web.peminjaman.verifikasi.index';
    $displayStatusLabel = function ($loan, array $approval) use ($isVerificationLoanPage): string {
        if (! $isVerificationLoanPage) {
            return $approval['label'];
        }

        return match ($loan->status) {
            LoanStatus::Submitted => 'Menunggu Verifikasi Tim Aset',
            LoanStatus::VerifiedByAssetTeam => 'Terverifikasi Tim Aset',
            LoanStatus::Approved => 'Disetujui Kasubbag',
            LoanStatus::LegacyApproved => 'Disetujui',
            default => $approval['label'],
        };
    };
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">{{ $pageTitle ?? 'Peminjaman Barang' }}</h1>
            <p class="m-0 text-[13px] text-[#66758a]">{{ $pageSubtitle ?? 'Kelola pengajuan, persetujuan, penyerahan, hingga pengembalian aset.' }}</p>
        </div>
        @if ($canCreateLoan)
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f] max-[580px]:w-full" type="button" data-modal-open="create-loan">
                <span class="text-sm font-normal leading-none">+</span> Ajukan Peminjaman
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
        <form class="filter-toolbar flex min-h-[72px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[920px]:flex-wrap" method="GET" action="{{ route($indexRouteName ?? 'web.peminjaman.index') }}">
            <input class="h-[42px] w-[min(340px,100%)] rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 text-[10px] text-[#34445a] outline-0 placeholder:text-[#78879a]" name="search" value="{{ request('search') }}" placeholder="Cari peminjam, barang, atau kode...">
            <select class="h-[42px] min-w-[190px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ($statusFilterLabels ?? [])[$status->value] ?? $status->label() }}</option>
                @endforeach
            </select>
            <button class="page-action-button inline-flex min-h-[42px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-4 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            @if (request()->filled('search') || request()->filled('status'))
                <a class="page-action-button inline-flex min-h-[42px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-4 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route($indexRouteName ?? 'web.peminjaman.index') }}">Reset</a>
            @endif
            <div class="ml-auto flex flex-wrap items-center gap-3 max-[920px]:ml-0">
                <x-export-dropdown dataset="peminjaman" />
            </div>
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Peminjam</th>
                        <th>Barang</th>
                        <th>Periode</th>
                        <th class="status-column">Status</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $index => $loan)
                        @php $loanApproval = $historyService->statusSummary('loan', $loan); @endphp
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td>
                                <strong>{{ $loan->requester?->name ?: '-' }}</strong>
                                <small class="cell-subtitle">{{ $workUnitLabel($loan->requester?->work_unit) }}</small>
                            </td>
                            <td><strong>{{ $loan->itemSummary() }}</strong><small class="cell-subtitle">{{ $loan->itemCount() }} unit{{ $loan->itemCount() > 1 ? ' • '.$loan->itemPreview() : '' }}</small></td>
                            <td>{{ $formatDate($loan->tanggal_pinjam) }}<small class="cell-subtitle">s.d. {{ $formatDate($loan->tanggal_kembali) }}</small></td>
                            <td class="status-column">
                                <span class="loan-status {{ $statusClass($loan->status) }}">{{ $displayStatusLabel($loan, $loanApproval) }}</span>
                                @if ($loan->tanggal_penyerahan)
                                    <small class="cell-subtitle">Dipinjam: {{ $formatDateTime($loan->tanggal_penyerahan) }}</small>
                                @endif
                                @if ($loan->tanggal_pengembalian)
                                    <small class="cell-subtitle">Dikembalikan: {{ $formatDateTime($loan->tanggal_pengembalian) }}</small>
                                @endif
                                @if ($loan->status === LoanStatus::Borrowed && today()->isAfter($loan->tanggal_kembali))
                                    <small class="overdue-label">Terlambat</small>
                                @endif
                            </td>
                            <td class="action-column">
                                <div class="row-actions">
                                    <button type="button" data-modal-open="detail-loan-{{ $loan->id }}" aria-label="Lihat pengajuan {{ $loan->itemSummary() }}" title="Lihat detail">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                    </button>
                                    @if ($canDeleteLoan)
                                        <button class="danger" type="button" data-modal-open="delete-loan-{{ $loan->id }}" aria-label="Hapus pengajuan {{ $loan->itemSummary() }}" title="Hapus">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v14H5z" /><path d="M8 9h8M8 13h5" /></svg></span>
                                    <strong>Belum ada pengajuan</strong>
                                    <p>Data peminjaman dengan status ini belum tersedia.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route($indexRouteName ?? 'web.peminjaman.index') }}">
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
            <span>Halaman {{ $loans->currentPage() }} dari {{ $loans->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $loans->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $loans->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18 9 12l6-6" /></svg></a>
                <a class="pagination-icon-button {{ $loans->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $loans->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg></a>
            </div>
        </div>
    </section>

    @if ($canCreateLoan)
        <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="create-loan" role="presentation">
            <section class="w-[min(620px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="create-loan-title">
                <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                    <div><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="create-loan-title">Ajukan Peminjaman</h2></div>
                    <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                </header>
                <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('web.peminjaman.store') }}" enctype="multipart/form-data" data-submit-loading>
                    @csrf
                    <div class="grid gap-3 rounded-[12px] border border-[#dfe7f0] bg-[#f8fafc] p-4" data-loan-assets>
                        <div>
                            <h3 class="m-0 text-[11px] font-extrabold text-[#0b2b50]">Barang Peminjaman</h3>
                            <p class="m-0 mt-1 text-[10px] font-semibold text-[#718095]">Pilih satu atau lebih jenis barang. Sistem akan menentukan unit fisik yang tersedia secara otomatis.</p>
                        </div>
                        <div class="grid gap-3" data-loan-asset-list>
                            @php($oldItems = old('items', [['group_key' => '', 'quantity' => 1]]))
                            @foreach ($oldItems as $oldItem)
                                @php($oldGroupKey = is_array($oldItem) ? (string) ($oldItem['group_key'] ?? '') : (string) $oldItem)
                                @php($oldQuantity = is_array($oldItem) ? max(1, (int) ($oldItem['quantity'] ?? 1)) : 1)
                                <div class="grid gap-2 rounded-[11px] border border-[#dce4ec] bg-white p-3" data-loan-asset-row>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-[10px] font-extrabold text-[#405267]" data-loan-asset-label>Barang {{ $loop->iteration }}</span>
                                        <button class="inline-flex h-7 w-7 items-center justify-center rounded-[8px] border border-[#f1c3bf] bg-[#fff5f4] text-[#b42318] transition hover:bg-[#fee4e2]" type="button" data-loan-asset-remove aria-label="Hapus barang">
                                            <svg class="h-3.5 w-3.5 fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                                        </button>
                                    </div>
                                    <label class="grid gap-[7px] text-[10px] font-bold text-[#405267]">
                                        <span>Barang/Aset</span>
                                        <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="items[{{ $loop->index }}][group_key]" required data-loan-asset-select style="font-size: 10px;">
                                        <option value="">Pilih barang/aset</option>
                                        @foreach ($assetGroups as $group)
                                            @php($groupLabel = $group['nama_aset'].(filled($group['merek_tipe'] ?? null) ? ' - '.$group['merek_tipe'] : '').' — Tersedia '.$number($group['available_count']).' unit')
                                            <option value="{{ $group['key'] }}" data-available-count="{{ $group['available_count'] }}" @selected($oldGroupKey === (string) $group['key'])>{{ $groupLabel }}</option>
                                        @endforeach
                                        </select>
                                        @error("items.{$loop->index}.group_key") <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                                    </label>
                                    <label class="grid gap-[7px] text-[10px] font-bold text-[#405267]">
                                        <span>Jumlah</span>
                                        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="items[{{ $loop->index }}][quantity]" type="number" min="1" max="999" value="{{ $oldQuantity }}" required data-loan-asset-quantity>
                                        <small class="text-[9px] font-semibold text-[#718095]" data-loan-asset-helper>Pilih barang terlebih dahulu.</small>
                                        @error("items.{$loop->index}.quantity") <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('items') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                        <button class="page-action-button inline-flex min-h-[34px] w-max items-center justify-center gap-1 rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#146bb5] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-loan-asset-add><span class="text-sm leading-none">+</span> Tambah Barang</button>
                    </div>
                    <div class="grid grid-cols-2 gap-4 max-[580px]:grid-cols-1">
                        <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                            <span>Tanggal pinjam</span>
                            <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="tanggal_pinjam" type="date" min="{{ now()->toDateString() }}" value="{{ old('tanggal_pinjam') }}" required>
                            @error('tanggal_pinjam') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                        </label>
                        <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                            <span>Tanggal kembali</span>
                            <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="tanggal_kembali" type="date" min="{{ now()->toDateString() }}" value="{{ old('tanggal_kembali') }}" required>
                            @error('tanggal_kembali') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Alasan peminjaman</span>
                        <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="alasan" rows="4" required>{{ old('alasan') }}</textarea>
                        @error('alasan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </label>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Surat Peminjaman <span class="font-semibold text-[#718095]">(Opsional)</span></span>
                        <input class="w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal text-[#34465a] outline-0 transition file:mr-3 file:rounded-[8px] file:border-0 file:bg-[#eef6ff] file:px-3 file:py-2 file:text-[10px] file:font-bold file:text-[#146bb5] focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="surat_peminjaman" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                        <small class="text-[9px] font-semibold text-[#718095]">PDF/JPG/JPEG/PNG, maks. 5 MB</small>
                        @error('surat_peminjaman') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </label>
                    <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Mengirim..."><span data-loading-label>Kirim Pengajuan</span></button>
                    </footer>
                </form>
            </section>
        </div>
    @endif

    @foreach ($loans as $loan)
        @include('peminjaman.partials.detail-modal', [
            'loan' => $loan,
            'canApproveLoan' => $canApproveLoan,
            'canHandOverLoan' => $canHandOverLoan,
            'canReturnLoan' => $canReturnLoan,
            'conditionOptions' => $conditionOptions,
            'statusClass' => $statusClass,
            'formatDate' => $formatDate,
            'formatDateTime' => $formatDateTime,
        ])
        @if ($canDeleteLoan)
            @include('peminjaman.partials.delete-modal', ['loan' => $loan, 'formatDate' => $formatDate])
        @endif
    @endforeach

    @include('components.image-preview-modal')
</main>

@once
    <template id="loan-asset-row-template">
        <div class="grid gap-2 rounded-[11px] border border-[#dce4ec] bg-white p-3" data-loan-asset-row>
            <div class="flex items-center justify-between gap-3">
                <span class="text-[10px] font-extrabold text-[#405267]" data-loan-asset-label>Barang</span>
                <button class="inline-flex h-7 w-7 items-center justify-center rounded-[8px] border border-[#f1c3bf] bg-[#fff5f4] text-[#b42318] transition hover:bg-[#fee4e2]" type="button" data-loan-asset-remove aria-label="Hapus barang">
                    <svg class="h-3.5 w-3.5 fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                </button>
            </div>
            <label class="grid gap-[7px] text-[10px] font-bold text-[#405267]">
                <span>Barang/Aset</span>
                <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="items[0][group_key]" required data-loan-asset-select style="font-size: 10px;">
                <option value="">Pilih barang/aset</option>
                @foreach ($assetGroups as $group)
                    @php($groupLabel = $group['nama_aset'].(filled($group['merek_tipe'] ?? null) ? ' - '.$group['merek_tipe'] : '').' — Tersedia '.$number($group['available_count']).' unit')
                    <option value="{{ $group['key'] }}" data-available-count="{{ $group['available_count'] }}">{{ $groupLabel }}</option>
                @endforeach
                </select>
            </label>
            <label class="grid gap-[7px] text-[10px] font-bold text-[#405267]">
                <span>Jumlah</span>
                <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="items[0][quantity]" type="number" min="1" max="999" value="1" required data-loan-asset-quantity>
                <small class="text-[9px] font-semibold text-[#718095]" data-loan-asset-helper>Pilih barang terlebih dahulu.</small>
            </label>
        </div>
    </template>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.querySelector('[data-loan-assets]');
            const template = document.getElementById('loan-asset-row-template');
            if (!wrapper || !template) return;

            const list = wrapper.querySelector('[data-loan-asset-list]');
            const addButton = wrapper.querySelector('[data-loan-asset-add]');

            const rows = () => Array.from(list.querySelectorAll('[data-loan-asset-row]'));
            const selects = () => Array.from(list.querySelectorAll('[data-loan-asset-select]'));
            const quantities = () => Array.from(list.querySelectorAll('[data-loan-asset-quantity]'));

            const selectedValues = () => selects()
                .map((select) => select.value)
                .filter(Boolean);

            const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value);

            const updateQuantityHelper = (row) => {
                const select = row.querySelector('[data-loan-asset-select]');
                const quantity = row.querySelector('[data-loan-asset-quantity]');
                const helper = row.querySelector('[data-loan-asset-helper]');
                const selected = select?.selectedOptions?.[0];
                const availableCount = Number.parseInt(selected?.dataset.availableCount || '0', 10);

                if (!quantity || !helper) return;

                if (!select?.value || availableCount < 1) {
                    quantity.max = '999';
                    helper.textContent = 'Pilih barang terlebih dahulu.';
                    return;
                }

                quantity.max = String(Math.min(availableCount, 999));
                if (Number.parseInt(quantity.value || '1', 10) > availableCount) {
                    quantity.value = String(availableCount);
                }
                helper.textContent = `Maksimal berdasarkan ketersediaan saat ini: ${formatNumber(availableCount)} unit`;
            };

            const refresh = () => {
                const selected = selectedValues();

                rows().forEach((row, index) => {
                    row.querySelector('[data-loan-asset-label]').textContent = `Barang ${index + 1}`;
                    row.querySelector('[data-loan-asset-remove]').disabled = rows().length === 1;
                    row.querySelector('[data-loan-asset-remove]').classList.toggle('opacity-45', rows().length === 1);

                    const select = row.querySelector('[data-loan-asset-select]');
                    const quantity = row.querySelector('[data-loan-asset-quantity]');
                    if (select) select.name = `items[${index}][group_key]`;
                    if (quantity) quantity.name = `items[${index}][quantity]`;

                    updateQuantityHelper(row);
                });

                selects().forEach((select) => {
                    Array.from(select.options).forEach((option) => {
                        if (option.value === '') {
                            option.disabled = false;
                            return;
                        }

                        const availableCount = Number.parseInt(option.dataset.availableCount || '0', 10);
                        const isSelectedInAnotherRow = option.value !== select.value && selected.includes(option.value);
                        option.disabled = availableCount < 1 || isSelectedInAnotherRow;
                    });
                });
            };

            addButton.addEventListener('click', () => {
                list.appendChild(template.content.firstElementChild.cloneNode(true));
                refresh();
            });

            list.addEventListener('click', (event) => {
                const remove = event.target.closest('[data-loan-asset-remove]');
                if (!remove || rows().length === 1) return;
                remove.closest('[data-loan-asset-row]').remove();
                refresh();
            });

            list.addEventListener('change', (event) => {
                if (event.target.matches('[data-loan-asset-select]')) refresh();
            });

            list.addEventListener('input', (event) => {
                if (!event.target.matches('[data-loan-asset-quantity]')) return;

                const quantity = event.target;
                const max = Number.parseInt(quantity.max || '999', 10);
                const value = Number.parseInt(quantity.value || '1', 10);
                if (value > max) quantity.value = String(max);
                if (value < 1) quantity.value = '1';
            });

            refresh();
        });
    </script>
@endonce
@endsection
