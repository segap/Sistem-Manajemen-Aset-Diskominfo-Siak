<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 22px 24px; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { color: #0b2b50; font-size: 17px; margin: 0 0 4px; }
        .meta { color: #66758a; margin: 0 0 14px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #eaf2f8; color: #0b2b50; font-size: 8px; text-align: left; }
        th, td { border: 1px solid #dce4ec; padding: 6px; vertical-align: top; }
        .muted { color: #66758a; }
        .center { text-align: center; }
        .footer { color: #66758a; font-size: 8px; margin-top: 12px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">SIMIVA · Tahun {{ $year }} · Diekspor {{ $generatedAt->translatedFormat('d M Y H:i') }} · {{ $documents->count() }} dokumen{{ $search !== '' ? ' · Pencarian: '.$search : '' }}</p>
    <table>
        <thead>
            <tr>
                <th class="center">No.</th>
                <th>Periode / Tahun</th>
                <th>Jenis APBD</th>
                <th>Judul Dokumen</th>
                <th>Nama File</th>
                <th>Tanggal Upload</th>
                <th>Pengunggah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($documents as $index => $document)
                @php
                    $period = $periodLabels[$document->periode] ?? ($months[$document->bulan] ?? $document->periode ?? '-');
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $period }}{{ $document->tahun ? ' '.$document->tahun : '' }}</td>
                    <td>{{ $periodLabels[$document->periode] ?? '-' }}</td>
                    <td>{{ $document->judul ?: '-' }}</td>
                    <td>{{ $document->nama_file_asli }}</td>
                    <td>{{ $document->created_at?->translatedFormat('d M Y H:i') ?: '-' }}</td>
                    <td>{{ $document->uploader?->name ?: '-' }}</td>
                </tr>
            @empty
                <tr><td class="center muted" colspan="7">Data tidak ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="footer">SIMIVA — Rekap daftar dokumen. Path penyimpanan internal tidak ditampilkan.</p>
</body>
</html>
