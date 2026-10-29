@php
    use App\Enums\WorkUnit;
    use App\Models\ProcurementProposal;

    $spendingTypes = ProcurementProposal::spendingTypes();
    $editDetails = old('details', $proposal->details->map(fn ($detail) => [
        'id' => $detail->id,
        'nama_barang' => $detail->nama_barang,
        'spesifikasi' => $detail->spesifikasi,
        'jumlah' => $detail->jumlah,
        'estimasi_harga_satuan' => $detail->estimasi_harga_satuan,
        'link_produk' => $detail->link_produk,
        'keterangan' => $detail->keterangan,
        'penanggung_jawab_id' => $detail->penanggung_jawab_id,
    ])->all());
    $userOptionLabel = fn ($user) => trim($user->name.($user->nip ? ' - '.$user->nip : ''));
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="edit-proposal-{{ $proposal->id }}" role="presentation">
    <section class="w-[min(980px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="edit-proposal-title-{{ $proposal->id }}">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="edit-proposal-title-{{ $proposal->id }}">Edit Usulan Pengadaan</h2><p class="mt-1 text-[11px] text-[#718096]">Perbarui rincian selama usulan masih berstatus diajukan.</p></div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>
        <form class="grid gap-[17px] p-[22px]" method="POST" action="{{ route('web.usulan-pengadaan.update', $proposal) }}" data-submit-loading data-edit-multi-proposal-form="{{ $proposal->id }}">
            @csrf
            @method('PATCH')
            <div class="grid grid-cols-2 gap-4 rounded-[12px] border border-[#dfe7f0] bg-[#f8fafc] p-4 max-[700px]:grid-cols-1">
                <div><span class="block text-[10px] font-bold text-[#718095]">Bidang / Unit Kerja</span><strong class="mt-1 block text-[11px] text-[#0b2b50]">{{ WorkUnit::labelFor($proposal->unit_kerja_snapshot ?: $proposal->requester?->work_unit) }}</strong></div>
                <div><span class="block text-[10px] font-bold text-[#718095]">Pengaju</span><strong class="mt-1 block text-[11px] text-[#0b2b50]">{{ $proposal->requester?->name ?: '-' }}</strong></div>
                @include('usulan-pengadaan.partials.field', ['label' => 'Tahun anggaran', 'name' => 'tahun_anggaran', 'type' => 'number', 'value' => old('tahun_anggaran', $proposal->tahun_anggaran), 'required' => true, 'attrs' => 'min=2000 max=2100'])
                @include('usulan-pengadaan.partials.field', ['label' => 'Tanggal pengajuan', 'name' => 'tanggal_pengajuan', 'type' => 'date', 'value' => old('tanggal_pengajuan', optional($proposal->tanggal_pengajuan)->toDateString()), 'required' => true])
                <fieldset class="col-span-full grid gap-[8px] text-[11px] font-bold text-[#405267]">
                    <legend class="leading-none">Jenis Belanja <span class="text-[#c94c45]">*</span></legend>
                    <div class="mt-2 grid grid-cols-2 gap-3 max-[560px]:grid-cols-1">
                        @foreach ($spendingTypes as $value => $label)
                            <label class="flex min-h-[42px] cursor-pointer items-center gap-[10px] rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-semibold text-[#34465a] transition hover:border-[#9fc5df] hover:bg-[#f8fbfe]">
                                <input class="m-0 h-4 w-4 shrink-0 border-[#8fa0b2] text-[#146bb5] focus:ring-[#146bb5]" type="radio" name="jenis_belanja" value="{{ $value }}" @checked(old('jenis_belanja', $proposal->jenis_belanja) === $value) required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('jenis_belanja') <small class="text-[9px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                </fieldset>
            </div>
            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Keterangan umum <em class="font-normal text-[#8290a3]">(opsional)</em></span><textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf]" name="keterangan_umum">{{ old('keterangan_umum', $proposal->keterangan_umum) }}</textarea></label>
            <div class="flex items-center justify-between gap-3 border-b border-[#edf1f5] pb-3"><div><h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">Rincian Barang</h3><p class="m-0 mt-1 text-[10px] text-[#718095]">Minimal satu barang harus tersisa.</p></div><button class="page-action-button inline-flex min-h-[36px] items-center justify-center rounded-[9px] border border-[#9fc5df] bg-[#e8f3ff] px-3 text-[10px] font-bold text-[#146bb5]" type="button" data-add-edit-detail>+ Tambah Barang</button></div>
            @error('details') <small class="-mt-3 text-[9px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
            <div class="grid gap-4" data-edit-multi-details>
                @foreach ($editDetails as $index => $detail)
                    <article class="grid gap-4 rounded-[12px] border border-[#dfe7f0] p-4" data-edit-multi-detail>
                        <div class="flex items-center justify-between gap-3"><strong class="text-[11px] text-[#0b2b50]" data-edit-detail-heading>Barang {{ $index + 1 }}</strong><button class="text-[12px] font-semibold text-[#c94c45]" type="button" data-remove-edit-detail>Hapus</button></div>
                        <input type="hidden" name="details[{{ $index }}][id]" value="{{ $detail['id'] ?? '' }}">
                        @foreach (['nama_barang', 'spesifikasi', 'jumlah', 'estimasi_harga_satuan', 'link_produk', 'keterangan', 'penanggung_jawab_id'] as $fieldName)
                            @error("details.$index.$fieldName") <small class="-mt-2 text-[9px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                        @endforeach
                        <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">@include('usulan-pengadaan.partials.field', ['label' => 'Nama Barang / Uraian', 'name' => "details[$index][nama_barang]", 'value' => $detail['nama_barang'] ?? '', 'required' => true])@include('usulan-pengadaan.partials.field', ['label' => 'Jumlah', 'name' => "details[$index][jumlah]", 'type' => 'number', 'value' => $detail['jumlah'] ?? 1, 'required' => true, 'attrs' => 'min=1'])</div>
                        <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Spesifikasi</span><textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf]" name="details[{{ $index }}][spesifikasi]" required>{{ $detail['spesifikasi'] ?? '' }}</textarea></label>
                        <div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1">@include('usulan-pengadaan.partials.field', ['label' => 'Harga Satuan', 'name' => "details[$index][estimasi_harga_satuan]", 'type' => 'number', 'value' => $detail['estimasi_harga_satuan'] ?? '', 'required' => true, 'attrs' => 'min=0 step=1'])<label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Penanggung Jawab <em class="font-normal text-[#8290a3]">(opsional)</em></span><select class="h-[42px] rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px]" name="details[{{ $index }}][penanggung_jawab_id]"><option value="">-- Pilih Penanggung Jawab --</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) ($detail['penanggung_jawab_id'] ?? '') === (string) $user->id)>{{ $userOptionLabel($user) }}</option>@endforeach</select></label></div>
                        @include('usulan-pengadaan.partials.field', ['label' => 'Link Produk / Referensi', 'name' => "details[$index][link_produk]", 'type' => 'url', 'value' => $detail['link_produk'] ?? '', 'attrs' => 'placeholder=https://...'])
                        <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Keterangan <em class="font-normal text-[#8290a3]">(opsional)</em></span><textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf]" name="details[{{ $index }}][keterangan]">{{ $detail['keterangan'] ?? '' }}</textarea></label>
                    </article>
                @endforeach
            </div>
            <footer class="flex items-center justify-end gap-2 border-t border-[#edf1f5] pt-4"><button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button><button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Simpan Perubahan</span></button></footer>
        </form>
    </section>
