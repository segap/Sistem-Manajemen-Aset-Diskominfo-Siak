@php
    use App\Enums\AssetCondition;
    use App\Enums\AssetMaintenanceStatus;
    use App\Enums\AssetMaintenanceType;
    use Illuminate\Support\Facades\Storage;

    $photoBefore = $maintenance->foto_sebelum ? Storage::disk('public')->url($maintenance->foto_sebelum) : null;
    $photoAfter = $maintenance->foto_setelah ? Storage::disk('public')->url($maintenance->foto_setelah) : null;
    $photoBeforeName = $maintenance->foto_sebelum ? basename($maintenance->foto_sebelum) : null;
    $photoAfterName = $maintenance->foto_setelah ? basename($maintenance->foto_setelah) : null;
    $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $isImageDocument = fn ($path) => in_array(strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)), $imageExtensions, true);
    $documentUrl = fn ($path) => Storage::disk('public')->url($path);
    $maintenanceDocuments = $maintenance->documents;
    $serviceDocuments = $maintenanceDocuments->where('jenis_dokumen', 'dokumentasi_servis')->values();
    $videoDocuments = $maintenanceDocuments->whereIn('jenis_dokumen', ['video_sebelum', 'video_dokumentasi'])->values();
    $invoiceDocuments = $maintenanceDocuments->where('jenis_dokumen', 'invoice')->values();
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="detail-maintenance-{{ $maintenance->id }}" role="presentation">
    <section class="w-[min(980px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
        <header class="flex items-start justify-between gap-4 border-b border-[#edf1f5] p-6">
            <div>
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Detail Pemeliharaan #{{ $maintenance->id }}</h2>
                <p class="m-0 mt-1 text-[11px] text-[#7f8ea1]">{{ $maintenance->asset?->display_code }} - {{ $maintenance->asset?->nama_aset }}</p>
            </div>
            <button class="grid h-10 w-10 place-items-center rounded-xl border-0 bg-[#f1f5f9] text-[#7e8da1]" type="button" data-modal-close>&times;</button>
        </header>
        <div class="grid gap-4 p-6">
            @if ($maintenance->kondisi_sebelum === AssetCondition::HeavyDamage || $maintenance->kondisi_setelah === AssetCondition::HeavyDamage)
                <div class="rounded-[12px] border border-[#fed7aa] bg-[#fff7ed] p-3 text-[11px] font-semibold text-[#9a3412]">Aset berkondisi Rusak Berat. Pertimbangkan kelayakan pemeliharaan atau pengajuan penghapusan.</div>
            @endif

            <div class="grid grid-cols-2 gap-4 max-[760px]:grid-cols-1">
                <article class="rounded-[14px] border border-[#e3e9f0] p-4">
                    <h3 class="m-0 mb-3 text-[13px] font-extrabold text-[#0b2b50]">Informasi Aset</h3>
                    <div class="grid gap-2 text-[11px] text-[#516176]">
                        <p class="m-0"><b>Kode:</b> {{ $maintenance->asset?->display_code }}</p>
                        <p class="m-0"><b>Kode Barang:</b> {{ $maintenance->asset?->kodeBarang?->kode_barang ?: '-' }}</p>
                        <p class="m-0"><b>Merek/Tipe:</b> {{ $maintenance->asset?->merek_tipe ?: '-' }}</p>
                        <p class="m-0"><b>Lokasi:</b> {{ $maintenance->asset?->lokasi_penyimpanan ?: '-' }}</p>
                        <p class="m-0"><b>Kondisi Sebelum:</b> {{ $maintenance->kondisi_sebelum?->label() }}</p>
                        <p class="m-0"><b>Jumlah Dipelihara:</b> {{ $maintenance->jumlah_dipelihara ?: 1 }} Unit</p>
                    </div>
                </article>
                <article class="rounded-[14px] border border-[#e3e9f0] p-4">
                    <h3 class="m-0 mb-3 text-[13px] font-extrabold text-[#0b2b50]">Informasi Pengajuan</h3>
                    <div class="grid gap-2 text-[11px] text-[#516176]">
                        <p class="m-0"><b>Pemohon:</b> {{ $maintenance->applicant?->name ?: '-' }}</p>
                        <p class="m-0"><b>Tanggal:</b> {{ $maintenance->tanggal_pengajuan?->translatedFormat('d M Y H:i') }}</p>
                        <p class="m-0"><b>Keluhan:</b> {{ $maintenance->keluhan }}</p>
                        <p class="m-0"><b>Catatan:</b> {{ $maintenance->catatan_pengajuan ?: '-' }}</p>
                    </div>
                </article>
            </div>

            <article class="rounded-[14px] border border-[#e3e9f0] p-4">
                <h3 class="m-0 mb-3 text-[13px] font-extrabold text-[#0b2b50]">Verifikasi & Prioritas</h3>
                <div class="grid grid-cols-3 gap-3 text-[11px] text-[#516176] max-[760px]:grid-cols-1">
                    <p class="m-0"><b>Status:</b> {{ $maintenance->status->label() }}</p>
                    <p class="m-0"><b>Prioritas:</b> <span class="loan-status {{ $priorityClass($maintenance->prioritas) }}">{{ $maintenance->prioritas?->label() ?: '-' }}</span></p>
                    <p class="m-0"><b>Diverifikasi:</b> {{ $maintenance->verifier?->name ?: '-' }}</p>
                </div>
                @if ($maintenance->catatan_verifikasi)
                    <p class="m-0 mt-3 text-[11px] text-[#516176]"><b>Catatan:</b> {{ $maintenance->catatan_verifikasi }}</p>
                @endif
            </article>

            @if ($canProcess && $maintenance->status === AssetMaintenanceStatus::Submitted)
                <form class="grid gap-3 rounded-[14px] border border-[#dbeafe] bg-[#f8fbff] p-4" method="POST" action="{{ route('pemeliharaan-aset.verify', $maintenance) }}" data-submit-loading>
                    @csrf @method('PATCH')
                    <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Keputusan
                        <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3" name="decision" required>
                            <option value="approve">Setujui</option>
                            <option value="reject">Tolak</option>
                        </select>
                    </label>
                    <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Prioritas saat disetujui
                        <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3" name="prioritas">
                            <option value="">Pilih prioritas</option>
                            @foreach ($priorities as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <textarea class="min-h-[90px] rounded-[9px] border border-[#dce4ee] px-3 py-3 text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="catatan_verifikasi" placeholder="Catatan verifikasi / alasan penolakan"></textarea>
                    <button class="page-action-button w-max rounded-[9px] border-0 bg-[#146bb5] px-4 py-3 text-[10px] font-bold text-white" type="submit">Simpan Keputusan</button>
                </form>
            @endif

            @if ($canProcess && $maintenance->status === AssetMaintenanceStatus::Approved)
                <form method="POST" action="{{ route('pemeliharaan-aset.start', $maintenance) }}" data-submit-loading>
                    @csrf @method('PATCH')
                    <button class="page-action-button rounded-[9px] border-0 bg-[#146bb5] px-4 py-3 text-[10px] font-bold text-white" type="submit">Mulai Pemeliharaan</button>
                </form>
            @endif

            @if ($canProcess && $maintenance->status === AssetMaintenanceStatus::InProgress)
                <form class="grid gap-3 rounded-[14px] border border-[#dcfce7] bg-[#fbfffd] p-4" method="POST" action="{{ route('pemeliharaan-aset.complete', $maintenance) }}" enctype="multipart/form-data" data-submit-loading>
                    @csrf @method('PATCH')
                    <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="penyedia_servis" placeholder="Penyedia/Jasa Servis">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="nama_pelaksana" required placeholder="Pelaksana/Teknisi *">
                    </div>
                    <div class="grid gap-3">
                        <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="jenis_pemeliharaan" required data-maintenance-type-select data-other-value="lainnya">
                            <option value="">Jenis Pemeliharaan *</option>
                            @foreach ($maintenanceTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('jenis_pemeliharaan', AssetMaintenanceType::MaintenanceAndSparePartReplacement->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input class="hidden h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="jenis_pemeliharaan_lainnya" value="{{ old('jenis_pemeliharaan_lainnya') }}" placeholder="Keterangan jenis pemeliharaan lainnya *" data-maintenance-other-field disabled>
                    </div>
                    <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="identitas_pelaksana" placeholder="Identitas pelaksana (opsional)">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="biaya_servis" type="number" min="0" step="1" placeholder="Biaya Servis">
                    </div>
                    <textarea class="min-h-[90px] rounded-[9px] border border-[#dce4ee] px-3 py-3 !text-[10px] font-semibold !leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="tindakan_servis" placeholder="Tindakan servis"></textarea>
                    <textarea class="min-h-[80px] rounded-[9px] border border-[#dce4ee] px-3 py-3 !text-[10px] font-semibold !leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="komponen_material" placeholder="Komponen/material yang digunakan (opsional)"></textarea>
                    <div class="grid grid-cols-3 gap-3 max-[860px]:grid-cols-1">
                        <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="fungsi_utama" required>
                            <option value="">Fungsi Utama *</option>
                            @foreach ($functionStatuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="hasil_pengujian" required>
                            <option value="">Hasil Pengujian *</option>
                            @foreach ($testResults as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="hasil_akhir_pemeliharaan" required>
                            <option value="">Hasil Akhir *</option>
                            @foreach ($finalResults as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-3 rounded-[12px] border border-[#dbeafe] bg-[#f8fbff] p-4">
                        <div>
                            <h4 class="m-0 text-[12px] font-extrabold text-[#0b2b50]">Data Berita Acara</h4>
                            <p class="m-0 mt-1 text-[10px] font-semibold text-[#718095]">Data ini akan langsung dipakai untuk unduhan Berita Acara setelah pemeliharaan selesai.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="nomor_berita_acara" value="{{ old('nomor_berita_acara') }}" placeholder="Nomor Berita Acara *" required>
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a]" name="tanggal_berita_acara" type="date" value="{{ old('tanggal_berita_acara', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="lokasi_pelaksanaan" value="{{ old('lokasi_pelaksanaan', $maintenance->asset?->lokasi_penyimpanan) }}" placeholder="Lokasi pelaksanaan *" required>
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="tempat_ttd" value="{{ old('tempat_ttd', 'Siak') }}" placeholder="Tempat tanda tangan *" required>
                        </div>
                        <textarea class="min-h-[80px] rounded-[9px] border border-[#dce4ee] px-3 py-3 !text-[10px] font-semibold !leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="dasar_pelaksanaan" placeholder="Dasar pelaksanaan (opsional)">{{ old('dasar_pelaksanaan') }}</textarea>
                        <div class="grid grid-cols-3 gap-3 max-[860px]:grid-cols-1">
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="jabatan_penanggung_jawab" value="{{ old('jabatan_penanggung_jawab', 'Penanggung Jawab') }}" placeholder="Jabatan penanggung jawab *" required>
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="nama_penanggung_jawab" value="{{ old('nama_penanggung_jawab', $maintenance->verifier?->name) }}" placeholder="Nama penanggung jawab *" required>
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="nip_penanggung_jawab" value="{{ old('nip_penanggung_jawab', $maintenance->verifier?->nip) }}" placeholder="NIP penanggung jawab *" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Invoice/Bukti Servis (PDF/JPG/PNG, maks. 2 MB)
                            <input class="w-full rounded-[10px] border border-[#dce4ee] bg-white p-3 !text-[10px] font-semibold text-[#0b2b50] file:mr-3 file:rounded-[9px] file:border-0 file:bg-[#eef7ff] file:px-4 file:py-2.5 file:!text-[10px] file:font-extrabold file:text-[#146bb5]" type="file" name="invoice" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" data-max-file-size="2097152" data-max-file-message="gambar/file lebih dari 2mb">
                        </label>
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Dokumentasi Servis (JPG/PNG/WEBP, maks. 2 MB)
                            <input class="w-full rounded-[10px] border border-[#dce4ee] bg-white p-3 !text-[10px] font-semibold text-[#0b2b50] file:mr-3 file:rounded-[9px] file:border-0 file:bg-[#eef7ff] file:px-4 file:py-2.5 file:!text-[10px] file:font-extrabold file:text-[#146bb5]" type="file" name="dokumentasi_servis[]" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-max-file-size="2097152" data-max-file-message="gambar/file lebih dari 2mb">
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Video Dokumentasi (MP4/MOV/WEBM, maks. 20 MB)
                            <input class="w-full rounded-[10px] border border-[#dce4ee] bg-white p-3 !text-[10px] font-semibold text-[#0b2b50] file:mr-3 file:rounded-[9px] file:border-0 file:bg-[#eef7ff] file:px-4 file:py-2.5 file:!text-[10px] file:font-extrabold file:text-[#146bb5]" type="file" name="video_dokumentasi" accept=".mp4,.mov,.webm,video/mp4,video/quicktime,video/webm" data-max-file-size="20971520" data-max-file-message="video lebih dari 20mb">
                        </label>
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Link Google Drive Dokumentasi
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="link_drive_dokumentasi" value="{{ old('link_drive_dokumentasi', $maintenance->link_drive_dokumentasi) }}" placeholder="https://drive.google.com/...">
                        </label>
                    </div>
                    <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Kondisi Setelah Pemeliharaan *
                        <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="kondisi_setelah" required>
                            <option value="">Pilih kondisi hasil</option>
                            @foreach ([AssetCondition::Good, AssetCondition::LightDamage, AssetCondition::MediumDamage, AssetCondition::HeavyDamage] as $condition)
                                <option value="{{ $condition->value }}">{{ $condition->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <textarea class="min-h-[90px] rounded-[9px] border border-[#dce4ee] px-3 py-3 !text-[10px] font-semibold !leading-relaxed text-[#34445a] placeholder:!text-[10px] placeholder:font-medium placeholder:text-[#8b99aa]" name="catatan_hasil" required placeholder="Catatan hasil pemeliharaan"></textarea>
                    <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Foto Kondisi Setelah Pemeliharaan (JPG/PNG/WEBP, maks. 2 MB)
                        <input class="w-full rounded-[10px] border border-[#dce4ee] bg-white p-3 !text-[10px] font-semibold text-[#0b2b50] file:mr-3 file:rounded-[9px] file:border-0 file:bg-[#eef7ff] file:px-4 file:py-2.5 file:!text-[10px] file:font-extrabold file:text-[#146bb5]" type="file" name="foto_setelah" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-max-file-size="2097152" data-max-file-message="gambar/file lebih dari 2mb">
                    </label>
                    <button class="page-action-button w-max rounded-[9px] border-0 bg-[#146bb5] px-4 py-3 text-[10px] font-bold text-white" type="submit">Selesaikan Pemeliharaan</button>
                </form>
            @endif

            @if ($maintenance->status === AssetMaintenanceStatus::Completed)
                <article class="rounded-[14px] border border-[#e3e9f0] p-4">
                    <h3 class="m-0 mb-3 text-[13px] font-extrabold text-[#0b2b50]">Data Berita Acara</h3>
                    @if (! $maintenance->hasCompleteReportData() && $canProcess)
                        <form class="grid gap-3 rounded-[12px] border border-[#dbeafe] bg-[#f8fbff] p-4" method="POST" action="{{ route('pemeliharaan-aset.report-data', $maintenance) }}" data-submit-loading>
                            @csrf @method('PATCH')
                            <div class="rounded-[12px] border border-[#fed7aa] bg-[#fff7ed] p-3 text-[10px] font-semibold text-[#9a3412]">Data Berita Acara belum lengkap. Lengkapi data wajib berikut sebelum download Word.</div>
                            <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                                <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="nomor_berita_acara" value="{{ old('nomor_berita_acara', $maintenance->nomor_berita_acara) }}" placeholder="Nomor Berita Acara *" required>
                                <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="tanggal_berita_acara" type="date" value="{{ old('tanggal_berita_acara', optional($maintenance->tanggal_berita_acara)->format('Y-m-d')) }}" required>
                            </div>
                            <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                                <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="lokasi_pelaksanaan" value="{{ old('lokasi_pelaksanaan', $maintenance->lokasi_pelaksanaan) }}" placeholder="Lokasi pelaksanaan *" required>
                                <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="tempat_ttd" value="{{ old('tempat_ttd', $maintenance->tempat_ttd) }}" placeholder="Tempat tanda tangan *" required>
                            </div>
                            <textarea class="min-h-[80px] rounded-[9px] border border-[#dce4ee] px-3 py-3 !text-[10px] font-semibold text-[#34445a]" name="dasar_pelaksanaan" placeholder="Dasar pelaksanaan (opsional)">{{ old('dasar_pelaksanaan', $maintenance->dasar_pelaksanaan) }}</textarea>
                            <div class="grid gap-3">
                                <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="jenis_pemeliharaan" required data-maintenance-type-select data-other-value="lainnya">
                                    <option value="">Jenis Pemeliharaan *</option>
                                    @foreach ($maintenanceTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('jenis_pemeliharaan', $maintenance->jenis_pemeliharaan?->value ?? AssetMaintenanceType::MaintenanceAndSparePartReplacement->value) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input class="hidden h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="jenis_pemeliharaan_lainnya" value="{{ old('jenis_pemeliharaan_lainnya', $maintenance->jenis_pemeliharaan_lainnya) }}" placeholder="Keterangan jenis pemeliharaan lainnya *" data-maintenance-other-field disabled>
                            </div>
                            <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                                <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="nama_pelaksana" value="{{ old('nama_pelaksana', $maintenance->nama_pelaksana) }}" placeholder="Pelaksana/Teknisi *" required>
                                <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="identitas_pelaksana" value="{{ old('identitas_pelaksana', $maintenance->identitas_pelaksana) }}" placeholder="Identitas pelaksana">
                            </div>
                            <textarea class="min-h-[80px] rounded-[9px] border border-[#dce4ee] px-3 py-3 !text-[10px] font-semibold text-[#34445a]" name="komponen_material" placeholder="Komponen/material yang digunakan">{{ old('komponen_material', $maintenance->komponen_material) }}</textarea>
                            <div class="grid grid-cols-3 gap-3 max-[860px]:grid-cols-1">
                                <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="fungsi_utama" required>
                                    <option value="">Fungsi Utama *</option>
                                    @foreach ($functionStatuses as $value => $label)
                                        <option value="{{ $value }}" @selected(old('fungsi_utama', $maintenance->fungsi_utama?->value) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="hasil_pengujian" required>
                                    <option value="">Hasil Pengujian *</option>
                                    @foreach ($testResults as $value => $label)
                                        <option value="{{ $value }}" @selected(old('hasil_pengujian', $maintenance->hasil_pengujian?->value) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="hasil_akhir_pemeliharaan" required>
                                    <option value="">Hasil Akhir *</option>
                                    @foreach ($finalResults as $value => $label)
                                        <option value="{{ $value }}" @selected(old('hasil_akhir_pemeliharaan', $maintenance->hasil_akhir_pemeliharaan?->value) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-3 gap-3 max-[860px]:grid-cols-1">
                                <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="jabatan_penanggung_jawab" value="{{ old('jabatan_penanggung_jawab', $maintenance->jabatan_penanggung_jawab) }}" placeholder="Jabatan penanggung jawab *" required>
                                <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="nama_penanggung_jawab" value="{{ old('nama_penanggung_jawab', $maintenance->nama_penanggung_jawab) }}" placeholder="Nama penanggung jawab *" required>
                                <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 !text-[10px] font-semibold text-[#34445a]" name="nip_penanggung_jawab" value="{{ old('nip_penanggung_jawab', $maintenance->nip_penanggung_jawab) }}" placeholder="NIP penanggung jawab *" required>
                            </div>
                            <button class="page-action-button w-max rounded-[9px] border-0 bg-[#146bb5] px-4 py-3 text-[10px] font-bold text-white" type="submit">Lengkapi Data Berita Acara</button>
                        </form>
                    @else
                        <div class="grid gap-2 text-[11px] text-[#516176]">
                            <p class="m-0"><b>Nomor:</b> {{ $maintenance->nomor_berita_acara ?: '-' }}</p>
                            <p class="m-0"><b>Tanggal:</b> {{ optional($maintenance->tanggal_berita_acara)->translatedFormat('d M Y') ?: '-' }}</p>
                            <p class="m-0"><b>Lokasi Pelaksanaan:</b> {{ $maintenance->lokasi_pelaksanaan ?: '-' }}</p>
                            <p class="m-0"><b>Penanggung Jawab:</b> {{ $maintenance->nama_penanggung_jawab ?: '-' }}</p>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a class="page-action-button inline-flex min-h-[38px] w-max items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white no-underline" href="{{ route('pemeliharaan-aset.word', $maintenance) }}">Download Word Berita Acara</a>
                        </div>
                    @endif
                </article>
            @endif

            <article class="rounded-[14px] border border-[#e3e9f0] p-4">
                <h3 class="m-0 mb-3 text-[13px] font-extrabold text-[#0b2b50]">Dokumen Pemeliharaan</h3>
                <div class="grid gap-4 text-[11px]">
                    <div>
                        <h4 class="m-0 mb-2 text-[11px] font-extrabold text-[#405267]">Kondisi Aset</h4>
                        <div class="grid grid-cols-2 gap-3 max-[640px]:grid-cols-1">
                            <div class="rounded-[12px] border border-[#edf1f5] bg-[#f8fbff] p-3">
                                <p class="m-0 mb-2 text-[10px] font-extrabold uppercase tracking-[.04em] text-[#718095]">Kondisi Sebelum</p>
                                @if ($photoBefore)
                                    <button class="group grid w-full gap-2 border-0 bg-transparent p-0 text-left" type="button" data-maintenance-preview-image="{{ $photoBefore }}" data-maintenance-preview-title="Kondisi Sebelum Pemeliharaan" data-maintenance-preview-name="{{ $photoBeforeName }}">
                                        <span class="block h-[145px] overflow-hidden rounded-[12px] border border-[#dce8f5] bg-[#eef7ff]">
                                            <img class="h-full w-full object-cover transition duration-200 group-hover:scale-105" src="{{ $photoBefore }}" alt="Kondisi sebelum pemeliharaan" loading="lazy" onerror="this.closest('button').outerHTML='<div class=&quot;grid h-[145px] place-items-center rounded-[12px] border border-dashed border-[#d8e2ee] bg-white px-3 text-center text-[10px] font-semibold text-[#8a98aa]&quot;>File tidak tersedia</div>'">
                                        </span>
                                        <span class="truncate text-[10px] font-semibold text-[#516176]">{{ $photoBeforeName }}</span>
                                    </button>
                                @else
                                    <div class="grid h-[145px] place-items-center rounded-[12px] border border-dashed border-[#d8e2ee] bg-white px-3 text-center text-[10px] font-semibold text-[#8a98aa]">Foto kondisi sebelum belum tersedia.</div>
                                @endif
                            </div>

                            <div class="rounded-[12px] border border-[#edf1f5] bg-[#f8fbff] p-3">
                                <p class="m-0 mb-2 text-[10px] font-extrabold uppercase tracking-[.04em] text-[#718095]">Kondisi Setelah</p>
                                @if ($photoAfter)
                                    <button class="group grid w-full gap-2 border-0 bg-transparent p-0 text-left" type="button" data-maintenance-preview-image="{{ $photoAfter }}" data-maintenance-preview-title="Kondisi Setelah Pemeliharaan" data-maintenance-preview-name="{{ $photoAfterName }}">
                                        <span class="block h-[145px] overflow-hidden rounded-[12px] border border-[#dce8f5] bg-[#eef7ff]">
                                            <img class="h-full w-full object-cover transition duration-200 group-hover:scale-105" src="{{ $photoAfter }}" alt="Kondisi setelah pemeliharaan" loading="lazy" onerror="this.closest('button').outerHTML='<div class=&quot;grid h-[145px] place-items-center rounded-[12px] border border-dashed border-[#d8e2ee] bg-white px-3 text-center text-[10px] font-semibold text-[#8a98aa]&quot;>File tidak tersedia</div>'">
                                        </span>
                                        <span class="truncate text-[10px] font-semibold text-[#516176]">{{ $photoAfterName }}</span>
                                    </button>
                                @else
                                    <div class="grid h-[145px] place-items-center rounded-[12px] border border-dashed border-[#d8e2ee] bg-white px-3 text-center text-[10px] font-semibold text-[#8a98aa]">Foto kondisi setelah belum tersedia.</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#edf1f5] pt-4">
                        <h4 class="m-0 mb-2 text-[11px] font-extrabold text-[#405267]">Dokumentasi Servis</h4>
                        @if ($serviceDocuments->isNotEmpty())
                            <div class="grid grid-cols-[repeat(auto-fill,minmax(120px,1fr))] gap-3">
                                @foreach ($serviceDocuments as $document)
                                    @php
                                        $fileUrl = $documentUrl($document->path_file);
                                        $fileName = $document->nama_file ?: basename($document->path_file);
                                    @endphp
                                    <button class="group grid gap-2 rounded-[12px] border border-[#edf1f5] bg-[#f8fbff] p-2 text-left transition hover:border-[#b8d7f2]" type="button" data-maintenance-preview-image="{{ $fileUrl }}" data-maintenance-preview-title="Dokumentasi Servis" data-maintenance-preview-name="{{ $fileName }}">
                                        <span class="block h-[110px] overflow-hidden rounded-[10px] border border-[#dce8f5] bg-[#eef7ff]">
                                            <img class="h-full w-full object-cover transition duration-200 group-hover:scale-105" src="{{ $fileUrl }}" alt="Dokumentasi servis" loading="lazy" onerror="this.closest('button').outerHTML='<div class=&quot;grid h-[146px] place-items-center rounded-[12px] border border-dashed border-[#d8e2ee] bg-white px-3 text-center text-[10px] font-semibold text-[#8a98aa]&quot;>File tidak tersedia</div>'">
                                        </span>
                                        <span class="truncate text-[10px] font-semibold text-[#516176]">{{ $fileName }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-[12px] border border-dashed border-[#d8e2ee] bg-[#f8fbff] px-3 py-4 text-[10px] font-semibold text-[#8a98aa]">Belum ada dokumentasi servis.</div>
                        @endif
                    </div>

                    <div class="border-t border-[#edf1f5] pt-4">
                        <h4 class="m-0 mb-2 text-[11px] font-extrabold text-[#405267]">Video & Link Google Drive</h4>
                        <div class="grid gap-3">
                            @if ($videoDocuments->isNotEmpty())
                                <div class="grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] gap-3">
                                    @foreach ($videoDocuments as $document)
                                        @php
                                            $fileUrl = $documentUrl($document->path_file);
                                            $fileName = $document->nama_file ?: basename($document->path_file);
                                        @endphp
                                        <div class="grid gap-2 rounded-[12px] border border-[#edf1f5] bg-[#f8fbff] p-2">
                                            <video class="h-[150px] w-full rounded-[10px] bg-[#0b2b50] object-contain" controls preload="metadata">
                                                <source src="{{ $fileUrl }}">
                                            </video>
                                            <span class="truncate text-[10px] font-semibold text-[#516176]">{{ $fileName }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($maintenance->link_drive_dokumentasi)
                                <a class="inline-flex min-h-[36px] w-max items-center justify-center rounded-[9px] bg-[#eef7ff] px-3 text-[10px] font-extrabold text-[#146bb5] no-underline" href="{{ $maintenance->link_drive_dokumentasi }}" target="_blank" rel="noopener">Buka Link Google Drive</a>
                            @endif

                            @if ($videoDocuments->isEmpty() && ! $maintenance->link_drive_dokumentasi)
                                <div class="rounded-[12px] border border-dashed border-[#d8e2ee] bg-[#f8fbff] px-3 py-4 text-[10px] font-semibold text-[#8a98aa]">Belum ada video atau link Google Drive.</div>
                            @endif
                        </div>
                    </div>

                    <div class="border-t border-[#edf1f5] pt-4">
                        <h4 class="m-0 mb-2 text-[11px] font-extrabold text-[#405267]">Invoice / Bukti Servis</h4>
                        @if ($invoiceDocuments->isNotEmpty())
                            <div class="grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-3">
                                @foreach ($invoiceDocuments as $document)
                                    @php
                                        $fileUrl = $documentUrl($document->path_file);
                                        $fileName = $document->nama_file ?: basename($document->path_file);
                                        $isImage = $isImageDocument($document->path_file);
                                    @endphp
                                    @if ($isImage)
                                        <button class="group grid gap-2 rounded-[12px] border border-[#edf1f5] bg-[#f8fbff] p-2 text-left transition hover:border-[#b8d7f2]" type="button" data-maintenance-preview-image="{{ $fileUrl }}" data-maintenance-preview-title="Invoice / Bukti Servis" data-maintenance-preview-name="{{ $fileName }}">
                                            <span class="block h-[110px] overflow-hidden rounded-[10px] border border-[#dce8f5] bg-[#eef7ff]">
                                                <img class="h-full w-full object-cover transition duration-200 group-hover:scale-105" src="{{ $fileUrl }}" alt="Invoice atau bukti servis" loading="lazy" onerror="this.closest('button').outerHTML='<div class=&quot;grid h-[146px] place-items-center rounded-[12px] border border-dashed border-[#d8e2ee] bg-white px-3 text-center text-[10px] font-semibold text-[#8a98aa]&quot;>File tidak tersedia</div>'">
                                            </span>
                                            <span class="truncate text-[10px] font-semibold text-[#516176]">{{ $fileName }}</span>
                                        </button>
                                    @else
                                        <div class="flex min-w-0 items-center gap-3 rounded-[12px] border border-[#edf1f5] bg-[#f8fbff] p-3">
                                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-[10px] bg-[#fee2e2] text-[10px] font-extrabold text-[#b42318]">PDF</div>
                                            <div class="min-w-0 flex-1">
                                                <p class="m-0 truncate text-[10px] font-extrabold text-[#0b2b50]">{{ $fileName }}</p>
                                                <a class="mt-1 inline-flex rounded-[8px] bg-[#eef7ff] px-3 py-1.5 text-[10px] font-extrabold text-[#146bb5] no-underline" href="{{ $fileUrl }}" target="_blank" rel="noopener">Lihat Invoice</a>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-[12px] border border-dashed border-[#d8e2ee] bg-[#f8fbff] px-3 py-4 text-[10px] font-semibold text-[#8a98aa]">Invoice/bukti servis belum tersedia.</div>
                        @endif
                    </div>
                </div>
            </article>

            <article class="rounded-[14px] border border-[#e3e9f0] p-4">
                <h3 class="m-0 mb-3 text-[13px] font-extrabold text-[#0b2b50]">Riwayat Aktivitas</h3>
                <div class="grid gap-2">
                    @foreach ($timeline as $step)
                        <div class="rounded-[12px] bg-[#f7f9fc] p-3 text-[11px]">
                            <strong class="block text-[#0b2b50]">{{ $step['state'] === 'rejected' ? '✕' : ($step['state'] === 'waiting' ? '⏳' : '✓') }} {{ $step['label'] }}</strong>
                            <small class="text-[#718095]">{{ $step['actor'] ?: '-' }}{{ $step['time'] ? ' • '.$step['time']->translatedFormat('d M Y H:i') : '' }}</small>
                            @if ($step['note'])<p class="m-0 mt-1 text-[#516176]">{{ $step['note'] }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </article>

        </div>
    </section>
</div>

@once
    <div class="fixed inset-0 z-[120] hidden place-items-center bg-[rgb(5_22_38_/_70%)] p-5 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-maintenance-preview-modal role="presentation">
        <section class="grid max-h-[92vh] w-[min(920px,100%)] gap-3 rounded-2xl bg-white p-4 shadow-[0_25px_80px_rgb(2_19_34_/_35%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="maintenance-preview-title">
            <header class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="m-0 text-[15px] font-extrabold text-[#0b2b50]" id="maintenance-preview-title" data-maintenance-preview-title>Preview Dokumen</h2>
                    <p class="m-0 mt-1 truncate text-[11px] text-[#718095]" data-maintenance-preview-name></p>
                </div>
                <button class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border-0 bg-[#f1f5f9] text-xl leading-none text-[#7e8da1]" type="button" data-maintenance-preview-close>&times;</button>
            </header>
            <div class="grid max-h-[78vh] place-items-center overflow-hidden rounded-[14px] bg-[#f6f9fc] p-2">
                <img class="max-h-[76vh] max-w-full rounded-[10px] object-contain" src="" alt="Preview dokumen pemeliharaan" data-maintenance-preview-img>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const syncMaintenanceOtherFields = () => {
                document.querySelectorAll('[data-maintenance-type-select]').forEach((select) => {
                    const field = select.parentElement?.querySelector('[data-maintenance-other-field]');
                    if (!field) return;

                    const isOther = select.value === (select.dataset.otherValue || 'lainnya');
                    field.classList.toggle('hidden', !isOther);
                    field.disabled = !isOther;
                    field.required = isOther;
                    if (!isOther) field.value = '';
                });
            };

            document.addEventListener('change', (event) => {
                if (event.target.matches('[data-maintenance-type-select]')) {
                    syncMaintenanceOtherFields();
                }
            });

            syncMaintenanceOtherFields();

            const modal = document.querySelector('[data-maintenance-preview-modal]');
            if (!modal || modal.dataset.bound === 'true') return;

            const image = modal.querySelector('[data-maintenance-preview-img]');
            const title = modal.querySelector('[data-maintenance-preview-title]');
            const name = modal.querySelector('[data-maintenance-preview-name]');
            const closeButton = modal.querySelector('[data-maintenance-preview-close]');

            const closePreview = () => {
                modal.classList.add('hidden');
                modal.classList.remove('grid');
                image.removeAttribute('src');
                title.textContent = 'Preview Dokumen';
                name.textContent = '';
            };

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-maintenance-preview-image]');
                if (!trigger) return;

                image.src = trigger.dataset.maintenancePreviewImage;
                title.textContent = trigger.dataset.maintenancePreviewTitle || 'Preview Dokumen';
                name.textContent = trigger.dataset.maintenancePreviewName || '';
                modal.classList.remove('hidden');
                modal.classList.add('grid');
            });

            closeButton?.addEventListener('click', closePreview);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closePreview();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) closePreview();
            });

            modal.dataset.bound = 'true';
        });
    </script>
@endonce
