@php
    $document ??= null;
    $isCompletion = $document !== null;
    $selectedHolderId = old('asset_holder_id', $document?->asset_holder_id ?? '');
    $selectedHolder = $assetHolders->firstWhere('id', (int) $selectedHolderId);
@endphp
<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="{{ $modalId }}" role="presentation">
    <section class="w-[min(600px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div><h2 class="m-0 text-xl font-extrabold text-[#0b2b50]" id="{{ $modalId }}-title">{{ $isCompletion ? 'Lengkapi Data Berita Acara' : 'Tambah Berita Acara' }}</h2><p class="m-0 mt-1 text-[10px] font-semibold text-[#8290a3]">Data pegawai disimpan sebagai snapshot arsip.</p></div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>
        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ $isCompletion ? route('laporan-dokumen.berita-acara.complete', $document) : route('laporan-dokumen.store', $jenis) }}" enctype="multipart/form-data" data-submit-loading>
            @csrf
            @if ($isCompletion) @method('PATCH') @else <input type="hidden" name="tahun" value="{{ $year }}"><input type="hidden" name="periode" value="berita_acara_bmd"> @endif
            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Tanggal Berita Acara</span><input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0" type="date" name="tanggal_berita_acara" value="{{ old('tanggal_berita_acara', $document?->tanggal_berita_acara?->format('Y-m-d') ?? '') }}" required></label>
            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Pilih Pemegang Aset</span><select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-semibold text-[#34465a] outline-0" name="asset_holder_id" data-berita-acara-holder required><option value="">Pilih pemegang aset</option>@foreach ($assetHolders as $holder)<option value="{{ $holder->id }}" data-name="{{ $holder->nama_pegawai }}" data-position="{{ $holder->jabatan }}" data-status="{{ $holder->is_active ? 'Aktif' : 'Tidak Aktif' }}" data-unit="{{ \App\Enums\WorkUnit::labelFor($holder->unit_kerja) }}" @selected((int) $selectedHolderId === $holder->id)>{{ $holder->nama_pegawai }}</option>@endforeach</select></label>
            <div class="grid grid-cols-2 gap-3 max-[460px]:grid-cols-1">
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Nama Pegawai</span><input class="h-[42px] w-full rounded-[9px] border border-[#e4eaf0] bg-[#f7f9fb] px-3 text-[10px] text-[#5e7084]" readonly data-berita-acara-field="name" value="{{ $selectedHolder?->nama_pegawai ?? '' }}"></label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Jabatan</span><input class="h-[42px] w-full rounded-[9px] border border-[#e4eaf0] bg-[#f7f9fb] px-3 text-[10px] text-[#5e7084]" readonly data-berita-acara-field="position" value="{{ $selectedHolder?->jabatan ?? '' }}"></label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Status Pegawai</span><input class="h-[42px] w-full rounded-[9px] border border-[#e4eaf0] bg-[#f7f9fb] px-3 text-[10px] text-[#5e7084]" readonly data-berita-acara-field="status" value="{{ $selectedHolder ? ($selectedHolder->is_active ? 'Aktif' : 'Tidak Aktif') : '' }}"></label>
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Unit Kerja</span><input class="h-[42px] w-full rounded-[9px] border border-[#e4eaf0] bg-[#f7f9fb] px-3 text-[10px] text-[#5e7084]" readonly data-berita-acara-field="unit" value="{{ $selectedHolder ? \App\Enums\WorkUnit::labelFor($selectedHolder->unit_kerja) : '' }}"></label>
            </div>
            @unless ($isCompletion)
                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Upload Dokumen PDF <small class="font-semibold text-[#8290a3]">(maks. 10 MB)</small></span><input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2.5 text-[10px] text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[10px] file:font-bold file:text-[#146bb5]" type="file" name="file" accept="application/pdf,.pdf" required></label>
            @endunless
            <footer class="mt-[5px] flex justify-end gap-2 border-t border-[#edf1f5] pt-[18px]"><button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button><button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>{{ $isCompletion ? 'Simpan Perubahan' : 'Simpan Berita Acara' }}</span></button></footer>
        </form>
    </section>
</div>
