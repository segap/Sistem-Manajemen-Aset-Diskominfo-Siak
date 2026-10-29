@php
    use App\Enums\StockOpnameVerificationStatus;
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="detail-stock-opname-{{ $item->id }}" role="presentation">
    <section class="w-[min(760px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)]" role="dialog" aria-modal="true">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">{{ $item->nama_barang }}</h2>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>
        <div class="grid gap-[17px] p-[22px]">
            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
                @include('peminjaman.partials.detail-item', ['label' => 'Kode Internal', 'value' => $item->kode_internal ?: '-'])
                @include('peminjaman.partials.detail-item', ['label' => 'Kode Barang Kodefikasi', 'value' => $item->kode_barang_id ? $item->kode_persediaan : 'Tanpa kodefikasi'])
                @include('peminjaman.partials.detail-item', ['label' => 'Kategori', 'value' => $item->category?->nama_kategori])
                @include('peminjaman.partials.detail-item', ['label' => 'Satuan', 'value' => $item->satuan])
                @include('peminjaman.partials.detail-item', ['label' => 'Stok', 'value' => $item->stok.' '.$item->satuan])
                @include('peminjaman.partials.detail-item', ['label' => 'Lokasi', 'value' => $item->lokasi_penyimpanan ?: '-'])
                @include('peminjaman.partials.detail-item', ['label' => 'Ditambahkan oleh', 'value' => $item->requester?->name ?: '-'])
                @include('peminjaman.partials.detail-item', ['label' => 'Tanggal penambahan', 'value' => $formatDateTime($item->created_at)])
            </div>
            @include('peminjaman.partials.detail-note', ['title' => 'Keterangan', 'body' => $item->keterangan ?: 'Tidak ada keterangan.'])
            <h3 class="m-0 text-[11px] font-extrabold text-[#0b2b50]">Hasil Stock Opname Fisik</h3>
            @php
                $physicalChecks = $item->histories()
                    ->with('user')
                    ->where('aksi', 'stock_opname_fisik')
                    ->latest()
                    ->get();
                $resultLabel = fn ($result) => $result === 'sesuai' ? 'Sesuai' : 'Tidak Sesuai';
                $resultClass = fn ($result) => $result === 'sesuai' ? 'status-disetujui' : 'status-ditolak';
            @endphp
            @if ($physicalChecks->isNotEmpty())
                <div class="grid gap-2">
                    @foreach ($physicalChecks as $check)
                        <div class="rounded-[11px] border border-[#e3e9f0] bg-[#f8fbff] p-3 text-[11px] text-[#60708a]">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <strong class="text-[#20364f]">{{ $formatDateTime($check->created_at) }}</strong>
                                <span class="loan-status {{ $resultClass($check->hasil_opname) }}">{{ $resultLabel($check->hasil_opname) }}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 max-[620px]:grid-cols-1">
                                <span>Stok Sistem: <b>{{ number_format((int) $check->stok_sistem, 0, ',', '.') }} {{ $item->satuan }}</b></span>
                                <span>Stok Fisik: <b>{{ number_format((int) $check->stok_fisik, 0, ',', '.') }} {{ $item->satuan }}</b></span>
                                <span>Selisih: <b>{{ ((int) $check->selisih) > 0 ? '+' : '' }}{{ number_format((int) $check->selisih, 0, ',', '.') }} {{ $item->satuan }}</b></span>
                            </div>
                            <small class="mt-2 block text-[#718095]">Pemeriksa: {{ $check->user?->name ?: 'Sistem' }}</small>
                            @if ($check->catatan)
                                <div class="mt-2">{{ $check->catatan }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-[11px] bg-[#f7f9fc] p-4 text-center text-[11px] text-[#718095]">Belum ada hasil stock opname fisik.</div>
            @endif
            <h3 class="m-0 text-[11px] font-extrabold text-[#0b2b50]">Riwayat Stok</h3>
            <div class="overflow-hidden rounded-[11px] border border-[#e3e9f0]">
                @php
                    $stockMutations = $item->mutations()->with('user')->latest('waktu_transaksi')->latest()->get();
                    $mutationLabel = fn ($jenis) => match ($jenis) {
                        'stok_masuk' => 'Stok Masuk',
                        'stock_opname_fisik' => 'Stock Opname Fisik',
                        'permintaan_persediaan' => 'Permintaan Persediaan',
                        default => str((string) $jenis)->replace('_', ' ')->title()->toString(),
                    };
                @endphp
                @if ($stockMutations->isNotEmpty())
                    <div class="table-scroll">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Jumlah</th>
                                    <th>Stok Sebelum</th>
                                    <th>Stok Sesudah</th>
                                    <th>Diproses Oleh</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stockMutations as $mutation)
                                    <tr>
                                        <td>{{ $formatDateTime($mutation->waktu_transaksi ?: $mutation->created_at) }}</td>
                                        <td>{{ $mutationLabel($mutation->jenis) }}</td>
                                        <td>{{ $mutation->jenis === 'stok_masuk' ? '+' : '' }}{{ number_format((int) $mutation->jumlah, 0, ',', '.') }}</td>
                                        <td>{{ number_format((int) $mutation->stok_sebelum, 0, ',', '.') }}</td>
                                        <td>{{ number_format((int) $mutation->stok_sesudah, 0, ',', '.') }}</td>
                                        <td>{{ $mutation->user?->name ?: 'Sistem' }}</td>
                                        <td>{{ $mutation->catatan ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-[11px] bg-[#f7f9fc] p-4 text-center text-[11px] text-[#718095]">Belum ada riwayat transaksi stok.</div>
                @endif
            </div>
            <h3 class="m-0 text-[11px] font-extrabold text-[#0b2b50]">Riwayat</h3>
            <div class="grid gap-2">
                @forelse ($item->histories()->with('user')->latest()->limit(5)->get() as $history)
                    @php
                        $historyStatus = $history->status_sesudah ? StockOpnameVerificationStatus::tryFrom($history->status_sesudah) : null;
                        $historyTitle = $history->aksi === 'verifikasi' && $historyStatus
                            ? 'Verifikasi lama: '.$historyStatus->label()
                            : str_replace('_', ' ', ucfirst($history->aksi));
                    @endphp
                    <div class="rounded-[11px] bg-[#f7f9fc] p-3 text-[11px] text-[#60708a]">
                        <strong class="block text-[#20364f]">{{ $historyTitle }}</strong>
                        <small>{{ $history->user?->name ?: 'Sistem' }} • {{ $formatDateTime($history->created_at) }}</small>
                        @if ($history->stok_sistem !== null)
                            <div>Stok sistem: {{ $history->stok_sistem }}, stok fisik: {{ $history->stok_fisik }}, selisih: {{ $history->selisih }}</div>
                        @endif
                        @if ($history->catatan)
                            <div>{{ $history->catatan }}</div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-[11px] bg-[#f7f9fc] p-4 text-center text-[11px] text-[#718095]">Belum ada riwayat.</div>
                @endforelse
            </div>
            <footer class="mt-[5px] flex justify-end border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Tutup</button>
            </footer>
        </div>
    </section>
</div>
