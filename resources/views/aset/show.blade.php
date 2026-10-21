@extends('layouts.app')

@section('title', $asset->nama_aset.' - SIMIVA')

@php
    use Illuminate\Support\Facades\Storage;

    $borrowed = (int) ($asset->borrowed_jumlah ?? 0);
    $reserved = (int) ($asset->reserved_jumlah ?? 0);
    $conditionStocks = $asset->condition_stock_summary;
    $goodStock = (int) ($conditionStocks['baik'] ?? 0);
    $damaged = array_sum($conditionStocks) - $goodStock;
    $approved = ($asset->status_verifikasi ?? 'disetujui') === 'disetujui';
    $stock = [
        'total' => (int) $asset->jumlah,
        'tersedia' => ! $approved ? 0 : max(0, $goodStock - $borrowed - $reserved),
        'direservasi' => $reserved,
        'dipinjam' => $borrowed,
        'rusak' => $damaged,
    ];
    $verificationLabels = ['menunggu_verifikasi' => 'Menunggu Verifikasi', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
    $conditionTone = match ($asset->kondisi->value) {
        'baik' => 'bg-[#dcfce7] text-[#166534]',
        'rusak_ringan' => 'bg-[#fef3c7] text-[#92400e]',
        'rusak_sedang' => 'bg-[#ffedd5] text-[#9a3412]',
        'rusak_berat', 'hilang' => 'bg-[#fee2e2] text-[#991b1b]',
        default => 'bg-[#eef2f7] text-[#475569]',
    };
    $qrFilename = 'qr-'.\Illuminate\Support\Str::slug(str_replace(['/', '.', '_'], '-', $asset->display_code)).'.png';
    $assetPhotoUrl = $asset->path_foto ? Storage::disk('public')->url($asset->path_foto) : null;
    $assetPhotos = $asset->photos ?? collect();
    $primaryPhoto = $assetPhotos->firstWhere('is_primary', true);
    $galleryPhotos = $assetPhotos->filter(fn ($photo) => $primaryPhoto ? $photo->id !== $primaryPhoto->id : $photo->path !== $asset->path_foto);
    $conditionLabel = fn (?string $condition): string => [
        'baik' => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_sedang' => 'Rusak Sedang',
        'rusak_berat' => 'Rusak Berat',
        'hilang' => 'Hilang',
    ][$condition] ?? '-';
    $formatDocumentSize = function (?int $bytes): string {
        if (! $bytes) {
            return '-';
        }

        return $bytes < 1024 * 1024
            ? round($bytes / 1024).' KB'
            : number_format($bytes / (1024 * 1024), 1).' MB';
    };
    $assetHolder = $asset->assetHolder;
    $assetHolderLabel = $assetHolder
        ? $assetHolder->nama_pegawai.($assetHolder->nip ? ' - NIP '.$assetHolder->nip : '').($assetHolder->is_active ? '' : ' (Nonaktif)')
        : 'Belum ditentukan';
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <a class="mb-[15px] block w-max text-[10px] font-bold text-[#146bb5] no-underline transition hover:text-[#0b4f88]" href="{{ route('web.aset.index') }}">← Kembali ke aset</a>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">{{ $asset->nama_aset }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                @if ($asset->kode_internal)
                    <p class="m-0 text-[13px] font-extrabold text-[#146bb5]">{{ $asset->kode_internal }}</p>
                @endif
                <p class="m-0 text-[13px] text-[#66758a]">Kode internal: {{ $asset->kode_internal ?: '-' }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-[minmax(0,1fr)_310px] gap-4 max-[900px]:grid-cols-1">
        <section class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
            <header>
                <h2 class="m-0 mb-[3px] text-[15px] font-extrabold text-[#20364f]">Informasi Aset</h2>
                <p class="m-0 text-[10px] text-[#94a0af]">Identitas dan rincian barang inventaris</p>
            </header>
            @if ($assetPhotoUrl)
                <div class="mt-[18px] overflow-hidden rounded-[13px] border border-[#e3e9f0] bg-[#f8fafc]">
                    <img class="max-h-[320px] w-full object-contain" src="{{ $assetPhotoUrl }}" alt="Foto {{ $asset->nama_aset }}">
                </div>
            @endif
            @if ($galleryPhotos->isNotEmpty() || $asset->google_drive_url)
                <div class="mt-[18px] grid gap-3 rounded-[13px] border border-[#e3e9f0] bg-[#f8fafc] p-4">
                    @if ($galleryPhotos->isNotEmpty())
                        <div>
                            <h3 class="m-0 mb-2 text-[12px] font-extrabold text-[#20364f]">Dokumentasi Foto</h3>
                            <div class="grid grid-cols-[repeat(auto-fill,minmax(92px,1fr))] gap-2">
                                @foreach ($galleryPhotos as $photo)
                                    <a class="block overflow-hidden rounded-[10px] border border-[#dce4ec] bg-white" href="{{ Storage::disk('public')->url($photo->path) }}" target="_blank" rel="noopener">
                                        <img class="h-[92px] w-full object-cover" src="{{ Storage::disk('public')->url($photo->path) }}" alt="{{ $photo->nama_file_asli ?: 'Dokumentasi foto aset' }}">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($asset->google_drive_url)
                        <div>
                            <h3 class="m-0 mb-2 text-[12px] font-extrabold text-[#20364f]">Dokumentasi Tambahan</h3>
                            <a class="inline-flex min-h-[36px] items-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#146bb5] no-underline transition hover:border-[#b8c6d6]" href="{{ $asset->google_drive_url }}" target="_blank" rel="noopener noreferrer">Buka Google Drive</a>
                        </div>
                    @endif
                </div>
            @endif
            <div class="mt-[18px] grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
                @include('aset.partials.show-item', ['label' => 'Kode internal', 'value' => $asset->kode_internal ?: 'Belum dibuat'])
                @include('aset.partials.show-item', ['label' => 'Kode barang', 'value' => $asset->kodeBarang ? $asset->kodeBarang->kode_barang.' - '.$asset->kodeBarang->nama_barang : '-'])
                @include('aset.partials.show-item', ['label' => 'Kategori', 'value' => $asset->category->kode_kategori.' - '.$asset->category->nama_kategori])
                @include('aset.partials.show-item', ['label' => 'Nama aset', 'value' => $asset->nama_aset])
                @include('aset.partials.show-item', ['label' => 'Merek / tipe', 'value' => $asset->merek_tipe ?: '-'])
                @include('aset.partials.show-item', ['label' => 'Jumlah unit', 'value' => $asset->jumlah])
                @include('aset.partials.show-item', ['label' => 'Status verifikasi', 'value' => $verificationLabels[$asset->status_verifikasi ?? 'disetujui'] ?? 'Disetujui'])
                @include('aset.partials.show-item', ['label' => 'Tahun perolehan', 'value' => $asset->tahun_perolehan ?: '-'])
                @include('aset.partials.show-item', ['label' => 'Pemegang Aset', 'value' => $assetHolderLabel])
                @include('aset.partials.show-item', ['label' => 'Lokasi penyimpanan', 'value' => $asset->lokasi_penyimpanan ?: '-'])
                @include('aset.partials.show-item', ['label' => in_array($asset->kondisi->value, ['rusak_ringan', 'rusak_sedang', 'rusak_berat', 'hilang'], true) ? 'Spesifikasi / Deskripsi Kerusakan' : 'Spesifikasi', 'value' => $asset->spesifikasi ?: 'Belum ada spesifikasi.', 'wide' => true])
            </div>
        </section>

        <aside class="flex flex-col items-center rounded-[14px] border border-slate-200 bg-white p-5 text-center shadow-[0_8px_25px_rgb(31_62_91_/_4%)] max-[900px]:order-first">
            <div class="w-full text-left">
                <h2 class="m-0 mb-[3px] text-[15px] font-extrabold text-[#20364f]">QR Code Aset</h2>
                <p class="m-0 text-[10px] text-[#94a0af]">{{ $approved ? 'Pindai untuk melihat informasi aset' : 'QR muncul setelah aset disetujui Tim Aset' }}</p>
            </div>

            @if ($approved)
                <div class="mx-auto mt-[18px] mb-3 grid h-[220px] w-[220px] place-items-center rounded-[13px] border border-[#e1e8ef] bg-white p-2">
                    <img id="asset-qr-image" class="h-full w-full" src="{{ route('web.aset.qr.svg', $asset) }}" alt="QR Code {{ $asset->nama_aset }}">
                </div>
                <strong class="text-sm font-extrabold text-[#0b2b50]">{{ $asset->display_code }}</strong>
                <span class="mt-1 mb-3.5 text-[10px] text-[#718095]">{{ $asset->nama_aset }}</span>
                <div class="flex flex-wrap justify-center gap-2">
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-download-qr>Unduh PNG</button>
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-print-qr>Print QR</button>
                </div>
                <small class="mt-[11px] text-[8px] text-[#97a3b1]">QR mengarah ke halaman informasi publik aset.</small>
            @else
                <div class="mt-[18px] grid min-h-[220px] w-full place-items-center content-center gap-2.5 rounded-[13px] border border-dashed border-[#cfd9e5] bg-[#f7f9fc] p-[22px]">
                    <span class="loan-status {{ ($asset->status_verifikasi ?? '') === 'ditolak' ? 'status-ditolak' : 'status-diajukan' }}">{{ $verificationLabels[$asset->status_verifikasi ?? 'menunggu_verifikasi'] ?? 'Menunggu Verifikasi' }}</span>
                    <strong class="text-[15px] font-extrabold text-[#0b2b50]">QR belum tersedia</strong>
                    <small class="max-w-[210px] text-[10px] leading-normal text-[#7f8ea1]">Aset harus diverifikasi oleh Tim Aset terlebih dahulu.</small>
                </div>
            @endif
        </aside>
    </div>

    <section class="mt-4 rounded-[14px] border border-slate-200 bg-white p-5 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <header>
            <h2 class="m-0 mb-[3px] text-[15px] font-extrabold text-[#20364f]">Riwayat Perubahan Kondisi</h2>
            <p class="m-0 text-[10px] text-[#94a0af]">Dokumentasi melekat pada setiap transaksi perubahan stok kondisi</p>
        </header>
        <div class="mt-4 grid gap-2">
            @forelse ($asset->conditionMutations->sortByDesc('created_at') as $mutation)
                @php
                    $photos = $mutation->documents->where('jenis', 'foto');
                    $videos = $mutation->documents->where('jenis', 'video');
                @endphp
                <article class="grid gap-3 rounded-[12px] bg-[#f7f9fc] p-3 text-[11px]">
                    <div class="grid grid-cols-[1fr_auto] gap-3 max-[580px]:grid-cols-1">
                        <div>
                            <strong class="block text-[#0b2b50]">{{ $conditionLabel($mutation->dari_kondisi) }} → {{ $conditionLabel($mutation->ke_kondisi) }}</strong>
                            <small class="text-[#718095]">{{ $mutation->jumlah }} unit • {{ $mutation->created_at?->translatedFormat('d M Y H:i') }} • Oleh: {{ $mutation->user?->name ?: '-' }}</small>
                        </div>
                    </div>
                    @if ($mutation->catatan)
                        <p class="m-0 whitespace-pre-line rounded-[10px] bg-white px-3 py-2 text-[#52657a]"><b>Catatan:</b> {{ $mutation->catatan }}</p>
                    @endif

                    @if ($photos->isNotEmpty() || $videos->isNotEmpty() || $mutation->google_drive_url)
                        <div class="grid gap-2">
                            @if ($photos->isNotEmpty())
                                <div>
                                    <strong class="mb-2 block text-[10px] uppercase tracking-[.04em] text-[#405267]">Foto Dokumentasi</strong>
                                    <div class="grid grid-cols-[repeat(auto-fill,minmax(86px,1fr))] gap-2">
                                        @foreach ($photos as $photo)
                                            <button class="block overflow-hidden rounded-[10px] border border-[#dce4ec] bg-white text-left transition hover:border-[#6fa9cf]" type="button" data-condition-photo-preview data-photo-url="{{ Storage::disk('public')->url($photo->path_file) }}" data-photo-alt="{{ $photo->nama_file_asli ?: 'Foto dokumentasi kondisi' }}" aria-label="Lihat {{ $photo->nama_file_asli ?: 'foto dokumentasi kondisi' }}">
                                                <img class="h-[86px] w-full object-cover" src="{{ Storage::disk('public')->url($photo->path_file) }}" alt="{{ $photo->nama_file_asli ?: 'Foto dokumentasi kondisi' }}">
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($videos->isNotEmpty())
                                <div class="grid gap-1.5">
                                    <strong class="text-[10px] uppercase tracking-[.04em] text-[#405267]">Video Dokumentasi</strong>
                                    @foreach ($videos as $video)
                                        <a class="inline-flex w-max max-w-full items-center gap-2 rounded-[9px] border border-[#dbe3eb] bg-white px-3 py-2 text-[10px] font-bold text-[#146bb5] no-underline transition hover:border-[#b8c6d6]" href="{{ Storage::disk('public')->url($video->path_file) }}" target="_blank" rel="noopener">
                                            Lihat Video
                                            <span class="font-semibold text-[#718095]">({{ $formatDocumentSize($video->ukuran_file) }})</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @if ($mutation->google_drive_url)
                                <a class="inline-flex w-max max-w-full items-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 py-2 text-[10px] font-bold text-[#146bb5] no-underline transition hover:border-[#b8c6d6]" href="{{ $mutation->google_drive_url }}" target="_blank" rel="noopener noreferrer">Buka Google Drive</a>
                            @endif
                        </div>
                    @else
                        <div class="rounded-[10px] border border-dashed border-[#d8e2ee] bg-white px-3 py-2 text-[10px] font-semibold text-[#8290a3]">Dokumentasi belum tersedia.</div>
                    @endif
                </article>
            @empty
                <div class="rounded-[12px] bg-[#f7f9fc] p-5 text-center text-[11px] text-[#718095]">Belum ada riwayat perubahan kondisi.</div>
            @endforelse
        </div>
    </section>

    <section class="mt-4 rounded-[14px] border border-slate-200 bg-white p-5 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <header>
            <h2 class="m-0 mb-[3px] text-[15px] font-extrabold text-[#20364f]">Riwayat Pemeliharaan</h2>
            <p class="m-0 text-[10px] text-[#94a0af]">Catatan pemeliharaan sepanjang umur aset</p>
        </header>
        <div class="mt-4 grid gap-2">
            @forelse ($asset->maintenances->sortByDesc('tanggal_pengajuan') as $maintenance)
                <div class="grid grid-cols-[1fr_auto] gap-3 rounded-[12px] bg-[#f7f9fc] p-3 text-[11px] max-[580px]:grid-cols-1">
                    <div>
                        <strong class="block text-[#0b2b50]">{{ $maintenance->tanggal_pengajuan?->translatedFormat('d M Y') }} - {{ $maintenance->keluhan }}</strong>
                        <small class="text-[#718095]">{{ $maintenance->kondisi_sebelum?->label() }}{{ $maintenance->kondisi_setelah ? ' → '.$maintenance->kondisi_setelah->label() : '' }}</small>
                    </div>
                    <span class="loan-status {{ $maintenance->status->value === 'ditolak_tim_aset' ? 'status-ditolak' : ($maintenance->status->value === 'selesai' ? 'status-disetujui' : 'status-diajukan') }}">{{ $maintenance->status->label() }}</span>
                </div>
            @empty
                <div class="rounded-[12px] bg-[#f7f9fc] p-5 text-center text-[11px] text-[#718095]">Belum ada riwayat pemeliharaan.</div>
            @endforelse
        </div>
    </section>

    <div class="fixed inset-0 z-[90] hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-condition-photo-modal role="presentation">
        <section class="w-[min(780px,100%)] max-h-[calc(100vh-48px)] overflow-hidden rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="condition-photo-modal-title">
            <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                <div>
                    <h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="condition-photo-modal-title">Foto Dokumentasi</h2>
                    <p class="m-0 mt-1 text-[11px] text-[#7f8ea1]">Dokumentasi perubahan kondisi aset.</p>
                </div>
                <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2] transition hover:bg-[#e8edf2]" type="button" data-condition-photo-close aria-label="Tutup preview foto">
                    <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                </button>
            </header>
            <div class="flex max-h-[calc(100vh-210px)] min-h-[180px] items-center justify-center overflow-auto bg-[#f7f9fc] p-5 max-[580px]:p-3">
                <img class="h-auto max-h-[calc(100vh-250px)] w-auto max-w-full rounded-[10px] object-contain shadow-[0_5px_18px_rgb(30_52_75_/_12%)]" src="" alt="" data-condition-photo-image>
            </div>
            <footer class="flex justify-end border-t border-[#edf1f5] px-[22px] py-[17px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-condition-photo-close>Tutup</button>
            </footer>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('[data-condition-photo-modal]')
    const image = modal?.querySelector('[data-condition-photo-image]')
    const close = () => {
        modal?.classList.add('hidden')
        document.body.classList.remove('overflow-hidden')
        if (image) image.src = ''
    }
    document.querySelectorAll('[data-condition-photo-preview]').forEach((trigger) => trigger.addEventListener('click', () => {
        image.src = trigger.dataset.photoUrl
        image.alt = trigger.dataset.photoAlt || 'Foto dokumentasi kondisi'
        modal.classList.remove('hidden')
        document.body.classList.add('overflow-hidden')
    }))
    modal?.querySelector('[data-condition-photo-close]')?.addEventListener('click', close)
    modal?.addEventListener('click', (event) => { if (event.target === modal) close() })
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal?.classList.contains('hidden')) close() })
})
</script>

