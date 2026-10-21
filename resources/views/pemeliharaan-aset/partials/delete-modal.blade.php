<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-maintenance-{{ $maintenance->id }}" role="presentation">
    <section class="w-[min(460px,100%)] rounded-2xl bg-white p-[27px] text-center shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="delete-maintenance-title-{{ $maintenance->id }}">
        <span class="mx-auto mb-3.5 grid h-12 w-12 place-items-center rounded-full bg-[#fff0ee] text-[#b4443d] [&_svg]:h-[22px] [&_svg]:w-[22px] [&_svg]:fill-none [&_svg]:stroke-current [&_svg]:stroke-2"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg></span>
        <h2 class="m-0 mb-2 text-[19px] font-extrabold text-[#0b2b50]" id="delete-maintenance-title-{{ $maintenance->id }}">Hapus data pemeliharaan?</h2>
        <p class="m-0 text-[11px] leading-relaxed text-[#718095]">Data pemeliharaan <strong>{{ $maintenance->asset?->nama_aset ?: '-' }}</strong> dengan kode internal <strong>{{ $maintenance->asset?->kode_internal ?: '-' }}</strong> akan dihapus. File dokumentasi yang terkait juga akan dihapus.</p>
        <footer class="mt-[22px] flex justify-center gap-2 border-t border-[#edf1f5] pt-[18px]">
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
            <form method="POST" action="{{ route('pemeliharaan-aset.destroy', $maintenance) }}" data-submit-loading>
                @csrf
                @method('DELETE')
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c94e47] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(201_78_71_/_16%)] transition hover:bg-[#ad3c36]" type="submit" data-loading-button data-loading-text="Menghapus..."><span data-loading-label>Hapus Data</span></button>
            </form>
        </footer>
    </section>
</div>
