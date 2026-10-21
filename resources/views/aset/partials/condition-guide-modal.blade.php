<div class="fixed inset-0 z-90 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="condition-classification-guide" role="presentation">
    <section class="w-[min(980px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="condition-guide-title">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <span class="hidden">PANDUAN</span>
                <h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="condition-guide-title">Panduan Klasifikasi Kondisi Barang</h2>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal">
                <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </header>

        <div class="grid gap-4 p-[22px]">
            <div class="overflow-x-auto rounded-[13px] border border-[#dce4ec]">
                <table class="min-w-[860px] w-full border-collapse text-left text-[10px] text-[#405267]">
                    <thead>
                        <tr class="bg-[#f6f8fb] text-[10px] uppercase tracking-[.03em] text-[#718096]">
                            <th class="border-b border-[#dce4ec] px-4 py-3">Kondisi</th>
                            <th class="border-b border-[#dce4ec] px-4 py-3">Fungsi Barang</th>
                            <th class="border-b border-[#dce4ec] px-4 py-3">Tingkat Kerusakan</th>
                            <th class="border-b border-[#dce4ec] px-4 py-3">Indikator / Contoh</th>
                        </tr>
                    </thead>
                    <tbody class="[&_td]:align-top [&_td]:border-b [&_td]:border-[#edf1f5] [&_td]:px-4 [&_td]:py-3">
                        <tr>
                            <td><span class="inline-flex rounded-full bg-[#dcfce7] px-2.5 py-1 font-extrabold text-[#08714e]">Baik</span></td>
                            <td>Berfungsi normal.</td>
                            <td>Tidak terdapat kerusakan yang mengganggu penggunaan.</td>
                            <td>Seluruh fungsi utama berjalan baik; tidak ada kerusakan yang memengaruhi penggunaan; barang layak digunakan sebagaimana mestinya.</td>
                        </tr>
                        <tr>
                            <td><span class="inline-flex rounded-full bg-[#fef3c7] px-2.5 py-1 font-extrabold text-[#92400e]">Rusak Ringan</span></td>
                            <td>Masih dapat digunakan dan fungsi utama tetap berjalan.</td>
                            <td>Kerusakan kecil yang tidak menghilangkan fungsi utama barang.</td>
                            <td>Lecet/kerusakan fisik kecil; tombol tertentu mulai bermasalah; printer masih mencetak tetapi hasil mulai kurang baik; dapat diperbaiki dengan pemeliharaan sederhana.</td>
                        </tr>
                        <tr>
                            <td><span class="inline-flex rounded-full bg-[#ffedd5] px-2.5 py-1 font-extrabold text-[#c2410c]">Rusak Sedang</span></td>
                            <td>Masih dapat digunakan, tetapi sebagian fungsi mengalami gangguan.</td>
                            <td>Membutuhkan perbaikan atau penggantian komponen tertentu.</td>
                            <td>Keyboard laptop tidak berfungsi sebagian; baterai laptop rusak; printer sering macet; sebagian fungsi perangkat tidak dapat digunakan; membutuhkan servis/penggantian komponen.</td>
                        </tr>
                        <tr>
                            <td><span class="inline-flex rounded-full bg-[#fee2e2] px-2.5 py-1 font-extrabold text-[#b42318]">Rusak Berat</span></td>
                            <td>Tidak dapat digunakan secara normal atau fungsi utama tidak berjalan.</td>
                            <td>Kerusakan serius yang membutuhkan perbaikan besar.</td>
                            <td>Perangkat mati total; komponen utama rusak; kerusakan fisik berat; biaya perbaikan diperkirakan besar; barang tidak layak digunakan normal.</td>
                        </tr>
                        <tr>
                            <td><span class="inline-flex rounded-full bg-[#e5e7eb] px-2.5 py-1 font-extrabold text-[#374151]">Hilang</span></td>
                            <td>Barang tidak dapat digunakan karena keberadaannya tidak diketahui.</td>
                            <td>Bukan kondisi kerusakan fisik, tetapi status keberadaan barang.</td>
                            <td>Barang tidak ditemukan pada lokasi seharusnya; tidak ditemukan setelah pemeriksaan; dinyatakan hilang berdasarkan proses yang berlaku.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="rounded-[12px] border border-[#bfdbfe] bg-[#eff6ff] px-4 py-3 text-[11px] font-semibold leading-relaxed text-[#1d4f8f]">
                Panduan ini digunakan sebagai acuan dalam menentukan kondisi barang. Pengguna tetap harus menyesuaikan kondisi dengan keadaan aktual barang.
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.assetConditionGuideInitialized) return
    window.assetConditionGuideInitialized = true

    const descriptions = {
        baik: 'Barang berfungsi normal dan layak digunakan.',
        rusak_ringan: 'Barang masih dapat digunakan dan fungsi utama masih berjalan, tetapi terdapat kerusakan kecil yang membutuhkan pemeliharaan/perbaikan ringan.',
        rusak_sedang: 'Barang masih dapat digunakan, tetapi sebagian fungsi terganggu dan membutuhkan perbaikan atau penggantian komponen.',
        rusak_berat: 'Barang tidak dapat digunakan secara normal karena mengalami kerusakan serius dan membutuhkan perbaikan besar.',
        hilang: 'Barang tidak ditemukan atau keberadaannya tidak diketahui berdasarkan hasil pemeriksaan.',
    }

    document.querySelectorAll('[data-condition-guide-field]').forEach((field) => {
        const select = field.querySelector('[data-condition-guide-select]')
        const description = field.querySelector('[data-condition-description]')
        if (!select || !description) return

        const update = () => {
            description.textContent = descriptions[select.value] || ''
        }

        select.addEventListener('change', update)
        update()
    })
})
</script>
