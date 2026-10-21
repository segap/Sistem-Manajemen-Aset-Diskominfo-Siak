@php
    use App\Enums\AssetCondition;
    use App\Enums\AssetMaintenanceStatus;
    use App\Enums\AssetMaintenanceType;
    use Illuminate\Support\Facades\Storage;

    $canEditMaintenance ??= in_array(auth()->user()?->role, ['tim_aset', 'super_admin'], true);
    $isSubmitted = $maintenance->status === AssetMaintenanceStatus::Submitted;
    $isApproved = $maintenance->status === AssetMaintenanceStatus::Approved;
    $isInProgress = $maintenance->status === AssetMaintenanceStatus::InProgress;
    $isCompleted = $maintenance->status === AssetMaintenanceStatus::Completed;
    $canEditProcessFields = $isApproved || $isInProgress || $isCompleted;
    $canEditResultFields = $isInProgress || $isCompleted;
    $photoBefore = $maintenance->foto_sebelum ? Storage::disk('public')->url($maintenance->foto_sebelum) : null;
    $photoAfter = $maintenance->foto_setelah ? Storage::disk('public')->url($maintenance->foto_setelah) : null;
    $serviceDocuments = $maintenance->documents->where('jenis_dokumen', 'dokumentasi_servis')->values();
    $videoDocuments = $maintenance->documents->whereIn('jenis_dokumen', ['video_sebelum', 'video_dokumentasi'])->values();
    $invoiceDocuments = $maintenance->documents->where('jenis_dokumen', 'invoice')->values();
@endphp

@if ($canEditMaintenance)
<style>
    [data-edit-maintenance-form] :where(label) {
        font-size: 10px !important;
        line-height: 1.35;
    }

    [data-edit-maintenance-form] :where(input, select, textarea) {
        font-size: 11px !important;
        line-height: 1.4;
    }

    [data-edit-maintenance-form] :where(input::placeholder, textarea::placeholder) {
        font-size: 11px !important;
    }

    [data-edit-maintenance-form] :where(option) {
        font-size: 11px;
    }

    [data-edit-maintenance-form] :where(h3) {
        font-size: 12px !important;
        line-height: 1.35;
    }

    [data-edit-maintenance-form] :where(small) {
        font-size: 10px !important;
        line-height: 1.35;
    }
