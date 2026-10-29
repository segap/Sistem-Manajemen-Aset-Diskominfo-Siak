@php
    use App\Enums\ProcurementProposalStatus;
    use App\Enums\WorkUnit;
    $isWaitingAssetTeam = $proposal->status === ProcurementProposalStatus::Submitted;
    $isWaitingSupervisor = $proposal->status === ProcurementProposalStatus::Verified;
    $requesterWorkUnit = WorkUnit::labelFor($proposal->unit_kerja_snapshot ?: $proposal->requester?->work_unit);
    $detailRows = $proposal->details->isNotEmpty()
        ? $proposal->details
        : collect([(object) [
            'nomor_urut' => 1,
            'nama_barang' => $proposal->nama_barang,
            'spesifikasi' => $proposal->spesifikasi,
            'jumlah' => $proposal->jumlah,
            'estimasi_harga_satuan' => $proposal->estimasi_harga_satuan,
            'link_produk' => $proposal->url_ekatalog ?: $proposal->url_referensi_gambar,
            'keterangan' => $proposal->alasan_kebutuhan,
            'responsiblePerson' => $proposal->responsiblePerson,
        ]]);
    $totalEstimate = $detailRows->sum(fn ($detail) => (float) $detail->estimasi_harga_satuan * (int) $detail->jumlah);
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
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Jumlah jenis barang', 'value' => $detailRows->count().' jenis'])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Estimasi total', 'value' => $rupiah($totalEstimate)])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Pengusul', 'value' => $proposal->requester?->name])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Unit Kerja', 'value' => $requesterWorkUnit])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Tahun anggaran', 'value' => $proposal->tahun_anggaran ?: '-'])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Jenis belanja', 'value' => $proposal->jenis_belanja_label])
                @include('usulan-pengadaan.partials.detail-item', ['label' => 'Tanggal pengajuan', 'value' => $formatDateTime($proposal->tanggal_pengajuan ?: $proposal->created_at)])
            </div>

            @if ($proposal->keterangan_umum)
                @include('usulan-pengadaan.partials.detail-note', ['title' => 'Keterangan umum', 'body' => $proposal->keterangan_umum])
            @endif

            <div class="overflow-hidden rounded-[12px] border border-[#dfe7f0] bg-white">
                <div class="border-b border-[#e8eef4] px-4 py-3 text-[12px] font-extrabold text-[#0b2b50]">Rincian Barang</div>
                <div class="overflow-x-auto">
                    <table class="data-table min-w-[780px]">
                        <thead>
                            <tr>
                                <th class="number-column">No</th>
                                <th class="min-w-[230px]">Barang / Spesifikasi</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-right">Harga Satuan</th>
                                <th class="text-center">Referensi</th>
                                <th>Penanggung Jawab</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detailRows as $detail)
                                <tr>
                                    <td class="number-column">{{ $detail->nomor_urut }}</td>
                                    <td class="align-middle">
                                        <strong class="block text-[#2c4159]">{{ $detail->nama_barang }}</strong>
                                        <span class="mt-1 block whitespace-pre-line text-[#66758a]">{{ $detail->spesifikasi }}</span>
                                        @if ($detail->keterangan)
                                            <span class="mt-1 block whitespace-pre-line text-[#66758a]">{{ $detail->keterangan }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">{{ $detail->jumlah }}</td>
                                    <td class="text-right align-middle whitespace-nowrap">{{ $rupiah($detail->estimasi_harga_satuan) }}</td>
                                    <td class="text-center align-middle">
                                        @if ($detail->link_produk)
                                            <a class="inline-flex items-center justify-center gap-1 font-bold text-[#146bb5] no-underline hover:text-[#0c568f] hover:underline" href="{{ $detail->link_produk }}" target="_blank" rel="noopener noreferrer">
                                                Buka Link
                                                <svg class="h-3 w-3 fill-none stroke-current stroke-[2]" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 5h5v5M19 5l-8 8M19 14v4a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h4" /></svg>
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ $detail->responsiblePerson?->name ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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

            <article class="rounded-[14px] border border-[#e3e9f0] p-4">
                <h3 class="m-0 mb-3 text-[13px] font-extrabold text-[#0b2b50]">Riwayat Aktivitas</h3>
                <div class="grid gap-2">
                    @foreach ($timeline as $step)
                        <div class="rounded-[12px] bg-[#f7f9fc] p-3 text-[11px]">
                            <strong class="block text-[#0b2b50]">{{ $step['state'] === 'rejected' ? '✕' : ($step['state'] === 'waiting' ? '⏳' : '✓') }} {{ $step['label'] }}</strong>
                            <small class="text-[#718095]">{{ $step['actor'] ?: '-' }}{{ $step['time'] ? ' • '.$step['time']->translatedFormat('d M Y H:i') : '' }}</small>
                            @if ($step['note'])<p class="m-0 mt-1 text-[#516176]">{{ $step['note'] }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </article>

            <footer class="mt-[5px] flex justify-end border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Tutup</button>
            </footer>
        </div>
    </section>
</div>
