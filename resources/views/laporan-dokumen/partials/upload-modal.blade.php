<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="{{ $modalId }}" role="presentation">
    <section class="w-[min(560px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]" id="{{ $modalId }}-title">Upload PDF</h2>
                <p class="m-0 mt-1 text-[10px] font-semibold text-[#8290a3]">{{ $periodLabel }}</p>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>
        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('laporan-dokumen.store', $jenis) }}" enctype="multipart/form-data" data-submit-loading>
            @csrf
            @if (($selectablePeriod ?? false) === true)
                <div class="grid grid-cols-2 gap-3 max-[460px]:grid-cols-1">
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Tahun</span>
                        <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-semibold text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="tahun" data-report-document-year>
                            @foreach ($yearOptions as $option)
                                <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Bulan</span>
                        <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-semibold text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="periode" data-report-document-period data-occupied-periods='@json($occupiedPeriodsByYear ?? [])'>
                            @foreach ($months as $month => $monthName)
                                @php($periodValue = 'bulan_'.$month)
                                @php($isOccupied = in_array($periodValue, $occupiedPeriods ?? [], true))
                                <option value="{{ $periodValue }}" data-period-label="{{ $monthName }}" @disabled($isOccupied)>{{ $monthName }}{{ $isOccupied ? ' — sudah memiliki dokumen' : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            @else
                <input type="hidden" name="tahun" value="{{ $year }}">
                <input type="hidden" name="periode" value="{{ $period }}">
            @endif

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Judul Dokumen <small class="font-semibold text-[#8290a3]">(opsional)</small></span>
                <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="text" name="judul" placeholder="Contoh: Laporan Rekonsiliasi Januari">
            </label>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>File PDF <small class="font-semibold text-[#8290a3]">(maks. 10 MB)</small></span>
                <input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] outline-0 transition file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[10px] file:font-bold file:text-[#146bb5] focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="file" name="file" accept="application/pdf,.pdf" required>
            </label>

            <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Mengunggah..."><span data-loading-label>Upload PDF</span></button>
            </footer>
        </form>
    </section>
</div>
