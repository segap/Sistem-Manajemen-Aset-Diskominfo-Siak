@php
    $isImageDocument = in_array(strtolower(pathinfo((string) $data->nama_dokumen, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    $evidences = $data->relationLoaded('evidences') ? $data->evidences : collect();
    $photoEvidences = $evidences->where('jenis', 'foto');
    $videoEvidences = $evidences->where('jenis', 'video');
    $conditionMutation = $data->relationLoaded('conditionMutation') ? $data->conditionMutation : null;
    $canDownloadDisposal = auth()->user()?->hasPermission('penghapusan.download') ?? false;
@endphp

<div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
    @include('persetujuan.partials.detail-item', ['label' => 'Pengaju', 'value' => $data->requester?->name])
    @include('persetujuan.partials.detail-item', ['label' => 'Aset', 'value' => $data->asset?->display_code.' - '.$data->asset?->nama_aset])
    @include('persetujuan.partials.detail-item', ['label' => 'Alasan', 'value' => $data->alasan->label()])
    @include('persetujuan.partials.detail-item', ['label' => 'Dokumen', 'value' => $data->nama_dokumen ?: '-'])
    <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]">
        <span class="text-[9px] uppercase text-[#8896a7]">Status</span>
        <strong><span class="loan-status {{ $statusClass($item) }}">{{ $data->status->label() }}</span></strong>
    </div>
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal pengajuan', 'value' => $formatDateTime($data->created_at)])
    @include('persetujuan.partials.detail-item', ['label' => 'Pemeriksa', 'value' => $data->reviewer?->name ?: '-'])
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal pemeriksaan', 'value' => $formatDateTime($data->tanggal_pemeriksaan)])
</div>

@include('persetujuan.partials.detail-note', ['title' => 'Catatan Pengajuan Penghapusan', 'body' => $data->catatan ?: 'Tanpa catatan pengajuan.'])

<div class="rounded-[11px] bg-[#f4f7fa] p-4 text-[11px] leading-relaxed text-[#6e7d91]">
    <strong class="mb-1.5 block text-[#405267]">Catatan Asli Perubahan Kondisi</strong>
    <p class="m-0 whitespace-pre-line">{{ $data->catatan_kondisi_snapshot ?: 'Bukti historis tidak tercatat pada pengajuan lama.' }}</p>
    @if ($conditionMutation)
        <small class="mt-2 block text-[9px] font-semibold text-[#8290a3]">{{ $formatDateTime($conditionMutation->created_at) }} · {{ $conditionMutation->user?->name ?: '-' }}</small>
    @endif
    @if ($data->google_drive_url_snapshot)
        <a class="mt-2 inline-flex min-h-[30px] items-center rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#146bb5] no-underline hover:bg-[#f8fafc]" href="{{ $data->google_drive_url_snapshot }}" target="_blank" rel="noopener noreferrer">Buka Google Drive</a>
    @endif
</div>

<div class="rounded-[12px] border border-[#e3e9f0] bg-white p-4">
    <strong class="mb-3 block text-[11px] text-[#405267]">Bukti Perubahan Kondisi</strong>
    @if ($photoEvidences->isNotEmpty() || $videoEvidences->isNotEmpty())
        @if ($photoEvidences->isNotEmpty())
            <div class="grid grid-cols-[repeat(auto-fill,minmax(110px,1fr))] gap-2">
                @foreach ($photoEvidences as $evidence)
                    <div class="overflow-hidden rounded-[10px] border border-[#edf1f5] bg-[#f8fafc]">
                        <a href="{{ route('penghapusan-aset.bukti.preview', $evidence) }}" target="_blank" rel="noopener noreferrer" title="Lihat bukti">
                            <img class="h-[110px] w-full object-cover" src="{{ route('penghapusan-aset.bukti.preview', $evidence) }}" alt="{{ $evidence->nama_file ?: 'Bukti kondisi' }}">
                        </a>
                        <div class="flex gap-1 p-2">
                            <a class="grid h-[30px] flex-1 place-items-center rounded-[7px] border border-[#dbe3eb] bg-white text-[#146bb5] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.bukti.preview', $evidence) }}" target="_blank" rel="noopener noreferrer" title="Lihat bukti" aria-label="Lihat bukti">
                                <svg class="h-[14px] w-[14px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                            </a>
                            @if ($canDownloadDisposal)
                                <a class="grid h-[30px] flex-1 place-items-center rounded-[7px] border border-[#dbe3eb] bg-white text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.bukti', $evidence) }}" title="Unduh bukti" aria-label="Unduh bukti">
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
                        <a class="grid h-[28px] w-[32px] place-items-center rounded-[7px] text-[#146bb5] no-underline hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.bukti.preview', $evidence) }}" target="_blank" rel="noopener noreferrer" title="Lihat video" aria-label="Lihat video"><svg class="h-[14px] w-[14px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg></a>
                        @if ($canDownloadDisposal)
                            <a class="grid h-[28px] w-[32px] place-items-center rounded-[7px] text-[#5e7084] no-underline hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.bukti', $evidence) }}" title="Unduh video" aria-label="Unduh video"><svg class="h-[14px] w-[14px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" /></svg></a>
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
        @if ($data->path_dokumen)
            <div class="flex items-center gap-2">
                <a class="page-action-button inline-flex min-h-[32px] items-center justify-center gap-1.5 rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#146bb5] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.dokumen.preview', $data) }}" target="_blank" rel="noopener noreferrer">
                    <svg class="h-[13px] w-[13px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                    View
                </a>
                @if ($canDownloadDisposal)
                    <a class="page-action-button inline-flex min-h-[32px] items-center justify-center gap-1.5 rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.dokumen', $data) }}">
                        <svg class="h-[13px] w-[13px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" /></svg>
                        Download
                    </a>
                @endif
            </div>
        @endif
    </div>
    @if (! $data->path_dokumen)
        <p class="m-0 text-[11px] leading-relaxed text-[#8290a3]">Tidak ada dokumen pendukung tambahan.</p>
    @elseif ($isImageDocument)
        <a href="{{ route('penghapusan-aset.dokumen.preview', $data) }}" target="_blank" rel="noopener noreferrer">
            <img class="max-h-[320px] w-full rounded-[10px] border border-[#edf1f5] bg-[#f8fafc] object-contain" src="{{ route('penghapusan-aset.dokumen.preview', $data) }}" alt="Dokumen penghapusan {{ $data->asset?->nama_aset ?? 'aset' }}">
        </a>
    @else
        <iframe class="h-[420px] w-full rounded-[10px] border border-[#edf1f5] bg-[#f8fafc]" src="{{ route('penghapusan-aset.dokumen.preview', $data) }}" title="Preview dokumen pendukung {{ $data->nama_dokumen ?: $data->asset?->nama_aset }}"></iframe>
    @endif
</div>

@if ($data->alasan_penolakan)
    @include('persetujuan.partials.detail-note', ['title' => 'Alasan penolakan', 'body' => $data->alasan_penolakan, 'danger' => true])
@endif
