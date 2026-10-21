@extends('layouts.guest')

@section('title', $asset ? $asset->nama_aset.' - SIMIVA' : 'Aset Tidak Ditemukan - SIMIVA')

@php
    $conditionTone = $asset ? match ($asset->kondisi->value) {
        'baik' => 'bg-[#dcfce7] text-[#166534]',
        'rusak_ringan' => 'bg-[#fef3c7] text-[#92400e]',
        'rusak_sedang' => 'bg-[#ffedd5] text-[#9a3412]',
        'rusak_berat', 'hilang' => 'bg-[#fee2e2] text-[#991b1b]',
        default => 'bg-[#eef2f7] text-[#475569]',
    } : 'bg-[#eef2f7] text-[#475569]';
@endphp

@section('content')
<main class="grid min-h-screen place-items-center bg-[#edf3f8] p-6 max-[420px]:p-4">
    <section class="w-[min(570px,100%)] rounded-[18px] border border-[#dbe4ec] bg-white p-7 shadow-[0_22px_60px_rgb(25_52_82_/_12%)] max-[420px]:p-5">
        <div class="-mx-7 -mt-7 mb-[26px] flex h-[72px] items-center gap-[9px] rounded-t-[18px] px-6 max-[420px]:-mx-5 max-[420px]:-mt-5">
            <img class="h-9 w-9 shrink-0 object-contain" src="{{ asset('images/logosiak.png') }}" alt="Logo Kabupaten Siak">
            <span class="grid">
                <strong class="text-[15px] font-extrabold leading-none tracking-[.02em] text-[#2457b6]">SIMIVA</strong>
                <small class="mt-1 text-[7px] font-extrabold tracking-[.18em] text-[#8a98aa]">Diskominfo Kabupaten Siak</small>
            </span>
        </div>

        @if ($error)
            <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] leading-relaxed text-[#b42318]" role="alert">
                {{ $error }}
            </div>
            <div class="mt-5 grid min-h-[230px] place-items-center text-center">
                <div>
                    <span class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-[13px] bg-[#eaf4fb] text-[#4d98c9]">
                        <svg class="h-6 w-6 fill-none stroke-current stroke-2 [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M9 9h.01M15 9h.01M8 15h8M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Z" />
                        </svg>
                    </span>
                    <strong class="block text-[15px] font-extrabold text-[#0b2b50]">Data aset belum tersedia</strong>
                    <p class="mx-auto mt-2 max-w-[330px] text-[10px] leading-relaxed text-[#8a98a8]">Pastikan QR berasal dari Sistem Informasi Manajemen Aset Diskominfo Kabupaten Siak.</p>
                </div>
            </div>
        @else
            <span class="hidden">INFORMASI ASET</span>
            <h1 class="my-2 mb-3 text-3xl font-extrabold leading-tight text-[#0b2b50] max-[420px]:text-2xl">{{ $asset->nama_aset }}</h1>
            <span class="inline-flex rounded-full px-[9px] py-[5px] text-[10px] font-bold whitespace-nowrap {{ $conditionTone }}">{{ $asset->kondisi->label() }}</span>

            <div class="mt-[22px] grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
                <div class="grid gap-[5px] bg-white p-[13px]">
                    <span class="text-[9px] text-[#8996a6]">Kode internal</span>
                    <strong class="break-words text-[11px] text-[#33485d]">{{ $asset->display_code }}</strong>
                </div>
                <div class="grid gap-[5px] bg-white p-[13px]">
                    <span class="text-[9px] text-[#8996a6]">Kode inventaris</span>
                    <strong class="break-words text-[11px] text-[#33485d]">{{ $asset->kode_internal ?: '-' }}</strong>
                </div>
                <div class="grid gap-[5px] bg-white p-[13px]">
                    <span class="text-[9px] text-[#8996a6]">Kode barang</span>
                    <strong class="break-words text-[11px] text-[#33485d]">{{ $asset->kodeBarang ? $asset->kodeBarang->kode_barang : '-' }}</strong>
                </div>
                <div class="grid gap-[5px] bg-white p-[13px]">
                    <span class="text-[9px] text-[#8996a6]">Kategori</span>
                    <strong class="break-words text-[11px] text-[#33485d]">{{ $asset->category->nama_kategori }}</strong>
                </div>
                <div class="grid gap-[5px] bg-white p-[13px]">
                    <span class="text-[9px] text-[#8996a6]">Merek / tipe</span>
                    <strong class="break-words text-[11px] text-[#33485d]">{{ $asset->merek_tipe ?: '-' }}</strong>
                </div>
                <div class="grid gap-[5px] bg-white p-[13px]">
                    <span class="text-[9px] text-[#8996a6]">Lokasi</span>
                    <strong class="break-words text-[11px] text-[#33485d]">{{ $asset->lokasi_penyimpanan ?: '-' }}</strong>
                </div>
                <div class="grid gap-[5px] bg-white p-[13px]">
                    <span class="text-[9px] text-[#8996a6]">Status</span>
                    <strong class="break-words text-[11px] text-[#33485d]">{{ $asset->tanggal_penghapusan ? 'Dihapus' : 'Aktif' }}</strong>
                </div>
            </div>

            <p class="m-0 mt-5 text-center text-[9px] leading-relaxed text-[#8a98a8]">
                Data ini berasal dari Sistem Informasi Manajemen Aset Diskominfo Kabupaten Siak.
            </p>
        @endif
    </section>
</main>
@endsection
