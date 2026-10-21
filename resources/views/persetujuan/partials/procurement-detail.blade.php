@php
    use App\Enums\WorkUnit;

    $photoUrl = $data->path_foto ? Storage::disk('public')->url($data->path_foto) : null;
    $total = (float) $data->estimasi_harga_satuan * (int) $data->jumlah;
    $requesterWorkUnit = WorkUnit::labelFor($data->requester?->work_unit);
@endphp

<div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
    @include('persetujuan.partials.detail-item', ['label' => 'Nama barang', 'value' => $data->nama_barang])
    <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]">
        <span class="text-[9px] uppercase text-[#8896a7]">Status usulan</span>
        <strong><span class="loan-status {{ $statusClass($item) }}">{{ $data->status->label() }}</span></strong>
    </div>
    @include('persetujuan.partials.detail-item', ['label' => 'Jumlah', 'value' => $data->jumlah.' unit'])
    @include('persetujuan.partials.detail-item', ['label' => 'Estimasi harga satuan', 'value' => $rupiah($data->estimasi_harga_satuan)])
    @include('persetujuan.partials.detail-item', ['label' => 'Estimasi total', 'value' => $rupiah($total)])
    @include('persetujuan.partials.detail-item', ['label' => 'Pengusul', 'value' => trim(($data->requester?->name ?? '-').($data->requester?->nip ? ' / '.$data->requester->nip : ''))])
    @include('persetujuan.partials.detail-item', ['label' => 'Unit Kerja', 'value' => $requesterWorkUnit])
    @include('persetujuan.partials.detail-item', ['label' => 'Pengguna barang', 'value' => trim(($data->intendedUser?->name ?? '-').($data->intendedUser?->nip ? ' / '.$data->intendedUser->nip : ''))])
    @include('persetujuan.partials.detail-item', ['label' => 'Penanggung jawab', 'value' => trim(($data->responsiblePerson?->name ?? '-').($data->responsiblePerson?->nip ? ' / '.$data->responsiblePerson->nip : ''))])
    <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]">
        <span class="text-[9px] uppercase text-[#8896a7]">Referensi gambar</span>
        <strong class="break-all text-[11px] font-extrabold leading-relaxed text-[#33485d]">
            @if ($data->url_referensi_gambar)
                <a class="text-[#146bb5] underline underline-offset-2" href="{{ $data->url_referensi_gambar }}" target="_blank" rel="noopener noreferrer">Buka link</a>
            @else
                -
            @endif
        </strong>
    </div>
    <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]">
        <span class="text-[9px] uppercase text-[#8896a7]">Link e-katalog</span>
        <strong class="break-all text-[11px] font-extrabold leading-relaxed text-[#33485d]">
            @if ($data->url_ekatalog)
                <a class="text-[#146bb5] underline underline-offset-2" href="{{ $data->url_ekatalog }}" target="_blank" rel="noopener noreferrer">Buka link</a>
            @else
                -
            @endif
        </strong>
    </div>
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal usulan', 'value' => $formatDateTime($data->created_at)])
    @include('persetujuan.partials.detail-item', ['label' => 'Terakhir diperbarui', 'value' => $formatDateTime($data->updated_at)])
</div>

@include('persetujuan.partials.detail-note', ['title' => 'Spesifikasi barang', 'body' => $data->spesifikasi])
@include('persetujuan.partials.detail-note', ['title' => 'Justifikasi teknis / alasan kebutuhan', 'body' => $data->alasan_kebutuhan])

<div class="rounded-[12px] border border-[#e3e9f0] bg-white p-4">
    <strong class="mb-3 block text-[11px] text-[#405267]">Foto barang</strong>
    @if ($photoUrl)
        <a href="{{ $photoUrl }}" target="_blank" rel="noopener noreferrer">
            <img class="max-h-[320px] w-full rounded-[10px] border border-[#edf1f5] object-contain" src="{{ $photoUrl }}" alt="Foto barang usulan pengadaan">
        </a>
    @else
        <p class="m-0 text-[11px] text-[#8290a3]">Foto belum tersedia.</p>
    @endif
</div>

<div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
    @include('persetujuan.partials.detail-item', ['label' => 'Pemeriksa Tim Aset', 'value' => $data->assetTeamReviewer?->name ?: '-'])
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal pemeriksaan Tim Aset', 'value' => $formatDateTime($data->tanggal_pemeriksaan_tim_aset)])
    @include('persetujuan.partials.detail-item', ['label' => 'Catatan Tim Aset', 'value' => $data->catatan_tim_aset ?: '-'])
    @include('persetujuan.partials.detail-item', ['label' => 'Pemeriksa Kasubbag', 'value' => $data->supervisorReviewer?->name ?: '-'])
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal pemeriksaan Kasubbag', 'value' => $formatDateTime($data->tanggal_pemeriksaan_kasubbag)])
    @include('persetujuan.partials.detail-item', ['label' => 'Catatan Kasubbag', 'value' => $data->catatan_kasubbag ?: '-'])
</div>

@if ($data->alasan_penolakan)
    @include('persetujuan.partials.detail-note', ['title' => 'Alasan penolakan', 'body' => $data->alasan_penolakan, 'danger' => true])
@endif
