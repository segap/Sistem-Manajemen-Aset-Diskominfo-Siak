<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Berita Acara Pemeliharaan Aset</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #102d4b; font-size: 11px; line-height: 1.55; }
        h1 { text-align: center; font-size: 18px; margin: 0 0 4px; }
        .subtitle { text-align: center; margin: 0 0 24px; color: #52657a; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td, th { border: 1px solid #d8e2ee; padding: 8px; vertical-align: top; }
        th { width: 32%; text-align: left; background: #f3f7fb; }
        .section { margin-top: 18px; font-weight: bold; font-size: 13px; }
        .signatures { margin-top: 48px; width: 100%; }
        .signatures td { border: 0; text-align: center; width: 50%; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>BERITA ACARA PEMELIHARAAN ASET</h1>
    <p class="subtitle">Sistem Informasi Manajemen Aset Diskominfo Kabupaten Siak</p>

    <p>Pada tanggal {{ now()->translatedFormat('d F Y') }}, telah dibuat berita acara pemeliharaan aset dengan rincian sebagai berikut:</p>

    <div class="section">Informasi Aset</div>
    <table>
        <tr><th>Nama Aset</th><td>{{ $maintenance->asset?->nama_aset }}</td></tr>
        <tr><th>Kode Internal</th><td>{{ $maintenance->asset?->kode_internal ?: '-' }}</td></tr>
        <tr><th>Kode Barang</th><td>{{ $maintenance->asset?->kodeBarang?->kode_barang ?: '-' }}</td></tr>
        <tr><th>Merek/Tipe</th><td>{{ $maintenance->asset?->merek_tipe ?: '-' }}</td></tr>
        <tr><th>Lokasi</th><td>{{ $maintenance->asset?->lokasi_penyimpanan ?: '-' }}</td></tr>
        <tr><th>Kondisi Sebelum</th><td>{{ $maintenance->kondisi_sebelum?->label() }}</td></tr>
    </table>

    <div class="section">Informasi Pemeliharaan</div>
    <table>
        <tr><th>Keluhan</th><td>{{ $maintenance->keluhan }}</td></tr>
        <tr><th>Prioritas</th><td>{{ $maintenance->prioritas?->label() ?: '-' }}</td></tr>
        <tr><th>Tanggal Mulai</th><td>{{ $maintenance->tanggal_mulai?->translatedFormat('d F Y H:i') ?: '-' }}</td></tr>
        <tr><th>Penyedia/Jasa Servis</th><td>{{ $maintenance->penyedia_servis ?: 'Internal / tidak dicatat' }}</td></tr>
        <tr><th>Tindakan Servis</th><td>{{ $maintenance->tindakan_servis ?: '-' }}</td></tr>
        <tr><th>Biaya Servis</th><td>{{ $maintenance->biaya_servis ? 'Rp '.number_format((float) $maintenance->biaya_servis, 0, ',', '.') : '-' }}</td></tr>
        <tr><th>Kondisi Setelah</th><td>{{ $maintenance->kondisi_setelah?->label() }}</td></tr>
        <tr><th>Catatan Hasil</th><td>{{ $maintenance->catatan_hasil }}</td></tr>
        <tr><th>Tanggal Selesai</th><td>{{ $maintenance->tanggal_selesai?->translatedFormat('d F Y H:i') ?: '-' }}</td></tr>
        <tr><th>Petugas/Tim Aset</th><td>{{ $maintenance->completedBy?->name ?: $maintenance->verifier?->name ?: '-' }}</td></tr>
        <tr><th>Pemohon</th><td>{{ $maintenance->applicant?->name ?: '-' }}</td></tr>
    </table>

    <div class="section">Lampiran</div>
    <ul>
        @forelse ($maintenance->documents as $document)
            <li>{{ str($document->jenis_dokumen)->replace('_', ' ')->title() }} - {{ $document->nama_file ?: basename($document->path_file) }}</li>
        @empty
            <li>Tidak ada lampiran tambahan.</li>
        @endforelse
    </ul>

    <table class="signatures">
        <tr>
            <td>
                Pemohon,<br><br><br><br>
                <strong>{{ $maintenance->applicant?->name ?: '........................' }}</strong>
            </td>
            <td>
                Petugas Tim Aset,<br><br><br><br>
                <strong>{{ $maintenance->completedBy?->name ?: $maintenance->verifier?->name ?: '........................' }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
