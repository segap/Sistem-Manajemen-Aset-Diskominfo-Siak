<div class="fixed inset-0 z-80 {{ $openModal === 'import' ? 'grid' : 'hidden' }} place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="import-asset-holder" role="presentation">
    <section class="w-[min(720px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]">Import Data Pegawai</h2>
                <p class="m-0 mt-1 text-[11px] text-[#7f8ea1]">Periksa file Excel terlebih dahulu sebelum data disimpan.</p>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup">
                <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </header>

        <div class="grid gap-4 p-[22px]">
            @if ($openModal === 'import' && $errors->any())
                <div class="rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="rounded-[12px] border border-[#dbeafe] bg-[#f8fbff] p-4 text-[11px] leading-relaxed text-[#53677d]">
                Format file harus <strong>.xlsx</strong>, maksimal <strong>5 MB</strong>. Kolom yang dibaca: Nama Pegawai, NIP, Email, Unit Kerja, Jabatan, Nomor HP, NIK, dan Alamat <em>(opsional)</em>.
            </div>

            <a class="page-action-button inline-flex min-h-[38px] w-max items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#146bb5] no-underline transition hover:bg-[#f8fafc]" href="{{ route('pemegang-aset.template-impor') }}">
                Download Template Excel
            </a>

            <form class="grid gap-3 rounded-[14px] border border-[#dce4ec] bg-white p-4" method="POST" action="{{ route('pemegang-aset.import.preview') }}" enctype="multipart/form-data" data-submit-loading>
                @csrf
                <input type="hidden" name="_modal" value="import">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Upload File Excel</span>
                    <input class="asset-holder-file-input h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[10px] file:font-bold file:text-[#146bb5]" type="file" name="file" accept=".xlsx" required>
                </label>
                <div class="flex justify-end gap-2">
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Memeriksa..."><span data-loading-label>Periksa Data</span></button>
                </div>
            </form>

            @if ($importPreview)
                <section class="grid gap-3 rounded-[14px] border border-[#dce4ec] bg-[#fbfcfe] p-4">
                    <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">Hasil Pemeriksaan</h3>
                    <div class="grid grid-cols-4 gap-2 max-[620px]:grid-cols-2">
                        <div class="rounded-[10px] bg-white p-3 text-center"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">Total</small><strong class="text-[18px] text-[#0b2b50]">{{ $importPreview['total'] }}</strong></div>
                        <div class="rounded-[10px] bg-white p-3 text-center"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">Valid</small><strong class="text-[18px] text-[#08714e]">{{ $importPreview['valid'] }}</strong></div>
                        <div class="rounded-[10px] bg-white p-3 text-center"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">Duplikat</small><strong class="text-[18px] text-[#92400e]">{{ $importPreview['duplicate'] }}</strong></div>
                        <div class="rounded-[10px] bg-white p-3 text-center"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">Tidak Valid</small><strong class="text-[18px] text-[#b42318]">{{ $importPreview['invalid'] }}</strong></div>
                    </div>

                    @if (($importPreview['errors'] ?? []) !== [])
                        <div class="max-h-[180px] overflow-y-auto rounded-[10px] border border-[#fecdca] bg-[#fef3f2] p-3 text-[10px] text-[#b42318]">
                            <strong class="mb-2 block">Daftar kesalahan:</strong>
                            <ul class="m-0 grid gap-1 pl-4">
                                @foreach ($importPreview['errors'] as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form class="flex justify-end gap-2 border-t border-[#edf1f5] pt-4" method="POST" action="{{ route('pemegang-aset.import.confirm') }}" data-submit-loading>
                        @csrf
                        <input type="hidden" name="token" value="{{ $importToken }}">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#16a34a] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#15803d] disabled:cursor-not-allowed disabled:opacity-50" type="submit" @disabled(($importPreview['valid'] ?? 0) < 1) data-loading-button data-loading-text="Mengimpor...">
                            <span data-loading-label>Import Data</span>
                        </button>
                    </form>
                </section>
            @endif
        </div>
    </section>
</div>
