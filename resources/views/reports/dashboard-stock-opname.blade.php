<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 22px; }
        body { font-family: "Times New Roman", Times, serif; color: #172b4d; font-size: 10px; }
        h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .subtitle { margin-top: 4px; font-size: 10px; color: #52647a; }
        .meta { margin: 12px 0 14px; font-size: 9px; color: #52647a; }
        h2 { margin: 18px 0 7px; font-size: 12px; color: #0b2b50; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #d8e2ee; padding: 6px; vertical-align: top; }
        th { background: #eef4fb; color: #0b2b50; text-align: left; font-size: 12px; text-transform: uppercase; }
        td { font-size: 12px; }
        .empty { color: #7f8ea1; font-style: italic; }
    </style>
</head>
<body>
    <h1>Laporan Barang Persediaan</h1>
    <div class="subtitle">Dinas Komunikasi dan Informatika Kabupaten Siak</div>
    <div class="meta">Tanggal Cetak: {{ $printedAt }}</div>

    @forelse ($categories as $category)
        @if (($category['items'] ?? []) !== [])
            <h2>Kategori {{ $category['nama_kategori'] }}</h2>
            <table>
                <colgroup>
                    <col style="width: 5%;">
                    <col style="width: 45%;">
                    <col style="width: 25%;">
                    <col style="width: 25%;">
                </colgroup>
                <thead>
                    <tr>
                        <th style="text-align: center;">No.</th>
                        <th>Nama Barang</th>
                        <th>Stok Tersedia</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($category['items'] as $index => $item)
                        <tr>
                            <td style="text-align: center;">{{ $index + 1 }}</td>
                            <td>{{ $item['nama_barang'] }}</td>
                            <td>{{ number_format((int) $item['stok'], 0, ',', '.') }} {{ $item['satuan'] }}</td>
                            <td>{{ $item['status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @empty
        <p class="empty">Belum ada barang persediaan yang dapat ditampilkan.</p>
    @endforelse
</body>
</html>
