<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18px 20px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 9px; }
        h1, h2, p { margin: 0; }
        h1 { font-size: 15px; text-align: center; font-weight: bold; }
        h2 { font-size: 12px; text-align: center; margin-top: 4px; }
        .meta { margin: 16px 0 10px; font-size: 9px; }
        .meta td { padding: 2px 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        th, td { border: 1px solid #000; padding: 5px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        th { background: #f4d64e; color: #000; font-weight: bold; text-align: center; }
        .center { text-align: center; }
        .right { text-align: right; }
        .link { color: #0645ad; text-decoration: underline; }
        .total-label { font-weight: bold; text-align: right; }
        .total-value { font-weight: bold; text-align: right; }
    </style>
</head>
<body>
    @php
        $formatCurrency = fn ($value) => number_format((float) $value, 2, ',', '.');
        $isValidUrl = fn (?string $url): bool => filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
        $totalEstimate = $details->sum(fn ($detail) => (int) $detail->jumlah * (float) $detail->estimasi_harga_satuan);
    @endphp
    <h1>KEBUTUHAN SARPRAS {{ strtoupper($unitLabel) }}</h1>
    <h2>{{ strtoupper($spendingTypeLabel) }}</h2>
    <h2>TA {{ strtoupper($yearLabel) }}</h2>
    <table class="meta">
        <tr><td width="120">Nomor Usulan</td><td>: #{{ $proposal->id }}</td></tr>
        <tr><td>Tanggal Pengajuan</td><td>: {{ ($proposal->tanggal_pengajuan ?: $proposal->created_at)?->translatedFormat('d F Y') ?: '-' }}</td></tr>
    </table>
    <table>
        <thead>
            <tr>
                <th style="width: 4%">NO</th>
                <th style="width: 18%">URAIAN</th>
                <th style="width: 25%">SPEK</th>
                <th style="width: 7%">JUMLAH</th>
                <th style="width: 13%">HARGA</th>
                <th style="width: 15%">LINK/GAMBAR</th>
                <th style="width: 18%">KETERANGAN/PENANGGUNG JAWAB</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details as $detail)
                <tr>
                    <td class="center">{{ $detail->nomor_urut }}</td>
                    <td>{{ $detail->nama_barang ?: '-' }}</td>
                    <td>{{ $detail->spesifikasi ?: '-' }}</td>
                    <td class="center">{{ $detail->jumlah ?: '-' }}</td>
                    <td class="right">{{ $detail->estimasi_harga_satuan !== null ? $formatCurrency($detail->estimasi_harga_satuan) : '-' }}</td>
                    <td>@if ($isValidUrl($detail->link_produk ?? null))<a class="link" href="{{ $detail->link_produk }}">{{ $detail->link_produk }}</a>@else - @endif</td>
                    <td>@if ($detail->keterangan)<div>{{ $detail->keterangan }}</div>@endif @if ($detail->responsiblePerson?->name)<div><strong>PJ:</strong> {{ $detail->responsiblePerson->name }}</div>@endif @if (! $detail->keterangan && ! $detail->responsiblePerson?->name)-@endif</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="5" class="total-label">TOTAL ESTIMASI ANGGARAN</td>
                <td colspan="2" class="total-value">{{ $formatCurrency($totalEstimate) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