</div>

<template id="edit-multi-detail-template-{{ $proposal->id }}"><article class="grid gap-4 rounded-[12px] border border-[#dfe7f0] p-4" data-edit-multi-detail><div class="flex items-center justify-between gap-3"><strong class="text-[11px] text-[#0b2b50]" data-edit-detail-heading></strong><button class="text-[12px] font-semibold text-[#c94c45]" type="button" data-remove-edit-detail>Hapus</button></div><input type="hidden" name="details[__INDEX__][id]"><div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1"><label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Nama Barang / Uraian</span><input class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" name="details[__INDEX__][nama_barang]" required></label><label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Jumlah</span><input class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" type="number" min="1" name="details[__INDEX__][jumlah]" value="1" required></label></div><label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Spesifikasi</span><textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px]" name="details[__INDEX__][spesifikasi]" required></textarea></label><div class="grid grid-cols-2 gap-4 max-[700px]:grid-cols-1"><label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Harga Satuan</span><input class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" type="number" min="0" step="1" name="details[__INDEX__][estimasi_harga_satuan]" required></label><label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Penanggung Jawab <em class="font-normal text-[#8290a3]">(opsional)</em></span><select class="h-[42px] rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px]" name="details[__INDEX__][penanggung_jawab_id]"><option value="">-- Pilih Penanggung Jawab --</option>@foreach ($users as $user)<option value="{{ $user->id }}">{{ $userOptionLabel($user) }}</option>@endforeach</select></label></div><label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Link Produk / Referensi</span><input class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px]" type="url" name="details[__INDEX__][link_produk]" placeholder="https://..."></label><label class="grid gap-[7px] text-[11px] font-bold text-[#405267]"><span>Keterangan <em class="font-normal text-[#8290a3]">(opsional)</em></span><textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px]" name="details[__INDEX__][keterangan]"></textarea></label></article></template>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-edit-multi-proposal-form="{{ $proposal->id }}"]'); if (!form) return
    const list = form.querySelector('[data-edit-multi-details]'); const template = document.querySelector('#edit-multi-detail-template-{{ $proposal->id }}')
    const renumber = () => list.querySelectorAll('[data-edit-multi-detail]').forEach((row, index) => { row.querySelector('[data-edit-detail-heading]').textContent = `Barang ${index + 1}`; row.querySelectorAll('[name]').forEach((field) => { field.name = field.name.replace(/details\[\d+\]/, `details[${index}]`) }); row.querySelector('[data-remove-edit-detail]').classList.toggle('hidden', list.children.length === 1) })
    form.querySelector('[data-add-edit-detail]').addEventListener('click', () => { const fragment = template.content.cloneNode(true); fragment.querySelectorAll('[name]').forEach((field) => { field.name = field.name.replace('__INDEX__', list.children.length) }); list.append(fragment); renumber() })
    list.addEventListener('click', (event) => { const button = event.target.closest('[data-remove-edit-detail]'); if (!button || list.children.length === 1) return; if (window.confirm('Hapus rincian barang ini dari usulan?')) { button.closest('[data-edit-multi-detail]').remove(); renumber() } })
    renumber()
})
</script>
