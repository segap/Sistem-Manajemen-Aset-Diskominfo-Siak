<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="{{ $modalId }}" role="presentation">
    <section class="w-[min(620px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)]" role="dialog" aria-modal="true">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">{{ $title }}</h2>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>
        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ $action }}" data-submit-loading>
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif
            <input type="hidden" name="modal_id" value="{{ $modalId }}">
            @include('kategori-aset.partials.field', ['label' => 'Nama Kategori', 'name' => 'nama_kategori', 'value' => old('nama_kategori', $category?->nama_kategori), 'placeholder' => 'Contoh: ATK', 'required' => true])
            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Keterangan</span>
                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)] @error('keterangan') border-[#e08a84] @enderror" name="keterangan" rows="4" placeholder="Contoh: Alat tulis dan perlengkapan kantor." maxlength="1000">{{ old('keterangan', $category?->keterangan) }}</textarea>
                @error('keterangan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>
            <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Simpan</span></button>
            </footer>
        </form>
    </section>
</div>
