@php
    use Illuminate\Support\Facades\Storage;

    $isEdit = $mode === 'edit';
    $modalId = $isEdit ? 'edit-asset-'.$asset->id : 'create-asset';
    $action = $isEdit ? route('web.aset.update', $asset) : route('web.aset.store');
    $title = $isEdit ? 'Edit Aset' : 'Tambah Aset';
    $assetPhotos = $asset?->photos ?? collect();
    $conditionDropdownLabels = [
        'baik' => 'Baik (B)',
        'rusak_ringan' => 'Rusak Ringan (RR)',
        'rusak_sedang' => 'Rusak Sedang (RS)',
        'rusak_berat' => 'Rusak Berat (RB)',
        'hilang' => 'Hilang (H)',
    ];
    $selectedAssetHolderId = old('pemegang_aset_id', $asset?->pemegang_aset_id);
    $selectedAssetHolder = $asset?->assetHolder;
    $assetHolderOptionsForForm = collect($assetHolderOptions ?? collect());
    if ($isEdit && $selectedAssetHolder && ! $selectedAssetHolder->is_active) {
        $assetHolderOptionsForForm = $assetHolderOptionsForForm->prepend($selectedAssetHolder)->unique('id');
    }
    $assetHolderLabel = fn ($holder) => $holder->nama_pegawai.($holder->nip ? ' - '.$holder->nip : '').($holder->is_active ? '' : ' (Nonaktif)');
    if ($isEdit && $assetPhotos->isEmpty() && $asset?->path_foto) {
        $assetPhotos = collect([(object) [
            'id' => null,
            'path' => $asset->path_foto,
            'is_primary' => true,
            'nama_file_asli' => 'Foto utama aset',
        ]]);
    }
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="{{ $modalId }}" role="presentation">
    <section class="w-[min(900px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <span class="hidden">{{ $isEdit ? 'PERBARUI DATA' : 'DATA BARU' }}</span>
                <h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="{{ $modalId }}-title">{{ $title }}</h2>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal">
                <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </header>
        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ $action }}" enctype="multipart/form-data" data-submit-loading data-asset-form>
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Kategori</span>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="kategori_aset_id" required>
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('kategori_aset_id', $asset?->kategori_aset_id) == $category->id)>{{ $category->nama_kategori }}</option>
                        @endforeach
                    </select>
                    @error('kategori_aset_id') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Kode barang master</span>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="kode_barang_id" data-kode-barang-master>
                        <option value="">Tidak memilih master kode</option>
                        @foreach (($kodeBarangOptions ?? collect()) as $kodeBarang)
                            <option value="{{ $kodeBarang->id }}" @selected(old('kode_barang_id', $asset?->kode_barang_id) == $kodeBarang->id)>{{ $kodeBarang->kode_barang }} - {{ $kodeBarang->nama_barang }}</option>
                        @endforeach
                    </select>
                    @error('kode_barang_id') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                <div class="grid gap-[7px] rounded-[11px] border border-[#dce4ec] bg-[#f8fafc] px-3 py-3 text-[11px] font-bold text-[#405267]">
                    <span>Kode Internal</span>
                    <strong class="text-[12px] text-[#146bb5]">{{ $isEdit ? ($asset?->kode_internal ?: 'Belum dibuat') : 'Dibuat otomatis oleh sistem' }}</strong>
                    <small class="text-[9px] font-medium leading-relaxed text-[#8290a3]">Contoh: PRN/001, LPT/001, RTR/001. Pengelolaan kode dilakukan dari menu Kodefikasi Internal.</small>
                </div>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Nama aset</span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="nama_aset" value="{{ old('nama_aset', $asset?->nama_aset) }}" placeholder="Contoh: Laptop Operasional" required>
                    @error('nama_aset') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Merek / tipe</span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="merek_tipe" value="{{ old('merek_tipe', $asset?->merek_tipe) }}" placeholder="Contoh: Lenovo ThinkPad">
                    @error('merek_tipe') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                @if ($isEdit)
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Jumlah</span>
                        <input type="hidden" name="jumlah" value="{{ old('jumlah', $asset?->jumlah ?? 1) }}">
                        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-[#f8fafc] px-3 text-[10px] font-bold text-[#34465a] outline-0" type="number" min="1" value="{{ old('jumlah', $asset?->jumlah ?? 1) }}" readonly>
                        <small class="text-[9px] font-medium leading-relaxed text-[#8290a3]">
                            Jumlah tidak dapat diubah melalui Edit Aset. Satu record aset mewakili satu unit fisik.
                        </small>
                        @if ((int) ($asset?->jumlah ?? 1) !== 1)
                            <small class="rounded-[8px] border border-[#fecdca] bg-[#fef3f2] px-2.5 py-2 text-[9px] font-semibold leading-relaxed text-[#b42318]">
                                Aset legacy dengan jumlah lebih dari 1 harus melalui proses perbaikan data khusus.
                            </small>
                        @endif
                        @error('jumlah') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </label>
                @else
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Jumlah unit yang dibuat</span>
                        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="jumlah" type="number" min="1" value="{{ old('jumlah', 1) }}" required>
                        <small class="text-[9px] font-medium leading-relaxed text-[#8290a3]">Sistem akan membuat satu record dan satu kode internal unik untuk setiap unit.</small>
                        @error('jumlah') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </label>
                @endif
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Tahun perolehan</span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="tahun_perolehan" type="number" min="1900" max="{{ date('Y') + 1 }}" value="{{ old('tahun_perolehan', $asset?->tahun_perolehan) }}" placeholder="{{ date('Y') }}">
                    @error('tahun_perolehan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                <div class="grid gap-[7px] text-[11px] font-bold text-[#405267]" data-condition-guide-field>
                    <div class="flex items-center justify-between gap-2">
                        <span>Kondisi</span>
                        <button class="inline-flex items-center gap-1 border-0 bg-transparent p-0 text-[9px] font-extrabold text-[#146bb5] hover:text-[#0b4f88]" type="button" data-modal-open="condition-classification-guide">
                            <span class="grid h-4 w-4 place-items-center rounded-full bg-[#e8f3ff] text-[10px]">i</span>
                            Lihat Panduan Klasifikasi
                        </button>
                    </div>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="kondisi" required data-condition-guide-select>
                        @foreach ($conditions as $value => $label)
                            <option value="{{ $value }}" @selected(old('kondisi', $asset?->kondisi?->value ?? 'baik') === $value)>{{ $conditionDropdownLabels[$value] ?? $label }}</option>
                        @endforeach
                    </select>
                    <small class="rounded-[9px] border border-[#dce4ec] bg-[#f8fafc] px-3 py-2 text-[9px] font-semibold leading-relaxed text-[#66758a]" data-condition-description></small>
                    @error('kondisi') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </div>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Lokasi penyimpanan</span>
                    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="lokasi_penyimpanan">
                        <option value="">Pilih ruangan</option>
                        @foreach (($ruanganOptions ?? collect()) as $ruangan)
                            <option value="{{ $ruangan->nama_ruangan }}" @selected(old('lokasi_penyimpanan', $asset?->lokasi_penyimpanan) === $ruangan->nama_ruangan)>
                                {{ $ruangan->kode_ruangan }} - {{ $ruangan->nama_ruangan }}
                            </option>
                        @endforeach
                    </select>
                    @error('lokasi_penyimpanan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Pemegang Aset</span>
                <div class="relative" data-asset-holder-combobox>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white py-0 pl-3 pr-10 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="search" value="{{ $selectedAssetHolder ? $assetHolderLabel($selectedAssetHolder) : '' }}" placeholder="Pilih atau cari pemegang aset..." autocomplete="off" data-asset-holder-search>
                    <button class="absolute right-1.5 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-[8px] border-0 bg-transparent text-[#53677d] transition hover:bg-[#f1f5f9]" type="button" aria-label="Tampilkan daftar pemegang aset" data-asset-holder-toggle><svg class="h-4 w-4 fill-none stroke-current stroke-[2.3] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg></button>
                    <div class="absolute left-0 right-0 top-[46px] z-[90] hidden max-h-[210px] overflow-y-auto rounded-[10px] border border-[#dce4ec] bg-white p-1 shadow-[0_14px_35px_rgb(15_38_64_/_14%)]" data-asset-holder-list>
                        <button class="block w-full rounded-[8px] px-3 py-2 text-left text-[10px] font-semibold text-[#7b8b9d] transition hover:bg-[#f4f7fa]" type="button" data-asset-holder-clear>Tanpa pemegang aset</button>
                        @foreach ($assetHolderOptionsForForm as $assetHolder)
                            <button class="block w-full rounded-[8px] px-3 py-2 text-left text-[10px] font-semibold text-[#34465a] transition hover:bg-[#e8f3ff] hover:text-[#146bb5]" type="button" data-asset-holder-option data-asset-holder-id="{{ $assetHolder->id }}" data-asset-holder-label="{{ $assetHolderLabel($assetHolder) }}">{{ $assetHolderLabel($assetHolder) }}</button>
                        @endforeach
                        <div class="hidden px-3 py-2 text-[10px] font-semibold text-[#8a98aa]" data-asset-holder-empty>Pemegang aset tidak ditemukan.</div>
                    </div>
                </div>
                <input type="hidden" name="pemegang_aset_id" value="{{ $selectedAssetHolderId }}" data-asset-holder-hidden>
                @if ($assetHolderOptionsForForm->isEmpty())
                    <small class="text-[9px] font-medium leading-relaxed text-[#8290a3]">Belum ada data pemegang aset. Tambahkan melalui menu Manajemen User → Pemegang Aset.</small>
                @endif
                @error('pemegang_aset_id') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Spesifikasi</span>
                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="spesifikasi" rows="3" placeholder="Rincian spesifikasi aset...">{{ old('spesifikasi', $asset?->spesifikasi) }}</textarea>
                @error('spesifikasi') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>

            <div class="grid gap-[10px] rounded-[13px] border border-[#dce4ec] bg-[#fbfdff] p-4 text-[11px] font-bold text-[#405267]" data-asset-camera-wrapper data-existing-photo-count="{{ $assetPhotos->whereNotNull('id')->count() }}">
                <div>
                    <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">Dokumentasi Foto Aset</h3>
                    <p class="m-0 mt-1 text-[10px] font-medium leading-relaxed text-[#718096]">Upload foto JPG/PNG/WEBP, maksimal 10 foto dan 2 MB per foto. Foto pertama menjadi Foto Utama.</p>
                </div>

                @if ($isEdit && $assetPhotos->isNotEmpty())
                    <div class="grid gap-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span>Foto Existing</span>
                            <small class="text-[9px] font-semibold text-[#8290a3]">Centang foto yang ingin dihapus.</small>
                        </div>
                        <div class="grid grid-cols-[repeat(auto-fill,minmax(82px,1fr))] gap-2">
                            @foreach ($assetPhotos as $photo)
                                <div class="relative overflow-hidden rounded-[10px] border border-[#dce4ec] bg-white">
                                    <a href="{{ Storage::disk('public')->url($photo->path) }}" target="_blank" rel="noopener">
                                        <img class="h-[82px] w-full object-cover" src="{{ Storage::disk('public')->url($photo->path) }}" alt="{{ $photo->nama_file_asli ?: 'Foto aset' }}">
                                    </a>
                                    @if ($photo->is_primary)
                                        <span class="absolute left-1 top-1 rounded-full bg-[#146bb5] px-2 py-0.5 text-[8px] font-extrabold text-white">Foto Utama</span>
                                    @endif
                                    @if ($photo->id)
                                        <label class="flex items-center gap-1 px-2 py-1.5 text-[9px] font-bold text-[#b42318]">
                                            <input class="h-3 w-3" type="checkbox" name="hapus_foto[]" value="{{ $photo->id }}" data-asset-existing-photo-delete>
                                            Hapus
                                        </label>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>Upload Foto Baru</span>
                    <small class="rounded-full bg-[#e8f3ff] px-2.5 py-1 text-[9px] font-extrabold text-[#146bb5]" data-asset-photo-count>Foto dipilih: 0/10</small>
                </div>
                <input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[11px] file:font-bold file:text-[#146bb5]" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple data-asset-camera-input>
                <div class="flex flex-wrap items-center gap-2">
                    <button class="page-action-button inline-flex min-h-[36px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-asset-camera-open>Buka Kamera</button>
                    <button class="page-action-button hidden min-h-[36px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-asset-camera-capture>Ambil Foto</button>
                    <button class="page-action-button hidden min-h-[36px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-asset-camera-close>Tutup Kamera</button>
                    <small class="text-[9px] font-medium text-[#8290a3]" data-asset-camera-status>Upload file atau ambil foto langsung dari kamera.</small>
                </div>
                <div class="hidden overflow-hidden rounded-[12px] border border-[#dce4ec] bg-[#0b2b50]" data-asset-camera-panel>
                    <video class="block aspect-video w-full bg-[#0b2b50] object-cover" autoplay playsinline muted data-asset-camera-video></video>
                    <canvas class="hidden" data-asset-camera-canvas></canvas>
                </div>
                <div class="grid grid-cols-[repeat(auto-fill,minmax(82px,1fr))] gap-2" data-asset-photo-preview></div>
                @error('foto') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                @error('photos') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                @error('photos.*') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                @error('hapus_foto') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror

                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Link Google Drive <small class="font-semibold text-[#8290a3]">(Opsional)</small></span>
                    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="google_drive_url" type="url" value="{{ old('google_drive_url', $asset?->google_drive_url) }}" placeholder="https://drive.google.com/...">
                    <small class="text-[9px] font-medium text-[#8290a3]">Tambahkan link Google Drive jika terdapat dokumentasi foto tambahan.</small>
                    @error('google_drive_url') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
            </div>

            <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Aset' }}</span></button>
            </footer>
        </form>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.assetCameraInitialized) return
    window.assetCameraInitialized = true

    document.querySelectorAll('[data-asset-form]').forEach((form) => {
        const wrapper = form.querySelector('[data-asset-camera-wrapper]')
        if (!wrapper) return

        const fileInput = wrapper.querySelector('[data-asset-camera-input]')
        const openButton = wrapper.querySelector('[data-asset-camera-open]')
        const captureButton = wrapper.querySelector('[data-asset-camera-capture]')
        const closeButton = wrapper.querySelector('[data-asset-camera-close]')
        const panel = wrapper.querySelector('[data-asset-camera-panel]')
        const video = wrapper.querySelector('[data-asset-camera-video]')
        const canvas = wrapper.querySelector('[data-asset-camera-canvas]')
        const preview = wrapper.querySelector('[data-asset-photo-preview]')
        const counter = wrapper.querySelector('[data-asset-photo-count]')
        const status = wrapper.querySelector('[data-asset-camera-status]')
        const deleteInputs = Array.from(wrapper.querySelectorAll('[data-asset-existing-photo-delete]'))
        const initialExistingCount = Number(wrapper.dataset.existingPhotoCount || 0)
        const maxPhotos = 10
        const maxSize = 2 * 1024 * 1024
        let selectedFiles = []
        let stream = null

        const setStatus = (message, isError = false) => {
            status.textContent = message
            status.classList.toggle('text-[#b8433b]', isError)
            status.classList.toggle('text-[#8290a3]', !isError)
        }

        const existingCount = () => initialExistingCount - deleteInputs.filter((input) => input.checked).length

        const syncCounter = () => {
            if (counter) counter.textContent = `Foto dipilih: ${existingCount() + selectedFiles.length}/10`
        }

        const syncFileInput = () => {
            const dataTransfer = new DataTransfer()
            selectedFiles.forEach((file) => dataTransfer.items.add(file))
            fileInput.files = dataTransfer.files
            syncCounter()
            renderPreview()
        }

        const addFiles = (files) => {
            const validFiles = []

            for (const file of files) {
                if (!file.type.startsWith('image/')) {
                    setStatus('File foto harus berupa gambar.', true)
                    continue
                }

                if (file.size > maxSize) {
                    setStatus('Ukuran setiap foto maksimal 2 MB.', true)
                    continue
                }

                validFiles.push(file)
            }

            if (existingCount() + selectedFiles.length + validFiles.length > maxPhotos) {
                setStatus('Maksimal 10 foto untuk setiap aset.', true)
                return
            }

            selectedFiles = [...selectedFiles, ...validFiles]
            syncFileInput()

            if (validFiles.length > 0) {
                setStatus('Foto berhasil ditambahkan.')
            }
        }

        const renderPreview = () => {
            if (!preview) return
            preview.innerHTML = ''

            selectedFiles.forEach((file, index) => {
                const item = document.createElement('div')
                item.className = 'relative overflow-hidden rounded-[10px] border border-[#dce4ec] bg-white'

                const img = document.createElement('img')
                img.className = 'h-[82px] w-full object-cover'
                img.alt = `Foto aset baru ${index + 1}`
                img.src = URL.createObjectURL(file)
                img.addEventListener('load', () => URL.revokeObjectURL(img.src), { once: true })

                const remove = document.createElement('button')
                remove.type = 'button'
                remove.className = 'absolute right-1 top-1 grid h-5 w-5 place-items-center rounded-full border-0 bg-[rgb(11_43_80_/_82%)] text-[13px] font-bold leading-none text-white'
                remove.textContent = '×'
                remove.setAttribute('aria-label', `Hapus foto baru ${index + 1}`)
                remove.addEventListener('click', () => {
                    selectedFiles = selectedFiles.filter((_, fileIndex) => fileIndex !== index)
                    syncFileInput()
                    setStatus('Foto dihapus dari daftar upload.')
                })

                if (existingCount() === 0 && index === 0) {
                    const badge = document.createElement('span')
                    badge.className = 'absolute left-1 top-1 rounded-full bg-[#146bb5] px-2 py-0.5 text-[8px] font-extrabold text-white'
                    badge.textContent = 'Foto Utama'
                    item.append(badge)
                }

                item.append(img, remove)
                preview.append(item)
            })
        }

        const stopCamera = () => {
            stream?.getTracks().forEach((track) => track.stop())
            stream = null
            panel.classList.add('hidden')
            captureButton.classList.add('hidden')
            captureButton.classList.remove('inline-flex')
            closeButton.classList.add('hidden')
            closeButton.classList.remove('inline-flex')
            video.srcObject = null
        }

        openButton.addEventListener('click', async () => {
            if (!navigator.mediaDevices?.getUserMedia) {
                setStatus('Browser ini belum mendukung akses kamera.', true)
                return
            }

            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
                video.srcObject = stream
                panel.classList.remove('hidden')
                captureButton.classList.remove('hidden')
                captureButton.classList.add('inline-flex')
                closeButton.classList.remove('hidden')
                closeButton.classList.add('inline-flex')
                setStatus('Kamera aktif. Arahkan ke aset lalu klik Ambil Foto.')
            } catch (error) {
                setStatus('Kamera tidak bisa dibuka. Pastikan izin kamera diizinkan browser.', true)
            }
        })

        captureButton.addEventListener('click', () => {
            canvas.width = video.videoWidth || 1280
            canvas.height = video.videoHeight || 720
            canvas.getContext('2d')?.drawImage(video, 0, 0, canvas.width, canvas.height)

            canvas.toBlob((blob) => {
                if (!blob) {
                    setStatus('Foto gagal diambil. Coba ulangi lagi.', true)
                    return
                }

                if (blob.size > maxSize) {
                    setStatus('Foto dari kamera melebihi 2 MB. Coba ambil ulang.', true)
                    return
                }

                const file = new File([blob], `foto-aset-${Date.now()}.jpg`, { type: 'image/jpeg' })
                addFiles([file])
                stopCamera()
            }, 'image/jpeg', 0.82)
        })

        closeButton.addEventListener('click', () => {
            stopCamera()
            setStatus('Kamera ditutup. Kamu tetap bisa upload file gambar.')
        })

        fileInput.addEventListener('change', () => {
            addFiles(Array.from(fileInput.files || []))
        })

        deleteInputs.forEach((input) => input.addEventListener('change', syncCounter))
        syncCounter()
    })
})
</script>
@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    const closeLists = (except = null) => document.querySelectorAll('[data-asset-holder-list]').forEach((list) => {
        if (list !== except) list.classList.add('hidden')
    })

    document.querySelectorAll('[data-asset-holder-combobox]').forEach((combobox) => {
        const input = combobox.querySelector('[data-asset-holder-search]')
        const list = combobox.querySelector('[data-asset-holder-list]')
        const toggle = combobox.querySelector('[data-asset-holder-toggle]')
        const hiddenInput = combobox.parentElement.querySelector('[data-asset-holder-hidden]')
        const options = Array.from(combobox.querySelectorAll('[data-asset-holder-option]'))
        const empty = combobox.querySelector('[data-asset-holder-empty]')
        const clear = combobox.querySelector('[data-asset-holder-clear]')

        const filter = () => {
            const keyword = input.value.trim().toLowerCase()
            let visible = 0
            options.forEach((option) => {
                const matches = option.dataset.assetHolderLabel.toLowerCase().includes(keyword)
                option.classList.toggle('hidden', !matches)
                if (matches) visible += 1
            })
            clear.classList.toggle('hidden', keyword !== '')
            empty.classList.toggle('hidden', visible > 0)
        }
        const open = () => { filter(); closeLists(list); list.classList.remove('hidden') }
        const select = (option) => { input.value = option.dataset.assetHolderLabel; hiddenInput.value = option.dataset.assetHolderId; list.classList.add('hidden') }

        options.forEach((option) => option.addEventListener('mousedown', (event) => { event.preventDefault(); select(option) }))
        clear.addEventListener('mousedown', (event) => { event.preventDefault(); input.value = ''; hiddenInput.value = ''; list.classList.add('hidden') })
        input.addEventListener('focus', open)
        input.addEventListener('input', () => {
            const selected = options.find((option) => option.dataset.assetHolderLabel === input.value.trim())
            hiddenInput.value = selected ? selected.dataset.assetHolderId : ''
            open()
        })
        input.addEventListener('blur', () => window.setTimeout(() => { if (!combobox.matches(':focus-within')) list.classList.add('hidden') }, 120))
        toggle.addEventListener('mousedown', (event) => event.preventDefault())
        toggle.addEventListener('click', () => list.classList.contains('hidden') ? input.focus() : list.classList.add('hidden'))
    })
})
</script>
@endonce
