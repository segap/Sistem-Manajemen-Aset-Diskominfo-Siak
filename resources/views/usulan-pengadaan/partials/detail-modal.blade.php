@php
    use App\Enums\ProcurementProposalStatus;
    use App\Enums\WorkUnit;
    use Illuminate\Support\Facades\Storage;

    $photoUrl = $proposal->path_foto ? Storage::disk('public')->url($proposal->path_foto) : null;
    $totalEstimate = (float) $proposal->estimasi_harga_satuan * (int) $proposal->jumlah;
    $referenceUrl = $proposal->url_referensi_gambar;
    $catalogUrl = $proposal->url_ekatalog;
    $isExternalHttpUrl = fn (?string $url): bool => filter_var($url, FILTER_VALIDATE_URL)
        && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    $hasReferenceUrl = $isExternalHttpUrl($referenceUrl);
    $hasCatalogUrl = $isExternalHttpUrl($catalogUrl);
    $isWaitingAssetTeam = $proposal->status === ProcurementProposalStatus::Submitted;
    $isWaitingSupervisor = $proposal->status === ProcurementProposalStatus::Verified;
    $requesterWorkUnit = WorkUnit::labelFor($proposal->requester?->work_unit);
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="detail-proposal-{{ $proposal->id }}" role="presentation">
    <section class="w-[min(930px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="detail-proposal-title-{{ $proposal->id }}">
        <header class="flex min-h-[83px] items-start justify-between gap-5 border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <p class="m-0 mb-1 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#146bb5]">Usulan Pengadaan</p>
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]" id="detail-proposal-title-{{ $proposal->id }}">Usulan #{{ $proposal->id }}</h2>
                <span class="loan-status status-{{ $proposal->status->value }} mt-2 inline-flex">{{ $proposal->status->label() }}</span>
            </div>
            <button class="inline-flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal">
                <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </header>

        <div class="grid gap-[17px] p-[22px]">
            <div class="grid overflow-hidden rounded-[12px] border border-[#dfe7f0] md:grid-cols-2">
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Barang', 'value' => $proposal->nama_barang])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Jumlah', 'value' => $proposal->jumlah.' unit'])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Estimasi harga satuan', 'value' => $rupiah($proposal->estimasi_harga_satuan)])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Estimasi total', 'value' => $rupiah($totalEstimate)])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Pengusul', 'value' => $proposal->requester?->name])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Unit Kerja', 'value' => $requesterWorkUnit])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Pengguna barang', 'value' => $proposal->intendedUser?->name])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Penanggung jawab', 'value' => $proposal->responsiblePerson?->name])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Tanggal pengajuan', 'value' => $formatDateTime($proposal->created_at)])
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-[12px] border border-[#dfe7f0] bg-white p-4">
                    <span class="mb-2 block text-[9px] font-bold uppercase tracking-[.08em] text-[#8896a7]">URL referensi gambar</span>
                    @if ($hasReferenceUrl)
                        <a class="page-action-button inline-flex min-h-[34px] items-center justify-center gap-1.5 rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#146bb5] no-underline transition hover:border-[#9fc5df] hover:bg-[#f8fafc]" href="{{ $referenceUrl }}" target="_blank" rel="noopener noreferrer">
                            Lihat Referensi
                            <svg class="h-3.5 w-3.5 fill-none stroke-current stroke-[2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 5h5v5M19 5l-8 8M17 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h5" /></svg>
                        </a>
                    @else
                        <p class="m-0 text-[11px] text-[#6e7d91]">Tidak tersedia</p>
                    @endif
                </div>
                <div class="rounded-[12px] border border-[#dfe7f0] bg-white p-4">
                    <span class="mb-2 block text-[9px] font-bold uppercase tracking-[.08em] text-[#8896a7]">URL e-katalog</span>
                    @if ($hasCatalogUrl)
                        <a class="page-action-button inline-flex min-h-[34px] items-center justify-center gap-1.5 rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#146bb5] no-underline transition hover:border-[#9fc5df] hover:bg-[#f8fafc]" href="{{ $catalogUrl }}" target="_blank" rel="noopener noreferrer">
                            Buka E-Katalog
                            <svg class="h-3.5 w-3.5 fill-none stroke-current stroke-[2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 5h5v5M19 5l-8 8M17 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h5" /></svg>
                        </a>
                    @else
                        <p class="m-0 text-[11px] text-[#6e7d91]">Tidak tersedia</p>
                    @endif
                </div>
            </div>

            @include('usulan-pengadaan.partials.detail-note', ['title' => 'Spesifikasi', 'body' => $proposal->spesifikasi])
            @include('usulan-pengadaan.partials.detail-note', ['title' => 'Justifikasi kebutuhan', 'body' => $proposal->alasan_kebutuhan])

            <div class="rounded-[12px] border border-[#dfe7f0] bg-white p-4">
                <span class="mb-3 block text-[11px] font-extrabold text-[#405267]">Foto barang</span>
                @if ($photoUrl)
                    <a href="{{ $photoUrl }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-[12px] border border-[#dfe7f0] bg-[#f7fafc]">
                        <img class="max-h-[320px] w-full object-contain" src="{{ $photoUrl }}" alt="Foto {{ $proposal->nama_barang }}">
                    </a>
                @else
                    <div class="grid min-h-[130px] place-items-center rounded-[12px] bg-[#f4f7fa] text-[11px] font-semibold text-[#7d8ba0]">Foto belum tersedia.</div>
                @endif
            </div>

            <div class="grid overflow-hidden rounded-[12px] border border-[#dfe7f0] md:grid-cols-2">
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Pemeriksa Tim Aset', 'value' => $proposal->assetTeamReviewer?->name])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Tanggal verifikasi Tim Aset', 'value' => $formatDateTime($proposal->tanggal_pemeriksaan_tim_aset)])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Pemeriksa Kasubbag', 'value' => $proposal->supervisorReviewer?->name])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Tanggal keputusan Kasubbag', 'value' => $formatDateTime($proposal->tanggal_pemeriksaan_kasubbag)])
            </div>

            @include('usulan-pengadaan.partials.detail-note', ['title' => 'Catatan Tim Aset', 'body' => $proposal->catatan_tim_aset])
            @include('usulan-pengadaan.partials.detail-note', ['title' => 'Catatan Kasubbag', 'body' => $proposal->catatan_kasubbag])

            @if ($proposal->alasan_penolakan)
                @include('usulan-pengadaan.partials.detail-note', ['title' => 'Alasan penolakan', 'body' => $proposal->alasan_penolakan, 'danger' => true])
            @endif

            @if ($canReviewAssetTeam && $isWaitingAssetTeam)
                <form class="grid gap-3 rounded-[12px] border border-[#dfe7f0] bg-white p-4" method="POST" action="{{ route('web.usulan-pengadaan.keputusan-tim-aset', $proposal) }}" data-submit-loading>
                    @csrf
                    @method('PATCH')
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Catatan Tim Aset</span>
                        <textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="catatan" placeholder="Catatan verifikasi (opsional)"></textarea>
                    </label>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Alasan penolakan</span>
                        <textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="alasan_penolakan" placeholder="Wajib diisi jika usulan ditolak"></textarea>
                    </label>
                    <div class="flex justify-end gap-2">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c94c45] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#a63d37]" type="submit" name="decision" value="{{ ProcurementProposalStatus::Rejected->value }}">Tolak</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#0c568f]" type="submit" name="decision" value="{{ ProcurementProposalStatus::Verified->value }}">Verifikasi</button>
                    </div>
                </form>
            @elseif ($canReviewSupervisor && $isWaitingSupervisor)
                <form class="grid gap-3 rounded-[12px] border border-[#dfe7f0] bg-white p-4" method="POST" action="{{ route('web.usulan-pengadaan.keputusan-kasubbag', $proposal) }}" data-submit-loading>
                    @csrf
                    @method('PATCH')
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Catatan Kasubbag</span>
                        <textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="catatan" placeholder="Catatan persetujuan (opsional)"></textarea>
                    </label>
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Alasan penolakan</span>
                        <textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="alasan_penolakan" placeholder="Wajib diisi jika usulan ditolak"></textarea>
                    </label>
                    <div class="flex justify-end gap-2">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c94c45] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#a63d37]" type="submit" name="decision" value="{{ ProcurementProposalStatus::Rejected->value }}">Tolak</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#0c568f]" type="submit" name="decision" value="{{ ProcurementProposalStatus::Approved->value }}">Setujui</button>
                    </div>
                </form>
            @endif

            <footer class="mt-[5px] flex justify-end border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Tutup</button>
            </footer>
        </div>
    </section>
</div>
