@extends('layouts.app')

@section('title', 'Permintaan Barang - SIMIVA')

@php
    use App\Enums\PermintaanPersediaanStatus;
    use App\Enums\UserRole;
    use App\Enums\WorkUnit;

    $role = auth()->user()?->role;
    $canCreate = in_array($role, [UserRole::AdminBidang->value, UserRole::SuperAdmin->value], true);
    $canProcess = in_array($role, [UserRole::TimAsetPersediaan->value, 'tim_aset_persediaan', UserRole::SuperAdmin->value], true);
    $numberStart = ($requests->currentPage() - 1) * $requests->perPage();
    $selectedPerPage = (int) request('per_page', $requests->perPage());
    $formatDate = fn ($date) => $date ? $date->translatedFormat('d M Y') : '-';
    $formatDateTime = fn ($date) => $date ? $date->translatedFormat('d M Y H:i') : '-';
    $workUnitLabel = fn (?string $value) => WorkUnit::labelFor($value);
    $nipLabel = fn (?string $value) => $value ? 'NIP. '.$value : 'NIP belum dicatat';
    $statusClass = fn ($status) => match ($status instanceof PermintaanPersediaanStatus ? $status->value : $status) {
        'disetujui_tim_aset_persediaan' => 'status-diajukan',
        'ditolak_tim_aset_persediaan' => 'status-ditolak',
        'selesai' => 'status-disetujui',
        default => 'status-diajukan',
    };
    $statusLabel = fn ($status) => ($status instanceof PermintaanPersediaanStatus ? $status : PermintaanPersediaanStatus::tryFrom((string) $status))?->label() ?? 'Menunggu Verifikasi';
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Permintaan Barang</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Permintaan barang persediaan khusus Stock Opname.</p>
        </div>
        @if ($canCreate)
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f] max-[580px]:w-full" type="button" data-modal-open="create-supply-request">
                <span class="text-sm font-normal leading-none">+</span> Buat Permintaan
            </button>
        @endif
    </div>

    @if (session('success'))
        <div class="fixed right-6 top-[88px] z-70 flex items-center gap-[9px] rounded-[10px] border border-[#a9e2c9] bg-[#effcf6] px-4 py-3 text-[11px] font-semibold text-[#08714e] shadow-[0_12px_30px_rgb(23_91_65_/_12%)] max-[580px]:left-3 max-[580px]:right-3" role="status" data-auto-dismiss>
            <span class="grid h-[19px] w-[19px] place-items-center rounded-full bg-[#24a576] text-white"><svg class="h-3 w-3 fill-none stroke-current stroke-[3] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" /></svg></span>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <form class="filter-toolbar flex min-h-[72px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[920px]:flex-wrap" method="GET" action="{{ route('permintaan-persediaan.index') }}">
            <input class="h-[42px] w-[min(320px,100%)] rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 text-[10px] text-[#34445a] outline-0 placeholder:text-[#78879a]" name="search" value="{{ request('search') }}" placeholder="Cari nomor, pemohon, barang...">
            <select class="h-[42px] min-w-[220px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            @if (request()->hasAny(['search', 'status']))
                <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('permintaan-persediaan.index') }}">Reset</a>
            @endif
            <x-export-dropdown dataset="permintaan-persediaan" />
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>No. Permintaan</th>
                        <th>Tanggal</th>
                        <th>Pemohon</th>
                        <th>Jumlah Jenis Barang</th>
                        <th>Justifikasi Kebutuhan</th>
                        <th class="status-column">Status</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $index => $requestItem)
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td><span class="code-badge">{{ $requestItem->nomor_permintaan }}</span></td>
                            <td>{{ $formatDate($requestItem->tanggal_permintaan) }}</td>
                            <td>
                                <strong>{{ $requestItem->pemohon?->name ?: '-' }}</strong>
                                <small class="cell-subtitle">{{ $workUnitLabel($requestItem->pemohon?->work_unit) }}</small>
                            </td>
                            <td>{{ $requestItem->details->count() }} Barang</td>
                            <td>{{ str($requestItem->details->pluck('justifikasi_kebutuhan')->filter()->implode('; ') ?: $requestItem->keperluan)->limit(55) }}</td>
                            <td class="status-column"><span class="loan-status {{ $statusClass($requestItem->status) }}">{{ $statusLabel($requestItem->status) }}</span></td>
                            <td class="action-column">
                                <div class="row-actions">
                                    <button type="button" data-modal-open="detail-supply-request-{{ $requestItem->id }}" title="Detail" aria-label="Detail {{ $requestItem->nomor_permintaan }}"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24"><path d="M5 5h14v14H5z" /><path d="M8 9h8M8 13h5" /></svg></span>
                                    <strong>Belum ada permintaan</strong>
                                    <p>Data permintaan persediaan belum tersedia pada filter ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route('permintaan-persediaan.index') }}">
                @foreach (request()->except(['per_page', 'page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <span>Tampilkan</span>
                <select name="per_page" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100] as $option)
                        <option value="{{ $option }}" @selected($selectedPerPage === $option)>{{ $option }} data</option>
                    @endforeach
                </select>
            </form>
            <span>Halaman {{ $requests->currentPage() }} dari {{ $requests->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $requests->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $requests->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24"><path d="M15 18 9 12l6-6" /></svg></a>
                <a class="pagination-icon-button {{ $requests->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $requests->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" /></svg></a>
            </div>
        </div>
    </section>

    @if ($canCreate)
        <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 max-[580px]:p-3" data-modal="create-supply-request" role="presentation">
            <section class="w-[min(780px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)]" role="dialog" aria-modal="true">
                <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                    <div>
                        <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Buat Permintaan Barang</h2>
                        <p class="m-0 mt-1 text-[10px] font-semibold text-[#7c8ca1]">Pemohon: {{ auth()->user()?->name }}</p>
                    </div>
                    <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                </header>
                <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('permintaan-persediaan.store') }}" data-submit-loading data-supply-request-form>
                    @csrf
                    <div class="grid gap-3" data-supply-items>
                        <div class="min-w-0 rounded-[14px] border border-[#e5edf5] bg-[#fbfdff] p-4" data-supply-row>
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <strong class="text-[11px] font-extrabold text-[#0b2b50]">Barang Permintaan</strong>
                                <button class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-[9px] border border-[#f0c8c5] bg-white text-[#b24a42] transition hover:bg-[#fff5f4]" type="button" data-remove-supply-row title="Hapus barang" aria-label="Hapus barang"><svg class="h-4 w-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                            </div>
                            <div class="grid min-w-0 grid-cols-[minmax(0,1fr)_minmax(110px,140px)] items-start gap-4 max-[700px]:grid-cols-1">
                                <label class="grid min-w-0 gap-[7px] text-[11px] font-bold text-[#405267]">
                                    <span>Barang</span>
                                    <select class="h-[42px] w-full min-w-0 rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] text-[#34465a]" name="items[0][persediaan_id]" required data-supply-select>
                                        <option value="">Pilih barang persediaan</option>
                                        @foreach ($stockItems as $stock)
                                            <option value="{{ $stock->id }}" data-stock="{{ $stock->stok }}" data-unit="{{ $stock->satuan }}">{{ $stock->kode_internal }} - {{ $stock->nama_barang }} ({{ $stock->stok }} {{ $stock->satuan }})</option>
                                        @endforeach
                                    </select>
                                    <small class="inline-flex w-fit rounded-full bg-[#eef6ff] px-2.5 py-1 text-[9px] font-extrabold text-[#55708d]" data-supply-stock>Stok tersedia: -</small>
                                </label>
                                <label class="grid min-w-0 gap-[7px] text-[11px] font-bold text-[#405267]">
                                    <span>Jumlah</span>
                                    <input class="h-[42px] w-full min-w-0 rounded-[9px] border border-[#dce4ec] bg-white px-3 text-center text-[11px] font-extrabold text-[#0b2b50]" name="items[0][jumlah_diminta]" type="number" min="1" required data-supply-qty>
                                </label>
                            </div>
                            <label class="mt-3 grid min-w-0 gap-[7px] text-[11px] font-bold text-[#405267]">
                                <span>Justifikasi Kebutuhan *</span>
                                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] text-[#34465a] outline-0" name="items[0][justifikasi_kebutuhan]" rows="3" placeholder="Jelaskan alasan kebutuhan barang ini..." required data-supply-justification></textarea>
                            </label>
                        </div>
                    </div>
                    <button class="page-action-button w-fit rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-add-supply-row>+ Tambah Barang</button>
                    <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                        <button class="page-action-button rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                        <button class="page-action-button rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit">Ajukan</button>
                    </footer>
                </form>
            </section>
        </div>
    @endif

    @foreach ($requests as $requestItem)
        <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 max-[580px]:p-3" data-modal="detail-supply-request-{{ $requestItem->id }}" role="presentation">
            <section class="w-[min(880px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)]" role="dialog" aria-modal="true">
                <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                    <div>
                        <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">{{ $requestItem->nomor_permintaan }}</h2>
                        <p class="m-0 mt-1 text-[10px] font-bold text-[#146bb5]">Permintaan Barang Persediaan</p>
                    </div>
                    <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                </header>
                <div class="grid gap-[17px] p-[22px]">
                    <div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
                        @include('peminjaman.partials.detail-item', ['label' => 'Pemohon', 'value' => $requestItem->pemohon?->name])
                        @include('peminjaman.partials.detail-item', ['label' => 'Unit Kerja', 'value' => $workUnitLabel($requestItem->pemohon?->work_unit)])
                        @include('peminjaman.partials.detail-item', ['label' => 'NIP', 'value' => $requestItem->pemohon?->nip ?: '-'])
                        @include('peminjaman.partials.detail-item', ['label' => 'Tanggal Permintaan', 'value' => $formatDateTime($requestItem->tanggal_permintaan)])
                        @include('peminjaman.partials.detail-item', ['label' => 'Status', 'value' => $statusLabel($requestItem->status)])
                        @include('peminjaman.partials.detail-item', ['label' => 'Diverifikasi oleh', 'value' => $requestItem->verifier?->name ?: '-'])
                        @include('peminjaman.partials.detail-item', ['label' => 'Diserahkan oleh', 'value' => $requestItem->handoverBy?->name ?: '-'])
                        @include('peminjaman.partials.detail-item', ['label' => 'Tanggal penyerahan', 'value' => $formatDateTime($requestItem->diserahkan_pada)])
                    </div>
                    @if ($requestItem->keperluan && $requestItem->details->whereNotNull('justifikasi_kebutuhan')->isEmpty())
                        @include('peminjaman.partials.detail-note', ['title' => 'Keperluan Lama', 'body' => $requestItem->keperluan])
                    @endif
                    @if ($requestItem->catatan)
                        @include('peminjaman.partials.detail-note', ['title' => 'Catatan', 'body' => $requestItem->catatan])
                    @endif
                    <div class="table-scroll rounded-[12px] border border-[#e3e9f0]">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th>Kategori</th>
                                    <th>Satuan</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Diminta</th>
                                    <th>Disetujui</th>
                                    <th>Justifikasi Kebutuhan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requestItem->details as $detail)
                                    @php $currentStock = (int) ($detail->persediaan?->stok ?? 0); @endphp
                                    <tr>
                                        <td><strong>{{ $detail->nama_barang_snapshot }}</strong><small class="cell-subtitle">{{ $detail->persediaan?->kode_internal ?: '-' }}</small></td>
                                        <td>{{ $detail->persediaan?->category?->nama_kategori ?: '-' }}</td>
                                        <td>{{ $detail->satuan_snapshot }}</td>
                                        <td><strong>{{ $currentStock }}</strong><small class="cell-subtitle">{{ $currentStock < $detail->jumlah_diminta && $requestItem->status === PermintaanPersediaanStatus::Submitted ? 'Stok tidak mencukupi' : 'tersedia' }}</small></td>
                                        <td>{{ $detail->jumlah_diminta }}</td>
                                        <td>{{ $detail->jumlah_disetujui ?? '-' }}</td>
                                        <td><span class="description-cell">{{ $detail->justifikasi_kebutuhan ?: 'Belum ada justifikasi per barang.' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($canProcess && $requestItem->status === PermintaanPersediaanStatus::Submitted)
                        <form class="grid gap-3 rounded-[12px] border border-[#e3e9f0] p-4" method="POST" action="{{ route('permintaan-persediaan.verify', $requestItem) }}" data-submit-loading>
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-3">
                                @foreach ($requestItem->details as $detail)
                                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                                        <span>Jumlah disetujui - {{ $detail->nama_barang_snapshot }} (stok saat ini {{ $detail->persediaan?->stok ?? 0 }})</span>
                                        <input class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" name="items[{{ $detail->id }}][jumlah_disetujui]" type="number" min="1" value="{{ min($detail->jumlah_diminta, max(1, (int) ($detail->persediaan?->stok ?? 0))) }}">
                                        <small class="rounded-[9px] border border-[#dce4ec] bg-[#f8fafc] px-3 py-2 text-[9px] font-semibold leading-relaxed text-[#66758a]">
                                            Justifikasi: {{ $detail->justifikasi_kebutuhan ?: ($requestItem->keperluan ?: '-') }}
                                        </small>
                                    </label>
                                @endforeach
                            </div>
                            <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] px-3 py-3 !text-[12px] font-medium leading-relaxed text-[#34465a] placeholder:!text-[12px] placeholder:font-medium placeholder:text-[#9aa7b8]" name="catatan" placeholder="Catatan / alasan penolakan..."></textarea>
                            <div class="flex justify-end gap-2">
                                <button class="page-action-button rounded-[9px] border-0 bg-[#c94c45] px-3.5 text-[10px] font-bold text-white" type="submit" name="decision" value="{{ PermintaanPersediaanStatus::Rejected->value }}">Tolak</button>
                                <button class="page-action-button rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit" name="decision" value="{{ PermintaanPersediaanStatus::Approved->value }}">Setujui</button>
                            </div>
                        </form>
                    @endif
                    @if ($canProcess && $requestItem->status === PermintaanPersediaanStatus::Approved)
                        <form class="flex flex-wrap items-center justify-between gap-3 rounded-[12px] border border-[#d7eadf] bg-[#f6fffa] p-4" method="POST" action="{{ route('permintaan-persediaan.handover', $requestItem) }}" data-submit-loading>
                            @csrf
                            @method('PATCH')
                            <p class="m-0 text-[11px] font-semibold text-[#476070]">Pastikan seluruh barang telah diserahkan kepada pemohon sebelum konfirmasi.</p>
                            <button class="page-action-button rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit">Serahkan Barang</button>
                        </form>
                    @endif
                    <footer class="mt-[5px] flex justify-end border-t border-[#edf1f5] pt-[18px]">
                        <button class="page-action-button rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Tutup</button>
                    </footer>
                </div>
            </section>
        </div>
    @endforeach
</main>

@if ($canCreate)
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-supply-request-form]').forEach((form) => {
            const container = form.querySelector('[data-supply-items]')
            const template = container?.querySelector('[data-supply-row]')
            const addButton = form.querySelector('[data-add-supply-row]')
            let index = 1

            const refreshRow = (row) => {
                const select = row.querySelector('[data-supply-select]')
                const qty = row.querySelector('[data-supply-qty]')
                const stockInfo = row.querySelector('[data-supply-stock]')
                const selected = select?.selectedOptions?.[0]
                const stock = selected?.dataset.stock || ''
                const unit = selected?.dataset.unit || ''
                if (stockInfo) stockInfo.textContent = stock ? `Stok tersedia: ${stock} ${unit}` : 'Stok tersedia: -'
                if (qty && stock) qty.max = stock
            }

            const reindex = () => {
                container?.querySelectorAll('[data-supply-row]').forEach((row, rowIndex) => {
                    row.querySelector('[data-supply-select]').name = `items[${rowIndex}][persediaan_id]`
                    row.querySelector('[data-supply-qty]').name = `items[${rowIndex}][jumlah_diminta]`
                    row.querySelector('[data-supply-justification]').name = `items[${rowIndex}][justifikasi_kebutuhan]`
                })
            }

            container?.addEventListener('change', (event) => {
                if (event.target.matches('[data-supply-select]')) refreshRow(event.target.closest('[data-supply-row]'))
            })

            container?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-supply-row]')
                if (!button) return
                if (container.querySelectorAll('[data-supply-row]').length <= 1) return
                button.closest('[data-supply-row]').remove()
                reindex()
            })

            addButton?.addEventListener('click', () => {
                const clone = template.cloneNode(true)
                clone.querySelector('[data-supply-select]').value = ''
                clone.querySelector('[data-supply-qty]').value = ''
                clone.querySelector('[data-supply-justification]').value = ''
                clone.querySelector('[data-supply-stock]').textContent = 'Stok tersedia: -'
                container.appendChild(clone)
                index++
                reindex()
            })
        })
    })
    </script>
@endif
@endsection