@if ($approved)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const qrImage = document.getElementById('asset-qr-image')
    const filename = @json($qrFilename)

    const svgToPng = () => new Promise((resolve, reject) => {
        const image = new Image()
        image.onload = () => {
            const canvas = document.createElement('canvas')
            canvas.width = 720
            canvas.height = 720
            const context = canvas.getContext('2d')
            context.fillStyle = '#ffffff'
            context.fillRect(0, 0, canvas.width, canvas.height)
            context.drawImage(image, 0, 0, canvas.width, canvas.height)
            resolve(canvas.toDataURL('image/png'))
        }
        image.onerror = reject
        image.src = qrImage.src
    })

    document.querySelector('[data-download-qr]')?.addEventListener('click', async () => {
        const pngUrl = await svgToPng()
        const anchor = document.createElement('a')
        anchor.href = pngUrl
        anchor.download = filename
        anchor.click()
    })

    document.querySelector('[data-print-qr]')?.addEventListener('click', async () => {
        const printWindow = window.open('', '_blank', 'width=720,height=850')
        if (!printWindow) return
        const pngUrl = await svgToPng()
        printWindow.document.write(`<!doctype html><html><head><title>Print QR Aset</title><style>body{margin:0;font-family:Arial,sans-serif;color:#102d4b}main{min-height:100vh;display:grid;place-content:center;justify-items:center;text-align:center;padding:32px;box-sizing:border-box}img{width:360px;height:360px}h1{font-size:24px;margin:18px 0 5px}p{font-size:16px;margin:0 0 5px}.org{font-size:12px;color:#65758a;margin-top:16px}@media print{body{print-color-adjust:exact;-webkit-print-color-adjust:exact}}</style></head><body><main><img src="${pngUrl}" alt="QR Code Aset"><h1>{{ $asset->display_code }}</h1><p>{{ $asset->nama_aset }}</p><p class="org">Sistem Informasi Manajemen Aset<br>Diskominfo Kabupaten Siak</p></main></body></html>`)
        printWindow.document.close()
        printWindow.focus()
        printWindow.print()
    })
})
</script>
@endif
@endsection
