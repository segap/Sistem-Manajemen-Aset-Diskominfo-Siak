<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 22px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172b4d; font-size: 10px; }
        h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .subtitle { margin-top: 4px; font-size: 10px; color: #52647a; }
        .meta { margin: 12px 0 14px; font-size: 9px; color: #52647a; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d8e2ee; padding: 6px; vertical-align: top; }
        th { background: #eef4fb; color: #0b2b50; text-align: left; font-size: 9px; text-transform: uppercase; }
        td { font-size: 9px; }
        .empty { text-align: center; color: #7f8ea1; padding: 18px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="subtitle">SIMIVA - Dinas Komunikasi dan Informatika Kabupaten Siak</div>
    <div class="meta">
        Tanggal Cetak: {{ $printedAt }}<br>
        Filter: {{ $filterInfo }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ count($headers) }}">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
