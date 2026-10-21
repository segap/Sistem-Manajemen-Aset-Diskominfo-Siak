<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="create-proposal" role="presentation">
    <section class="w-[min(900px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="create-proposal-title">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="create-proposal-title">Buat Usulan Pengadaan</h2></div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>
        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('web.usulan-pengadaan.store') }}" enctype="multipart/form-data" data-submit-loading data-proposal-form>
            @csrf
            @php
                $oldPenggunaBarang = $users->firstWhere('id', (int) old('pengguna_barang_id'));
                $oldPenanggungJawab = $users->firstWhere('id', (int) old('penanggung_jawab_id'));
                $userOptionLabel = fn ($optionUser) => trim($optionUser->name.($optionUser->nip ? ' - '.$optionUser->nip : ''));
            @endphp
            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                @include('usulan-pengadaan.partials.field', ['label' => 'Nama barang', 'name' => 'nama_barang', 'value' => old('nama_barang'), 'required' => true])
                @include('usulan-pengadaan.partials.field', ['label' => 'Jumlah', 'name' => 'jumlah', 'type' => 'number', 'value' => old('jumlah', 1), 'required' => true, 'attrs' => 'min=1 data-proposal-qty'])
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Spesifikasi</span>
                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="spesifikasi" rows="4" required>{{ old('spesifikasi') }}</textarea>
                @error('spesifikasi') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>

            @include('usulan-pengadaan.partials.field', ['label' => 'Estimasi harga satuan', 'name' => 'estimasi_harga_satuan', 'type' => 'number', 'value' => old('estimasi_harga_satuan'), 'required' => true, 'attrs' => 'min=0 step=1 data-proposal-price'])

            <div class="rounded-[11px] bg-[#f4f7fa] p-4 text-[11px] leading-relaxed text-[#6e7d91]">
                <strong class="mb-1.5 block text-[#405267]">Estimasi total</strong>
                <p class="m-0 text-[15px] font-extrabold text-[#0b2b50]" data-proposal-total>Rp 0</p>
            </div>

            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                <span>Justifikasi kebutuhan (kenapa barang ini diperlukan)</span>
                <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="alasan_kebutuhan" rows="4" required>{{ old('alasan_kebutuhan') }}</textarea>
                @error('alasan_kebutuhan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </label>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Pengguna barang</span>
                    <div class="relative" data-user-combobox>
                        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white py-0 pl-3 pr-10 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="search" value="{{ $oldPenggunaBarang ? $userOptionLabel($oldPenggunaBarang) : '' }}" placeholder="Pilih atau cari pengguna..." autocomplete="off" required data-user-search data-user-target="pengguna_barang_id">
                        <button class="absolute right-1.5 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-[8px] border-0 bg-transparent text-[#53677d] transition hover:bg-[#f1f5f9]" type="button" aria-label="Tampilkan daftar pengguna" data-user-toggle>
                            <svg class="h-4 w-4 fill-none stroke-current stroke-[2.3] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                        </button>
                        <div class="absolute left-0 right-0 top-[46px] z-[90] hidden max-h-[210px] overflow-y-auto rounded-[10px] border border-[#dce4ec] bg-white p-1 shadow-[0_14px_35px_rgb(15_38_64_/_14%)]" data-user-list>
                            @foreach ($users as $optionUser)
                                <button class="block w-full rounded-[8px] px-3 py-2 text-left text-[10px] font-semibold text-[#34465a] transition hover:bg-[#e8f3ff] hover:text-[#146bb5]" type="button" data-user-option data-user-id="{{ $optionUser->id }}" data-user-label="{{ $userOptionLabel($optionUser) }}">{{ $userOptionLabel($optionUser) }}</button>
                            @endforeach
                            <div class="hidden px-3 py-2 text-[10px] font-semibold text-[#8a98aa]" data-user-empty>Pengguna tidak ditemukan.</div>
                        </div>
                    </div>
                    <input type="hidden" name="pengguna_barang_id" value="{{ old('pengguna_barang_id') }}" data-user-hidden="pengguna_barang_id">
                    @error('pengguna_barang_id') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                    <span>Penanggung jawab</span>
                    <div class="relative" data-user-combobox>
                        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white py-0 pl-3 pr-10 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="search" value="{{ $oldPenanggungJawab ? $userOptionLabel($oldPenanggungJawab) : '' }}" placeholder="Pilih atau cari penanggung jawab..." autocomplete="off" required data-user-search data-user-target="penanggung_jawab_id">
                        <button class="absolute right-1.5 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-[8px] border-0 bg-transparent text-[#53677d] transition hover:bg-[#f1f5f9]" type="button" aria-label="Tampilkan daftar penanggung jawab" data-user-toggle>
                            <svg class="h-4 w-4 fill-none stroke-current stroke-[2.3] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                        </button>
                        <div class="absolute left-0 right-0 top-[46px] z-[90] hidden max-h-[210px] overflow-y-auto rounded-[10px] border border-[#dce4ec] bg-white p-1 shadow-[0_14px_35px_rgb(15_38_64_/_14%)]" data-user-list>
                            @foreach ($users as $optionUser)
                                <button class="block w-full rounded-[8px] px-3 py-2 text-left text-[10px] font-semibold text-[#34465a] transition hover:bg-[#e8f3ff] hover:text-[#146bb5]" type="button" data-user-option data-user-id="{{ $optionUser->id }}" data-user-label="{{ $userOptionLabel($optionUser) }}">{{ $userOptionLabel($optionUser) }}</button>
                            @endforeach
                            <div class="hidden px-3 py-2 text-[10px] font-semibold text-[#8a98aa]" data-user-empty>Pengguna tidak ditemukan.</div>
                        </div>
                    </div>
                    <input type="hidden" name="penanggung_jawab_id" value="{{ old('penanggung_jawab_id') }}" data-user-hidden="penanggung_jawab_id">
                    @error('penanggung_jawab_id') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">
                @include('usulan-pengadaan.partials.field', ['label' => 'Link Google Drive gambar barang', 'name' => 'url_referensi_gambar', 'type' => 'url', 'value' => old('url_referensi_gambar'), 'attrs' => 'placeholder=https://drive.google.com/...'])
                @include('usulan-pengadaan.partials.field', ['label' => 'URL e-katalog', 'name' => 'url_ekatalog', 'type' => 'url', 'value' => old('url_ekatalog')])
            </div>

            <div class="grid gap-[10px] text-[11px] font-bold text-[#405267]" data-camera-wrapper>
                <span>Foto barang (JPG/PNG/WEBP, maks. 2 MB)</span>
                <input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[11px] file:font-bold file:text-[#146bb5]" type="file" name="photo" accept="image/*" required data-camera-input>
                <div class="flex flex-wrap items-center gap-2">
                    <button class="page-action-button inline-flex min-h-[36px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-camera-open>
                        Buka Kamera
                    </button>
                    <button class="page-action-button hidden min-h-[36px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-camera-capture>
                        Ambil Foto
                    </button>
                    <button class="page-action-button hidden min-h-[36px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-camera-close>
                        Tutup Kamera
                    </button>
                    <small class="text-[9px] font-medium text-[#8290a3]" data-camera-status>Kamu bisa upload file atau ambil foto langsung dari kamera.</small>
                </div>
                <div class="hidden overflow-hidden rounded-[12px] border border-[#dce4ec] bg-[#0b2b50]" data-camera-panel>
                    <video class="block aspect-video w-full bg-[#0b2b50] object-cover" autoplay playsinline muted data-camera-video></video>
                    <canvas class="hidden" data-camera-canvas></canvas>
                </div>
                <img class="hidden max-h-[220px] w-full rounded-[12px] border border-[#dce4ec] object-contain bg-[#f8fafc]" alt="Preview foto barang" data-camera-preview>
                @error('photo') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            </div>

            <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Simpan</span></button>
            </footer>
        </form>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-proposal-form]')
    if (!form) return
    const qty = form.querySelector('[data-proposal-qty]')
    const price = form.querySelector('[data-proposal-price]')
    const total = form.querySelector('[data-proposal-total]')
    const format = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 })
    const updateTotal = () => {
        total.textContent = format.format((Number(qty.value) || 0) * (Number(price.value) || 0))
    }
    qty.addEventListener('input', updateTotal)
    price.addEventListener('input', updateTotal)
    updateTotal()

    const closeUserLists = (exceptList = null) => {
        form.querySelectorAll('[data-user-list]').forEach((list) => {
            if (list !== exceptList) list.classList.add('hidden')
        })
    }

    form.querySelectorAll('[data-user-combobox]').forEach((combobox) => {
        const input = combobox.querySelector('[data-user-search]')
        const list = combobox.querySelector('[data-user-list]')
        const toggle = combobox.querySelector('[data-user-toggle]')
        const options = Array.from(combobox.querySelectorAll('[data-user-option]'))
        const empty = combobox.querySelector('[data-user-empty]')
        const hiddenInput = form.querySelector(`[data-user-hidden="${input.dataset.userTarget}"]`)

        const selectUser = (option) => {
            input.value = option.dataset.userLabel
            hiddenInput.value = option.dataset.userId
            input.setCustomValidity('')
            list.classList.add('hidden')
        }

        const filterOptions = () => {
            const keyword = input.value.trim().toLowerCase()
            let visibleCount = 0

            options.forEach((option) => {
                const isVisible = option.dataset.userLabel.toLowerCase().includes(keyword)
                option.classList.toggle('hidden', !isVisible)
                if (isVisible) visibleCount += 1
            })

            empty?.classList.toggle('hidden', visibleCount > 0)
        }

        const openList = () => {
            filterOptions()
            closeUserLists(list)
            list.classList.remove('hidden')
        }

        const syncSelectedUser = () => {
            const selectedOption = options.find((option) => option.dataset.userLabel === input.value.trim())
            hiddenInput.value = selectedOption ? selectedOption.dataset.userId : ''
            input.setCustomValidity(selectedOption ? '' : 'Pilih pengguna dari daftar yang tersedia.')
        }

        options.forEach((option) => {
            option.addEventListener('mousedown', (event) => {
                event.preventDefault()
                selectUser(option)
            })
        })

        input.addEventListener('focus', openList)
        input.addEventListener('input', () => {
            syncSelectedUser()
            openList()
        })
        input.addEventListener('change', syncSelectedUser)
        input.addEventListener('blur', () => window.setTimeout(() => {
            if (!combobox.matches(':focus-within')) list.classList.add('hidden')
        }, 120))
        toggle.addEventListener('mousedown', (event) => {
            event.preventDefault()
        })
        toggle.addEventListener('click', () => {
            if (list.classList.contains('hidden')) {
                input.focus()
                openList()
            } else {
                list.classList.add('hidden')
            }
        })
        syncSelectedUser()
    })

    document.addEventListener('mousedown', (event) => {
        if (!form.contains(event.target)) closeUserLists()
    })

    const cameraWrapper = form.querySelector('[data-camera-wrapper]')
    const fileInput = cameraWrapper?.querySelector('[data-camera-input]')
    const openCameraButton = cameraWrapper?.querySelector('[data-camera-open]')
    const captureButton = cameraWrapper?.querySelector('[data-camera-capture]')
    const closeCameraButton = cameraWrapper?.querySelector('[data-camera-close]')
    const panel = cameraWrapper?.querySelector('[data-camera-panel]')
    const video = cameraWrapper?.querySelector('[data-camera-video]')
    const canvas = cameraWrapper?.querySelector('[data-camera-canvas]')
    const preview = cameraWrapper?.querySelector('[data-camera-preview]')
    const status = cameraWrapper?.querySelector('[data-camera-status]')
    let cameraStream = null

    const setCameraStatus = (message, isError = false) => {
        if (!status) return
        status.textContent = message
        status.classList.toggle('text-[#b8433b]', isError)
        status.classList.toggle('text-[#8290a3]', !isError)
    }

    const stopCamera = () => {
        cameraStream?.getTracks().forEach((track) => track.stop())
        cameraStream = null
        panel?.classList.add('hidden')
        captureButton?.classList.add('hidden')
        captureButton?.classList.remove('inline-flex')
        closeCameraButton?.classList.add('hidden')
        closeCameraButton?.classList.remove('inline-flex')
        if (video) video.srcObject = null
    }

    openCameraButton?.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            setCameraStatus('Browser ini belum mendukung akses kamera.', true)
            return
        }

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
            if (video) video.srcObject = cameraStream
            panel?.classList.remove('hidden')
            captureButton?.classList.remove('hidden')
            captureButton?.classList.add('inline-flex')
            closeCameraButton?.classList.remove('hidden')
            closeCameraButton?.classList.add('inline-flex')
            setCameraStatus('Kamera aktif. Arahkan ke barang lalu klik Ambil Foto.')
        } catch (error) {
            setCameraStatus('Kamera tidak bisa dibuka. Pastikan izin kamera diizinkan browser.', true)
        }
    })

    captureButton?.addEventListener('click', () => {
        if (!video || !canvas || !fileInput) return

        canvas.width = video.videoWidth || 1280
        canvas.height = video.videoHeight || 720
        canvas.getContext('2d')?.drawImage(video, 0, 0, canvas.width, canvas.height)

        canvas.toBlob((blob) => {
            if (!blob) {
                setCameraStatus('Foto gagal diambil. Coba ulangi lagi.', true)
                return
            }

            if (blob.size > 2 * 1024 * 1024) {
                setCameraStatus('Foto dari kamera melebihi 2 MB. Coba ambil ulang dengan jarak lebih dekat.', true)
                return
            }

            const file = new File([blob], `foto-barang-${Date.now()}.jpg`, { type: 'image/jpeg' })
            const dataTransfer = new DataTransfer()
            dataTransfer.items.add(file)
            fileInput.files = dataTransfer.files

            if (preview) {
                preview.src = URL.createObjectURL(file)
                preview.classList.remove('hidden')
            }

            setCameraStatus('Foto dari kamera berhasil dipilih.')
            stopCamera()
        }, 'image/jpeg', 0.82)
    })

    closeCameraButton?.addEventListener('click', () => {
        stopCamera()
        setCameraStatus('Kamera ditutup. Kamu tetap bisa upload file gambar.')
    })

    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0]
        if (!file) return

        if (file.size > 2 * 1024 * 1024) {
            setCameraStatus('Ukuran foto maksimal 2 MB.', true)
            return
        }

        if (preview) {
            preview.src = URL.createObjectURL(file)
            preview.classList.remove('hidden')
        }
        setCameraStatus('Foto berhasil dipilih.')
    })
})
</script>
