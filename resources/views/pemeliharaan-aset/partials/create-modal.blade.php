@php
    $canSubmit ??= in_array(auth()->user()?->role, ['admin_bidang', 'tim_aset', 'super_admin'], true);
@endphp

@if ($canSubmit)
<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="create-maintenance" role="presentation">
    <section class="w-[min(760px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
        <header class="flex items-start justify-between gap-4 border-b border-[#edf1f5] p-6">
            <div>
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Ajukan Pemeliharaan</h2>
                <p class="m-0 mt-1 text-[11px] text-[#7f8ea1]">Pilih satu unit aset rusak yang tersedia untuk dipelihara.</p>
            </div>
            <button class="grid h-10 w-10 place-items-center rounded-xl border-0 bg-[#f1f5f9] text-[#7e8da1]" type="button" data-modal-close>&times;</button>
        </header>
        <form class="grid gap-4 p-6" method="POST" action="{{ route('pemeliharaan-aset.store') }}" enctype="multipart/form-data" data-submit-loading>
            @csrf
            <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">
                Unit Aset *
                <select class="h-[46px] rounded-[10px] border border-[#dce4ee] bg-white px-3 text-[11px] font-semibold text-[#34445a]" name="maintenance_unit" required>
                    <option value="">Pilih unit aset rusak yang tersedia</option>
                    @foreach ($maintenanceOptions as $option)
                        <option value="{{ $option['value'] }}" @selected(old('maintenance_unit') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <small class="text-[10px] font-semibold text-[#718095]">Satu pengajuan hanya memproses 1 unit. Unit yang sedang diajukan/disetujui/dipelihara tidak akan muncul lagi.</small>
            </label>
            <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">
                Keluhan / Kerusakan *
                <textarea class="min-h-[120px] rounded-[10px] border border-[#dce4ee] px-3 py-3 text-[11px] text-[#34445a]" name="keluhan" required placeholder="Contoh: printer tidak dapat menarik kertas...">{{ old('keluhan') }}</textarea>
            </label>
            <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">
                Catatan
                <textarea class="min-h-[90px] rounded-[10px] border border-[#dce4ee] px-3 py-3 text-[11px] text-[#34445a]" name="catatan_pengajuan">{{ old('catatan_pengajuan') }}</textarea>
            </label>
            <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">
                Foto Kondisi Sebelum Servis (JPG/PNG/WEBP, maks. 2 MB)
                <input class="w-full rounded-[10px] border border-[#dce4ee] bg-white p-3 text-[11px] text-[#0b2b50] file:mr-3 file:rounded-[9px] file:border-0 file:bg-[#eef7ff] file:px-4 file:py-2.5 file:text-[10px] file:font-extrabold file:text-[#146bb5]" type="file" name="foto_sebelum" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-max-file-size="2097152" data-max-file-message="gambar/file lebih dari 2mb">
            </label>
            <div class="grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
                <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">
                    Video Kondisi Sebelum (MP4/MOV/WEBM, maks. 20 MB)
                    <input class="w-full rounded-[10px] border border-[#dce4ee] bg-white p-3 text-[11px] text-[#0b2b50] file:mr-3 file:rounded-[9px] file:border-0 file:bg-[#eef7ff] file:px-4 file:py-2.5 file:text-[10px] file:font-extrabold file:text-[#146bb5]" type="file" name="video_sebelum" accept=".mp4,.mov,.webm,video/mp4,video/quicktime,video/webm" data-max-file-size="20971520" data-max-file-message="video lebih dari 20mb">
                </label>
                <label class="grid gap-2 text-[11px] font-extrabold text-[#405267]">
                    Link Google Drive Dokumentasi
                    <input class="h-[46px] rounded-[10px] border border-[#dce4ee] bg-white px-3 text-[11px] font-semibold text-[#34445a]" name="link_drive_dokumentasi" value="{{ old('link_drive_dokumentasi') }}" placeholder="https://drive.google.com/...">
                </label>
            </div>
            <footer class="mt-2 flex justify-end gap-2 border-t border-[#edf1f5] pt-5">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit">Ajukan</button>
            </footer>
        </form>
    </section>
</div>
@endif
