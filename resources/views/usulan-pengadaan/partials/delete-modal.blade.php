<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-proposal-{{ $proposal->id }}" role="presentation">
    <section class="w-[min(460px,100%)] rounded-2xl bg-white p-7 text-center shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="delete-proposal-title-{{ $proposal->id }}">
        <div class="mx-auto mb-4 grid h-[54px] w-[54px] place-items-center rounded-full bg-[#fee2e2] text-[#c94c45]">
            <svg class="h-7 w-7 fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>
        </div>
        <h2 class="m-0 text-[20px] font-extrabold text-[#0b2b50]" id="delete-proposal-title-{{ $proposal->id }}">Hapus Usulan Pengadaan?</h2>
        <p class="mx-auto mb-5 mt-2 max-w-[370px] text-[12px] leading-relaxed text-[#66758a]">
            Apakah Anda yakin ingin menghapus usulan pengadaan ini? Detail barang dan seluruh riwayat persetujuan usulan ini akan ikut dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.
        </p>
        <form class="border-t border-[#edf1f5] pt-5" method="POST" action="{{ route('web.usulan-pengadaan.destroy', $proposal) }}" data-submit-loading>
            @csrf
            @method('DELETE')
            <div class="flex justify-center gap-2">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c94c45] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#a63d37]" type="submit" data-loading-button data-loading-text="Menghapus..."><span data-loading-label>Hapus Usulan</span></button>
            </div>
        </form>
    </section>
</div>
