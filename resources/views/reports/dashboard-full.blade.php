<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: 215mm 330mm;
            margin: 15mm 12mm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            color: #172b4d;
            font-size: 12px;
        }

        h1 {
            margin: 0;
            font-size: 16px;
            text-align: center;
        }

        h2 {
            margin: 18px 0 7px;
            font-size: 14px;
            color: #0b2b50;
            page-break-after: avoid;
        }

        h3 {
            margin: 12px 0 6px;
            font-size: 12px;
            color: #0b2b50;
            page-break-after: avoid;
        }

        .subtitle,
        .meta {
            text-align: center;
            color: #52647a;
        }

        .subtitle {
            margin-top: 4px;
            font-size: 12px;
        }

        .meta {
            margin: 10px 0 14px;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #cfd9e5;
            padding: 4px 5px;
            vertical-align: top;
        }

        th {
            background: #eef4fb;
            color: #0b2b50;
            font-size: 12px;
            text-transform: uppercase;
        }

        td {
            font-size: 12px;
        }

        /* Kolom nomor lebih kecil */
        .col-no {
            width: 25px;
            min-width: 25px;
            max-width: 25px;
            padding-left: 2px;
            padding-right: 2px;
            text-align: center;
            white-space: nowrap;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .empty {
            color: #7f8ea1;
            font-style: italic;
        }

        .section {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @php
        $summary = $dashboard['summary'] ?? [];
        $stock = $dashboard['stock_opname_summary'] ?? [];
        $conditions = $dashboard['kondisi_distribution'] ?? [];

        $conditionLabels = [
            'baik' => 'Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_sedang' => 'Rusak Sedang',
            'rusak_berat' => 'Rusak Berat',
            'hilang' => 'Hilang',
        ];

        $activityLabels = [
            'incoming' => 'Barang Masuk',
            'borrowed' => 'Dipinjam',
            'returned' => 'Dikembalikan',
            'maintenance' => 'Pemeliharaan',
            'condition_changed' => 'Perubahan Kondisi',
            'disposed' => 'Dihapus',
        ];

        $number = fn ($value) => number_format((int) $value, 0, ',', '.');
    @endphp

    <h1>LAPORAN DASHBOARD SISTEM INFORMASI MANAJEMEN INVENTORY DAN ASET</h1>
    <div class="subtitle">
        Dinas Komunikasi dan Informatika Kabupaten Siak
    </div>
    <div class="meta">
        Periode: {{ $reportPeriod }}<br>
        Tanggal Cetak: {{ $printedAt }}
    </div>

    <h2>1. Ringkasan Statistik</h2>
    <table>
        <thead>
            <tr>
                <th>Total Jenis Aset</th>
                <th>Total Unit Aset</th>
                <th>Aset Tersedia</th>
                <th>Sedang Dipinjam</th>
                <th>Total Barang Persediaan</th>
                <th>Total Stok Persediaan</th>
                <th>Stok Menipis</th>
                <th>Stok Habis</th>
            </tr>
        </thead>
        <tbody>
            <tr class="center">
                <td>{{ $number($summary['total_jenis_aset'] ?? 0) }}</td>
                <td>{{ $number($summary['total_unit'] ?? 0) }}</td>
                <td>{{ $number($summary['unit_tersedia'] ?? 0) }}</td>
                <td>{{ $number($summary['unit_dipinjam'] ?? 0) }}</td>
                <td>{{ $number($stock['total_barang'] ?? 0) }}</td>
                <td>{{ $number($stock['total_stok'] ?? 0) }}</td>
                <td>{{ $number($stock['stok_menipis'] ?? 0) }}</td>
                <td>{{ $number($stock['stok_habis'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>2. Kartu Inventaris Ruangan</h2>
    <table>
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th>Ruangan</th>
                <th>Kode</th>
                <th>Penanggung Jawab</th>
                <th>Total</th>
                <th>B</th>
                <th>RR</th>
                <th>RS</th>
                <th>RB</th>
                <th>H</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($dashboard['kir_summary']['ruangan'] ?? []) as $index => $room)
                <tr>
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td>{{ $room['nama_ruangan'] }}</td>
                    <td>{{ $room['kode_ruangan'] ?: '-' }}</td>
                    <td>{{ $room['penanggung_jawab'] ?: '-' }}</td>
                    <td class="center">{{ $number($room['jumlah_barang'] ?? 0) }}</td>
                    @foreach ($conditionLabels as $key => $label)
                        <td class="center">{{ $number($room['kondisi'][$key] ?? 0) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty center" colspan="10">
                        Belum ada data KIR.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>3. Daftar Kategori Aset</h2>
    <table>
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th>Kategori</th>
                <th>Kode</th>
                <th>Jenis</th>
                <th>Total Unit</th>
                <th>B</th>
                <th>RR</th>
                <th>RS</th>
                <th>RB</th>
                <th>H</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($dashboard['asset_categories'] ?? []) as $index => $category)
                <tr>
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td>{{ $category['nama_kategori'] }}</td>
                    <td>{{ $category['kode_kategori'] ?: '-' }}</td>
                    <td class="center">{{ $number($category['jenis_count'] ?? 0) }}</td>
                    <td class="center">{{ $number($category['jumlah'] ?? 0) }}</td>
                    @foreach ($conditionLabels as $key => $label)
                        <td class="center">{{ $number($category['kondisi'][$key] ?? 0) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty center" colspan="10">
                        Belum ada kategori aset.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>4. Daftar Barang Berdasarkan Kondisi</h2>
    <table>
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Total Unit</th>
                <th>B</th>
                <th>RR</th>
                <th>RS</th>
                <th>RB</th>
                <th>H</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($dashboard['asset_items_by_condition'] ?? []) as $index => $item)
                <tr>
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td>{{ $item['asset_name'] }}</td>
                    <td>{{ $item['category_name'] }}</td>
                    <td class="center">{{ $number($item['total'] ?? 0) }}</td>
                    @foreach ($conditionLabels as $key => $label)
                        <td class="center">{{ $number($item['conditions'][$key] ?? 0) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty center" colspan="9">
                        Belum ada aset aktif.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>5. Aktivitas Aset — {{ $activityMonth }}</h2>
    <table>
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th>Jenis Aktivitas</th>
                <th>Jumlah Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($activityLabels as $key => $label)
                <tr>
                    <td class="col-no">{{ $loop->iteration }}</td>
                    <td>{{ $label }}</td>
                    <td class="center">{{ $number($activity[$key] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>6. Ringkasan Kondisi Aset</h2>
    <table>
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th>Kondisi</th>
                <th>Jumlah Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($conditionLabels as $key => $label)
                <tr>
                    <td class="col-no">{{ $loop->iteration }}</td>
                    <td>{{ $label }}</td>
                    <td class="center">{{ $number($conditions[$key] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>7. Barang Persediaan Per Kategori</h2>
    @forelse (($dashboard['stock_opname_categories'] ?? []) as $category)
        @if (($category['items'] ?? []) !== [])
            <h3>Kategori {{ $category['nama_kategori'] }}</h3>
            <table>
                <thead>
                    <tr>
                        <th class="col-no">No.</th>
                        <th>Nama Barang</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($category['items'] as $index => $item)
                        <tr>
                            <td class="col-no">{{ $index + 1 }}</td>
                            <td>{{ $item['nama_barang'] }}</td>
                            <td class="right">{{ $number($item['stok']) }}</td>
                            <td>{{ $item['satuan'] }}</td>
                            <td>{{ $item['status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @empty
        <p class="empty">Belum ada barang persediaan.</p>
    @endforelse

    <h2>8. Monitoring Kelengkapan Laporan Aset</h2>
    @foreach (($dashboard['report_monitoring_years'] ?? []) as $monitoring)
        <table>
            <thead>
                <tr>
                    <th>Tahun</th>
                    <th>Jenis Laporan</th>
                    <th>Status</th>
                    <th>Tanggal / Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($monitoring['asset_reports'] as $report)
                    <tr>
                        <td>{{ $monitoring['year'] }}</td>
                        <td>{{ $report['label'] }}</td>
                        <td>
                            {{ ($report['is_complete'] ?? false) ? 'Lengkap' : 'Belum Lengkap' }}
                        </td>
                        <td>
                            {{ $report['detail'] ?? $report['uploaded_at'] ?? '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h3>Periode Rekonsiliasi Bulanan {{ $monitoring['year'] }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($monitoring['reconciliation']['months'] ?? []) as $month)
                    <tr>
                        <td>{{ $month['label'] }}</td>
                        <td>
                            {{ $month['status'] === 'uploaded'
                                ? 'Sudah Diunggah'
                                : ($month['status'] === 'missing'
                                    ? 'Belum Diunggah'
                                    : ($month['status'] === 'current'
                                        ? 'Bulan Berjalan'
                                        : 'Belum Jatuh Tempo')) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <h2>9. Monitoring Kelengkapan Laporan Persediaan</h2>
    @foreach (($dashboard['report_monitoring_years'] ?? []) as $monitoring)
        <table>
            <thead>
                <tr>
                    <th>Tahun</th>
                    <th>Jenis Laporan</th>
                    <th>Status</th>
                    <th>Tanggal / Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($monitoring['inventory_reports']['reports'] ?? []) as $report)
                    <tr>
                        <td>{{ $monitoring['year'] }}</td>
                        <td>{{ $report['label'] }}</td>
                        <td>
                            {{ ($report['is_complete'] ?? false) ? 'Lengkap' : 'Belum Lengkap' }}
                        </td>
                        <td>
                            {{ $report['detail'] ?? $report['uploaded_at'] ?? '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h3>Periode Persediaan Bulanan {{ $monitoring['year'] }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($monitoring['inventory_reports']['monthly']['months'] ?? []) as $month)
                    <tr>
                        <td>{{ $month['label'] }}</td>
                        <td>
                            {{ $month['status'] === 'uploaded'
                                ? 'Sudah Diunggah'
                                : ($month['status'] === 'missing'
                                    ? 'Belum Diunggah'
                                    : ($month['status'] === 'current'
                                        ? 'Bulan Berjalan'
                                        : 'Belum Jatuh Tempo')) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
