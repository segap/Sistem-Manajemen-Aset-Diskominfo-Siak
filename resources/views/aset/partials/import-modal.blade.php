<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="import-assets" role="presentation">
    <section class="w-[min(620px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="import-assets-title">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div><span class="hidden">IMPORT DATA</span><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="import-assets-title">Import Aset dari Excel</h2></div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>
        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('web.aset.impor') }}" enctype="multipart/form-data" data-submit-loading>
            @csrf
            <div class="rounded-[11px] bg-[#f4f7fa] p-4 text-[11px] leading-relaxed text-[#6e7d91]">
                <strong class="mb-2 block text-[#405267]">Format kolom Excel</strong>
                <p class="m-0 mb-1.5">Gunakan header: kategori, kode_barang_opsional, kode_internal_opsional, nama_aset, merek_tipe, spesifikasi, jumlah, tahun_perolehan, kondisi, pemasok, lokasi.</p>
                <p class="m-0">Kondisi dapat diisi: Baik, Rusak Ringan, Rusak Sedang, Rusak Berat, atau Hilang. Data yang diimport akan masuk sebagai Menunggu Verifikasi Tim Aset.</p>
            </div>
            <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('web.aset.template-impor') }}">Unduh Template</a>
            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>File Excel / CSV (maks. 10 MB)</span>
                <input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[11px] file:font-bold file:text-[#146bb5]" type="file" name="file" accept=".xlsx,.xls,.csv" required>
                @error('file') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>
            <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Mengimport..."><span data-loading-label>Import Data</span></button>
            </footer>
        </form>
    </section>
</div>
