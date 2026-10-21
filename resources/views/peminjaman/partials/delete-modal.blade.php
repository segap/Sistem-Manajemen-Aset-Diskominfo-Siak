<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-loan-{{ $loan->id }}" role="presentation">
    <section class="w-[min(430px,100%)] rounded-2xl bg-white p-[27px] text-center shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="alertdialog" aria-modal="true" aria-labelledby="delete-loan-title-{{ $loan->id }}">
        <div class="mx-auto mb-[14px] grid h-12 w-12 place-items-center rounded-2xl bg-[#fff0ee] text-[#c94c45]">
            <svg class="h-[23px] w-[23px] fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg>
        </div>
        <h2 class="m-0 mb-2 text-[19px] font-extrabold text-[#0b2b50]" id="delete-loan-title-{{ $loan->id }}">Hapus pengajuan peminjaman?</h2>
        <p class="m-0 mb-[18px] text-[11px] leading-relaxed text-[#6c7a8d]">
            Data peminjaman <strong>{{ $loan->itemSummary() }}</strong> oleh <strong>{{ $loan->requester->name }}</strong>
            periode {{ $formatDate($loan->tanggal_pinjam) }} s.d. {{ $formatDate($loan->tanggal_kembali) }} akan dihapus.
        </p>
        <form class="flex justify-center gap-2" method="POST" action="{{ route('web.peminjaman.destroy', $loan) }}" data-submit-loading>
            @csrf
            @method('DELETE')
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c94c45] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(201_76_69_/_16%)] transition hover:bg-[#a93d37]" type="submit" data-loading-button data-loading-text="Menghapus..."><span data-loading-label>Hapus</span></button>
        </form>
    </section>
</div>
