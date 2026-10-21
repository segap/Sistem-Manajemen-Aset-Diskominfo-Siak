<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 max-[580px]:p-3" data-modal="verify-stock-opname-{{ $item->id }}" role="presentation">
    <section class="w-[min(520px,100%)] rounded-2xl bg-white p-[27px] shadow-[0_25px_80px_rgb(2_19_34_/_28%)]" role="dialog" aria-modal="true">
        <h2 class="m-0 mb-2 text-[19px] font-extrabold text-[#0b2b50]">Verifikasi Barang Persediaan</h2>
        <p class="m-0 mb-4 text-[11px] text-[#6c7a8d]">{{ $item->kode_persediaan }} - {{ $item->nama_barang }}</p>
        <form class="grid gap-3" method="POST" action="{{ route('stock-opname.verify', $item) }}" data-submit-loading>
            @csrf
            @method('PATCH')
            <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] px-3 py-3 !text-[10px] text-[#34465a] placeholder:!text-[10px] placeholder:text-[#9aa8b8]" name="catatan_verifikasi" placeholder="Catatan verifikasi...">{{ old('catatan_verifikasi') }}</textarea>
            <div class="flex justify-end gap-2">
                <button class="page-action-button rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button rounded-[9px] border-0 bg-[#c94c45] px-3.5 text-[10px] font-bold text-white" type="submit" name="decision" value="ditolak_tim_aset">Tolak</button>
                <button class="page-action-button rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit" name="decision" value="disetujui_tim_aset">Setujui</button>
            </div>
        </form>
    </section>
</div>
