@php
    use Illuminate\Support\Facades\Storage;

    $isEdit = $mode === 'edit';
    $holder = $holder ?? null;
    $modalName = $isEdit ? 'edit-'.$holder->id : 'create';
    $isOpen = $openModal === $modalName;
    $fieldValue = fn (string $field, mixed $default = '') => $isOpen ? old($field, $default) : $default;
    $photoUrl = $holder?->foto_path ? Storage::disk('public')->url($holder->foto_path) : null;
@endphp

<div class="fixed inset-0 z-80 {{ $isOpen ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="{{ $isEdit ? 'edit-asset-holder-'.$holder->id : 'create-asset-holder' }}" role="presentation">
    <section class="w-[min(760px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]">{{ $isEdit ? 'Edit Pemegang Aset' : 'Tambah Pemegang Aset' }}</h2>
                <p class="m-0 mt-1 text-[11px] text-[#7f8ea1]">Lengkapi data pegawai tanpa membuat akun login baru.</p>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup">
                <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </header>

        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ $isEdit ? route('pemegang-aset.update', $holder) : route('pemegang-aset.store') }}" enctype="multipart/form-data" data-submit-loading data-asset-holder-form>
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif
            <input type="hidden" name="_modal" value="{{ $modalName }}">

            @if ($isOpen && $errors->any())
                <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="grid gap-4">
                <div class="rounded-[14px] border border-[#dce4ec] bg-[#fbfcfe] p-4">
                    <span class="mb-3 block text-[11px] font-bold text-[#405267]">Foto Pegawai</span>
                    <div class="flex items-center gap-4 max-[520px]:items-start">
                        <div class="grid h-[92px] w-[92px] shrink-0 place-items-center overflow-hidden rounded-[14px] border border-[#dce4ec] bg-white">
                            @if ($photoUrl)
                                <img class="h-full w-full object-cover" src="{{ $photoUrl }}" alt="Foto {{ $holder->nama_pegawai }}" data-photo-preview>
                            @else
                                <img class="hidden h-full w-full object-cover" alt="Preview foto pegawai" data-photo-preview>
                                <span class="text-[22px] font-extrabold text-[#8aa0b8]" data-photo-placeholder>{{ $isEdit ? mb_substr($holder->nama_pegawai, 0, 1) : '?' }}</span>
                            @endif
                        </div>
                        <div class="grid min-w-0 flex-1 gap-2">
                            <input class="asset-holder-file-input h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[10px] file:font-bold file:text-[#146bb5]" type="file" name="foto" accept="image/*" data-photo-input>
                            <small class="text-[9px] font-semibold leading-relaxed text-[#718095]">Opsional. Format JPG, JPEG, PNG, atau WEBP. Ukuran maksimal 2 MB.</small>
                            @error('foto') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                        </div>
                    </div>
                </div>

                <div class="grid gap-4">
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Nama Pegawai <b class="text-[#c44f47]">*</b></span>
                        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="nama_pegawai" value="{{ $fieldValue('nama_pegawai', $holder->nama_pegawai ?? '') }}" required>
                        @error('nama_pegawai') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </label>

                    <div class="grid grid-cols-2 gap-4 max-[620px]:grid-cols-1">
                        <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                            <span>NIP</span>
                            <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="nip" value="{{ $fieldValue('nip', $holder->nip ?? '') }}" inputmode="numeric" placeholder="Angka maksimal 30 digit">
                            @error('nip') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                        </label>
                        <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                            <span>Email</span>
                            <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="email" name="email" value="{{ $fieldValue('email', $holder->email ?? '') }}">
                            @error('email') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                        </label>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 max-[620px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Unit Kerja <b class="text-[#c44f47]">*</b></span>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="unit_kerja" required>
                        <option value="">Pilih Unit Kerja</option>
                        @foreach ($workUnits as $unitValue => $unitLabel)
                            <option value="{{ $unitValue }}" @selected($fieldValue('unit_kerja', $holder->unit_kerja ?? '') === $unitValue)>{{ $unitLabel }}</option>
                        @endforeach
                    </select>
                    @error('unit_kerja') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Jabatan <b class="text-[#c44f47]">*</b></span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="jabatan" value="{{ $fieldValue('jabatan', $holder->jabatan ?? '') }}" required>
                    @error('jabatan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4 max-[620px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Nomor HP</span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="nomor_hp" value="{{ $fieldValue('nomor_hp', $holder->nomor_hp ?? '') }}">
                    @error('nomor_hp') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Nomor KTP/NIK <small class="font-semibold text-[#8a98aa]">(opsional)</small></span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="nik" value="{{ $canViewSensitiveAssetHolderData ? $fieldValue('nik', $holder?->getRawOriginal('nik') ?? '') : '' }}" inputmode="numeric" maxlength="16" @disabled(! $canViewSensitiveAssetHolderData)>
                    @error('nik') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Upload Dokumen KTP <small class="font-semibold text-[#8a98aa]">(opsional, private)</small></span>
                <input class="asset-holder-file-input h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[10px] file:font-bold file:text-[#146bb5]" type="file" name="dokumen_ktp" accept=".pdf,image/jpeg,image/png" @disabled(! $canViewSensitiveAssetHolderData)>
                @if ($isEdit && $holder->ktp_path)
                    <small class="text-[9px] font-semibold text-[#718095]">Dokumen lama tersimpan aman. Unggah file baru hanya jika ingin mengganti.</small>
                @endif
                @error('dokumen_ktp') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>

            <input type="hidden" name="is_active" value="0">
            <label class="flex items-center gap-2 rounded-[10px] border border-[#dce4ec] bg-[#fbfcfe] px-3 py-3 text-[11px] font-bold text-[#405267]">
                <input class="h-4 w-4 accent-[#146bb5]" type="checkbox" name="is_active" value="1" @checked($isOpen ? old('is_active', '1') === '1' : ($holder->is_active ?? true))>
                Data pegawai aktif
            </label>

            <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan...">
                    <span data-loading-label>Simpan</span>
                </button>
            </footer>
        </form>
    </section>
</div>
