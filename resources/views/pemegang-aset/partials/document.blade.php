@php
    use App\Enums\WorkUnit;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pegawai - {{ $holder->nama_pegawai }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; color: #1f2937; font-size: 9px; margin: 0; }
        h1 { margin: 0; text-align: center; font-size: 17px; color: #0b2b50; }
        .subtitle { margin: 4px 0 15px; text-align: center; color: #52657b; font-size: 10px; }
        h2 { margin: 17px 0 7px; border-bottom: 1px solid #b9c8d8; padding-bottom: 5px; color: #0b2b50; font-size: 11px; }
        .profile { width: 100%; border-collapse: collapse; }
        .profile td { width: 25%; border: 1px solid #cbd5e1; padding: 7px; vertical-align: top; }
        .profile .photo { width: 100px; text-align: center; vertical-align: middle; }
        .photo img { display: block; max-width: 76px; max-height: 76px; margin: 0 auto; border: 1px solid #cbd5e1; }
        .photo .placeholder { display: inline-block; width: 76px; height: 76px; padding-top: 22px; border: 1px solid #cbd5e1; background: #f1f5f9; color: #718095; font-size: 24px; font-weight: bold; }
        .label { display: block; margin-bottom: 3px; color: #64748b; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .value { color: #0f2f52; font-size: 9px; font-weight: bold; word-break: break-word; }
        table.assets { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .assets th, .assets td { border: 1px solid #94a3b8; padding: 5px; vertical-align: top; word-wrap: break-word; }
        .assets th { background: #1f4e78; color: #fff; font-size: 8px; text-align: left; }
        .assets tr { page-break-inside: avoid; }
        .assets .no { width: 4%; text-align: center; }
        .assets .code { width: 12%; }
        .assets .name { width: 17%; }
        .assets .category { width: 14%; }
        .assets .condition { width: 12%; }
        .assets .location { width: 23%; }
        .assets .status { width: 18%; }
        .empty { border: 1px solid #cbd5e1; padding: 14px; text-align: center; color: #64748b; }
        .total { margin-top: 9px; text-align: right; color: #0b2b50; font-size: 10px; font-weight: bold; }
        .generated { margin-top: 18px; text-align: right; color: #64748b; font-size: 8px; }
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
    <h1>DATA PEGAWAI</h1>
    <div class="subtitle">Dinas Komunikasi dan Informatika Kabupaten Siak</div>

    <h2>A. Informasi Pegawai</h2>
    <table class="profile">
        <tr>
            <td class="photo" rowspan="2">
                @if ($photoDataUri && $photoDimensions)
                    <img src="{{ $photoDataUri }}" alt="Foto {{ $holder->nama_pegawai }}" width="{{ $photoDimensions['width'] }}" height="{{ $photoDimensions['height'] }}">
                @else
                    <span class="placeholder">{{ mb_substr($holder->nama_pegawai, 0, 1) }}</span>
                @endif
            </td>
            <td><span class="label">Nama Pegawai</span><span class="value">{{ $holder->nama_pegawai }}</span></td>
            <td><span class="label">Status</span><span class="value">{{ $holder->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td><span class="label">NIP</span><span class="value">{{ $holder->nip ?: '-' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Unit Kerja</span><span class="value">{{ WorkUnit::labelFor($holder->unit_kerja) }}</span></td>
            <td><span class="label">Jabatan</span><span class="value">{{ $holder->jabatan }}</span></td>
            <td><span class="label">Nomor HP</span><span class="value">{{ $holder->nomor_hp ?: '-' }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Email</span><span class="value">{{ $holder->email ?: '-' }}</span></td>
            <td colspan="2"><span class="label">Keterangan Privasi</span><span class="value">NIK dan dokumen KTP tidak dicantumkan dalam dokumen ini.</span></td>
        </tr>
    </table>

    <h2>B. Daftar Aset yang Dipegang</h2>
    @if ($holderAssets->isNotEmpty())
        <table class="assets">
            <thead><tr><th class="no">No</th><th class="code">Kode Internal</th><th class="name">Nama Aset</th><th class="category">Kategori</th><th class="condition">Kondisi</th><th class="location">Lokasi</th><th class="status">Status Aset</th></tr></thead>
            <tbody>
                @foreach ($holderAssets as $index => $asset)
                    <tr>
                        <td class="no">{{ $index + 1 }}</td><td>{{ $asset->kode_internal ?: '-' }}</td><td>{{ $asset->nama_aset }}</td><td>{{ $asset->category?->nama_kategori ?: '-' }}</td><td>{{ $asset->kondisi?->label() ?: '-' }}</td><td>{{ $asset->lokasi_penyimpanan ?: '-' }}</td><td>{{ $asset->tanggal_penghapusan ? 'Dinonaktifkan' : 'Aktif' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="total">Total Aset yang Dipegang: {{ $holderAssets->count() }} Unit</div>
    @else
        <div class="empty">Belum ada aset yang tercatat.</div>
    @endif
    <div class="generated">Dicetak dari SIMIVA pada {{ $generatedAt->format('d-m-Y H:i') }} WIB</div>
    @if ($autoPrint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
