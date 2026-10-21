@php
    use App\Enums\StockOpnameVerificationStatus;

    $number = fn ($value) => number_format((int) $value, 0, ',', '.');
    $stockTotal = fn ($item) => max(
        (int) $item->stok,
        (int) ($item->mutations->max('stok_sebelum') ?? 0),
        (int) ($item->mutations->max('stok_sesudah') ?? 0),
    );
    $stockStatusLabel = fn ($stock) => $stock === 0 ? 'Stok Habis' : ($stock < 5 ? 'Stok Menipis' : 'Tersedia');
    $stockStatusClass = fn ($stock) => $stock === 0 ? 'status-ditolak' : ($stock < 5 ? 'status-diajukan' : 'status-disetujui');
    $verificationClass = fn ($status) => match ($status instanceof StockOpnameVerificationStatus ? $status->value : $status) {
        StockOpnameVerificationStatus::Approved->value => 'status-disetujui',
        StockOpnameVerificationStatus::Rejected->value => 'status-ditolak',
        default => 'status-diajukan',
    };
    $verificationLabel = fn ($status) => match ($status instanceof StockOpnameVerificationStatus ? $status : StockOpnameVerificationStatus::tryFrom((string) $status)) {
        StockOpnameVerificationStatus::Approved => 'Disetujui',
        default => $status instanceof StockOpnameVerificationStatus ? $status->label() : (string) $status,
    };
@endphp

<section class="mt-5 overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
    <div class="flex min-h-[72px] items-center justify-between gap-4 border-b border-[#edf1f5] px-[18px] py-3.5 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h2 class="m-0 text-[16px] font-extrabold text-[#0b2b50]">Barang Persediaan</h2>
            <p class="m-0 mt-1 text-[11px] text-[#66758a]">Data ini diambil dari halaman Stock Opname.</p>
        </div>
        <div class="flex items-center gap-2 max-[580px]:justify-between">
            <span class="text-[10px] font-semibold text-[#8593a5]">{{ $items->count() }} barang</span>
            <a class="page-action-button inline-flex min-h-[34px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('stock-opname.index') }}">Buka Stock Opname</a>
        </div>
    </div>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="number-column">No</th>
                    <th>Kode Internal</th>
                    <th>Nama Barang</th>
                    <th>Kode Barang</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th>Stok</th>
                    <th>Status Stok</th>
                    <th>Verifikasi</th>
                    <th>Lokasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $index => $item)
                    <tr>
                        <td class="number-column">{{ $index + 1 }}</td>
                        <td>
                            <span class="code-badge">{{ $item->kode_internal ?: '-' }}</span>
                            <small class="cell-subtitle">{{ $item->kode_barang_id ? $item->kode_persediaan : 'Tanpa kodefikasi' }}</small>
                        </td>
                        <td><strong>{{ $item->nama_barang }}</strong><small class="cell-subtitle">{{ $item->merek_tipe ?: 'Merek/tipe belum dicatat' }}</small></td>
                        <td>{{ $item->kodeBarang?->kode_barang ?: '-' }}</td>
                        <td>{{ $item->category?->nama_kategori ?: '-' }}</td>
                        <td>{{ $item->satuan }}</td>
                        <td>
                            <strong>{{ $number($item->stok) }}</strong>
                            <small class="cell-subtitle">dari {{ $number($stockTotal($item)) }} tersedia</small>
                        </td>
                        <td><span class="loan-status {{ $stockStatusClass($item->stok) }}">{{ $stockStatusLabel($item->stok) }}</span></td>
                        <td><span class="loan-status {{ $verificationClass($item->status_verifikasi) }}">{{ $verificationLabel($item->status_verifikasi) }}</span></td>
                        <td>{{ $item->lokasi_penyimpanan ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            <div class="table-empty">
                                <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 10h16M4 14h10M4 18h8" /></svg></span>
                                <strong>Barang persediaan belum tersedia</strong>
                                <p>Data dari Stock Opname akan muncul di sini setelah ditambahkan.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
