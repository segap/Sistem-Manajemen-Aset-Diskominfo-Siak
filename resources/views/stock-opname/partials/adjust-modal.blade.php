@php
    $systemStock = (int) $item->stok;
    $unit = $item->satuan ?: 'unit';
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 max-[580px]:p-3" data-modal="adjust-stock-opname-{{ $item->id }}" role="presentation">
    <section class="w-[min(560px,100%)] rounded-2xl bg-white p-[27px] shadow-[0_25px_80px_rgb(2_19_34_/_28%)]" role="dialog" aria-modal="true">
        <h2 class="m-0 mb-2 text-[19px] font-extrabold text-[#0b2b50]">Stock Opname Fisik</h2>
        <p class="m-0 mb-4 text-[11px] text-[#6c7a8d]">{{ $item->kode_internal ?: '-' }} - {{ $item->nama_barang }}</p>
        <form class="grid gap-3" method="POST" action="{{ route('stock-opname.adjust', $item) }}" data-stock-physical-form data-system-stock="{{ $systemStock }}" data-stock-unit="{{ $unit }}" data-submit-loading>
            @csrf
            @method('PATCH')
            <input type="hidden" name="jenis" value="stock_opname_fisik">

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Stok Sistem</span>
                <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#607189] outline-0" value="{{ number_format($systemStock, 0, ',', '.') }} {{ $unit }}" readonly>
            </label>

            @include('pengadaan.partials.field', ['label' => 'Stok Fisik', 'name' => 'stok_fisik', 'type' => 'number', 'value' => old('stok_fisik', $systemStock), 'required' => true, 'attrs' => 'min=0 data-stock-physical-input'])

            <div class="grid grid-cols-2 gap-3 max-[560px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Selisih</span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#607189] outline-0" value="0 {{ $unit }}" readonly data-stock-difference-output>
                </label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Hasil</span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#08714e] outline-0" value="Sesuai" readonly data-stock-result-output>
                </label>
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Catatan Hasil Opname</span>
                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] text-[#34465a]" name="catatan" placeholder="Wajib diisi jika stok fisik tidak sesuai stok sistem..." data-stock-note>{{ old('catatan') }}</textarea>
            </label>

            <div class="flex justify-end gap-2">
                <button class="page-action-button rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit">Simpan Hasil Opname</button>
            </div>
        </form>
    </section>
</div>

@once
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value)

        document.querySelectorAll('[data-stock-physical-form]').forEach((form) => {
            const systemStock = Number(form.dataset.systemStock || 0)
            const unit = form.dataset.stockUnit || 'unit'
            const input = form.querySelector('[data-stock-physical-input]')
            const differenceOutput = form.querySelector('[data-stock-difference-output]')
            const resultOutput = form.querySelector('[data-stock-result-output]')
            const note = form.querySelector('[data-stock-note]')

            const refresh = () => {
                const physicalStock = Math.max(0, Number(input?.value || 0))
                const difference = physicalStock - systemStock
                const prefix = difference > 0 ? '+' : ''

                if (differenceOutput) {
                    differenceOutput.value = `${prefix}${formatNumber(difference)} ${unit}`
                }
                if (resultOutput) {
                    resultOutput.value = difference === 0 ? 'Sesuai' : 'Tidak Sesuai'
                    resultOutput.classList.toggle('text-[#08714e]', difference === 0)
                    resultOutput.classList.toggle('text-[#b42318]', difference !== 0)
                }
                if (note) {
                    note.required = difference !== 0
                }
            }

            input?.addEventListener('input', refresh)
            refresh()
        })
    })
    </script>
@endonce
