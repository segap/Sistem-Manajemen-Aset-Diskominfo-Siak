@extends('layouts.app')

@section('title', 'Penghapusan Aset - SIMIVA')

@php
    use App\Enums\AssetDisposalStatus;
    $user = auth()->user();
    $canCreateDisposal = $user?->hasPermission('penghapusan.create') ?? false;
    $canApproveDisposal = $user?->hasPermission('penghapusan.approve') ?? false;
    $canDownloadDisposal = $user?->hasPermission('penghapusan.download') ?? false;
    $canExportDisposal = $user?->hasPermission('penghapusan.export') ?? false;
    $numberStart = ($disposals->currentPage() - 1) * $disposals->perPage();
    $selectedPerPage = (int) request('per_page', $disposals->perPage());
    $statusClass = fn ($status) => match ($status instanceof AssetDisposalStatus ? $status->value : $status) {
        'diajukan' => 'status-diajukan',
        'disetujui' => 'status-disetujui',
        'ditolak' => 'status-ditolak',
        default => 'status-diajukan',
    };
    $formatDateTime = fn ($date) => $date ? $date->translatedFormat('d M Y H:i') : '-';
    $hasCreateErrors = $errors->has('aset_id') || $errors->has('catatan') || $errors->has('document');
    $evidenceUrl = fn ($evidence) => route('penghapusan-aset.bukti', $evidence);
    $evidencePreviewUrl = fn ($evidence) => route('penghapusan-aset.bukti.preview', $evidence);
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Penghapusan Aset</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Kelola usulan dan persetujuan penghapusan barang inventaris.</p>
        </div>
        @if ($canCreateDisposal)
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f] max-[580px]:w-full" type="button" data-modal-open="create-disposal">
                <span class="text-sm font-normal leading-none">+</span> Ajukan Penghapusan
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

    @if ($errors->any() && ! $hasCreateErrors)
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <form class="filter-toolbar flex min-h-[72px] items-center justify-between gap-5 border-b border-[#edf1f5] px-[18px] py-3.5 max-[580px]:flex-col max-[580px]:items-stretch" method="GET" action="{{ route('penghapusan-aset.index') }}">
            <select class="h-[42px] min-w-[190px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <div class="flex flex-wrap items-center gap-3">
                @if ($canExportDisposal)
                    <x-export-dropdown dataset="penghapusan-aset" />
                @endif
            </div>
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Aset</th>
                        <th>Kategori</th>
                        <th>Kondisi</th>
                        <th>Pengaju</th>
                        <th>Catatan</th>
                        <th class="status-column">Status</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($disposals as $index => $disposal)
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td>
                                <strong>{{ $disposal->asset->nama_aset }}</strong>
                                <small class="cell-subtitle">{{ $disposal->asset->display_code }}</small>
                            </td>
                            <td>{{ $disposal->asset->category?->nama_kategori ?: '-' }}</td>
                            <td>{{ $disposal->asset->kondisi?->label() ?: '-' }}</td>
                            <td>{{ $disposal->requester->name }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($disposal->catatan ?: '-', 100) }}
                            </td>
                            <td class="status-column"><span class="loan-status {{ $statusClass($disposal->status) }}">{{ $disposal->status->label() }}</span></td>
                            <td class="action-column">
                                <div class="row-actions">
                                    <button title="Detail" type="button" data-modal-open="detail-disposal-{{ $disposal->id }}" aria-label="Lihat penghapusan {{ $disposal->asset->nama_aset }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16" /><path d="M10 11v6M14 11v6" /><path d="M6 7l1 14h10l1-14" /><path d="M9 7V4h6v3" /></svg></span>
                                    <strong>Belum ada pengajuan</strong>
                                    <p>Belum ada usulan penghapusan aset pada filter ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route('penghapusan-aset.index') }}">
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
            <span>Halaman {{ $disposals->currentPage() }} dari {{ $disposals->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $disposals->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $disposals->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18 9 12l6-6" /></svg></a>
                <a class="pagination-icon-button {{ $disposals->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $disposals->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg></a>
            </div>
        </div>
    </section>

    @if ($canCreateDisposal)
        <div class="fixed inset-0 z-80 {{ $hasCreateErrors ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="create-disposal" role="presentation">
            <section class="w-[min(620px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="create-disposal-title">
                <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                    <div><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="create-disposal-title">Ajukan Penghapusan Aset</h2></div>
                    <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                </header>
                <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('penghapusan-aset.store') }}" enctype="multipart/form-data" data-submit-loading>
                    @csrf
                    @if ($hasCreateErrors)
                        <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Aset</span>
                        <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="aset_id" required autofocus data-disposal-asset-select>
                            <option value="">Pilih aset Rusak Berat (RB)</option>
                            @foreach ($eligibleAssets as $asset)
                                <option value="{{ $asset->id }}" data-url="{{ route('penghapusan-aset.asset-evidence', $asset) }}" @selected(old('aset_id') == $asset->id)>{{ $asset->display_code }} - {{ $asset->nama_aset }}</option>
                            @endforeach
                        </select>
                        @error('aset_id') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </label>

                    <section class="hidden grid gap-3 rounded-[13px] border border-[#dce4ec] bg-[#f8fafc] p-4" data-disposal-evidence-panel>
                        <div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
                            <div class="grid gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Nama Aset</span><strong class="text-[11px] text-[#33485d]" data-disposal-asset-name>-</strong></div>
                            <div class="grid gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Kode Internal</span><strong class="text-[11px] text-[#33485d]" data-disposal-asset-code>-</strong></div>
                            <div class="grid gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Kategori</span><strong class="text-[11px] text-[#33485d]" data-disposal-asset-category>-</strong></div>
                            <div class="grid gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Kondisi</span><strong class="text-[11px] text-[#33485d]" data-disposal-asset-condition>-</strong></div>
                        </div>
                        <div class="rounded-[11px] bg-white p-3 text-[11px] leading-relaxed text-[#66758a]">
                            <strong class="mb-1.5 block text-[#405267]">Riwayat Perubahan Kondisi</strong>
                            <small class="mt-2 block text-[9px] font-semibold text-[#8290a3]" data-disposal-mutation-meta></small>
                            <a class="mt-2 hidden w-max rounded-[8px] border border-[#dbe3eb] bg-white px-3 py-2 text-[9px] font-bold text-[#146bb5] no-underline" href="#" target="_blank" rel="noopener noreferrer" data-disposal-mutation-drive>Buka Google Drive</a>
                        </div>
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <strong class="text-[11px] text-[#405267]">Foto/Video Kondisi Barang</strong>
                                <small class="text-[9px] font-semibold text-[#8290a3]">Dari riwayat perubahan kondisi Master Data</small>
                            </div>
                            <div class="grid grid-cols-[repeat(auto-fill,minmax(92px,1fr))] gap-2" data-disposal-evidence-list></div>
                        </div>
                    </section>
                    <div class="hidden rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert" data-disposal-evidence-error></div>

                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Catatan Pengajuan Penghapusan</span>
                        <small class="font-semibold leading-relaxed text-[#8290a3]">Catatan awal diambil dari riwayat perubahan kondisi. Perubahan di sini hanya berlaku untuk pengajuan penghapusan.</small>
                        <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="catatan" rows="4" placeholder="Tambahkan catatan pengajuan penghapusan..." data-disposal-submission-note>{{ old('catatan') }}</textarea>
                        @error('catatan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </label>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Dokumen Pendukung <small class="font-semibold text-[#8290a3]">(Opsional, PDF/JPG/PNG, maks. 10 MB)</small></span>
                        <input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] outline-0 transition file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[10px] file:font-bold file:text-[#146bb5] focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png">
                        @error('document') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </label>
                    <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Mengirim..."><span data-loading-label>Kirim Pengajuan</span></button>
                    </footer>
                </form>
            </section>
        </div>
    @endif

    @foreach ($disposals as $disposal)
        @php
            $isImageDocument = in_array(strtolower(pathinfo((string) $disposal->nama_dokumen, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true);
            $disposalEvidences = $disposal->relationLoaded('evidences') ? $disposal->evidences : collect();
            $conditionMutation = $disposal->relationLoaded('conditionMutation') ? $disposal->conditionMutation : null;
            $photoEvidences = $disposalEvidences->where('jenis', 'foto');
            $videoEvidences = $disposalEvidences->where('jenis', 'video');
        @endphp
        <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="detail-disposal-{{ $disposal->id }}" role="presentation">
            <section class="w-[min(720px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="detail-disposal-title-{{ $disposal->id }}">
                <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                    <div>
                        <h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="detail-disposal-title-{{ $disposal->id }}">Penghapusan #{{ $disposal->id }}</h2>
                        <p class="m-0 mt-1 text-[10px] font-bold text-[#146bb5]">Penghapusan Aset</p>
                    </div>
                    <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                </header>
                <div class="grid gap-[17px] p-[22px]">
                    <div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
                        <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Aset</span><strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $disposal->asset->display_code }} - {{ $disposal->asset->nama_aset }}</strong></div>
                        <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Kategori</span><strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $disposal->asset->category?->nama_kategori ?: '-' }}</strong></div>
                        <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Kondisi</span><strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $disposal->asset->kondisi?->label() ?: '-' }}</strong></div>
                        <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Pengaju</span><strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $disposal->requester->name }}</strong></div>
                        <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Alasan</span><strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $disposal->alasan->label() }}</strong></div>
                        <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Status</span><strong><span class="loan-status {{ $statusClass($disposal->status) }}">{{ $disposal->status->label() }}</span></strong></div>
                        <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Tanggal pengajuan</span><strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $formatDateTime($disposal->created_at) }}</strong></div>
                        <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Pemeriksa</span><strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $disposal->reviewer?->name ?: '-' }}</strong></div>
                    </div>
                    <div class="rounded-[11px] bg-[#f4f7fa] p-4 text-[11px] leading-relaxed text-[#6e7d91]">
                        <strong class="mb-1.5 block text-[#405267]">Catatan Asli Perubahan Kondisi</strong>
                        <p class="m-0 whitespace-pre-line">{{ $disposal->catatan_kondisi_snapshot ?: 'Bukti historis tidak tercatat pada pengajuan lama.' }}</p>
                        @if ($conditionMutation)
                            <small class="mt-2 block text-[9px] font-semibold text-[#8290a3]">
                                {{ $formatDateTime($conditionMutation->created_at) }} · {{ $conditionMutation->user?->name ?: '-' }}
                            </small>
                        @endif
                        @if ($disposal->google_drive_url_snapshot)
                            <a class="mt-2 inline-flex min-h-[30px] items-center rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#146bb5] no-underline hover:bg-[#f8fafc]" href="{{ $disposal->google_drive_url_snapshot }}" target="_blank" rel="noopener noreferrer">Buka Google Drive</a>
                        @endif
                    </div>
                    <div class="rounded-[11px] bg-[#f4f7fa] p-4 text-[11px] leading-relaxed text-[#6e7d91]"><strong class="mb-1.5 block text-[#405267]">Catatan Pengajuan Penghapusan</strong><p class="m-0 whitespace-pre-line">{{ $disposal->catatan ?: 'Tanpa catatan pengajuan.' }}</p></div>

                    <div class="rounded-[12px] border border-[#e3e9f0] bg-white p-4">
                        <strong class="mb-3 block text-[11px] text-[#405267]">Bukti Perubahan Kondisi</strong>
                        @if ($photoEvidences->isNotEmpty() || $videoEvidences->isNotEmpty())
                            @if ($photoEvidences->isNotEmpty())
                                <div class="grid grid-cols-[repeat(auto-fill,minmax(98px,1fr))] gap-2">
                                    @foreach ($photoEvidences as $evidence)
                                        <div class="overflow-hidden rounded-[10px] border border-[#edf1f5] bg-[#f8fafc]">
                                            <a href="{{ $evidencePreviewUrl($evidence) }}" target="_blank" rel="noopener noreferrer" title="Lihat bukti" aria-label="Lihat bukti {{ $evidence->nama_file ?: 'kondisi' }}">
                                                <img class="h-[96px] w-full object-cover" src="{{ $evidencePreviewUrl($evidence) }}" alt="{{ $evidence->nama_file ?: 'Bukti kondisi' }}">
                                            </a>
                                            <div class="flex gap-1 p-2">
                                                <a class="grid h-[30px] flex-1 place-items-center rounded-[7px] border border-[#dbe3eb] bg-white text-[#146bb5] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ $evidencePreviewUrl($evidence) }}" target="_blank" rel="noopener noreferrer" title="Lihat bukti" aria-label="Lihat bukti {{ $evidence->nama_file ?: 'kondisi' }}">
                                                    <svg class="h-[14px] w-[14px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                                                </a>
                                                @if ($canDownloadDisposal)
                                                    <a class="grid h-[30px] flex-1 place-items-center rounded-[7px] border border-[#dbe3eb] bg-white text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ $evidenceUrl($evidence) }}" title="Unduh bukti" aria-label="Unduh bukti {{ $evidence->nama_file ?: 'kondisi' }}">
                                                        <svg class="h-[14px] w-[14px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" /></svg>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @if ($videoEvidences->isNotEmpty())
                                <div class="mt-3 grid gap-2">
                                    @foreach ($videoEvidences as $evidence)
                                        <div class="inline-flex w-max max-w-full items-center gap-1 rounded-[8px] border border-[#dbe3eb] bg-white p-1">
                                            <span class="max-w-[220px] truncate px-2 text-[9px] font-bold text-[#5e7084]">{{ $evidence->nama_file ?: 'Video kondisi' }}</span>
                                            <a class="grid h-[28px] w-[32px] place-items-center rounded-[7px] text-[#146bb5] no-underline hover:bg-[#f8fafc]" href="{{ $evidencePreviewUrl($evidence) }}" target="_blank" rel="noopener noreferrer" title="Lihat video" aria-label="Lihat video {{ $evidence->nama_file ?: 'kondisi' }}"><svg class="h-[14px] w-[14px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg></a>
                                            @if ($canDownloadDisposal)
                                                <a class="grid h-[28px] w-[32px] place-items-center rounded-[7px] text-[#5e7084] no-underline hover:bg-[#f8fafc]" href="{{ $evidenceUrl($evidence) }}" title="Unduh video" aria-label="Unduh video {{ $evidence->nama_file ?: 'kondisi' }}"><svg class="h-[14px] w-[14px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" /></svg></a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <p class="m-0 text-[11px] leading-relaxed text-[#8290a3]">Bukti historis tidak tercatat pada pengajuan lama.</p>
                        @endif
                    </div>

                    <div class="rounded-[12px] border border-[#e3e9f0] bg-white p-4">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                            <strong class="block text-[11px] text-[#405267]">Dokumen pendukung tambahan</strong>
                            @if ($disposal->path_dokumen)
                                <div class="flex items-center gap-2">
                                    <a class="page-action-button inline-flex min-h-[32px] items-center justify-center gap-1.5 rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#146bb5] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.dokumen.preview', $disposal) }}" target="_blank" rel="noopener noreferrer">
                                        <svg class="h-[13px] w-[13px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                                        View
                                    </a>
                                    @if ($canDownloadDisposal)
                                        <a class="page-action-button inline-flex min-h-[32px] items-center justify-center gap-1.5 rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.dokumen', $disposal) }}">
                                            <svg class="h-[13px] w-[13px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" /></svg>
                                            Download
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if (! $disposal->path_dokumen)
                            <p class="m-0 text-[11px] leading-relaxed text-[#8290a3]">Tidak ada dokumen pendukung tambahan.</p>
                        @elseif ($isImageDocument)
                            <a href="{{ route('penghapusan-aset.dokumen.preview', $disposal) }}" target="_blank" rel="noopener noreferrer">
                                <img class="max-h-[320px] w-full rounded-[10px] border border-[#edf1f5] bg-[#f8fafc] object-contain" src="{{ route('penghapusan-aset.dokumen.preview', $disposal) }}" alt="Dokumen penghapusan {{ $disposal->asset->nama_aset }}">
                            </a>
                        @else
                            <iframe class="h-[420px] w-full rounded-[10px] border border-[#edf1f5] bg-[#f8fafc]" src="{{ route('penghapusan-aset.dokumen.preview', $disposal) }}" title="Preview dokumen pendukung {{ $disposal->nama_dokumen ?: $disposal->asset->nama_aset }}"></iframe>
                        @endif
                    </div>
                    @if ($disposal->alasan_penolakan)
                        <div class="rounded-[11px] bg-[#fff0ee] p-4 text-[11px] leading-relaxed text-[#8f3a34]"><strong class="mb-1.5 block text-[#405267]">Alasan penolakan</strong><p class="m-0 whitespace-pre-line">{{ $disposal->alasan_penolakan }}</p></div>
                    @endif
                    @if ($canApproveDisposal && $disposal->status === AssetDisposalStatus::Submitted)
                        <div class="grid gap-3 rounded-[12px] border border-[#e3e9f0] bg-white p-4">
                            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                                <span>Alasan penolakan</span>
                                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="alasan_penolakan" rows="3" form="reject-disposal-{{ $disposal->id }}" placeholder="Wajib diisi jika pengajuan ditolak"></textarea>
                            </label>
                            <div class="flex justify-end gap-2">
                                <form id="reject-disposal-{{ $disposal->id }}" method="POST" action="{{ route('penghapusan-aset.keputusan', $disposal) }}" data-submit-loading>
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="decision" value="{{ AssetDisposalStatus::Rejected->value }}">
                                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c44f47] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#ad433c]" type="submit" data-loading-button data-loading-text="Memproses..."><span data-loading-label>Tolak</span></button>
                                </form>
                                <form method="POST" action="{{ route('penghapusan-aset.keputusan', $disposal) }}" data-submit-loading>
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="decision" value="{{ AssetDisposalStatus::Approved->value }}">
                                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Memproses..."><span data-loading-label>Setujui</span></button>
                                </form>
                            </div>
                        </div>
                    @endif
                    <div class="mt-[5px] flex justify-end border-t border-[#edf1f5] pt-[18px]"><button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Tutup</button></div>
                </div>
            </section>
        </div>
    @endforeach
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const select = document.querySelector('[data-disposal-asset-select]')
    if (!select) return

    const panel = document.querySelector('[data-disposal-evidence-panel]')
    const errorBox = document.querySelector('[data-disposal-evidence-error]')
    const list = document.querySelector('[data-disposal-evidence-list]')
    const fields = {
        name: document.querySelector('[data-disposal-asset-name]'),
        code: document.querySelector('[data-disposal-asset-code]'),
        category: document.querySelector('[data-disposal-asset-category]'),
        condition: document.querySelector('[data-disposal-asset-condition]'),
        note: document.querySelector('[data-disposal-mutation-note]'),
        meta: document.querySelector('[data-disposal-mutation-meta]'),
        drive: document.querySelector('[data-disposal-mutation-drive]'),
    }
    const submissionNote = document.querySelector('[data-disposal-submission-note]')
    const canDownloadDisposal = @json($canDownloadDisposal)

    const resetPanel = () => {
        panel?.classList.add('hidden')
        errorBox?.classList.add('hidden')
        if (errorBox) errorBox.textContent = ''
        if (list) list.innerHTML = ''
        if (submissionNote) submissionNote.value = ''
        Object.values(fields).forEach((field) => {
            if (!field) return
            if (field === fields.drive) {
                field.classList.add('hidden')
                field.href = '#'
                return
            }
            field.textContent = '-'
        })
    }

    const showError = (message) => {
        resetPanel()
        if (!errorBox) return
        errorBox.textContent = message
        errorBox.classList.remove('hidden')
    }

    const renderDocuments = (documents) => {
        if (!list) return
        list.innerHTML = ''

        documents.forEach((evidence) => {
            const card = window.document.createElement('div')
            card.className = 'overflow-hidden rounded-[10px] border border-[#edf1f5] bg-white'

            if (evidence.jenis === 'foto' && evidence.url) {
                const link = window.document.createElement('a')
                link.href = evidence.url
                link.target = '_blank'
                link.rel = 'noopener noreferrer'
                const image = window.document.createElement('img')
                image.className = 'h-[92px] w-full object-cover'
                image.alt = evidence.nama_file || 'Foto kondisi'
                image.src = evidence.url
                link.append(image)
                card.append(link)
            } else {
                const placeholder = window.document.createElement('div')
                placeholder.className = 'grid h-[92px] place-items-center bg-[#f8fafc] px-2 text-center text-[9px] font-bold text-[#66758a]'
                placeholder.textContent = evidence.jenis === 'video' ? 'Video kondisi' : 'Bukti tidak tersedia'
                card.append(placeholder)
            }

            const footer = window.document.createElement('div')
            footer.className = 'grid gap-1 p-2'
            const name = window.document.createElement('small')
            name.className = 'truncate text-[8px] font-semibold text-[#66758a]'
            name.textContent = evidence.nama_file || '-'
            footer.append(name)

            if (evidence.url) {
                const actions = window.document.createElement('div')
                actions.className = 'flex gap-1'

                const viewAnchor = window.document.createElement('a')
                viewAnchor.className = 'grid h-[30px] flex-1 place-items-center rounded-[7px] border border-[#dbe3eb] bg-white text-[#146bb5] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]'
                viewAnchor.href = evidence.url
                viewAnchor.target = '_blank'
                viewAnchor.rel = 'noopener noreferrer'
                viewAnchor.title = 'Lihat bukti'
                viewAnchor.setAttribute('aria-label', `Lihat bukti ${evidence.nama_file || 'kondisi'}`)
                viewAnchor.innerHTML = '<svg class="h-[14px] w-[14px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>'
                actions.append(viewAnchor)

                if (canDownloadDisposal) {
                    const downloadAnchor = window.document.createElement('a')
                    downloadAnchor.className = 'grid h-[30px] flex-1 place-items-center rounded-[7px] border border-[#dbe3eb] bg-white text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]'
                    downloadAnchor.href = evidence.url
                    downloadAnchor.download = evidence.nama_file || ''
                    downloadAnchor.title = 'Unduh bukti'
                    downloadAnchor.setAttribute('aria-label', `Unduh bukti ${evidence.nama_file || 'kondisi'}`)
                    downloadAnchor.innerHTML = '<svg class="h-[14px] w-[14px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" /></svg>'
                    actions.append(downloadAnchor)
                }
                footer.append(actions)
            }

            card.append(footer)
            list.append(card)
        })
    }

    const loadEvidence = async () => {
        resetPanel()
        const option = select.selectedOptions?.[0]
        const url = option?.dataset.url
        if (!url) return

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            const payload = await response.json()

            if (!response.ok) {
                showError(payload.message || 'Bukti kondisi aset tidak dapat dimuat.')
                return
            }

            fields.name.textContent = payload.asset.nama_aset
            fields.code.textContent = payload.asset.kode_internal
            fields.category.textContent = payload.asset.kategori
            fields.condition.textContent = payload.asset.kondisi
            fields.meta.textContent = `${payload.mutation.tanggal} Â· ${payload.mutation.petugas}`
            if (submissionNote) {
                submissionNote.value = payload.mutation.catatan || ''
            }
            if (payload.mutation.google_drive_url) {
                fields.drive.href = payload.mutation.google_drive_url
                fields.drive.classList.remove('hidden')
            }
            renderDocuments(payload.documents || [])
            panel?.classList.remove('hidden')
        } catch (error) {
            showError('Bukti kondisi aset tidak dapat dimuat. Coba pilih ulang aset.')
        }
    }

    select.addEventListener('change', loadEvidence)
    if (select.value) loadEvidence()
})
</script>
@endsection
