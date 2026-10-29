@extends('layouts.app')

@section('title', 'Peminjaman #'.$loan->id.' - SIMIVA')

@php
    use App\Enums\UserRole;

    $role = auth()->user()?->role;
    $canVerifyLoan = auth()->user()?->hasPermission('peminjaman.verify') ?? false;
    $canApproveLoan = $role === UserRole::KasubbagUmumPerencanaan->value;
    $canHandOverLoan = auth()->user()?->hasPermission('peminjaman.handover') ?? false;
    $canReturnLoan = auth()->user()?->hasPermission('peminjaman.return') ?? false;
    $conditionOptions = collect($conditions)->mapWithKeys(fn ($condition) => [$condition->value => $condition->label()]);
    $statusClass = fn ($status) => match ($status instanceof \App\Enums\LoanStatus ? $status->value : $status) {
        'diajukan' => 'status-diajukan',
        'terverifikasi_tim_aset' => 'status-terverifikasi',
        'disetujui_kasubbag', 'disetujui' => 'status-disetujui',
        'ditolak' => 'status-ditolak',
        'dipinjam' => 'status-dipinjam',
        'dikembalikan' => 'status-dikembalikan',
        default => 'status-diajukan',
    };
    $formatDate = fn ($date) => $date ? $date->translatedFormat('d M Y') : '-';
    $formatDateTime = fn ($date) => $date ? $date->translatedFormat('d M Y H:i') : '-';
@endphp

@section('content')
<main class="mx-auto w-full max-w-[980px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <a class="mb-[15px] block w-max text-[10px] font-bold text-[#146bb5] no-underline transition hover:text-[#0b4f88]" href="{{ route('web.peminjaman.index') }}">← Kembali ke peminjaman</a>
    @include('peminjaman.partials.detail-modal', compact('loan', 'canVerifyLoan', 'canApproveLoan', 'canHandOverLoan', 'canReturnLoan', 'conditionOptions', 'statusClass', 'formatDate', 'formatDateTime'))
    <div class="rounded-[14px] border border-slate-200 bg-white p-8 text-center shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <h1 class="m-0 mb-3 text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Detail Peminjaman #{{ $loan->id }}</h1>
        <p class="m-0 mb-5 text-[13px] text-[#66758a]">Detail utama tersedia dalam modal agar konsisten dengan halaman daftar.</p>
        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="detail-loan-{{ $loan->id }}">Buka Detail</button>
    </div>
</main>
@endsection
