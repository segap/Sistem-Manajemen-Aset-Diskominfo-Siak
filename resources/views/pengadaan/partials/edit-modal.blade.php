<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="edit-procurement-{{ $procurement->id }}" role="presentation">
    <section class="w-[min(720px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="edit-procurement-title-{{ $procurement->id }}">
        <header class="flex min-h-[83px] items-center justify-between gap-5 border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <p class="m-0 mb-1 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#146bb5]">Pengadaan</p>
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]" id="edit-procurement-title-{{ $procurement->id }}">Edit Pengadaan</h2>
                <p class="mt-1 text-[11px] text-[#718096]">Perbarui data sebelum barang diterima atau diverifikasi.</p>
            </div>
            <button class="inline-flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal">
                <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </header>

        <form class="grid gap-4 p-[22px]" method="POST" action="{{ route('pengadaan.update', $procurement) }}" data-submit-loading>
            @csrf
            @method('PATCH')

            <div class="rounded-[11px] border border-[#dfe7f0] bg-[#f8fafc] px-4 py-3 text-[10px] leading-relaxed text-[#66758a]">
                <strong class="block text-[11px] text-[#405267]">Data dari usulan tidak dapat diubah</strong>
                {{ $procurement->nama_barang }} · {{ $procurement->jumlah_dipesan }} unit · Rp {{ number_format((float) $procurement->harga_satuan, 0, ',', '.') }}
            </div>

            <div class="grid grid-cols-2 gap-4 max-[580px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Kategori</span>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="kategori_aset_id" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('kategori_aset_id', $procurement->kategori_aset_id) === $category->id)>{{ $category->nama_kategori }}</option>
                        @endforeach
                    </select>
                    @error('kategori_aset_id') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
                @include('pengadaan.partials.field', ['label' => 'Merek / tipe', 'name' => 'merek_tipe', 'value' => old('merek_tipe', $procurement->merek_tipe)])
            </div>

            <div class="grid grid-cols-2 gap-4 max-[580px]:grid-cols-1">
                @include('pengadaan.partials.field', ['label' => 'Supplier', 'name' => 'pemasok', 'value' => old('pemasok', $procurement->pemasok), 'required' => true])
                @include('pengadaan.partials.field', ['label' => 'Tanggal pesan', 'name' => 'tanggal_pesan', 'type' => 'date', 'value' => old('tanggal_pesan', $procurement->tanggal_pesan?->format('Y-m-d')), 'required' => true])
            </div>

            @include('pengadaan.partials.field', ['label' => 'Perkiraan tanggal tiba', 'name' => 'perkiraan_tanggal_tiba', 'type' => 'date', 'value' => old('perkiraan_tanggal_tiba', $procurement->perkiraan_tanggal_tiba?->format('Y-m-d'))])

            <footer class="mt-1 flex items-center justify-end gap-2 border-t border-[#edf1f5] pt-4">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Simpan Perubahan</span></button>
            </footer>
        </form>
    </section>
</div>
