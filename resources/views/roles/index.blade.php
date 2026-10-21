@extends('layouts.app')

@section('title', 'Manajemen Role - SIMIVA')

@php
    $openModal = old('_modal');
    $canManageRoles = auth()->user()?->role === \App\Enums\UserRole::SuperAdmin->value;
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Manajemen Role</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Tambah dan kelola role pengguna sistem.</p>
        </div>
        @if ($canManageRoles)<button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f] max-[580px]:w-full" type="button" data-modal-open="create-role">
            <span class="text-sm font-normal leading-none">+</span> Tambah Role
        </button>@endif
    </div>

    @if (session('success'))
        <div class="fixed right-6 top-[88px] z-70 flex items-center gap-[9px] rounded-[10px] border border-[#a9e2c9] bg-[#effcf6] px-4 py-3 text-[11px] font-semibold text-[#08714e] shadow-[0_12px_30px_rgb(23_91_65_/_12%)] max-[580px]:left-3 max-[580px]:right-3" role="status" data-auto-dismiss>
            <span class="grid h-[19px] w-[19px] place-items-center rounded-full bg-[#24a576] text-white">
                <svg class="h-3 w-3 fill-none stroke-current stroke-[3] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7" /></svg>
            </span>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any() && ! $openModal)
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <div class="filter-toolbar flex min-h-[72px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[900px]:flex-wrap">
            <form class="flex flex-1 items-center gap-3 max-[900px]:flex-wrap" method="GET" action="{{ route('web.roles.index') }}">
                <input class="h-[42px] w-[min(330px,100%)] rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 text-[10px] text-[#34445a] outline-0 placeholder:text-[#78879a]" name="search" value="{{ request('search') }}" placeholder="Cari nama role atau kode role...">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
                @if (request()->filled('search'))
                    <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('web.roles.index') }}">Reset</a>
                @endif
            </form>
            <x-export-dropdown dataset="roles" align="left" />
            <span class="text-[10px] font-semibold text-[#8593a5]">{{ $roles->count() }} role</span>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Nama Role</th>
                        <th>Kode</th>
                        <th>Jenis</th>
                        <th>Pengguna</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $index => $role)
                        <tr>
                            <td class="number-column">{{ $index + 1 }}</td>
                            <td>{{ $role->nama }}</td>
                            <td><span class="code-badge">{{ $role->kode }}</span></td>
                            <td>
                                <span class="loan-status {{ $role->is_system ? 'status-approved' : 'status-submitted' }}">
                                    {{ $role->is_system ? 'Sistem' : 'Tambahan' }}
                                </span>
                            </td>
                            <td>{{ $role->users_count ?? 0 }}</td>
                            <td class="action-column">
                                <div class="row-actions">
                                    @if ($canManageRoles)<button title="Edit" type="button" data-modal-open="edit-role-{{ $role->id }}" aria-label="Edit {{ $role->nama }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" /></svg>
                                    </button>
                                    @if (! $role->is_system)
                                        <button class="danger" title="Hapus" type="button" data-modal-open="delete-role-{{ $role->id }}" aria-label="Hapus {{ $role->nama }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg>
                                        </button>
                                    @endif @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11l2 2 4-5" /><path d="M5 4h14v16H5z" /></svg></span>
                                    <strong>Role belum tersedia</strong>
                                    <p>Tambahkan role baru untuk pengguna sistem.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="fixed inset-0 z-80 {{ $openModal === 'create' ? 'grid' : 'hidden' }} place-items-center overflow-hidden bg-[rgb(5_22_38_/_48%)] p-3 animate-[modalBackdropIn_.2s_ease-out_both]" data-modal="create-role" role="presentation">
        <section class="overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" style="width: min(700px, calc(100vw - 24px)); height: min(680px, 80dvh); max-height: calc(100dvh - 24px);" role="dialog" aria-modal="true" aria-labelledby="create-role-title" tabindex="-1">
            <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                <div><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="create-role-title">Tambah Role</h2></div>
                <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
            </header>
            <form method="POST" action="{{ route('web.roles.store') }}" data-submit-loading>
                @csrf
                <input type="hidden" name="_modal" value="create">
                <div class="grid gap-[14px] p-[22px] max-[580px]:p-4">
                @if ($openModal === 'create' && $errors->any())
                    <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
                @endif
                <div class="grid grid-cols-2 gap-3 max-[580px]:grid-cols-1">
                    @include('users.partials.input', ['label' => 'Nama role', 'name' => 'nama', 'value' => old('nama'), 'required' => true])
                    @include('users.partials.input', ['label' => 'Kode role', 'name' => 'kode', 'value' => old('kode'), 'placeholder' => 'contoh: operator_bidang'])
                </div>
                <p class="m-0 text-[10px] leading-relaxed text-[#718095]">Kode otomatis dibuat dari nama jika dikosongkan. Gunakan huruf kecil, angka, dan underscore.</p>
                @include('roles.partials.permission-picker', [
                    'permissionModalKey' => 'create',
                    'selectedPermissions' => old('permissions', []),
                    'superAdminRole' => false,
                ])
                </div>
                <footer class="flex shrink-0 justify-end gap-2 border-t border-[#edf1f5] bg-white px-[22px] py-4 max-[580px]:px-4">
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Simpan Role</span></button>
                </footer>
            </form>
        </section>
    </div>

    @foreach ($roles as $role)
        @php
            $editOpen = $openModal === 'edit-'.$role->id;
            $deleteOpen = $openModal === 'delete-'.$role->id;
            $rolePermissionCodes = $role->permissions->pluck('kode')->all();
        @endphp

        <div class="fixed inset-0 z-80 {{ $editOpen ? 'grid' : 'hidden' }} place-items-center overflow-hidden bg-[rgb(5_22_38_/_48%)] p-3 animate-[modalBackdropIn_.2s_ease-out_both]" data-modal="edit-role-{{ $role->id }}" role="presentation">
            <section class="overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" style="width: min(700px, calc(100vw - 24px)); height: min(680px, 80dvh); max-height: calc(100dvh - 24px);" role="dialog" aria-modal="true" tabindex="-1">
                <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]"><div><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]">Edit Role</h2></div><button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button></header>
                <form method="POST" action="{{ route('web.roles.update', $role) }}" data-submit-loading>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_modal" value="edit-{{ $role->id }}">
                    <div class="grid gap-[14px] p-[22px] max-[580px]:p-4">
                    @if ($editOpen && $errors->any())
                        <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <div class="grid grid-cols-2 gap-3 max-[580px]:grid-cols-1">
                    @include('users.partials.input', ['label' => 'Nama role', 'name' => 'nama', 'value' => $editOpen ? old('nama') : $role->nama, 'required' => true])
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Kode role</span>
                        <input
                            class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] {{ $role->is_system ? 'bg-[#f3f6f9] text-[#718095]' : 'bg-white text-[#34465a]' }} px-3 text-[10px] font-normal outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]"
                            name="kode"
                            value="{{ $editOpen ? old('kode', $role->kode) : $role->kode }}"
                            @readonly($role->is_system)
                        >
                        @error('kode') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </label>
                    </div>
                    @if ($role->is_system)
                        <p class="m-0 rounded-[10px] bg-[#f4f7fa] p-3 text-[10px] leading-relaxed text-[#718095]">Role sistem hanya bisa diubah namanya. Kode role tidak dapat diubah agar akses pengguna tetap aman.</p>
                    @else
                        <p class="m-0 rounded-[10px] bg-[#f4f7fa] p-3 text-[10px] leading-relaxed text-[#718095]">Jika kode role diubah, pengguna yang memakai role ini akan otomatis ikut diperbarui.</p>
                    @endif
                    @include('roles.partials.permission-picker', [
                        'permissionModalKey' => 'edit-'.$role->id,
                        'selectedPermissions' => $editOpen ? old('permissions', $rolePermissionCodes) : $rolePermissionCodes,
                        'superAdminRole' => $role->kode === \App\Enums\UserRole::SuperAdmin->value,
                    ])
                    </div>
                    <footer class="flex shrink-0 justify-end gap-2 border-t border-[#edf1f5] bg-white px-[22px] py-4 max-[580px]:px-4">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Simpan Role</span></button>
                    </footer>
                </form>
            </section>
        </div>

        @if (! $role->is_system)
            <div class="fixed inset-0 z-80 {{ $deleteOpen ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-role-{{ $role->id }}" role="presentation">
                <section class="w-[min(520px,100%)] rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
                    <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]"><div><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]">Hapus Role</h2></div><button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button></header>
                    <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('web.roles.destroy', $role) }}" data-submit-loading>
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="_modal" value="delete-{{ $role->id }}">
                        @if ($deleteOpen && $errors->any())
                            <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <div class="rounded-[12px] bg-[#f7f9fc] p-4 text-center text-[11px] leading-relaxed text-[#6e7d91]">
                            <strong class="mb-1.5 block text-[14px] text-[#0b2b50]">{{ $role->nama }}</strong>
                            <p class="m-0">Role dengan kode <strong>{{ $role->kode }}</strong> akan dihapus jika belum digunakan pengguna.</p>
                        </div>
                        <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c44f47] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#ad433c]" type="submit" data-loading-button data-loading-text="Menghapus..."><span data-loading-label>Hapus Role</span></button>
                        </footer>
                    </form>
                </section>
            </div>
        @endif
    @endforeach
</main>
@endsection
