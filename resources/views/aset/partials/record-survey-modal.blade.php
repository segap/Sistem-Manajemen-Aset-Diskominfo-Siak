<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="record-survey-asset-{{ $survey->id }}" role="presentation">
    <section class="w-[min(680px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="record-survey-asset-title-{{ $survey->id }}">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <h2 id="record-survey-asset-title-{{ $survey->id }}" class="m-0 text-xl font-extrabold text-[#0b2b50]">Catat Aset dari Pendataan</h2>
                <p class="mt-1 text-[11px] text-[#6c7b8e]">Catatan pendataan tetap tersimpan sebagai riwayat setelah aset dibuat.</p>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>
        <form class="p-[22px]" method="POST" action="{{ route('web.aset.inventory-surveys.record-asset', $survey) }}" data-submit-loading data-survey-record-form data-survey-name="{{ mb_strtolower(trim($survey->nama_barang)) }}" data-survey-holder-id="{{ $survey->pemegang_aset_id }}" data-survey-duplicate-keys='@json($surveyDuplicateKeys ?? [])'>
            @csrf
            <div class="rounded-[10px] border border-[#e1e9f1] bg-[#f8fbfe] px-4 py-3 text-[11px] text-[#4e6175]">
                <strong class="block text-[#173b60]">{{ $survey->nama_barang }}</strong>
                <span>{{ $survey->assetHolder?->nama_pegawai ?: $survey->nama_pegawai_excel }} - {{ $survey->assetHolder ? \App\Enums\WorkUnit::labelFor($survey->assetHolder->unit_kerja) : ($survey->unit_kerja_excel ?: 'Unit kerja belum tercatat') }}</span>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-[11px] font-bold text-[#344b65]">Nama Aset<input class="mt-1 h-[40px] w-full rounded-[8px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[11px] text-[#52657a]" value="{{ $survey->nama_barang }}" readonly></label>
                <label class="block text-[11px] font-bold text-[#344b65]">Kategori Aset
                    <select class="mt-1 h-[40px] w-full rounded-[8px] border border-[#dce4ee] bg-white px-3 text-[11px] text-[#34445a]" name="kategori_aset_id" data-survey-category required>
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('kategori_aset_id', $survey->kategori_aset_id) == $category->id)>{{ $category->nama_kategori }}</option>
                        @endforeach
                    </select>
                </label>
                @if ($survey->assetHolder)
                    <label class="block text-[11px] font-bold text-[#344b65]">Pemegang Aset<input class="mt-1 h-[40px] w-full rounded-[8px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[11px] text-[#52657a]" value="{{ $survey->assetHolder->nama_pegawai }}" readonly></label>
                @else
                    <label class="block text-[11px] font-bold text-[#344b65]">Pemegang Aset
                        <select class="mt-1 h-[40px] w-full rounded-[8px] border border-[#dce4ee] bg-white px-3 text-[11px] text-[#34445a]" name="pemegang_aset_id" data-survey-holder>
                            <option value="">Belum ditentukan</option>
                            @foreach ($assetHolderOptions as $holder)
                                <option value="{{ $holder->id }}" @selected(old('pemegang_aset_id') == $holder->id)>{{ $holder->nama_pegawai }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <label class="block text-[11px] font-bold text-[#344b65]">Tahun Perolehan<input class="mt-1 h-[40px] w-full rounded-[8px] border border-[#dce4ee] bg-white px-3 text-[11px] text-[#34445a]" name="tahun_perolehan" type="number" min="1900" max="{{ now()->year + 1 }}" value="{{ old('tahun_perolehan') }}"></label>
                <label class="block text-[11px] font-bold text-[#344b65]">Kondisi
                    <select class="mt-1 h-[40px] w-full rounded-[8px] border border-[#dce4ee] bg-white px-3 text-[11px] text-[#34445a]" name="kondisi" required>
                        @foreach ($conditions as $value => $label)
                            @if ($value !== 'tidak_ada')
                                <option value="{{ $value }}" @selected(old('kondisi', 'baik') === $value)>{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                </label>
            </div>
            <label class="mt-4 block text-[11px] font-bold text-[#344b65]">Spesifikasi / Deskripsi<textarea class="mt-1 min-h-[84px] w-full rounded-[8px] border border-[#dce4ee] bg-white px-3 py-2 text-[11px] text-[#34445a]" name="spesifikasi">{{ old('spesifikasi') }}</textarea></label>
            <label class="mt-4 block text-[11px] font-bold text-[#344b65]">Catatan Kondisi / Kerusakan<textarea class="mt-1 min-h-[84px] w-full rounded-[8px] border border-[#dce4ee] bg-white px-3 py-2 text-[11px] text-[#34445a]" name="catatan_kondisi">{{ old('catatan_kondisi', $survey->catatan_kerusakan) }}</textarea></label>
            <div class="mt-4 hidden rounded-[9px] border border-[#fed7aa] bg-[#fff7ed] px-3 py-2.5 text-[11px] text-[#9a3412]" data-survey-duplicate-warning>
                <p class="m-0 font-semibold">Pegawai ini sudah memiliki aset dengan nama dan kategori yang sama. Pastikan barang yang akan dicatat merupakan unit fisik yang berbeda.</p>
                <label class="mt-2 flex items-start gap-2 text-[#7c2d12]">
                    <input class="mt-0.5" type="checkbox" name="confirm_duplicate" value="1" data-survey-duplicate-confirm @checked(old('confirm_duplicate'))>
                    <span>Saya memastikan bahwa barang ini merupakan unit aset yang berbeda dan belum pernah tercatat dalam sistem.</span>
                </label>
            </div>
            <footer class="mt-5 flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]"><button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button><button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit" data-loading-button data-loading-text="Mencatat..."><span data-loading-label>Catat Aset</span></button></footer>
        </form>
    </section>
</div>

<script>
    (() => {
        const initialize = () => document.querySelectorAll('[data-survey-record-form]').forEach((form) => {
            if (form.dataset.duplicateReady === 'true') return
            form.dataset.duplicateReady = 'true'

            const category = form.querySelector('[data-survey-category]')
            const holder = form.querySelector('[data-survey-holder]')
            const warning = form.querySelector('[data-survey-duplicate-warning]')
            const confirmation = form.querySelector('[data-survey-duplicate-confirm]')
            const duplicateKeys = new Set(JSON.parse(form.dataset.surveyDuplicateKeys || '[]'))
            const fixedHolderId = form.dataset.surveyHolderId || ''
            const assetName = form.dataset.surveyName || ''

            const syncDuplicateWarning = () => {
                const holderId = fixedHolderId || holder?.value || ''
                const key = `${category?.value || ''}|${holderId}|${assetName}`
                const hasDuplicate = holderId !== '' && category?.value !== '' && duplicateKeys.has(key)

                warning.classList.toggle('hidden', !hasDuplicate)
                confirmation.required = hasDuplicate

                if (!hasDuplicate) confirmation.checked = false
            }

            category?.addEventListener('change', syncDuplicateWarning)
            holder?.addEventListener('change', syncDuplicateWarning)
            syncDuplicateWarning()
        })

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initialize, { once: true })
        } else {
            initialize()
        }
    })()
</script>
