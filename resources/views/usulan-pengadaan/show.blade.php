@extends('layouts.app')

@section('title', 'Detail Usulan Pengadaan - SIMIVA')

@php
    use App\Enums\UserRole;

    $role = auth()->user()?->role;
    $canReviewAssetTeam = in_array($role, [UserRole::TimAset->value, UserRole::SuperAdmin->value], true);
    $canReviewSupervisor = in_array($role, [UserRole::KasubbagUmumPerencanaan->value, UserRole::SuperAdmin->value], true);
    $rupiah = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $formatDateTime = fn ($date) => $date ? $date->translatedFormat('d M Y H:i') : '-';
@endphp

@section('content')
<main class="mx-auto w-full max-w-[980px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <a class="mb-4 inline-flex text-[11px] font-bold text-[#146bb5] no-underline hover:underline" href="{{ route('web.usulan-pengadaan.index') }}">← Kembali ke usulan pengadaan</a>
    <section class="rounded-[14px] border border-slate-200 bg-white p-6 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <p class="m-0 mb-1 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#146bb5]">Usulan Pengadaan</p>
        <h1 class="m-0 text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Usulan Pengadaan #{{ $proposal->id }}</h1>
        <p class="mb-5 mt-2 text-[13px] text-[#66758a]">Klik tombol detail untuk melihat informasi lengkap usulan.</p>
        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="detail-proposal-{{ $proposal->id }}">Buka Detail</button>
    </section>

    @include('usulan-pengadaan.partials.detail-modal', compact('proposal', 'canReviewAssetTeam', 'canReviewSupervisor', 'rupiah', 'formatDateTime', 'timeline'))
</main>
@endsection
