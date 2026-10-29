@php
    $spendingTypes = \App\Models\ProcurementProposal::spendingTypes();
    $editUserLabel = fn ($user) => trim($user?->name.($user?->nip ? ' - '.$user->nip : ''));
    $existingPhotoUrl = $proposal->path_foto ? \Illuminate\Support\Facades\Storage::disk('public')->url($proposal->path_foto) : null;
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="edit-proposal-{{ $proposal->id }}" role="presentation">
    <section class="w-[min(900px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="edit-proposal-title-{{ $proposal->id }}">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="edit-proposal-title-{{ $proposal->id }}">Edit Usulan Pengadaan</h2>
                <p class="mt-1 text-[11px] text-[#718096]">Perbarui data usulan selama masih berstatus diajukan.</p>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal">
                <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </header>

        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('web.usulan-pengadaan.update', $proposal) }}" enctype="multipart/form-data" data-submit-loading data-edit-proposal-form>
            @csrf
            @method('PATCH')

            <fieldset class="grid gap-[8px] text-[11px] font-bold text-[#405267]">
                <legend class="leading-none">Jenis Belanja <span class="text-[#c94c45]">*</span></legend>
                <div class="mt-2 grid grid-cols-2 gap-3 max-[560px]:grid-cols-1">
                    @foreach ($spendingTypes as $value => $label)
                        <label class="flex min-h-[42px] cursor-pointer items-center gap-[10px] rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-semibold text-[#34465a] transition hover:border-[#9fc5df] hover:bg-[#f8fbfe]">
                            <input class="m-0 h-4 w-4 shrink-0 border-[#8fa0b2] text-[#146bb5] focus:ring-[#146bb5]" type="radio" name="jenis_belanja" value="{{ $value }}" @checked(old('jenis_belanja', $proposal->jenis_belanja) === $value) required>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('jenis_belanja') <small class="text-[9px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </fieldset>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                @include('usulan-pengadaan.partials.field', ['label' => 'Nama barang', 'name' => 'nama_barang', 'value' => $proposal->nama_barang, 'required' => true])
                @include('usulan-pengadaan.partials.field', ['label' => 'Jumlah', 'name' => 'jumlah', 'type' => 'number', 'value' => $proposal->jumlah, 'required' => true, 'attrs' => 'min=1 data-edit-proposal-qty'])
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Spesifikasi</span>
                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="spesifikasi" rows="4" required>{{ $proposal->spesifikasi }}</textarea>
            </label>

            @include('usulan-pengadaan.partials.field', ['label' => 'Estimasi harga satuan', 'name' => 'estimasi_harga_satuan', 'type' => 'number', 'value' => $proposal->estimasi_harga_satuan, 'required' => true, 'attrs' => 'min=0 step=1 data-edit-proposal-price'])

            <div class="rounded-[11px] bg-[#f4f7fa] p-4 text-[11px] leading-relaxed text-[#6e7d91]">
                <strong class="mb-1.5 block text-[#405267]">Estimasi total</strong>
                <p class="m-0 text-[15px] font-extrabold text-[#0b2b50]" data-edit-proposal-total>Rp 0</p>
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Justifikasi kebutuhan (kenapa barang ini diperlukan)</span>
                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="alasan_kebutuhan" rows="4" required>{{ $proposal->alasan_kebutuhan }}</textarea>
            </label>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Pengguna barang</span>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="pengguna_barang_id" required>
                        @foreach ($users as $optionUser)
                            <option value="{{ $optionUser->id }}" @selected($proposal->pengguna_barang_id === $optionUser->id)>{{ $editUserLabel($optionUser) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Penanggung jawab</span>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="penanggung_jawab_id" required>
                        @foreach ($users as $optionUser)
                            <option value="{{ $optionUser->id }}" @selected($proposal->penanggung_jawab_id === $optionUser->id)>{{ $editUserLabel($optionUser) }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                @include('usulan-pengadaan.partials.field', ['label' => 'Link Google Drive gambar barang', 'name' => 'url_referensi_gambar', 'type' => 'url', 'value' => $proposal->url_referensi_gambar, 'attrs' => 'placeholder=https://drive.google.com/...'])
                @include('usulan-pengadaan.partials.field', ['label' => 'URL e-katalog', 'name' => 'url_ekatalog', 'type' => 'url', 'value' => $proposal->url_ekatalog])
            </div>

            <div class="grid gap-[10px] text-[11px] font-bold text-[#405267]">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>Foto barang</span>
                    <small class="font-medium text-[#718096]">Kosongkan jika foto tidak diubah.</small>
                </div>
                @if ($existingPhotoUrl)
                    <img class="h-28 w-28 rounded-[10px] border border-[#dce4ec] bg-[#f6f8fb] object-cover" src="{{ $existingPhotoUrl }}" alt="Foto {{ $proposal->nama_barang }} saat ini">
                @else
                    <p class="m-0 rounded-[9px] bg-[#f6f8fb] px-3 py-2.5 text-[10px] font-medium text-[#718096]">Belum ada foto barang.</p>
                @endif
                <input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[11px] file:font-bold file:text-[#146bb5]" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                <small class="font-medium text-[#718096]">JPG, PNG, atau WEBP; ukuran maksimal 2 MB.</small>
            </div>

            <footer class="flex items-center justify-end gap-2 border-t border-[#edf1f5] pt-4">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Simpan Perubahan</span></button>
            </footer>
        </form>
    </section>
</div>
