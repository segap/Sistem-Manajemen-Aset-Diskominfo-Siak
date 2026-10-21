@extends('layouts.app')

@section('title', 'Detail Penghapusan Aset - SIMIVA')

@php
    $formatDateTime = fn ($date) => $date ? $date->translatedFormat('d M Y H:i') : '-';
    $conditionMutation = $disposal->relationLoaded('conditionMutation') ? $disposal->conditionMutation : null;
    $evidences = $disposal->relationLoaded('evidences') ? $disposal->evidences : collect();
    $photoEvidences = $evidences->where('jenis', 'foto');
    $videoEvidences = $evidences->where('jenis', 'video');
    $isImageDocument = in_array(strtolower(pathinfo((string) $disposal->nama_dokumen, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true);
@endphp

@section('content')
<main class="mx-auto w-full max-w-[980px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <a class="mb-5 inline-flex text-[11px] font-bold text-[#146bb5] no-underline" href="{{ route('penghapusan-aset.index') }}">â† Kembali ke penghapusan aset</a>
    <section class="rounded-[14px] border border-slate-200 bg-white p-6 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <h1 class="m-0 text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Penghapusan #{{ $disposal->id }}</h1>
        <p class="mt-2 text-[13px] text-[#66758a]">{{ $disposal->asset->display_code }} - {{ $disposal->asset->nama_aset }}</p>
        <div class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
            <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Pengaju</span><strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $disposal->requester->name }}</strong></div>
            <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Status</span><strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $disposal->status->label() }}</strong></div>
            <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Alasan</span><strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $disposal->alasan->label() }}</strong></div>
            <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Dokumen Pendukung</span><strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">
                @if ($disposal->path_dokumen)
                    <a class="text-[#146bb5]" href="{{ route('penghapusan-aset.dokumen', $disposal) }}">{{ $disposal->nama_dokumen }}</a>
                @else
                    -
                @endif
            </strong></div>
        </div>
        <div class="mt-5 rounded-[11px] bg-[#f4f7fa] p-4 text-[11px] leading-relaxed text-[#6e7d91]">
            <strong class="mb-1.5 block text-[#405267]">Catatan Asli Perubahan Kondisi</strong>
            <p class="m-0 whitespace-pre-line">{{ $disposal->catatan_kondisi_snapshot ?: 'Bukti historis tidak tercatat pada pengajuan lama.' }}</p>
            @if ($conditionMutation)
                <small class="mt-2 block text-[9px] font-semibold text-[#8290a3]">{{ $formatDateTime($conditionMutation->created_at) }} · {{ $conditionMutation->user?->name ?: '-' }}</small>
            @endif
            @if ($disposal->google_drive_url_snapshot)
                <a class="mt-2 inline-flex min-h-[30px] items-center rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#146bb5] no-underline hover:bg-[#f8fafc]" href="{{ $disposal->google_drive_url_snapshot }}" target="_blank" rel="noopener noreferrer">Buka Google Drive</a>
            @endif
        </div>
        <div class="mt-5 rounded-[11px] bg-[#f4f7fa] p-4 text-[11px] leading-relaxed text-[#6e7d91]">
            <strong class="mb-1.5 block text-[#405267]">Catatan Pengajuan Penghapusan</strong>
            <p class="m-0 whitespace-pre-line">{{ $disposal->catatan ?: 'Tanpa catatan pengajuan.' }}</p>
        </div>
        <div class="mt-5 rounded-[12px] border border-[#e3e9f0] bg-white p-4">
            <strong class="mb-3 block text-[11px] text-[#405267]">Bukti Perubahan Kondisi</strong>
            @if ($photoEvidences->isNotEmpty() || $videoEvidences->isNotEmpty())
                @if ($photoEvidences->isNotEmpty())
                    <div class="grid grid-cols-[repeat(auto-fill,minmax(110px,1fr))] gap-2">
                        @foreach ($photoEvidences as $evidence)
                            <a class="block overflow-hidden rounded-[10px] border border-[#edf1f5] bg-[#f8fafc]" href="{{ route('penghapusan-aset.bukti.preview', $evidence) }}" target="_blank" rel="noopener noreferrer">
                                <img class="h-[110px] w-full object-cover" src="{{ route('penghapusan-aset.bukti.preview', $evidence) }}" alt="{{ $evidence->nama_file ?: 'Bukti kondisi' }}">
                            </a>
                        @endforeach
                    </div>
                @endif
                @foreach ($videoEvidences as $evidence)
                    <span class="mt-2 inline-flex min-h-[30px] items-center gap-1 rounded-[8px] border border-[#dbe3eb] bg-white p-1 text-[9px] font-bold text-[#5e7084]">
                        <span class="px-2">{{ $evidence->nama_file ?: 'Video kondisi' }}</span>
                        <a class="grid h-[28px] w-[32px] place-items-center rounded-[7px] text-[#146bb5] no-underline hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.bukti.preview', $evidence) }}" target="_blank" rel="noopener noreferrer" title="Lihat video" aria-label="Lihat video"><svg class="h-[14px] w-[14px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg></a>
                        <a class="grid h-[28px] w-[32px] place-items-center rounded-[7px] text-[#5e7084] no-underline hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.bukti', $evidence) }}" title="Unduh video" aria-label="Unduh video"><svg class="h-[14px] w-[14px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" /></svg></a>
                    </span>
                @endforeach
            @else
                <p class="m-0 text-[11px] leading-relaxed text-[#8290a3]">Bukti historis tidak tercatat pada pengajuan lama.</p>
            @endif
        </div>
        @if ($disposal->path_dokumen)
            <div class="mt-5 rounded-[12px] border border-[#e3e9f0] bg-white p-4">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <strong class="block text-[11px] text-[#405267]">Dokumen pendukung tambahan</strong>
                    <div class="flex items-center gap-2">
                        <a class="inline-flex min-h-[30px] items-center justify-center gap-1.5 rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#146bb5] no-underline hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.dokumen.preview', $disposal) }}" target="_blank" rel="noopener noreferrer">
                            <svg class="h-[13px] w-[13px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                            View
                        </a>
                        <a class="inline-flex min-h-[30px] items-center justify-center gap-1.5 rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#5e7084] no-underline hover:bg-[#f8fafc]" href="{{ route('penghapusan-aset.dokumen', $disposal) }}">
                            <svg class="h-[13px] w-[13px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" /></svg>
                            Download
                        </a>
                    </div>
                </div>
                @if ($isImageDocument)
                    <a href="{{ route('penghapusan-aset.dokumen.preview', $disposal) }}" target="_blank" rel="noopener noreferrer">
                        <img class="max-h-[320px] w-full rounded-[10px] border border-[#edf1f5] bg-[#f8fafc] object-contain" src="{{ route('penghapusan-aset.dokumen.preview', $disposal) }}" alt="Dokumen penghapusan {{ $disposal->asset->nama_aset }}">
                    </a>
                @else
                    <iframe class="h-[420px] w-full rounded-[10px] border border-[#edf1f5] bg-[#f8fafc]" src="{{ route('penghapusan-aset.dokumen.preview', $disposal) }}" title="Preview dokumen pendukung {{ $disposal->nama_dokumen ?: $disposal->asset->nama_aset }}"></iframe>
                @endif
            </div>
        @endif
        @if ($disposal->alasan_penolakan)
            <div class="mt-5 rounded-[11px] bg-[#fff0ee] p-4 text-[11px] leading-relaxed text-[#8f3a34]">
                <strong class="mb-1.5 block text-[#405267]">Alasan penolakan</strong>
                <p class="m-0 whitespace-pre-line">{{ $disposal->alasan_penolakan }}</p>
            </div>
        @endif
    </section>
</main>
@endsection