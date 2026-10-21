@php
    $currentConditionValue = $asset->kondisi?->value ?? 'baik';
    $conditionDropdownLabels = [
        'baik' => 'Baik (B)',
        'rusak_ringan' => 'Rusak Ringan (RR)',
        'rusak_sedang' => 'Rusak Sedang (RS)',
        'rusak_berat' => 'Rusak Berat (RB)',
        'hilang' => 'Hilang (H)',
    ];
    $currentConditionLabel = $conditionDropdownLabels[$currentConditionValue] ?? $conditions[$currentConditionValue] ?? $asset->kondisi?->label() ?? 'Baik';
    $conditionTone = fn (string $condition) => match ($condition) {
        'baik' => 'bg-[#dcfce7] text-[#08714e]',
        'rusak_ringan' => 'bg-[#fef3c7] text-[#92400e]',
        'rusak_sedang' => 'bg-[#ffedd5] text-[#c2410c]',
        'rusak_berat' => 'bg-[#fee2e2] text-[#b42318]',
        'hilang' => 'bg-[#e5e7eb] text-[#374151]',
        default => 'bg-[#eef4fb] text-[#52657a]',
    };
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="condition-asset-{{ $asset->id }}" role="presentation">
    <section class="w-[min(680px,100%)] rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="condition-asset-title-{{ $asset->id }}">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <span class="hidden">TIM ASET</span>
                <h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="condition-asset-title-{{ $asset->id }}">Perbarui Kondisi Aset</h2>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>
        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('web.aset.kondisi', $asset) }}" enctype="multipart/form-data" data-submit-loading data-condition-document-form>
            @csrf @method('PATCH')

            <div class="rounded-[13px] border border-[#dce4ec] bg-[#f8fafc] p-4">
                <strong class="mb-1.5 block text-[12px] text-[#0b2b50]">{{ $asset->display_code }} - {{ $asset->nama_aset }}</strong>
                <p class="m-0 mb-3 text-[10px] font-semibold text-[#718096]">Perubahan kondisi berlaku untuk satu unit aset ini.</p>
                <div class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Kondisi Saat Ini</span>
                    <span class="inline-flex min-h-[38px] w-max items-center rounded-[10px] px-3 text-[10px] font-extrabold {{ $conditionTone($currentConditionValue) }}">
                        {{ $currentConditionLabel }}
                    </span>
                </div>
            </div>

            <div class="grid gap-4">
                <div class="grid gap-[7px] text-[11px] font-bold text-[#405267]" data-condition-guide-field>
                    <div class="flex items-center justify-between gap-2">
                        <span>Kondisi Baru</span>
                        <button class="inline-flex items-center gap-1 border-0 bg-transparent p-0 text-[9px] font-extrabold text-[#146bb5] hover:text-[#0b4f88]" type="button" data-modal-open="condition-classification-guide">
                            <span class="grid h-4 w-4 place-items-center rounded-full bg-[#e8f3ff] text-[10px]">i</span>
                            Panduan
                        </button>
                    </div>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="ke_kondisi" required data-condition-guide-select>
                        @foreach ($conditions as $value => $label)
                            <option value="{{ $value }}" @selected($value === 'rusak_sedang')>{{ $conditionDropdownLabels[$value] ?? $label }}</option>
                        @endforeach
                    </select>
                    <small class="rounded-[9px] border border-[#dce4ec] bg-[#f8fafc] px-3 py-2 text-[9px] font-semibold leading-relaxed text-[#66758a]" data-condition-description></small>
                </div>
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Catatan</span>
                <textarea class="min-h-[82px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="catatan" rows="3" placeholder="Contoh: 2 meja rusak pada bagian kaki."></textarea>
            </label>

            <section class="grid gap-3 rounded-[13px] border border-[#dce4ec] bg-[#fbfdff] p-4">
                <div>
                    <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">Dokumentasi Kondisi Barang *</h3>
                    <p class="m-0 mt-1 text-[10px] font-medium leading-relaxed text-[#718096]">Tambahkan foto atau video sebagai bukti kondisi barang. Maksimal 10 foto. Jika dokumentasi lebih banyak, Anda dapat menambahkan link Google Drive.</p>
                </div>

                <div class="grid gap-2 text-[11px] font-bold text-[#405267]">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span>Foto Dokumentasi</span>
                        <small class="rounded-full bg-[#e8f3ff] px-2.5 py-1 text-[9px] font-extrabold text-[#146bb5]" data-condition-photo-count>Foto 0/10</small>
                    </div>
                    <input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[10px] file:font-bold file:text-[#146bb5]" type="file" name="dokumentasi_foto[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple data-condition-photo-input>
                    <div class="flex flex-wrap items-center gap-2">
                        <button class="page-action-button inline-flex min-h-[36px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-condition-photo-camera>Buka Kamera</button>
                        <button class="page-action-button hidden min-h-[36px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-condition-camera-capture>Ambil Foto</button>
                        <button class="page-action-button hidden min-h-[36px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-condition-camera-close>Tutup Kamera</button>
                    </div>
                    <div class="hidden overflow-hidden rounded-[12px] border border-[#dce4ec] bg-[#0b2b50]" data-condition-camera-panel>
                        <video class="block aspect-video w-full bg-[#0b2b50] object-cover" autoplay playsinline muted data-condition-camera-video></video>
                        <canvas class="hidden" data-condition-camera-canvas></canvas>
                    </div>
                    <div class="grid grid-cols-[repeat(auto-fill,minmax(72px,1fr))] gap-2" data-condition-photo-preview></div>
                    <small class="hidden text-[9px] font-semibold text-[#b42318]" data-condition-photo-error></small>
                    @error('dokumentasi_foto') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    @error('dokumentasi_foto.*') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </div>

                <div class="grid gap-2 text-[11px] font-bold text-[#405267]">
                    <span>Video Dokumentasi <small class="font-semibold text-[#8290a3]">(Opsional jika foto sudah tersedia)</small></span>
                    <input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[10px] file:font-bold file:text-[#146bb5]" type="file" name="dokumentasi_video" accept=".mp4,.mov,.webm,video/mp4,video/quicktime,video/webm" data-condition-video-input>
                    <div class="flex flex-wrap items-center gap-2">
                        <small class="text-[9px] font-medium text-[#8290a3]" data-condition-video-info>Belum ada video dipilih.</small>
                        <button class="page-action-button hidden min-h-[30px] items-center justify-center rounded-[8px] border border-[#f1c7c3] bg-white px-2.5 text-[9px] font-bold text-[#b42318] transition hover:bg-[#fff5f4]" type="button" data-condition-video-remove>Hapus</button>
                    </div>
                    @error('dokumentasi_video') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </div>

                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Link Google Drive <small class="font-semibold text-[#8290a3]">(Opsional)</small></span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="google_drive_url" type="url" placeholder="https://drive.google.com/...">
                    <small class="text-[9px] font-medium text-[#8290a3]">Gunakan jika dokumentasi tambahan melebihi batas upload.</small>
                    @error('google_drive_url') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
            </section>

            <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Perbarui Kondisi</span></button>
            </footer>
        </form>
    </section>
</div>
