@php
    $currentStock = (int) $item->stok;
    $unit = $item->satuan ?: 'unit';
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="add-stock-opname-{{ $item->id }}" role="presentation">
    <section class="w-[min(760px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Tambah Stok</h2>
                <p class="m-0 mt-1 text-[11px] text-[#718095]">{{ $item->kode_internal ?: '-' }} - {{ $item->nama_barang }}</p>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>

        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('stock-opname.add-stock', $item) }}" data-add-stock-form data-current-stock="{{ $currentStock }}" data-stock-unit="{{ $unit }}" data-submit-loading>
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
                @include('peminjaman.partials.detail-item', ['label' => 'Kode Internal', 'value' => $item->kode_internal ?: '-'])
                @include('peminjaman.partials.detail-item', ['label' => 'Nama Barang', 'value' => $item->nama_barang])
                @include('peminjaman.partials.detail-item', ['label' => 'Merek/Tipe', 'value' => $item->merek_tipe ?: '-'])
                @include('peminjaman.partials.detail-item', ['label' => 'Kategori', 'value' => $item->category?->nama_kategori ?: '-'])
                @include('peminjaman.partials.detail-item', ['label' => 'Satuan', 'value' => $unit])
                @include('peminjaman.partials.detail-item', ['label' => 'Lokasi', 'value' => $item->lokasi_penyimpanan ?: '-'])
                @include('peminjaman.partials.detail-item', ['label' => 'Stok Saat Ini', 'value' => number_format($currentStock, 0, ',', '.').' '.$unit])
            </div>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Jumlah Stok Masuk *</span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] text-[#34465a] outline-0" type="number" name="jumlah_masuk" min="1" step="1" required data-add-stock-quantity>
                </label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Tanggal Masuk *</span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] text-[#34465a] outline-0" type="date" name="tanggal_masuk" value="{{ now()->format('Y-m-d') }}" required>
                </label>
            </div>

            <div class="grid gap-2 rounded-[11px] border border-[#dbeafe] bg-[#f8fbff] p-3 text-[11px] font-semibold text-[#405267]">
                <div class="flex justify-between gap-3"><span>Stok Saat Ini</span><strong>{{ number_format($currentStock, 0, ',', '.') }} {{ $unit }}</strong></div>
                <div class="flex justify-between gap-3"><span>Jumlah Ditambahkan</span><strong data-add-stock-preview-added>0 {{ $unit }}</strong></div>
                <div class="flex justify-between gap-3 border-t border-[#dbeafe] pt-2 text-[#0b2b50]"><span>Stok Setelahnya</span><strong data-add-stock-preview-after>{{ number_format($currentStock, 0, ',', '.') }} {{ $unit }}</strong></div>
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Keterangan / Catatan</span>
                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] text-[#34465a] outline-0" name="keterangan" rows="4" placeholder="Contoh: Pembelian persediaan September"></textarea>
            </label>

            <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit" data-loading-button data-loading-text="Menambah..."><span data-loading-label>Tambah Stok</span></button>
            </footer>
        </form>
    </section>
</div>

@once
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value)

        document.querySelectorAll('[data-add-stock-form]').forEach((form) => {
            const currentStock = Number(form.dataset.currentStock || 0)
            const unit = form.dataset.stockUnit || 'unit'
            const input = form.querySelector('[data-add-stock-quantity]')
            const added = form.querySelector('[data-add-stock-preview-added]')
            const after = form.querySelector('[data-add-stock-preview-after]')

            const refresh = () => {
                const quantity = Math.max(0, Number(input?.value || 0))
                if (added) added.textContent = `${formatNumber(quantity)} ${unit}`
                if (after) after.textContent = `${formatNumber(currentStock + quantity)} ${unit}`
            }

            input?.addEventListener('input', refresh)
            refresh()
        })
    })
    </script>
@endonce
