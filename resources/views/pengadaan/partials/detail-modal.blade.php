@php
    use App\Enums\ProcurementStatus;
    use App\Enums\UserRole;
    use Illuminate\Support\Facades\Storage;

    $role = auth()->user()?->role;
    $canReceive = in_array($role, [UserRole::Admin->value, UserRole::TimAset->value, UserRole::SuperAdmin->value], true);
    $canVerify = in_array($role, [UserRole::TimAset->value, UserRole::SuperAdmin->value], true);
    $total = (float) $procurement->harga_satuan * (int) $procurement->jumlah_dipesan;
    $receiptProofUrl = $procurement->bukti_penerimaan ? Storage::disk('public')->url($procurement->bukti_penerimaan) : null;
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="detail-procurement-{{ $procurement->id }}" role="presentation">
    <section class="w-[min(930px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="detail-procurement-title-{{ $procurement->id }}">
        <header class="flex min-h-[83px] items-start justify-between gap-5 border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <p class="m-0 mb-1 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#146bb5]">Pengadaan</p>
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]" id="detail-procurement-title-{{ $procurement->id }}">Pengadaan #{{ $procurement->id }}</h2>
                <span class="loan-status {{ $statusClass($procurement->status) }} mt-2 inline-flex">{{ $procurement->status->label() }}</span>
            </div>
            <button class="inline-flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal">
                <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </header>

        <div class="grid gap-[17px] p-[22px]">
            <div class="grid overflow-hidden rounded-[12px] border border-[#dfe7f0] md:grid-cols-2">
                @include('pengadaan.partials.detail-item', ['label' => 'Usulan', 'value' => 'Usulan #'.$procurement->usulan_pengadaan_id])
                @include('pengadaan.partials.detail-item', ['label' => 'Pengusul', 'value' => $procurement->proposal?->requester?->name])
                @include('pengadaan.partials.detail-item', ['label' => 'Kategori', 'value' => $procurement->category?->nama_kategori])
                @include('pengadaan.partials.detail-item', ['label' => 'Barang', 'value' => $procurement->nama_barang])
                @include('pengadaan.partials.detail-item', ['label' => 'Merek / tipe', 'value' => $procurement->merek_tipe ?: '-'])
                @include('pengadaan.partials.detail-item', ['label' => 'Jumlah dipesan', 'value' => $procurement->jumlah_dipesan.' unit'])
                @include('pengadaan.partials.detail-item', ['label' => 'Jumlah diterima', 'value' => $procurement->jumlah_diterima ? $procurement->jumlah_diterima.' unit' : '-'])
                @include('pengadaan.partials.detail-item', ['label' => 'Harga satuan', 'value' => $rupiah($procurement->harga_satuan)])
                @include('pengadaan.partials.detail-item', ['label' => 'Total harga', 'value' => $rupiah($total)])
                @include('pengadaan.partials.detail-item', ['label' => 'Supplier', 'value' => $procurement->pemasok])
                @include('pengadaan.partials.detail-item', ['label' => 'Dibuat oleh', 'value' => $procurement->creator?->name])
                @include('pengadaan.partials.detail-item', ['label' => 'Tanggal pesan', 'value' => $formatDate($procurement->tanggal_pesan)])
                @include('pengadaan.partials.detail-item', ['label' => 'Perkiraan tiba', 'value' => $formatDate($procurement->perkiraan_tanggal_tiba)])
            </div>

            @include('pengadaan.partials.detail-note', ['title' => 'Spesifikasi', 'body' => $procurement->spesifikasi])
            @include('pengadaan.partials.detail-note', ['title' => 'Alasan pengadaan', 'body' => $procurement->alasan_pengadaan])

            <div class="grid overflow-hidden rounded-[12px] border border-[#dfe7f0] md:grid-cols-2">
                @include('pengadaan.partials.detail-item', ['label' => 'Tanggal penerimaan', 'value' => $formatDateTime($procurement->tanggal_penerimaan)])
                @include('pengadaan.partials.detail-item', ['label' => 'Diterima oleh', 'value' => $procurement->receiver?->name])
                @include('pengadaan.partials.detail-item', ['label' => 'Kondisi hasil verifikasi', 'value' => $procurement->kondisi?->label()])
                @include('pengadaan.partials.detail-item', ['label' => 'Lokasi penyimpanan', 'value' => $procurement->lokasi_penyimpanan])
                @include('pengadaan.partials.detail-item', ['label' => 'Diverifikasi oleh', 'value' => $procurement->verifier?->name])
                @include('pengadaan.partials.detail-item', ['label' => 'Tanggal verifikasi', 'value' => $formatDateTime($procurement->tanggal_verifikasi)])
                @include('pengadaan.partials.detail-item', ['label' => 'Aset terkait', 'value' => $procurement->asset ? $procurement->asset->display_code.' - '.$procurement->asset->nama_aset : '-'])
            </div>

            @include('pengadaan.partials.detail-note', ['title' => 'Catatan penerimaan', 'body' => $procurement->catatan_penerimaan])
            @if ($receiptProofUrl)
                <section class="grid gap-3 rounded-[12px] border border-[#dfe7f0] bg-white p-4">
                    <div>
                        <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">Bukti Barang Diterima</h3>
                        <a class="mt-1 inline-flex text-[10px] font-bold text-[#146bb5] no-underline hover:underline" href="{{ $receiptProofUrl }}" target="_blank" rel="noopener">Lihat foto bukti penerimaan</a>
                    </div>
                    <img class="max-h-[260px] w-full rounded-[12px] border border-[#dce4ec] bg-[#f8fafc] object-contain" src="{{ $receiptProofUrl }}" alt="Bukti barang diterima">
                </section>
            @endif
            @include('pengadaan.partials.detail-note', ['title' => 'Catatan verifikasi', 'body' => $procurement->catatan_verifikasi])
            @if ($procurement->alasan_penolakan)
                @include('pengadaan.partials.detail-note', ['title' => 'Alasan penolakan', 'body' => $procurement->alasan_penolakan, 'danger' => true])
            @endif

            @if ($canReceive && $procurement->status === ProcurementStatus::Ordered)
                <form class="grid gap-3 rounded-[12px] border border-[#dfe7f0] bg-white p-4" method="POST" action="{{ route('pengadaan.penerimaan', $procurement) }}" enctype="multipart/form-data" data-submit-loading>
                    @csrf
                    @method('PATCH')
                    <div class="grid grid-cols-2 gap-4 max-[580px]:grid-cols-1">
                        @include('pengadaan.partials.field', ['label' => 'Jumlah diterima', 'name' => 'jumlah_diterima', 'type' => 'number', 'value' => old('jumlah_diterima', $procurement->jumlah_dipesan), 'required' => true, 'attrs' => 'min=1 max='.$procurement->jumlah_dipesan])
                        @include('pengadaan.partials.field', ['label' => 'Waktu diterima', 'name' => 'tanggal_penerimaan', 'type' => 'datetime-local', 'value' => old('tanggal_penerimaan', now()->format('Y-m-d\TH:i')), 'required' => true])
                    </div>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Catatan penerimaan</span>
                        <textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="catatan_penerimaan" placeholder="Catatan penerimaan (opsional)">{{ old('catatan_penerimaan') }}</textarea>
                    </label>
                    <div class="grid gap-[10px] text-[11px] font-bold text-[#405267]" data-procurement-camera-wrapper>
                        <span>Foto bukti barang diterima (JPG/PNG/WEBP, maks. 2 MB)</span>
                        <input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[11px] file:font-bold file:text-[#146bb5]" type="file" name="bukti_penerimaan" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required data-procurement-camera-input data-max-file-size="2097152" data-max-file-message="gambar/file lebih dari 2mb">
                        <div class="flex flex-wrap items-center gap-2">
                            <button class="page-action-button inline-flex min-h-[36px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-procurement-camera-open>Buka Kamera</button>
                            <button class="page-action-button hidden min-h-[36px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-procurement-camera-capture>Ambil Foto</button>
                            <button class="page-action-button hidden min-h-[36px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-procurement-camera-close>Tutup Kamera</button>
                            <small class="text-[9px] font-medium text-[#8290a3]" data-procurement-camera-status>Upload file atau ambil foto langsung dari kamera.</small>
                        </div>
                        <div class="hidden overflow-hidden rounded-[12px] border border-[#dce4ec] bg-[#0b2b50]" data-procurement-camera-panel>
                            <video class="block aspect-video w-full bg-[#0b2b50] object-cover" autoplay playsinline muted data-procurement-camera-video></video>
                            <canvas class="hidden" data-procurement-camera-canvas></canvas>
                        </div>
                        <img class="hidden max-h-[220px] w-full rounded-[12px] border border-[#dce4ec] bg-[#f8fafc] object-contain" alt="Preview foto bukti barang diterima" data-procurement-camera-preview>
                        @error('bukti_penerimaan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </div>
                    <div class="flex justify-end">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Memproses..."><span data-loading-label>Konfirmasi Penerimaan</span></button>
                    </div>
                </form>
            @endif

            @if ($canVerify && $procurement->status === ProcurementStatus::Received)
                <form class="grid gap-3 rounded-[12px] border border-[#dfe7f0] bg-white p-4" method="POST" action="{{ route('pengadaan.verifikasi', $procurement) }}" data-submit-loading>
                    @csrf
                    @method('PATCH')
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Kondisi</span>
                        <select class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="kondisi">
                            @foreach ($conditions as $condition)
                                <option value="{{ $condition->value }}" @selected(old('kondisi', 'baik') === $condition->value)>{{ $condition->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    @include('pengadaan.partials.field', ['label' => 'Lokasi penyimpanan', 'name' => 'lokasi_penyimpanan', 'value' => old('lokasi_penyimpanan')])
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Catatan verifikasi</span>
                        <textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="catatan_verifikasi" placeholder="Catatan verifikasi (opsional)">{{ old('catatan_verifikasi') }}</textarea>
                    </label>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Alasan penolakan</span>
                        <textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="alasan_penolakan" placeholder="Wajib diisi jika barang ditolak">{{ old('alasan_penolakan') }}</textarea>
                    </label>
                    <div class="flex justify-end gap-2">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c94c45] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#a63d37]" type="submit" name="decision" value="{{ ProcurementStatus::Rejected->value }}">Tolak</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#0c568f]" type="submit" name="decision" value="{{ ProcurementStatus::Verified->value }}">Verifikasi</button>
                    </div>
                </form>
            @endif

            <footer class="mt-[5px] flex justify-end border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Tutup</button>
            </footer>
        </div>
    </section>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-procurement-camera-wrapper]').forEach((wrapper) => {
                    const fileInput = wrapper.querySelector('[data-procurement-camera-input]')
                    const openButton = wrapper.querySelector('[data-procurement-camera-open]')
                    const captureButton = wrapper.querySelector('[data-procurement-camera-capture]')
                    const closeButton = wrapper.querySelector('[data-procurement-camera-close]')
                    const panel = wrapper.querySelector('[data-procurement-camera-panel]')
                    const video = wrapper.querySelector('[data-procurement-camera-video]')
                    const canvas = wrapper.querySelector('[data-procurement-camera-canvas]')
                    const preview = wrapper.querySelector('[data-procurement-camera-preview]')
                    const status = wrapper.querySelector('[data-procurement-camera-status]')
                    const maxSize = Number(fileInput?.dataset.maxFileSize || 2097152)
                    let cameraStream = null

                    const setStatus = (message, danger = false) => {
                        if (!status) return
                        status.textContent = message
                        status.classList.toggle('text-[#b42318]', danger)
                        status.classList.toggle('text-[#8290a3]', !danger)
                    }

                    const stopCamera = () => {
                        cameraStream?.getTracks().forEach((track) => track.stop())
                        cameraStream = null
                        panel?.classList.add('hidden')
                        captureButton?.classList.add('hidden')
                        captureButton?.classList.remove('inline-flex')
                        closeButton?.classList.add('hidden')
                        closeButton?.classList.remove('inline-flex')
                    }

                    openButton?.addEventListener('click', async () => {
                        if (!navigator.mediaDevices?.getUserMedia) {
                            setStatus('Browser ini belum mendukung akses kamera.', true)
                            return
                        }

                        try {
                            stopCamera()
                            cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
                            if (video) video.srcObject = cameraStream
                            panel?.classList.remove('hidden')
                            captureButton?.classList.remove('hidden')
                            captureButton?.classList.add('inline-flex')
                            closeButton?.classList.remove('hidden')
                            closeButton?.classList.add('inline-flex')
                            setStatus('Kamera aktif. Arahkan ke barang lalu klik Ambil Foto.')
                        } catch (error) {
                            setStatus('Kamera tidak bisa dibuka. Pastikan izin kamera diizinkan browser.', true)
                        }
                    })

                    captureButton?.addEventListener('click', () => {
                        if (!video || !canvas || !fileInput) return
                        const width = video.videoWidth || 1280
                        const height = video.videoHeight || 720
                        canvas.width = width
                        canvas.height = height
                        canvas.getContext('2d')?.drawImage(video, 0, 0, width, height)

                        canvas.toBlob((blob) => {
                            if (!blob) return
                            if (blob.size > maxSize) {
                                setStatus('Foto dari kamera melebihi 2 MB. Coba ambil ulang.', true)
                                return
                            }

                            const file = new File([blob], `bukti-penerimaan-${Date.now()}.jpg`, { type: 'image/jpeg' })
                            const dataTransfer = new DataTransfer()
                            dataTransfer.items.add(file)
                            fileInput.files = dataTransfer.files

                            if (preview) {
                                preview.src = URL.createObjectURL(file)
                                preview.classList.remove('hidden')
                            }

                            setStatus('Foto dari kamera berhasil dipilih.')
                            stopCamera()
                        }, 'image/jpeg', 0.85)
                    })

                    closeButton?.addEventListener('click', stopCamera)

                    fileInput?.addEventListener('change', () => {
                        const file = fileInput.files?.[0]
                        if (!file) return

                        if (file.size > maxSize) {
                            fileInput.value = ''
                            if (preview) {
                                preview.removeAttribute('src')
                                preview.classList.add('hidden')
                            }
                            setStatus(fileInput.dataset.maxFileMessage || 'gambar/file lebih dari 2mb', true)
                            return
                        }

                        if (preview && file.type.startsWith('image/')) {
                            preview.src = URL.createObjectURL(file)
                            preview.classList.remove('hidden')
                        }

                        setStatus('Foto bukti berhasil dipilih.')
                    })
                })
            })
        </script>
    @endpush
@endonce
