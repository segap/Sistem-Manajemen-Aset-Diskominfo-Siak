@extends('layouts.guest')

@section('title', 'Login - SIMIVA')

@section('content')
<main class="min-h-screen bg-white md:grid md:grid-cols-[minmax(420px,0.95fr)_minmax(500px,1.05fr)]">
    <section
        class="relative hidden overflow-hidden bg-[linear-gradient(145deg,#082544_0%,#0c4779_58%,#1674b8_100%)] p-[clamp(40px,6vw,84px)] text-white md:flex md:flex-col md:justify-between"
        aria-label="Informasi aplikasi"
    >
        <div class="relative z-10 grid h-[62px] w-[62px] place-items-center rounded-[18px] border border-white/30 bg-white/10 backdrop-blur-xl" aria-hidden="true">
            <svg class="w-10 fill-none stroke-white stroke-2" viewBox="0 0 48 48" aria-hidden="true">
                <path d="M9 14.5 24 6l15 8.5v19L24 42 9 33.5z" />
                <path d="M17 19h14M17 24h14M17 29h9" />
            </svg>
        </div>

        <div class="relative z-10 my-20 max-w-[620px]">
            <span class="block text-xs font-bold tracking-[.14em] text-[#63b8f0]">DISKOMINFO KABUPATEN SIAK</span>
            <h1 class="my-[18px] max-w-[600px] text-[clamp(38px,4vw,62px)] font-extrabold leading-[1.08] tracking-[-.035em]">
                Kelola aset dengan lebih tertib, cepat, dan terintegrasi.
            </h1>
            <p class="m-0 max-w-[520px] text-lg leading-[1.7] text-[#cfdfed]">
                Pantau inventaris, peminjaman, pengadaan, dan kondisi aset melalui satu sistem.
            </p>
        </div>

        <div
            class="absolute -right-40 -bottom-[210px] h-[520px] w-[520px] rounded-full border-[90px] border-white/5 after:absolute after:inset-[58px] after:rounded-full after:border-2 after:border-white/10 after:content-['']"
            aria-hidden="true"
        ></div>
    </section>

    <section class="grid min-h-screen place-items-center bg-[linear-gradient(180deg,#fff,#f8fbfe)] px-[22px] py-8 md:min-h-0 md:p-12">
        <div class="w-full max-w-[440px]">
            <div class="mb-12 flex items-center gap-3 font-extrabold text-[#0b2b50] md:hidden">
                <div class="grid h-[45px] w-[45px] place-items-center rounded-xl bg-[#146bb5]" aria-hidden="true">
                    <svg class="w-[30px] fill-none stroke-white stroke-2" viewBox="0 0 48 48" aria-hidden="true">
                        <path d="M9 14.5 24 6l15 8.5v19L24 42 9 33.5z" />
                        <path d="M17 19h14M17 24h14M17 29h9" />
                    </svg>
                </div>
                <span>SIMIVA</span>
            </div>

            <header class="mb-8">
                <span class="block text-xs font-bold tracking-[.14em] text-[#63b8f0]">SELAMAT DATANG</span>
                <h2 class="my-2.5 text-[30px] font-extrabold tracking-[-.025em] text-[#0b2b50] md:text-[34px]">
                    Masuk ke akun Anda
                </h2>
            </header>

            @if ($errors->any())
                <div class="alert" role="alert">{{ $errors->first() }}</div>
            @endif

            <form class="grid gap-2.5" method="POST" action="{{ route('web.login') }}" data-submit-loading>
                @csrf

                <label class="mt-2 text-sm font-semibold text-[#344054]" for="email">Email</label>
                <div class="flex h-[54px] items-center gap-[11px] rounded-xl border border-[#dce4ee] bg-white px-[15px] transition focus-within:border-[#146bb5] focus-within:shadow-[0_0_0_4px_rgb(20_107_181_/_10%)]">
                    <svg class="w-5 shrink-0 fill-none stroke-[#7b8ba1] stroke-[1.7]" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 6.5h18v11H3zM3.5 7l8.5 6 8.5-6" />
                    </svg>
                    <input
                        class="min-w-0 flex-1 border-0 bg-transparent text-[#172033] outline-0 placeholder:text-[#9aa7b7]"
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        placeholder="nama@diskominfo.go.id"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >
                </div>

                <label class="mt-2 text-sm font-semibold text-[#344054]" for="password">Password</label>
                <div class="flex h-[54px] items-center gap-[11px] rounded-xl border border-[#dce4ee] bg-white px-[15px] transition focus-within:border-[#146bb5] focus-within:shadow-[0_0_0_4px_rgb(20_107_181_/_10%)]">
                    <svg class="w-5 shrink-0 fill-none stroke-[#7b8ba1] stroke-[1.7]" viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="5" y="10" width="14" height="10" rx="2" />
                        <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                    </svg>
                    <input
                        class="min-w-0 flex-1 border-0 bg-transparent text-[#172033] outline-0 placeholder:text-[#9aa7b7]"
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Masukkan password"
                        required
                    >
                    <button
                        class="inline-grid h-9 w-9 shrink-0 place-items-center rounded-lg border-0 bg-transparent p-0 text-[#146bb5] transition hover:bg-[#eef7ff]"
                        type="button"
                        data-toggle-password="password"
                        aria-label="Tampilkan password"
                    >
                        <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[1.9] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true" data-password-icon-show>
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg class="hidden h-[18px] w-[18px] fill-none stroke-current stroke-[1.9] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true" data-password-icon-hide>
                            <path d="M3 3l18 18" />
                            <path d="M10.6 10.6a3 3 0 0 0 4.2 4.2" />
                            <path d="M9.9 5.2A10.3 10.3 0 0 1 12 5c6.5 0 10 7 10 7a18.8 18.8 0 0 1-2.5 3.4" />
                            <path d="M6.6 6.6C3.6 8.6 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 4.1-.9" />
                        </svg>
                    </button>
                </div>

                <button
                    class="mt-[18px] inline-flex min-h-12 items-center justify-center gap-[9px] rounded-[11px] border-0 bg-[#146bb5] font-bold text-white shadow-[0_10px_20px_rgb(20_107_181_/_18%)] transition hover:-translate-y-px hover:bg-[#0c568f] disabled:cursor-wait disabled:opacity-70"
                    type="submit"
                    data-loading-button
                    data-loading-text="Memeriksa akun..."
                >
                    <span class="hidden h-[17px] w-[17px] animate-spin rounded-full border-2 border-white/40 border-t-white" data-loading-spinner aria-hidden="true"></span>
                    <span data-loading-label>Masuk</span>
                </button>
            </form>

            <footer class="mt-[42px] text-center text-xs text-[#98a2b3]">Copyright 2025 Dinas Komunikasi dan Informatika Kabupaten Siak</footer>
        </div>
    </section>
</main>
@endsection
