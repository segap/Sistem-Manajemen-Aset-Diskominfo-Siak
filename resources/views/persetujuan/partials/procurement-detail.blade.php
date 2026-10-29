@php
    use App\Enums\WorkUnit;
    use Illuminate\Support\Facades\Storage;

    $hasDetails = $data->details->isNotEmpty();
    $details = $hasDetails
        ? $data->details
        : collect([(object) [
            'nomor_urut' => 1,
            'nama_barang' => $data->nama_barang,
            'spesifikasi' => $data->spesifikasi,
            'jumlah' => $data->jumlah,
            'estimasi_harga_satuan' => $data->estimasi_harga_satuan,
            'link_produk' => $data->url_ekatalog ?: $data->url_referensi_gambar,
            'keterangan' => $data->alasan_kebutuhan,
            'responsiblePerson' => $data->responsiblePerson,
        ]]);
    $total = $details->sum(fn ($detail) => (float) $detail->estimasi_harga_satuan * (int) $detail->jumlah);
    $requesterWorkUnit = WorkUnit::labelFor($data->unit_kerja_snapshot ?: $data->requester?->work_unit);
    $photoUrl = ! $hasDetails && $data->path_foto ? Storage::disk('public')->url($data->path_foto) : null;
@endphp

<div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
    @include('persetujuan.partials.detail-item', ['label' => 'Jenis barang', 'value' => $details->count().' jenis'])
    <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]"><span class="text-[9px] uppercase text-[#8896a7]">Status usulan</span><strong><span class="loan-status {{ $statusClass($item) }}">{{ $data->status->label() }}</span></strong></div>
    @include('persetujuan.partials.detail-item', ['label' => 'Estimasi total', 'value' => $rupiah($total)])
    @include('persetujuan.partials.detail-item', ['label' => 'Pengusul', 'value' => trim(($data->requester?->name ?? '-').($data->requester?->nip ? ' / '.$data->requester->nip : ''))])
    @include('persetujuan.partials.detail-item', ['label' => 'Unit Kerja', 'value' => $requesterWorkUnit])
    @include('persetujuan.partials.detail-item', ['label' => 'Tahun Anggaran', 'value' => $data->tahun_anggaran ?: '-'])
    @include('persetujuan.partials.detail-item', ['label' => 'Jenis Belanja', 'value' => $data->jenis_belanja_label])
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal usulan', 'value' => $formatDateTime($data->tanggal_pengajuan ?: $data->created_at)])
    @include('persetujuan.partials.detail-item', ['label' => 'Terakhir diperbarui', 'value' => $formatDateTime($data->updated_at)])
</div>

@if ($data->keterangan_umum)
    @include('persetujuan.partials.detail-note', ['title' => 'Keterangan umum', 'body' => $data->keterangan_umum])
@endif

<div class="overflow-hidden rounded-[12px] border border-[#e3e9f0] bg-white">
    <div class="border-b border-[#edf1f5] px-4 py-3 text-[12px] font-extrabold text-[#0b2b50]">Rincian Barang</div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-[10px] text-[#405267]">
            <thead class="bg-[#f7f9fc] text-[9px] uppercase tracking-[.05em] text-[#718095]"><tr><th class="px-3 py-2">No</th><th class="px-3 py-2">Barang / Spesifikasi</th><th class="px-3 py-2">Jumlah</th><th class="px-3 py-2">Harga Satuan</th><th class="px-3 py-2">Referensi</th><th class="px-3 py-2">Penanggung Jawab</th></tr></thead>
            <tbody>
                @foreach ($details as $detail)
                    <tr class="border-t border-[#edf1f5] align-top"><td class="px-3 py-3">{{ $detail->nomor_urut }}</td><td class="px-3 py-3"><strong class="block text-[#0b2b50]">{{ $detail->nama_barang ?: '-' }}</strong><span class="mt-1 block whitespace-pre-line text-[#66758a]">{{ $detail->spesifikasi ?: '-' }}</span>@if ($detail->keterangan)<span class="mt-1 block text-[#66758a]">{{ $detail->keterangan }}</span>@endif</td><td class="px-3 py-3">{{ $detail->jumlah ?: '-' }}</td><td class="px-3 py-3">{{ $detail->estimasi_harga_satuan !== null ? $rupiah($detail->estimasi_harga_satuan) : '-' }}</td><td class="px-3 py-3">@if ($detail->link_produk)<a class="font-bold text-[#146bb5] underline" href="{{ $detail->link_produk }}" target="_blank" rel="noopener noreferrer">Buka link</a>@else - @endif</td><td class="px-3 py-3">{{ $detail->responsiblePerson?->name ?: '-' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if ($photoUrl)
    <div class="rounded-[12px] border border-[#e3e9f0] bg-white p-4"><strong class="mb-3 block text-[11px] text-[#405267]">Foto barang legacy</strong><a href="{{ $photoUrl }}" target="_blank" rel="noopener noreferrer"><img class="max-h-[320px] w-full rounded-[10px] border border-[#edf1f5] object-contain" src="{{ $photoUrl }}" alt="Foto barang usulan pengadaan"></a></div>
@endif

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
