<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="create-procurement" role="presentation">
    <section class="w-[min(900px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="create-procurement-title">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <p class="m-0 mb-1 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#146bb5]">Pengadaan</p>
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]" id="create-procurement-title">Catat Pengadaan</h2>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>

        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('pengadaan.store') }}" data-submit-loading data-procurement-form>
            @csrf

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Usulan pengadaan yang sudah disetujui</span>
                <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="usulan_pengadaan_id" required data-proposal-select>
                    <option value="">Pilih usulan disetujui</option>
                    @foreach ($approvedProposals as $proposal)
                        <option value="{{ $proposal->id }}"
                            data-nama="{{ $proposal->nama_barang }}"
                            data-spesifikasi="{{ $proposal->spesifikasi }}"
                            data-jumlah="{{ $proposal->jumlah }}"
                            data-harga="{{ $proposal->estimasi_harga_satuan }}"
                            data-alasan="{{ $proposal->alasan_kebutuhan }}"
                            @selected(old('usulan_pengadaan_id') == $proposal->id)>
                            Usulan #{{ $proposal->id }} - {{ $proposal->nama_barang }} - {{ $proposal->requester?->name }}
                        </option>
                    @endforeach
                </select>
                @error('usulan_pengadaan_id') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>

            @if ($approvedProposals->isEmpty())
                <div class="rounded-[11px] border border-[#fedf89] bg-[#fffbeb] p-4 text-[11px] font-semibold leading-relaxed text-[#8a4b12]">
                    Belum ada usulan pengadaan yang sudah disetujui Kasubbag dan belum diproses. Admin baru bisa mencatat pengadaan setelah usulan selesai diverifikasi Tim Aset dan disetujui Kasubbag.
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Kategori</span>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="kategori_aset_id" required>
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('kategori_aset_id') == $category->id)>{{ $category->nama_kategori }}</option>
                        @endforeach
                    </select>
                    @error('kategori_aset_id') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
                <div class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Kode inventaris</span>
                    <div class="grid min-h-[42px] place-items-center rounded-[9px] border border-dashed border-[#c9d8e8] bg-[#f7faff] px-3 text-[10px] font-semibold text-[#607189]">
                        Dibuat otomatis setelah disimpan, contoh: MK/001
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                @include('pengadaan.partials.field', ['label' => 'Nama barang dari usulan', 'name' => 'nama_barang', 'value' => old('nama_barang'), 'required' => true, 'readonly' => true, 'attrs' => 'data-order-nama'])
                @include('pengadaan.partials.field', ['label' => 'Merek / tipe', 'name' => 'merek_tipe', 'value' => old('merek_tipe')])
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Spesifikasi dari usulan</span>
                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-[#f6f8fb] px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="spesifikasi" rows="4" readonly required data-order-spesifikasi>{{ old('spesifikasi') }}</textarea>
                @error('spesifikasi') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                @include('pengadaan.partials.field', ['label' => 'Jumlah pesanan dari usulan', 'name' => 'jumlah_dipesan', 'type' => 'number', 'value' => old('jumlah_dipesan', 1), 'required' => true, 'readonly' => true, 'attrs' => 'min=1 data-order-jumlah'])
                @include('pengadaan.partials.field', ['label' => 'Harga satuan dari usulan', 'name' => 'harga_satuan', 'type' => 'number', 'value' => old('harga_satuan'), 'required' => true, 'readonly' => true, 'attrs' => 'min=0 step=1 data-order-harga'])
            </div>

            <div class="rounded-[11px] bg-[#f4f7fa] p-4 text-[11px] leading-relaxed text-[#6e7d91]">
                <strong class="mb-1.5 block text-[#405267]">Total harga</strong>
                <p class="m-0 text-[15px] font-extrabold text-[#0b2b50]" data-order-total>Rp 0</p>
            </div>

            @include('pengadaan.partials.field', ['label' => 'Supplier', 'name' => 'pemasok', 'value' => old('pemasok'), 'required' => true])

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                @include('pengadaan.partials.field', ['label' => 'Tanggal pesan', 'name' => 'tanggal_pesan', 'type' => 'date', 'value' => old('tanggal_pesan'), 'required' => true])
                @include('pengadaan.partials.field', ['label' => 'Perkiraan tiba', 'name' => 'perkiraan_tanggal_tiba', 'type' => 'date', 'value' => old('perkiraan_tanggal_tiba')])
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Alasan pengadaan dari usulan</span>
                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-[#f6f8fb] px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="alasan_pengadaan" rows="4" readonly required data-order-alasan>{{ old('alasan_pengadaan') }}</textarea>
                @error('alasan_pengadaan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>

            <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Simpan</span></button>
            </footer>
        </form>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-procurement-form]')
    if (!form) return

    const select = form.querySelector('[data-proposal-select]')
    const fields = {
        nama: form.querySelector('[data-order-nama]'),
        spesifikasi: form.querySelector('[data-order-spesifikasi]'),
        jumlah: form.querySelector('[data-order-jumlah]'),
        harga: form.querySelector('[data-order-harga]'),
        alasan: form.querySelector('[data-order-alasan]'),
        total: form.querySelector('[data-order-total]'),
    }
    const format = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 })
    const sync = () => {
        const option = select.selectedOptions[0]
        if (!option || !option.value) return
        fields.nama.value = option.dataset.nama || ''
        fields.spesifikasi.value = option.dataset.spesifikasi || ''
        fields.jumlah.value = option.dataset.jumlah || '1'
        fields.harga.value = option.dataset.harga || ''
        fields.alasan.value = option.dataset.alasan || ''
        fields.total.textContent = format.format((Number(fields.jumlah.value) || 0) * (Number(fields.harga.value) || 0))
    }
    select.addEventListener('change', sync)
    sync()
})
</script>
