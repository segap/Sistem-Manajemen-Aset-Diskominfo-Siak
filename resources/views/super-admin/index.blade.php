@extends('layouts.app')

@section('title', 'Kontrol Sistem - SIMIVA')

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <span class="hidden">SUPER ADMIN</span>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Kontrol Seluruh Sistem</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Buka dan periksa seluruh halaman Sistem Informasi Manajemen Aset.</p>
        </div>
    </div>

    <section class="grid grid-cols-2 gap-3.5 max-[700px]:grid-cols-1" aria-label="Daftar halaman sistem">
        @foreach ($modules as $index => [$name, $description, $path])
            <a
                class="flex min-h-[100px] items-center gap-3.5 rounded-[14px] border border-[#e1e8ef] bg-white p-[18px] text-inherit no-underline shadow-[0_8px_22px_rgb(35_63_92_/_4%)] transition hover:scale-[1.015] hover:border-[#bcd3e4] hover:shadow-[0_13px_30px_rgb(35_63_92_/_9%)]"
                href="{{ $path }}"
            >
                <span class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-[11px] bg-[#eaf4fb] text-xs font-extrabold text-[#176a9f]">
                    {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                </span>
                <div class="min-w-0 flex-1">
                    <strong class="text-[13px] text-[#0b2b50]">{{ $name }}</strong>
                    <p class="m-0 mt-1 text-[9px] leading-normal text-[#7f8ea0]">{{ $description }}</p>
                </div>
                <b class="text-lg text-[#146bb5]" aria-hidden="true">&rarr;</b>
            </a>
        @endforeach
    </section>
</main>
@endsection
