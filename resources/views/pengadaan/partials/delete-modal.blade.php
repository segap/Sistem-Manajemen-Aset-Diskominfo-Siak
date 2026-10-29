<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-procurement-{{ $procurement->id }}" role="presentation">
    <section class="w-[min(460px,100%)] rounded-2xl bg-white p-7 text-center shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="delete-procurement-title-{{ $procurement->id }}">
        <div class="mx-auto mb-4 grid h-[54px] w-[54px] place-items-center rounded-full bg-[#fee2e2] text-[#c94c45]">
            <svg class="h-7 w-7 fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>
        </div>
        <h2 class="m-0 text-[20px] font-extrabold text-[#0b2b50]" id="delete-procurement-title-{{ $procurement->id }}">Hapus Pengadaan</h2>
        <p class="mx-auto mb-2 mt-2 max-w-[370px] text-[12px] leading-relaxed text-[#66758a]">
            Pengadaan <strong>{{ $procurement->nama_barang }}</strong> dari supplier <strong>{{ $procurement->pemasok }}</strong> akan dihapus secara permanen.
        </p>
        <p class="mx-auto mb-5 max-w-[370px] text-[11px] leading-relaxed text-[#b5473b]">
            Tindakan ini tidak dapat dibatalkan. Sistem hanya akan melanjutkan jika pengadaan belum memasuki penerimaan, verifikasi, atau memiliki aset terkait.
        </p>
        <form class="border-t border-[#edf1f5] pt-5" method="POST" action="{{ route('pengadaan.destroy', $procurement) }}" data-submit-loading>
            @csrf
            @method('DELETE')
            <div class="flex justify-center gap-2">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c94c45] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#a63d37]" type="submit" data-loading-button data-loading-text="Menghapus..."><span data-loading-label>Hapus Permanen</span></button>
            </div>
        </form>
    </section>
</div>
