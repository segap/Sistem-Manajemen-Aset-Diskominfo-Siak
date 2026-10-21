@php
    use App\Enums\WorkUnit;
    use Illuminate\Support\Facades\Storage;

    $photoUrl = $holder->foto_path ? Storage::disk('public')->url($holder->foto_path) : null;
    $holderAssets = $hasAssetHolderColumn ? ($holder->assets ?? collect()) : collect();
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="detail-asset-holder-{{ $holder->id }}" role="presentation">
    <section class="w-[min(820px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]">Detail Pemegang Aset</h2>
                <p class="m-0 mt-1 text-[11px] text-[#7f8ea1]">Informasi pegawai dan penanggung jawab aset.</p>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup">
                <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </header>

        <div class="grid gap-5 p-[22px]">
            <div class="grid grid-cols-[120px_1fr] gap-5 max-[620px]:grid-cols-1">
                <div class="grid h-[112px] w-[112px] place-items-center overflow-hidden rounded-[16px] border border-[#dce4ec] bg-[#f7fafc]">
                    @if ($photoUrl)
                        <img class="h-full w-full object-cover" src="{{ $photoUrl }}" alt="Foto {{ $holder->nama_pegawai }}">
                    @else
                        <span class="text-[30px] font-extrabold text-[#8aa0b8]">{{ mb_substr($holder->nama_pegawai, 0, 1) }}</span>
                    @endif
                </div>
                <div class="grid grid-cols-2 overflow-hidden rounded-[14px] border border-[#dfe7f0] max-[620px]:grid-cols-1">
                    <div class="border-b border-r border-[#edf1f5] p-4 max-[620px]:border-r-0"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">Nama Pegawai</small><strong class="text-[12px] text-[#0b2b50]">{{ $holder->nama_pegawai }}</strong></div>
                    <div class="border-b border-[#edf1f5] p-4"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">Status</small><span class="loan-status {{ $holder->is_active ? 'status-approved' : 'status-rejected' }}">{{ $holder->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
                    <div class="border-b border-r border-[#edf1f5] p-4 max-[620px]:border-r-0"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">NIP</small><strong class="text-[12px] text-[#0b2b50]">{{ $holder->nip ?: '-' }}</strong></div>
                    <div class="border-b border-[#edf1f5] p-4"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">Unit Kerja</small><strong class="text-[12px] text-[#0b2b50]">{{ WorkUnit::labelFor($holder->unit_kerja) }}</strong></div>
                    <div class="border-b border-r border-[#edf1f5] p-4 max-[620px]:border-r-0"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">Jabatan</small><strong class="text-[12px] text-[#0b2b50]">{{ $holder->jabatan }}</strong></div>
                    <div class="border-b border-[#edf1f5] p-4"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">Nomor HP</small><strong class="text-[12px] text-[#0b2b50]">{{ $holder->nomor_hp ?: '-' }}</strong></div>
                    <div class="border-r border-[#edf1f5] p-4 max-[620px]:border-b max-[620px]:border-r-0"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">Email</small><strong class="text-[12px] text-[#0b2b50]">{{ $holder->email ?: '-' }}</strong></div>
                    <div class="p-4"><small class="block text-[9px] font-bold uppercase text-[#7f8ea1]">Dokumen KTP</small>
                        @if ($canViewSensitiveAssetHolderData)
                            <strong class="block text-[12px] text-[#0b2b50]">NIK: {{ $holder->getRawOriginal('nik') ?: '-' }}</strong>
                            @if ($holder->ktp_path)
                                <a class="mt-2 inline-flex min-h-[30px] items-center rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#146bb5] no-underline hover:bg-[#f8fafc]" href="{{ route('pemegang-aset.ktp', $holder) }}">Unduh KTP</a>
                            @else
                                <small class="text-[10px] font-semibold text-[#8a98aa]">Belum ada dokumen.</small>
                            @endif
                        @else
                            <small class="text-[10px] font-semibold text-[#8a98aa]">Data KTP hanya dapat diakses role berwenang.</small>
                        @endif
                    </div>
                </div>
            </div>

            <section class="rounded-[14px] border border-[#dfe7f0] bg-[#f8fbff] p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">Daftar Aset yang Dipegang</h3>
                    <span class="loan-status status-diajukan">{{ $holderAssets->count() }} aset</span>
                </div>

                @if ($holderAssets->isNotEmpty())
                    <div class="mt-3 overflow-x-auto rounded-[12px] border border-[#dfe7f0] bg-white">
                        <table class="min-w-full border-collapse text-left text-[10px] text-[#405267]">
                            <thead class="bg-[#f3f6fa] text-[9px] font-extrabold uppercase tracking-[.02em] text-[#7a8798]">
                                <tr>
                                    <th class="border-b border-[#dfe7f0] px-3 py-2">Kode Internal</th>
                                    <th class="border-b border-[#dfe7f0] px-3 py-2">Nama Aset</th>
                                    <th class="border-b border-[#dfe7f0] px-3 py-2">Kategori</th>
                                    <th class="border-b border-[#dfe7f0] px-3 py-2">Kondisi</th>
                                    <th class="border-b border-[#dfe7f0] px-3 py-2">Lokasi</th>
                                    <th class="border-b border-[#dfe7f0] px-3 py-2">Status Aset</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($holderAssets as $heldAsset)
                                    <tr>
                                        <td class="border-b border-[#edf1f5] px-3 py-2 font-extrabold text-[#146bb5]">{{ $heldAsset->kode_internal ?: '-' }}</td>
                                        <td class="border-b border-[#edf1f5] px-3 py-2 font-bold text-[#0b2b50]">{{ $heldAsset->nama_aset }}</td>
                                        <td class="border-b border-[#edf1f5] px-3 py-2">{{ $heldAsset->category?->nama_kategori ?: '-' }}</td>
                                        <td class="border-b border-[#edf1f5] px-3 py-2">{{ $heldAsset->kondisi?->label() ?: '-' }}</td>
                                        <td class="border-b border-[#edf1f5] px-3 py-2">{{ $heldAsset->lokasi_penyimpanan ?: '-' }}</td>
                                        <td class="border-b border-[#edf1f5] px-3 py-2">
                                            <span class="loan-status {{ $heldAsset->tanggal_penghapusan ? 'status-rejected' : 'status-approved' }}">{{ $heldAsset->tanggal_penghapusan ? 'Dinonaktifkan' : 'Aktif' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="m-0 mt-2 text-[11px] leading-relaxed text-[#66758a]">Belum ada aset yang terhubung ke pemegang aset ini.</p>
                @endif
            </section>

            <footer class="flex flex-wrap justify-end gap-2 border-t border-[#edf1f5] pt-[18px]">
                <a class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('pemegang-aset.print', $holder) }}" target="_blank" rel="noopener" title="Cetak data pemegang aset">
                    <svg class="h-4 w-4 fill-none stroke-current stroke-[1.9]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7Z" /></svg>
                    Cetak
                </a>
                <a class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#146bb5] no-underline transition hover:border-[#9fc5df] hover:bg-[#f8fafc]" href="{{ route('pemegang-aset.pdf', $holder) }}" title="Unduh PDF data pemegang aset">
                    <svg class="h-4 w-4 fill-none stroke-current stroke-[1.9]" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6ZM14 2v6h6M8 16h8M8 12h3" /></svg>
                    Unduh PDF
                </a>
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Tutup</button>
            </footer>
        </div>
    </section>
</div>
