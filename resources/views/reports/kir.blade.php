<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KIR {{ $ruangan->nama_ruangan }}</title>
    <style>
        @page { margin: 18px 22px; }
        body {
            color: #111;
            font-family: "Times New Roman", Times, serif;
            font-size: 8px;
            line-height: 1.2;
        }
        .title {
            margin: 0;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .subtitle {
            margin: 2px 0 18px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
        }
        .meta {
            width: 100%;
            margin-bottom: 8px;
            border-collapse: collapse;
        }
        .meta td {
            padding: 1px 2px;
            vertical-align: top;
        }
        .meta-label {
            width: 120px;
            font-weight: bold;
        }
        .meta-separator {
            width: 8px;
            text-align: center;
        }
        .kir-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .kir-table th,
        .kir-table td {
            border: 1px solid #222;
            padding: 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }
        .kir-table .number-cell {
            width: 18px;
            padding-left: 1px;
            padding-right: 1px;
            text-align: center;
        }
        .kir-table th {
            text-align: center;
            font-size: 7px;
            font-weight: bold;
        }
        .kir-table td {
            font-size: 7px;
        }
        .center { text-align: center; }
        .italic { font-style: italic; }
        .note {
            margin: 4px 0 22px;
            font-size: 7px;
            font-weight: bold;
        }
        .signature {
            width: 100%;
            border-collapse: collapse;
        }
        .signature td {
            width: 50%;
            vertical-align: top;
            font-size: 7px;
        }
        .signature-right {
            text-align: center;
            padding-left: 160px;
        }
        .signature-space {
            height: 52px;
        }
        .underline {
            text-decoration: underline;
            font-weight: bold;
        }
    </style>
</head>
<body>
    @php
        $pengurus = auth()->user();
    @endphp

    <h1 class="title">Kartu Inventaris Ruangan (KIR)</h1>
    <p class="subtitle">BMD</p>

    <table class="meta">
        <tr>
            <td class="meta-label">Pengguna Barang</td>
            <td class="meta-separator">:</td>
            <td>{{ $kir->pengguna_barang }}</td>
        </tr>
        <tr>
            <td class="meta-label">Kode Lokasi</td>
            <td class="meta-separator">:</td>
            <td>{{ $kir->kode_lokasi ?: '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Nama Ruangan</td>
            <td class="meta-separator">:</td>
            <td><strong>{{ $ruangan->nama_ruangan }}</strong></td>
        </tr>
    </table>

    <table class="kir-table">
        <thead>
            <tr>
                <th rowspan="2" class="number-cell">No.</th>
                <th rowspan="2" style="width: 58px;">NIBAR</th>
                <th rowspan="2" style="width: 150px;">Nomor Register</th>
                <th rowspan="2" style="width: 145px;">Kode Lokasi</th>
                <th rowspan="2" style="width: 95px;">Kode Barang</th>
                <th rowspan="2" style="width: 95px;">Nama Barang</th>
                <th rowspan="2" style="width: 100px;">Spesifikasi Nama Barang</th>
                <th colspan="2">Spesifikasi Barang</th>
                <th rowspan="2" style="width: 55px;">Jumlah</th>
                <th rowspan="2" style="width: 55px;">Satuan</th>
                <th rowspan="2" style="width: 55px;">Ket.</th>
            </tr>
            <tr>
                <th style="width: 78px;">Merek/Tipe</th>
                <th style="width: 70px;">Tahun Perolehan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($details as $index => $detail)
                <tr>
                    <td class="number-cell">{{ $index + 1 }}</td>
                    <td>{{ $detail->nibar ?: '' }}</td>
                    <td>{{ $detail->nomor_register ?: '' }}</td>
                    <td>{{ $detail->kode_lokasi ?: '' }}</td>
                    <td>{{ $detail->kode_barang ?: '' }}</td>
                    <td>{{ $detail->nama_barang }}</td>
                    <td>{{ $detail->spesifikasi_nama_barang ?: '' }}</td>
                    <td class="center italic">{{ $detail->merek_tipe ?: '' }}</td>
                    <td class="center">{{ $detail->tahun_perolehan ?: '' }}</td>
                    <td class="center">{{ $detail->jumlah }}</td>
                    <td class="center italic">{{ $detail->satuan }}</td>
                    <td class="center italic">{{ $detail->keterangan ?: '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="center">Belum ada data barang pada KIR ruangan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="note">Catatan : Tidak dibenarkan memindahkan barang-barang yang ada pada daftar barang ini tanpa sepengetahuan pengurus barang pengguna/pengurus barang pembantu dan penanggungjawab ruangan</p>

    <table class="signature">
        <tr>
            <td>
                Pengurus Barang Pengguna
                <div class="signature-space"></div>
                <span class="underline">Reza Febri</span><br>
                NIP/PPK. {{ $pengurus?->nip ?: '19920220 202521 1 026' }}
            </td>
            <td class="signature-right">
                Siak, {{ $generatedAt->translatedFormat('d F Y') }}<br>
                Penanggung Jawab Ruangan
                <div class="signature-space"></div>
                <span class="underline">{{ $ruangan->pemegangAset?->nama_pegawai ?? $ruangan->penanggungJawab?->name ?? 'ADITYA WIDYAWAN PRIMA, S.KOM.,M.SI.' }}</span><br>
                NIP. {{ $ruangan->pemegangAset?->nip ?? ($ruangan->penanggungJawab?->nip ?: '19880718 201001 1 008') }}
            </td>
        </tr>
    </table>
</body>
</html>
