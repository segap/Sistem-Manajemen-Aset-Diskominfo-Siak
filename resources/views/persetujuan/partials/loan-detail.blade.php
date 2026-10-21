@php
    use App\Enums\WorkUnit;
    use Illuminate\Support\Facades\Storage;

    $requesterWorkUnit = WorkUnit::labelFor($data->requester?->work_unit);
    $loanLetterUrl = $data->surat_peminjaman ? Storage::disk('public')->url($data->surat_peminjaman) : null;
    $loanLetterName = $data->nama_surat_peminjaman ?: ($data->surat_peminjaman ? basename($data->surat_peminjaman) : null);
    $loanDetails = $data->details->isNotEmpty() ? $data->details : collect([(object) ['asset' => $data->asset, 'kondisi_kembali' => $data->kondisi_kembali, 'catatan_pengembalian' => $data->catatan_pengembalian]]);
@endphp

@include('persetujuan.partials.section-title', ['title' => 'Ringkasan peminjaman'])
<div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
    @include('persetujuan.partials.detail-item', ['label' => 'Nomor pengajuan', 'value' => 'Peminjaman #'.$data->id])
    <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]">
        <span class="text-[9px] uppercase text-[#8896a7]">Status</span>
        <strong><span class="loan-status {{ $statusClass($item) }}">{{ $data->status->label() }}</span></strong>
    </div>
    @include('persetujuan.partials.detail-item', ['label' => 'Jumlah barang', 'value' => $data->itemCount().' unit'])
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal pengajuan', 'value' => $formatDateTime($data->created_at)])
</div>

@include('persetujuan.partials.section-title', ['title' => 'Detail peminjam'])
<div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
    @include('persetujuan.partials.detail-item', ['label' => 'Nama peminjam', 'value' => $data->requester?->name])
    @include('persetujuan.partials.detail-item', ['label' => 'Unit Kerja', 'value' => $requesterWorkUnit])
    @include('persetujuan.partials.detail-item', ['label' => 'NIP', 'value' => $data->requester?->nip ?: '-'])
</div>

@include('persetujuan.partials.section-title', ['title' => 'Detail barang/aset'])
<div class="overflow-x-auto rounded-[11px] border border-[#e3e9f0]">
    <table class="w-full min-w-[620px] border-collapse text-left text-[10px]">
        <thead class="bg-[#f8fafc] text-[9px] uppercase text-[#7c8da3]">
            <tr>
                <th class="px-3 py-2">No</th>
                <th class="px-3 py-2 text-center">Foto Barang</th>
                <th class="px-3 py-2">Kode Internal</th>
                <th class="px-3 py-2">Nama Barang</th>
                <th class="px-3 py-2">Kondisi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#edf1f5]">
            @foreach ($loanDetails as $detail)
                @php($asset = $detail->asset)
                <tr>
                    <td class="px-3 py-2 font-bold text-[#405267]">{{ $loop->iteration }}</td>
                    <td class="px-3 py-2 text-center">@include('peminjaman.partials.asset-photo-thumbnail', ['asset' => $asset])</td>
                    <td class="px-3 py-2"><span class="code-badge">{{ $asset?->display_code ?: '-' }}</span></td>
                    <td class="px-3 py-2"><strong>{{ $asset?->nama_aset ?: '-' }}</strong><small class="cell-subtitle">{{ $asset?->merek_tipe ?: '-' }}</small></td>
                    <td class="px-3 py-2">{{ $asset?->kondisi?->label() ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@include('persetujuan.partials.section-title', ['title' => 'Jadwal peminjaman'])
<div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal pinjam', 'value' => $formatDate($data->tanggal_pinjam)])
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal kembali rencana', 'value' => $formatDate($data->tanggal_kembali)])
</div>

@include('persetujuan.partials.detail-note', ['title' => 'Alasan peminjaman', 'body' => $data->alasan ?: 'Tanpa alasan.'])

<div class="rounded-[11px] border border-[#e3e9f0] bg-white p-[13px]">
    <span class="mb-[7px] block text-[9px] font-semibold uppercase text-[#8896a7]">Surat Peminjaman</span>
    @if ($loanLetterUrl)
        <div class="flex flex-wrap items-center justify-between gap-3">
            <strong class="break-all text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $loanLetterName }}</strong>
            <a class="page-action-button inline-flex min-h-[34px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#146bb5] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ $loanLetterUrl }}" target="_blank" rel="noopener noreferrer">Lihat Surat</a>
        </div>
    @else
        <p class="m-0 text-[11px] font-semibold leading-relaxed text-[#718095]">Surat peminjaman tidak dilampirkan.</p>
    @endif
</div>

@include('persetujuan.partials.section-title', ['title' => 'Riwayat proses'])
<div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
    @include('persetujuan.partials.detail-item', ['label' => 'Diperiksa oleh', 'value' => $data->reviewer?->name ?: '-'])
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal pemeriksaan', 'value' => $formatDateTime($data->tanggal_pemeriksaan)])
    @include('persetujuan.partials.detail-item', ['label' => 'Diserahkan oleh', 'value' => $data->handedOverBy?->name ?: '-'])
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal penyerahan', 'value' => $formatDateTime($data->tanggal_penyerahan)])
    @include('persetujuan.partials.detail-item', ['label' => 'Diterima kembali oleh', 'value' => $data->returnedBy?->name ?: '-'])
    @include('persetujuan.partials.detail-item', ['label' => 'Tanggal pengembalian', 'value' => $formatDateTime($data->tanggal_pengembalian)])
</div>

@if ($data->alasan_penolakan)
    @include('persetujuan.partials.detail-note', ['title' => 'Alasan penolakan', 'body' => $data->alasan_penolakan, 'danger' => true])
@endif
@if ($data->status->value === 'dikembalikan')
    @include('persetujuan.partials.section-title', ['title' => 'Hasil pengembalian barang'])
    <div class="grid gap-2">
        @foreach ($loanDetails as $detail)
            @php($asset = $detail->asset)
            <div class="rounded-[11px] border border-[#e3e9f0] bg-white p-3 text-[11px]">
                <strong class="block text-[#0b2b50]">{{ $asset?->display_code ?: '-' }} - {{ $asset?->nama_aset ?: '-' }}</strong>
                <span class="mt-1 block text-[#718095]">Kondisi kembali: {{ $detail->kondisi_kembali?->label() ?: '-' }}</span>
                <p class="m-0 mt-1 text-[#718095]">{{ $detail->catatan_pengembalian ?: 'Tanpa catatan.' }}</p>
            </div>
        @endforeach
    </div>
@endif
