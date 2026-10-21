@extends('layouts.app')

@section('title', 'Profil Pengguna - SIMIVA')

@php
    use App\Enums\UserRole;

    $roleLabel = $user->roleData?->nama
        ?? UserRole::tryFrom((string) $user->role)?->label()
        ?? str($user->role)->replace('_', ' ')->title()->toString();
    $initials = collect(explode(' ', $user->name ?? 'SA'))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
    $profileHasError = $errors->hasAny(['name', 'nip', 'email']);
    $passwordHasError = $errors->hasAny(['current_password', 'password']);
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6">
        <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Profil Pengguna</h1>
        <p class="m-0 text-[13px] text-[#66758a]">Perbarui identitas akun dan keamanan password Anda.</p>
    </div>

    @if (session('success'))
        <div class="fixed right-6 top-[88px] z-70 flex items-center gap-[9px] rounded-[10px] border border-[#a9e2c9] bg-[#effcf6] px-4 py-3 text-[11px] font-semibold text-[#08714e] shadow-[0_12px_30px_rgb(23_91_65_/_12%)] max-[580px]:left-3 max-[580px]:right-3" role="status" data-auto-dismiss>
            <span class="grid h-[19px] w-[19px] place-items-center rounded-full bg-[#24a576] text-white">
                <svg class="h-3 w-3 fill-none stroke-current stroke-[3] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7" /></svg>
            </span>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="mb-4 flex items-center gap-4 rounded-[14px] border border-slate-200 bg-white p-5 shadow-[0_8px_25px_rgb(31_62_91_/_4%)] max-[580px]:items-start">
        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-[#e4f1fa] text-[17px] font-extrabold uppercase text-[#0c5d95]">{{ $initials }}</span>
        <div class="min-w-0 flex-1">
            <h2 class="m-0 truncate text-[18px] font-extrabold text-[#0b2b50]">{{ $user->name }}</h2>
            <p class="m-0 mt-1 text-[11px] text-[#66758a]">{{ $user->email }}</p>
        </div>
        <div class="flex flex-wrap justify-end gap-2 max-[580px]:hidden">
            <span class="loan-status status-submitted">{{ $roleLabel }}</span>
            <span class="loan-status {{ $user->is_active ? 'status-approved' : 'status-rejected' }}">{{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}</span>
        </div>
    </section>

    <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
        <section class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
            <header class="flex items-start justify-between">
                <div>
                    <h2 class="m-0 mb-[3px] text-[15px] font-bold text-[#20364f]">Informasi Profil</h2>
                    <p class="m-0 text-[10px] text-[#94a0af]">Data identitas yang digunakan pada sistem</p>
                </div>
            </header>

            <form class="mt-5 grid gap-[15px]" method="POST" action="{{ route('profile.update') }}" data-submit-loading>
                @csrf
                @method('PATCH')
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Nama lengkap</span>
                    <input class="h-[42px] w-full rounded-[9px] border {{ $errors->has('name') ? 'border-[#e08a84]' : 'border-[#dce4ec]' }} bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>

                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>NIP/NIK</span>
                    <input class="h-[42px] w-full rounded-[9px] border {{ $errors->has('nip') ? 'border-[#e08a84]' : 'border-[#dce4ec]' }} bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="nip" value="{{ old('nip', $user->nip) }}" placeholder="Masukkan angka saja" inputmode="numeric" pattern="[0-9]*">
                    @error('nip') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>

                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Email</span>
                    <input class="h-[42px] w-full rounded-[9px] border {{ $errors->has('email') ? 'border-[#e08a84]' : 'border-[#dce4ec]' }} bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>

                <div class="grid grid-cols-2 gap-3 max-[580px]:grid-cols-1">
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Role</span>
                        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-[#f3f6f9] px-3 text-[10px] font-normal text-[#718095] outline-0" value="{{ $roleLabel }}" readonly>
                    </label>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Status akun</span>
                        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-[#f3f6f9] px-3 text-[10px] font-normal text-[#718095] outline-0" value="{{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}" readonly>
                    </label>
                </div>

                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center justify-self-end rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan...">
                    <span data-loading-label>Simpan Profil</span>
                </button>
            </form>
        </section>

        <section class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
            <header class="flex items-start justify-between">
                <div>
                    <h2 class="m-0 mb-[3px] text-[15px] font-bold text-[#20364f]">Ganti Password</h2>
                    <p class="m-0 text-[10px] text-[#94a0af]">Gunakan minimal 8 karakter, huruf, dan angka</p>
                </div>
            </header>

            <form class="mt-5 grid gap-[15px]" method="POST" action="{{ route('profile.password') }}" data-submit-loading>
                @csrf
                @method('PATCH')
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Password saat ini</span>
                    <input class="h-[42px] w-full rounded-[9px] border {{ $errors->has('current_password') ? 'border-[#e08a84]' : 'border-[#dce4ec]' }} bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="password" name="current_password" required autocomplete="current-password">
                    @error('current_password') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>

                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Password baru</span>
                    <input class="h-[42px] w-full rounded-[9px] border {{ $errors->has('password') ? 'border-[#e08a84]' : 'border-[#dce4ec]' }} bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="password" name="password" required autocomplete="new-password">
                    @error('password') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>

                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Konfirmasi password baru</span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="password" name="password_confirmation" required autocomplete="new-password">
                </label>

                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center justify-self-end rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan...">
                    <span data-loading-label>Ganti Password</span>
                </button>
            </form>
        </section>
    </div>
</main>
@endsection
