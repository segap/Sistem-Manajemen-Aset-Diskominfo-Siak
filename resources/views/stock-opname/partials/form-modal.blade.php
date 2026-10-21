@php
    $isEdit = filled($item);
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="{{ $modalId }}" role="presentation">
    <section class="w-[min(820px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div><h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">{{ $title }}</h2></div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>
        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ $action }}" data-submit-loading>
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Kategori Stock Opname</span>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] text-[#34465a] outline-0" name="stock_opname_category_id" required>
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('stock_opname_category_id', $item?->stock_opname_category_id) == $category->id)>{{ $category->nama_kategori }}</option>
                        @endforeach
                    </select>
                    @error('stock_opname_category_id') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Kode Barang dari Kodefikasi <span class="font-semibold text-[#97a3b1]">(opsional)</span></span>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] text-[#34465a] outline-0" name="kode_barang_id" data-stock-code-select>
                        <option value="">Tidak memakai kodefikasi</option>
                        @foreach ($kodeBarangOptions as $kode)
                            <option value="{{ $kode->id }}" data-name="{{ $kode->nama_barang }}" @selected(old('kode_barang_id', $item?->kode_barang_id) == $kode->id)>{{ $kode->kode_barang }} - {{ $kode->nama_barang }}</option>
                        @endforeach
                    </select>
                    @error('kode_barang_id') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                @include('pengadaan.partials.field', ['label' => 'Nama Barang', 'name' => 'nama_barang', 'value' => old('nama_barang', $item?->nama_barang), 'required' => true, 'attrs' => 'data-stock-name-input'])
                <div class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Kode Internal</span>
                    @if ($isEdit)
                        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-[#f8fafc] px-3 text-[10px] font-semibold uppercase text-[#607189] outline-0" value="{{ $item?->kode_internal }}" readonly>
                    @else
                        <div class="grid min-h-[42px] place-items-center rounded-[9px] border border-dashed border-[#c9d8e8] bg-[#f7faff] px-3 text-[10px] font-semibold text-[#607189]">
                            Dibuat otomatis setelah disimpan, contoh: KB/001
                        </div>
                    @endif
                    @error('kode_internal') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="{{ $isEdit ? 'grid grid-cols-2 gap-4 max-[700px]:grid-cols-1' : 'grid grid-cols-3 gap-4 max-[820px]:grid-cols-1' }}">
                @include('pengadaan.partials.field', ['label' => 'Merek/Tipe', 'name' => 'merek_tipe', 'value' => old('merek_tipe', $item?->merek_tipe)])
                @include('pengadaan.partials.field', ['label' => 'Satuan', 'name' => 'satuan', 'value' => old('satuan', $item?->satuan), 'required' => true, 'attrs' => 'placeholder=Rim/Botol/Kotak'])
                @unless ($isEdit)
                    @include('pengadaan.partials.field', ['label' => 'Stok Awal', 'name' => 'stok', 'type' => 'number', 'value' => old('stok', $item?->stok ?? 0), 'required' => true, 'attrs' => 'min=0'])
                @endunless
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Lokasi Penyimpanan</span>
                <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] text-[#34465a] outline-0" name="lokasi_penyimpanan">
                    <option value="">Pilih lokasi</option>
                    @foreach ($ruanganOptions as $ruangan)
                        <option value="{{ $ruangan->nama_ruangan }}" @selected(old('lokasi_penyimpanan', $item?->lokasi_penyimpanan) === $ruangan->nama_ruangan)>{{ $ruangan->nama_ruangan }}</option>
                    @endforeach
                </select>
                @error('lokasi_penyimpanan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Keterangan</span>
                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] text-[#34465a] outline-0" name="keterangan" rows="4">{{ old('keterangan', $item?->keterangan) }}</textarea>
                @error('keterangan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>

            @if ($isEdit)
                <label class="inline-flex items-center gap-2 text-[11px] font-bold text-[#405267]">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item?->is_active))>
                    Barang persediaan aktif
                </label>
            @endif

            <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Simpan</span></button>
            </footer>
        </form>
    </section>
</div>

@once
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-stock-code-select]').forEach((select) => {
            const form = select.closest('form')
            const nameInput = form?.querySelector('[data-stock-name-input]')
            select.addEventListener('change', () => {
                const option = select.selectedOptions[0]
                if (!option?.value) return
                if (nameInput && !nameInput.value) nameInput.value = option.dataset.name || ''
            })
        })
    })
    </script>
@endonce