</style>
<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="edit-maintenance-{{ $maintenance->id }}" role="presentation">
    <section class="w-[min(980px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
        <header class="flex items-start justify-between gap-4 border-b border-[#edf1f5] p-6">
            <div>
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Edit Data Pemeliharaan</h2>
                <p class="m-0 mt-1 text-[11px] text-[#7f8ea1]">{{ $maintenance->asset?->display_code }} - {{ $maintenance->asset?->nama_aset }}</p>
            </div>
            <button class="grid h-10 w-10 place-items-center rounded-xl border-0 bg-[#f1f5f9] text-[#7e8da1]" type="button" data-modal-close>&times;</button>
        </header>

        <form class="grid gap-4 p-6" method="POST" action="{{ route('pemeliharaan-aset.update', $maintenance) }}" enctype="multipart/form-data" data-edit-maintenance-form data-submit-loading>
            @csrf
            @method('PATCH')

            <section class="grid gap-3 rounded-[14px] border border-[#e3e9f0] p-4">
                <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">1. Informasi Pengajuan</h3>
                <div class="rounded-[10px] bg-[#f8fafc] px-3 py-2 text-[11px] font-semibold text-[#516176]">
                    Aset bersifat read-only agar riwayat proses tidak berpindah ke aset lain.
                </div>
                <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                    <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Aset
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#66758a]" value="{{ $maintenance->asset?->display_code }} - {{ $maintenance->asset?->nama_aset }}" readonly>
                    </label>
                    <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Kondisi Sebelum
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#66758a]" value="{{ $maintenance->kondisi_sebelum?->label() }}" readonly>
                    </label>
                </div>
                <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Jumlah Dipelihara
                    <input class="h-[42px] rounded-[9px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#66758a]" value="{{ $maintenance->jumlah_dipelihara ?: 1 }} Unit" readonly>
                    <small class="text-[10px] font-semibold text-[#718095]">Jumlah dan unit aset tidak dapat diubah agar mutasi stok tidak berubah.</small>
                </label>
                <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Keluhan / Kerusakan *
                    <textarea class="min-h-[100px] rounded-[10px] border border-[#dce4ee] px-3 py-3 text-[11px] text-[#34445a]" name="keluhan" required>{{ old('keluhan', $maintenance->keluhan) }}</textarea>
                </label>
                <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Catatan Pengajuan
                    <textarea class="min-h-[80px] rounded-[10px] border border-[#dce4ee] px-3 py-3 text-[11px] text-[#34445a]" name="catatan_pengajuan">{{ old('catatan_pengajuan', $maintenance->catatan_pengajuan) }}</textarea>
                </label>
            </section>

            <section class="grid gap-3 rounded-[14px] border border-[#e3e9f0] p-4">
                <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">2. Verifikasi & Prioritas</h3>
                <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                    <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Prioritas
                        <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="prioritas">
                            <option value="">Belum ditentukan</option>
                            @foreach ($priorities as $value => $label)
                                <option value="{{ $value }}" @selected(old('prioritas', $maintenance->prioritas?->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Status
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#66758a]" value="{{ $maintenance->status->label() }}" readonly>
                    </label>
                </div>
                <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Catatan Verifikasi
                    <textarea class="min-h-[80px] rounded-[10px] border border-[#dce4ee] px-3 py-3 text-[11px] text-[#34445a]" name="catatan_verifikasi">{{ old('catatan_verifikasi', $maintenance->catatan_verifikasi) }}</textarea>
                </label>
            </section>

            @if ($canEditProcessFields)
                <section class="grid gap-3 rounded-[14px] border border-[#e3e9f0] p-4">
                    <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">3. Proses Pemeliharaan</h3>
                    <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Tanggal Mulai
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="tanggal_mulai" type="datetime-local" value="{{ old('tanggal_mulai', optional($maintenance->tanggal_mulai)->format('Y-m-d\\TH:i')) }}">
                        </label>
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Penyedia/Jasa Servis
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="penyedia_servis" value="{{ old('penyedia_servis', $maintenance->penyedia_servis) }}">
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                        <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="jenis_pemeliharaan" data-maintenance-type-select data-other-value="lainnya">
                            <option value="">Jenis Pemeliharaan</option>
                            @foreach ($maintenanceTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('jenis_pemeliharaan', $maintenance->jenis_pemeliharaan?->value ?? AssetMaintenanceType::MaintenanceAndSparePartReplacement->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="jenis_pemeliharaan_lainnya" value="{{ old('jenis_pemeliharaan_lainnya', $maintenance->jenis_pemeliharaan_lainnya) }}" placeholder="Keterangan jenis lainnya" data-maintenance-other-field>
                    </div>
                    <div class="grid grid-cols-3 gap-3 max-[860px]:grid-cols-1">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="nama_pelaksana" value="{{ old('nama_pelaksana', $maintenance->nama_pelaksana) }}" placeholder="Nama pelaksana/teknisi">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="identitas_pelaksana" value="{{ old('identitas_pelaksana', $maintenance->identitas_pelaksana) }}" placeholder="Identitas pelaksana">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="biaya_servis" type="number" min="0" step="1" value="{{ old('biaya_servis', $maintenance->biaya_servis) }}" placeholder="Biaya servis">
                    </div>
                    <textarea class="min-h-[80px] rounded-[9px] border border-[#dce4ee] px-3 py-3 text-[10px] font-semibold text-[#34445a]" name="tindakan_servis" placeholder="Tindakan servis">{{ old('tindakan_servis', $maintenance->tindakan_servis) }}</textarea>
                    <textarea class="min-h-[80px] rounded-[9px] border border-[#dce4ee] px-3 py-3 text-[10px] font-semibold text-[#34445a]" name="komponen_material" placeholder="Komponen/material">{{ old('komponen_material', $maintenance->komponen_material) }}</textarea>
                </section>
            @endif

            @if ($canEditResultFields)
                <section class="grid gap-3 rounded-[14px] border border-[#e3e9f0] p-4">
                    <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">4. Hasil Pemeliharaan</h3>
                    <div class="grid grid-cols-4 gap-3 max-[960px]:grid-cols-1">
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Tanggal Selesai
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="tanggal_selesai" type="datetime-local" value="{{ old('tanggal_selesai', optional($maintenance->tanggal_selesai)->format('Y-m-d\\TH:i')) }}" @disabled($isInProgress)>
                        </label>
                        <select class="h-[42px] self-end rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="fungsi_utama">
                            <option value="">Fungsi Utama</option>
                            @foreach ($functionStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('fungsi_utama', $maintenance->fungsi_utama?->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select class="h-[42px] self-end rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="hasil_pengujian">
                            <option value="">Hasil Pengujian</option>
                            @foreach ($testResults as $value => $label)
                                <option value="{{ $value }}" @selected(old('hasil_pengujian', $maintenance->hasil_pengujian?->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select class="h-[42px] self-end rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="hasil_akhir_pemeliharaan">
                            <option value="">Hasil Akhir</option>
                            @foreach ($finalResults as $value => $label)
                                <option value="{{ $value }}" @selected(old('hasil_akhir_pemeliharaan', $maintenance->hasil_akhir_pemeliharaan?->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Kondisi Setelah
                        @if ($isCompleted)
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#66758a]" value="{{ $maintenance->kondisi_setelah?->label() ?: '-' }}" readonly>
                            <small class="text-[10px] font-semibold text-[#b54708]">Read-only untuk data selesai agar mutasi stok kondisi tidak berjalan ulang.</small>
                        @else
                            <select class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="kondisi_setelah">
                                <option value="">Pilih kondisi hasil</option>
                                @foreach ([AssetCondition::Good, AssetCondition::LightDamage, AssetCondition::MediumDamage, AssetCondition::HeavyDamage] as $condition)
                                    <option value="{{ $condition->value }}" @selected(old('kondisi_setelah', $maintenance->kondisi_setelah?->value) === $condition->value)>{{ $condition->label() }}</option>
                                @endforeach
                            </select>
                        @endif
                    </label>
                    <textarea class="min-h-[90px] rounded-[9px] border border-[#dce4ee] px-3 py-3 text-[10px] font-semibold text-[#34445a]" name="catatan_hasil" placeholder="Catatan hasil">{{ old('catatan_hasil', $maintenance->catatan_hasil) }}</textarea>
                </section>
            @endif

            <section class="grid gap-3 rounded-[14px] border border-[#e3e9f0] p-4">
                <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">5. Dokumentasi</h3>
                <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                    <div class="rounded-[12px] bg-[#f8fbff] p-3 text-[10px] font-semibold text-[#516176]">
                        Kondisi Sebelum: {{ $photoBefore ? basename($maintenance->foto_sebelum) : 'Belum tersedia' }}
                    </div>
                    <div class="rounded-[12px] bg-[#f8fbff] p-3 text-[10px] font-semibold text-[#516176]">
                        Kondisi Setelah: {{ $photoAfter ? basename($maintenance->foto_setelah) : 'Belum tersedia' }}
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                    <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Ganti/Tambah Foto Sebelum (maks. 2 MB)
                        <input class="w-full rounded-[10px] border border-[#dce4ee] bg-white p-3 text-[10px]" type="file" name="foto_sebelum" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-max-file-size="2097152" data-max-file-message="gambar/file lebih dari 2mb">
                    </label>
                    @if ($canEditResultFields)
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Ganti/Tambah Foto Setelah (maks. 2 MB)
                            <input class="w-full rounded-[10px] border border-[#dce4ee] bg-white p-3 text-[10px]" type="file" name="foto_setelah" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-max-file-size="2097152" data-max-file-message="gambar/file lebih dari 2mb">
                        </label>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                    <div class="rounded-[12px] bg-[#f8fbff] p-3 text-[10px] font-semibold text-[#516176]">Dokumentasi servis lama: {{ $serviceDocuments->count() }} file</div>
                    <div class="rounded-[12px] bg-[#f8fbff] p-3 text-[10px] font-semibold text-[#516176]">Invoice lama: {{ $invoiceDocuments->count() }} file</div>
                </div>
                <div class="rounded-[12px] bg-[#f8fbff] p-3 text-[10px] font-semibold text-[#516176]">
                    Video lama: {{ $videoDocuments->count() }} file
                    <span class="ml-2 text-[#718095]">Link Drive: {{ $maintenance->link_drive_dokumentasi ? 'Ada' : 'Belum ada' }}</span>
                </div>
                @if ($canEditResultFields)
                    <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Tambah Dokumentasi Servis (maks. 5 file, 2 MB/file)
                            <input class="w-full rounded-[10px] border border-[#dce4ee] bg-white p-3 text-[10px]" type="file" name="dokumentasi_servis[]" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-max-file-size="2097152" data-max-file-message="gambar/file lebih dari 2mb">
                        </label>
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Tambah Invoice/Bukti Servis (maks. 2 MB)
                            <input class="w-full rounded-[10px] border border-[#dce4ee] bg-white p-3 text-[10px]" type="file" name="invoice" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" data-max-file-size="2097152" data-max-file-message="gambar/file lebih dari 2mb">
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Tambah Video Dokumentasi (maks. 20 MB)
                            <input class="w-full rounded-[10px] border border-[#dce4ee] bg-white p-3 text-[10px]" type="file" name="video_dokumentasi" accept=".mp4,.mov,.webm,video/mp4,video/quicktime,video/webm" data-max-file-size="20971520" data-max-file-message="video lebih dari 20mb">
                        </label>
                        <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">Link Google Drive Dokumentasi
                            <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="link_drive_dokumentasi" value="{{ old('link_drive_dokumentasi', $maintenance->link_drive_dokumentasi) }}" placeholder="https://drive.google.com/...">
                        </label>
                    </div>
                @endif
                <small class="text-[10px] font-semibold text-[#718095]">Jika tidak memilih file baru, dokumen lama tetap dipertahankan.</small>
            </section>

            @if ($isCompleted)
                <section class="grid gap-3 rounded-[14px] border border-[#e3e9f0] p-4">
                    <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">6. Data Berita Acara</h3>
                    <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="nomor_berita_acara" value="{{ old('nomor_berita_acara', $maintenance->nomor_berita_acara) }}" placeholder="Nomor Berita Acara">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="tanggal_berita_acara" type="date" value="{{ old('tanggal_berita_acara', optional($maintenance->tanggal_berita_acara)->format('Y-m-d')) }}">
                    </div>
                    <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="lokasi_pelaksanaan" value="{{ old('lokasi_pelaksanaan', $maintenance->lokasi_pelaksanaan) }}" placeholder="Lokasi pelaksanaan">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="tempat_ttd" value="{{ old('tempat_ttd', $maintenance->tempat_ttd) }}" placeholder="Tempat tanda tangan">
                    </div>
                    <textarea class="min-h-[80px] rounded-[9px] border border-[#dce4ee] px-3 py-3 text-[10px] font-semibold text-[#34445a]" name="dasar_pelaksanaan" placeholder="Dasar pelaksanaan">{{ old('dasar_pelaksanaan', $maintenance->dasar_pelaksanaan) }}</textarea>
                    <div class="grid grid-cols-3 gap-3 max-[860px]:grid-cols-1">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="jabatan_penanggung_jawab" value="{{ old('jabatan_penanggung_jawab', $maintenance->jabatan_penanggung_jawab) }}" placeholder="Jabatan penanggung jawab">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="nama_penanggung_jawab" value="{{ old('nama_penanggung_jawab', $maintenance->nama_penanggung_jawab) }}" placeholder="Nama penanggung jawab">
                        <input class="h-[42px] rounded-[9px] border border-[#dce4ee] px-3 text-[10px] font-semibold text-[#34445a]" name="nip_penanggung_jawab" value="{{ old('nip_penanggung_jawab', $maintenance->nip_penanggung_jawab) }}" placeholder="NIP penanggung jawab">
                    </div>
                </section>
            @endif

            <footer class="mt-2 flex justify-end gap-2 border-t border-[#edf1f5] pt-5">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit">Simpan Perubahan</button>
            </footer>
        </form>
    </section>
</div>
@endif
