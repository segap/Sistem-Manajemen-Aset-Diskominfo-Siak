@extends('layouts.app')

@section('title', 'Detail Pengadaan - SIMIVA')

@php
    use App\Enums\ProcurementStatus;

    $rupiah = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $formatDate = fn ($date) => $date ? $date->translatedFormat('d M Y') : '-';
    $formatDateTime = fn ($date) => $date ? $date->translatedFormat('d M Y H:i') : '-';
    $statusClass = fn ($status) => match ($status instanceof ProcurementStatus ? $status->value : $status) {
        'dipesan' => 'status-diajukan',
        'diterima' => 'status-dipinjam',
        'terverifikasi' => 'status-disetujui',
        'ditolak' => 'status-ditolak',
        default => 'status-diajukan',
    };
@endphp

@section('content')
<main class="mx-auto w-full max-w-[980px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <a class="mb-4 inline-flex text-[11px] font-bold text-[#146bb5] no-underline hover:underline" href="{{ route('pengadaan.index') }}">← Kembali ke pengadaan</a>
    <section class="rounded-[14px] border border-slate-200 bg-white p-6 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <p class="m-0 mb-1 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#146bb5]">Pengadaan</p>
        <h1 class="m-0 text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Pengadaan #{{ $procurement->id }} - {{ $procurement->nama_barang }}</h1>
        <p class="mb-5 mt-2 text-[13px] text-[#66758a]">Klik tombol detail untuk melihat informasi lengkap pengadaan.</p>
        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="detail-procurement-{{ $procurement->id }}">Buka Detail</button>
    </section>

    @include('pengadaan.partials.detail-modal', compact('procurement', 'conditions', 'rupiah', 'formatDate', 'formatDateTime', 'statusClass'))
</main>
@endsection
