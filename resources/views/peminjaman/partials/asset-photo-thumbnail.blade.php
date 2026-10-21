@php
    $primaryPhoto = $asset?->thumbnailPhoto;
    $photoPath = $primaryPhoto?->path ?: $asset?->path_foto;
    $photoUrl = $photoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($photoPath) : null;
    $photoAlt = $asset?->nama_aset ? 'Foto '.$asset->nama_aset : 'Foto barang';
@endphp

@if ($photoUrl)
    <button class="mx-auto block h-[56px] w-[56px] overflow-hidden rounded-[9px] border border-[#dce4ec] bg-[#f4f7fa] p-0 transition hover:border-[#6fa9cf] focus:outline-none focus:ring-2 focus:ring-[#6fa9cf]/30" type="button" data-image-preview-open data-image-preview-url="{{ $photoUrl }}" data-image-preview-alt="{{ $photoAlt }}" title="Lihat foto barang" aria-label="Lihat foto {{ $asset?->nama_aset ?: 'barang' }}">
        <img class="h-full w-full object-cover" src="{{ $photoUrl }}" alt="{{ $photoAlt }}" loading="lazy">
    </button>
@else
    <span class="mx-auto grid h-[56px] w-[56px] place-items-center rounded-[9px] border border-dashed border-[#cbd7e3] bg-[#f8fafc] text-[#8291a2]" title="Foto belum tersedia" aria-label="Foto belum tersedia">
        <svg class="h-5 w-5 fill-none stroke-current stroke-[1.8] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="8.5" cy="9" r="1.5" /><path d="m21 15-4.5-4.5L7 20" /></svg>
    </span>
@endif
