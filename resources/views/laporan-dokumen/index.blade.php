@extends('layouts.app')

@section('title', $config['title'].' - SIMIVA')

@php
    $formatBytes = function (?int $bytes): string {
        if (! $bytes) {
            return '-';
        }

        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1).' MB'
            : number_format($bytes / 1024, 0).' KB';
    };
    $isMonthlyReport = ($config['table_by_month'] ?? false) === true;
    $isSingleDocumentMonthly = $isMonthlyReport && (($config['single_document_per_period'] ?? false) === true);
    $occupiedPeriods = $isSingleDocumentMonthly ? ($occupiedPeriodsByYear[$year] ?? []) : [];
    $monthlyConflicts = $isSingleDocumentMonthly
        ? collect($slots)->filter(fn ($slot) => $slot['documents']->count() > 1)
        : collect();
    $exportQuery = request()->except('page');
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">{{ $config['title'] }}</h1>
            <p class="m-0 text-[13px] text-[#66758a]">{{ $config['description'] }}</p>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[10px] border border-[#a9e2c9] bg-[#effcf6] px-4 py-3 text-[11px] font-semibold text-[#08714e]" role="status">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
    @endif

    @unless ($schemaReady)
        <div class="mb-4 rounded-[10px] border border-[#fed7aa] bg-[#fff7ed] px-[15px] py-[13px] text-[11px] font-semibold text-[#9a3412]" role="alert">
            {{ ($config['asset_holder_archive'] ?? false) === true
                ? 'Struktur arsip Berita Acara belum tersedia. Jalankan migration Berita Acara terlebih dahulu sebelum menambah atau melengkapi data.'
                : 'Struktur database Laporan Dokumen belum tersedia. Jalankan migration modul laporan dokumen terlebih dahulu sebelum upload PDF.' }}
        </div>
    @endunless

    @if ($jenis === 'rkbmd' || ((($config['type'] ?? null) === 'multi') && (($config['asset_holder_archive'] ?? false) !== true)))
        @php
            $isRkbmd = ($config['rkbmd'] ?? false) === true;
        @endphp
        <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
            @include('laporan-dokumen.partials.filter-toolbar')
            <div class="flex min-h-[72px] items-center justify-between gap-5 border-b border-[#edf1f5] px-[18px] py-3.5 max-[580px]:flex-col max-[580px]:items-stretch">
                <div>
                    <strong class="block text-[12px] text-[#0b2b50]">Dokumen tahun {{ $year }}</strong>
                    <small class="text-[10px] text-[#8290a3]">{{ $documents->total() ? $documents->total().' dokumen tersedia' : 'Dokumen belum tersedia' }}</small>
                </div>
                @if ($canUpload && $schemaReady)
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="upload-report-document">+ Tambah Dokumen PDF</button>
                @endif
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="number-column">No</th>
                            @if ($isRkbmd)
                                <th>Tahun</th>
                                <th>Jenis APBD</th>
                                <th>Judul Dokumen</th>
                                <th>Upload Terakhir</th>
                            @else
                                <th>Judul</th>
                                <th>File</th>
                                <th>Upload</th>
                            @endif
                            <th class="action-column">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $index => $document)
                            <tr>
                                <td class="number-column">{{ $documents->firstItem() + $index }}</td>
                                @if ($isRkbmd)
                                    <td><strong>{{ $document->tahun }}</strong></td>
                                    <td><strong>{{ $config['period_labels'][$document->periode] ?? $document->periode }}</strong></td>
                                    <td><strong>{{ $document->judul ?: $config['title'] }}</strong><small class="cell-subtitle">{{ $document->nama_file_asli }} · {{ $formatBytes($document->ukuran_file) }}</small></td>
                                    <td>{{ $document->created_at?->translatedFormat('d M Y H:i') ?: '-' }}<small class="cell-subtitle">{{ $document->uploader?->name ?: '-' }}</small></td>
                                @else
                                    <td><strong>{{ $document->judul ?: $config['title'] }}</strong><small class="cell-subtitle">Tahun {{ $document->tahun }}</small></td>
                                    <td><strong>{{ $document->nama_file_asli }}</strong><small class="cell-subtitle">{{ $formatBytes($document->ukuran_file) }}</small></td>
                                    <td>{{ $document->created_at?->translatedFormat('d M Y H:i') ?: '-' }}<small class="cell-subtitle">{{ $document->uploader?->name ?: '-' }}</small></td>
                                @endif
                                <td class="action-column">
                                    <div class="row-actions">
                                        <button type="button" title="Lihat PDF" data-modal-open="preview-report-document-{{ $document->id }}" aria-label="Lihat {{ $document->judul ?: $document->nama_file_asli }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                        </button>
                                        @if (! $isRkbmd || $canDownload)
                                            <a href="{{ route('laporan-dokumen.download', $document) }}" title="Unduh PDF" aria-label="Unduh {{ $document->judul ?: $document->nama_file_asli }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 21h14" /></svg>
                                            </a>
                                        @endif
                                        @if ($canDelete)
                                            <button class="delete-action" type="button" title="Hapus Permanen" data-modal-open="delete-report-document-{{ $document->id }}" aria-label="Hapus {{ $document->judul ?: $document->nama_file_asli }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" /></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isRkbmd ? 6 : 5 }}">
                                    <div class="table-empty">
                                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6V3Zm9 0v5h4M9 13h6m-6 4h6" /></svg></span>
                                        <strong>Dokumen belum tersedia</strong>
                                        <p>Belum ada PDF untuk {{ strtolower($config['title']) }} tahun {{ $year }}.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('laporan-dokumen.partials.pagination', ['paginator' => $documents, 'paginationRoute' => route('laporan-dokumen.index', $jenis)])
        </section>
    @elseif (($config['asset_holder_archive'] ?? false) === true)
        @include('laporan-dokumen.partials.berita-acara-table')
    @elseif ($isMonthlyReport)
        @if ($monthlyConflicts->isNotEmpty())
            <div class="mb-4 rounded-[10px] border border-[#fed7aa] bg-[#fff7ed] px-[15px] py-[13px] text-[11px] text-[#9a3412]" role="alert">
                Terdapat dokumen ganda pada {{ $monthlyConflicts->pluck('label')->join(', ') }} {{ $year }}. Tidak ada dokumen yang dipilih otomatis; tentukan dokumen yang akan dipertahankan sebelum PDF dapat dibuka langsung dari tabel.
            </div>
        @endif
        <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
            @include('laporan-dokumen.partials.filter-toolbar')
            <div class="flex min-h-[72px] items-center justify-between gap-5 border-b border-[#edf1f5] px-[18px] py-3.5 max-[580px]:flex-col max-[580px]:items-stretch">
                <div>
                    <strong class="block text-[12px] text-[#0b2b50]">{{ $config['monthly_table_heading'] ?? 'Dokumen' }} {{ $year }}</strong>
                    <small class="text-[10px] text-[#8290a3]">{{ $documents->count() ? $documents->count().' dokumen pada '.count($slots).' bulan' : 'Belum ada dokumen pada tahun ini' }}</small>
                </div>
                @if ($canUpload && $schemaReady)
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="upload-report-document">+ Tambah Dokumen PDF</button>
                @endif
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="number-column">No</th>
                            <th>Bulan</th>
                            <th>{{ $isSingleDocumentMonthly ? 'Nama Dokumen' : 'Jumlah Dokumen' }}</th>
                            <th>Upload Terakhir</th>
                            <th class="action-column">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($slotPagination as $index => $slot)
                            @php
                                $slotDocuments = $slot['documents'];
                                $latestDocument = $slotDocuments->sortByDesc('created_at')->first();
                                $document = $slotDocuments->count() === 1 ? $slotDocuments->first() : null;
                            @endphp
                            <tr>
                                <td class="number-column">{{ $slotPagination->firstItem() + $index }}</td>
                                <td><strong>{{ $slot['label'] }} {{ $year }}</strong><small class="cell-subtitle">{{ $config['monthly_document_label'] ?? $config['title'] }}</small></td>
                                @if ($isSingleDocumentMonthly)
                                    <td>
                                        @if ($document)
                                            <strong>{{ $document->nama_file_asli }}</strong><small class="cell-subtitle">{{ $formatBytes($document->ukuran_file) }}</small>
                                        @else
                                            <strong class="text-[#9a3412]">Dokumen ganda — perlu keputusan</strong><small class="cell-subtitle">{{ $slotDocuments->count() }} PDF tersimpan untuk bulan ini</small>
                                        @endif
                                    </td>
                                @else
                                    <td class="status-column"><span class="loan-status status-disetujui">{{ $slotDocuments->count() }} Dokumen</span></td>
                                @endif
                                <td>{{ $latestDocument?->created_at?->translatedFormat('d M Y H:i') ?: '-' }}<small class="cell-subtitle">{{ $latestDocument?->uploader?->name ?: '-' }}</small></td>
                                <td class="action-column">
                                    <div class="row-actions">
                                        @if ($isSingleDocumentMonthly && $document)
                                            <button type="button" title="Lihat PDF" aria-label="Lihat {{ $document->nama_file_asli }}" data-reconciliation-preview-open data-preview-url="{{ route('laporan-dokumen.preview', $document) }}" data-download-url="{{ route('laporan-dokumen.download', $document) }}" data-preview-title="{{ $config['title'] }} {{ $slot['label'] }} {{ $year }}" data-preview-file="{{ $document->nama_file_asli }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                            </button>
                                            @if ($jenis === 'rekonsiliasi-bulanan')
                                                <a href="{{ route('laporan-dokumen.download', $document) }}" title="Unduh PDF" aria-label="Unduh {{ $document->nama_file_asli }}">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 21h14" /></svg>
                                                </a>
                                            @elseif ($jenis === 'persediaan-bulanan')
                                                <a href="{{ route('laporan-dokumen.download', $document) }}" title="Download PDF" aria-label="Download {{ $document->nama_file_asli }}">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 21h14" /></svg>
                                                </a>
                                            @endif
                                            @if ($canDelete)
                                                <button class="danger" type="button" title="Hapus PDF" aria-label="Hapus {{ $document->nama_file_asli }}" data-modal-open="delete-report-document-{{ $document->id }}">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" /></svg>
                                                </button>
                                            @endif
                                        @elseif (! $isSingleDocumentMonthly)
                                            <button type="button" title="Lihat Dokumen" data-modal-open="monthly-report-documents-{{ $slot['periode'] }}" aria-label="Lihat dokumen {{ $slot['label'] }} {{ $year }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                            </button>
                                        @else
                                            <span class="text-[9px] font-semibold text-[#9a3412]">Perlu keputusan</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="table-empty">
                                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6V3Zm9 0v5h4M9 13h6m-6 4h6" /></svg></span>
                                        <strong>Dokumen belum tersedia</strong>
                                        <p>Belum ada PDF {{ $config['monthly_document_label'] ?? strtolower($config['title']) }} untuk tahun {{ $year }}.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('laporan-dokumen.partials.pagination', ['paginator' => $slotPagination, 'paginationRoute' => route('laporan-dokumen.index', $jenis)])
        </section>
    @elseif (in_array(($config['period'] ?? null), ['semester_1', 'tahunan', 'aset_unaudited', 'aset_audited', 'persediaan_semester_1', 'persediaan_tahunan'], true))
        @php $usesYearColumn = ! in_array(($config['period'] ?? null), ['semester_1', 'persediaan_semester_1'], true); @endphp
        <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
            @include('laporan-dokumen.partials.filter-toolbar')
            <div class="flex min-h-[72px] items-center justify-between gap-5 border-b border-[#edf1f5] px-[18px] py-3.5 max-[580px]:flex-col max-[580px]:items-stretch">
                <div>
                    <strong class="block text-[12px] text-[#0b2b50]">Dokumen {{ $usesYearColumn ? $config['title'] : 'Semester 1' }} {{ $year }}</strong>
                    <small class="text-[10px] text-[#8290a3]">{{ $documents->total() ? $documents->total().' dokumen tersedia' : 'Belum ada dokumen pada tahun ini' }}</small>
                </div>
                @if ($canUpload && $schemaReady)
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="upload-report-document-{{ $config['period'] }}">+ Tambah Dokumen PDF</button>
                @endif
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="number-column">No</th>
                            <th>{{ $usesYearColumn ? 'Tahun' : 'Periode' }}</th>
                            <th>Nama Dokumen</th>
                            <th>Upload Terakhir</th>
                            <th class="action-column">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $index => $document)
                            <tr>
                                <td class="number-column">{{ $documents->firstItem() + $index }}</td>
                                <td><strong>{{ $usesYearColumn ? $document->tahun : 'Semester 1 '.$document->tahun }}</strong><small class="cell-subtitle">{{ $config['title'] }}</small></td>
                                <td><strong>{{ $document->nama_file_asli }}</strong><small class="cell-subtitle">{{ $formatBytes($document->ukuran_file) }}</small></td>
                                <td>{{ $document->created_at?->translatedFormat('d M Y H:i') ?: '-' }}<small class="cell-subtitle">{{ $document->uploader?->name ?: '-' }}</small></td>
                                <td class="action-column">
                                    <div class="row-actions">
                                        <button type="button" title="Lihat PDF" data-modal-open="preview-report-document-{{ $document->id }}" aria-label="Lihat {{ $document->nama_file_asli }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                        </button>
                                        <a href="{{ route('laporan-dokumen.download', $document) }}" title="Unduh PDF" aria-label="Unduh {{ $document->nama_file_asli }}">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 21h14" /></svg>
                                        </a>
                                        @if ($canDelete)
                                            <button class="danger" type="button" title="Hapus PDF" data-modal-open="delete-report-document-{{ $document->id }}" aria-label="Hapus {{ $document->nama_file_asli }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" /></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="table-empty">
                                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6V3Zm9 0v5h4M9 13h6m-6 4h6" /></svg></span>
                                        <strong>Dokumen belum tersedia</strong>
                                        <p>Belum ada PDF {{ $config['title'] }} untuk tahun {{ $year }}.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('laporan-dokumen.partials.pagination', ['paginator' => $documents, 'paginationRoute' => route('laporan-dokumen.index', $jenis)])
        </section>
    @else
        <section class="grid grid-cols-[repeat(auto-fit,minmax(280px,1fr))] gap-4">
            @foreach ($slots as $slot)
                @php
                    $slotDocuments = $slot['documents'];
                @endphp
                <article class="rounded-[14px] border border-slate-200 bg-white p-4 shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h2 class="m-0 text-[15px] font-extrabold text-[#0b2b50]">{{ $slot['label'] }} {{ $year }}</h2>
                            <p class="m-0 mt-1 text-[10px] text-[#8290a3]">{{ $config['title'] }}</p>
                        </div>
                        <span class="loan-status {{ $slotDocuments->isNotEmpty() ? 'status-disetujui' : 'status-diajukan' }}">{{ $slotDocuments->isNotEmpty() ? $slotDocuments->count().' Dokumen' : 'Belum tersedia' }}</span>
                    </div>

                    @if ($slotDocuments->isNotEmpty())
                        <div class="grid max-h-[360px] gap-2 overflow-y-auto pr-1">
                            @foreach ($slotDocuments as $document)
                                <div class="rounded-[11px] bg-[#f4f7fa] p-3 text-[11px] text-[#66758a]">
                                    <strong class="block text-[#405267]">{{ $document->judul ?: $config['title'] }}</strong>
                                    <small class="mt-1 block">{{ $document->nama_file_asli }} - {{ $formatBytes($document->ukuran_file) }}</small>
                                    <small class="mt-1 block">Diunggah {{ $document->created_at?->translatedFormat('d M Y H:i') ?: '-' }} oleh {{ $document->uploader?->name ?: '-' }}</small>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button class="page-action-button inline-flex min-h-[30px] flex-1 items-center justify-center rounded-[8px] border border-[#dbe3eb] bg-white px-2.5 text-[9px] font-bold text-[#146bb5] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-open="preview-report-document-{{ $document->id }}">Lihat</button>
                                        <a class="page-action-button inline-flex min-h-[30px] flex-1 items-center justify-center rounded-[8px] border border-[#dbe3eb] bg-white px-2.5 text-[9px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('laporan-dokumen.download', $document) }}">Unduh</a>
                                        @if ($canDelete)
                                            <button class="page-action-button inline-flex min-h-[30px] flex-1 items-center justify-center rounded-[8px] border border-[#fecaca] bg-white px-2.5 text-[9px] font-bold text-[#d92d20] transition hover:bg-[#fef3f2]" type="button" data-modal-open="delete-report-document-{{ $document->id }}">Hapus</button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="m-0 rounded-[11px] bg-[#f4f7fa] p-3 text-[11px] text-[#8290a3]">Dokumen belum diunggah.</p>
                    @endif

                    @if ($canUpload && $schemaReady)
                        <button class="page-action-button mt-4 inline-flex min-h-[34px] w-full items-center justify-center rounded-[8px] border-0 bg-[#146bb5] px-3 text-[9px] font-bold text-white transition hover:bg-[#0c568f]" type="button" data-modal-open="upload-report-document-{{ $slot['periode'] }}">+ Tambah Dokumen PDF</button>
                    @endif
                </article>
            @endforeach
        </section>
    @endif

    @if ($canUpload && $schemaReady)
        @if (($config['asset_holder_archive'] ?? false) === true)
            @include('laporan-dokumen.partials.berita-acara-modal', ['modalId' => 'upload-berita-acara', 'document' => null])
        @elseif (($config['type'] ?? null) === 'multi')
            @include('laporan-dokumen.partials.upload-modal', ['modalId' => 'upload-report-document', 'period' => $config['period'] ?? null, 'periodLabel' => $config['title'], 'needsTitle' => true, 'selectableRkbmdPeriod' => ($config['rkbmd'] ?? false) === true, 'periodLabels' => $config['period_labels'] ?? []])
        @elseif ($isMonthlyReport)
            @include('laporan-dokumen.partials.upload-modal', ['modalId' => 'upload-report-document', 'period' => null, 'periodLabel' => 'Pilih tahun dan bulan dokumen', 'needsTitle' => true, 'selectablePeriod' => true, 'occupiedPeriods' => $occupiedPeriods ?? [], 'occupiedPeriodsByYear' => $occupiedPeriodsByYear ?? []])
        @else
            @foreach ($slots as $slot)
                @include('laporan-dokumen.partials.upload-modal', ['modalId' => 'upload-report-document-'.$slot['periode'], 'period' => $slot['periode'], 'periodLabel' => $slot['label'].' '.$year, 'needsTitle' => true])
            @endforeach
        @endif
    @endif

    @if ($isMonthlyReport && ! $isSingleDocumentMonthly)
        @foreach ($slots as $slot)
            @php
                $slotDocuments = $slot['documents'];
            @endphp
            <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="monthly-report-documents-{{ $slot['periode'] }}" role="presentation">
                <section class="w-[min(680px,100%)] max-h-[calc(100vh-48px)] overflow-hidden rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="monthly-report-title-{{ $slot['periode'] }}">
                    <header class="flex min-h-[72px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[14px]">
                        <div>
                            <h2 class="m-0 text-[18px] font-extrabold text-[#0b2b50]" id="monthly-report-title-{{ $slot['periode'] }}">{{ $config['monthly_table_heading'] ?? 'Dokumen' }} {{ $slot['label'] }} {{ $year }}</h2>
                            <p class="m-0 mt-1 text-[10px] font-semibold text-[#8290a3]">{{ $slotDocuments->count() }} dokumen {{ $config['monthly_document_label'] ?? $config['title'] }}</p>
                        </div>
                        <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                    </header>
                    <div class="grid max-h-[60vh] gap-2 overflow-y-auto p-[22px]">
                        @foreach ($slotDocuments->sortByDesc('created_at') as $document)
                            <div class="flex items-center justify-between gap-3 rounded-[11px] bg-[#f4f7fa] p-3 max-[460px]:items-start max-[460px]:flex-col">
                                <div class="min-w-0 text-[11px] text-[#66758a]">
                                    <strong class="block truncate text-[#405267]">{{ $document->judul ?: $config['title'] }}</strong>
                                    <small class="mt-1 block">{{ $document->nama_file_asli }} · {{ $formatBytes($document->ukuran_file) }}</small>
                                    <small class="mt-1 block">Diunggah {{ $document->created_at?->translatedFormat('d M Y H:i') ?: '-' }}</small>
                                </div>
                                <div class="report-document-actions flex shrink-0 gap-2">
                                    <button class="page-action-button inline-flex h-[40px] w-[40px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white text-[#146bb5] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" title="Lihat PDF" aria-label="Lihat {{ $document->judul ?: $document->nama_file_asli }}" data-modal-open="preview-report-document-{{ $document->id }}">
                                        <svg class="h-[20px] w-[20px] fill-none stroke-current stroke-[2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                    </button>
                                    <a class="page-action-button inline-flex h-[40px] w-[40px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('laporan-dokumen.download', $document) }}" title="Unduh PDF" aria-label="Unduh {{ $document->judul ?: $document->nama_file_asli }}">
                                        <svg class="h-[20px] w-[20px] fill-none stroke-current stroke-[2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 21h14" /></svg>
                                    </a>
                                    @if ($canDelete)
                                        <button class="page-action-button inline-flex h-[40px] w-[40px] items-center justify-center rounded-[9px] border border-[#fecaca] bg-white text-[#d92d20] transition hover:bg-[#fef3f2]" type="button" title="Hapus Permanen" aria-label="Hapus {{ $document->judul ?: $document->nama_file_asli }}" data-modal-open="delete-report-document-{{ $document->id }}">
                                            <svg class="h-[20px] w-[20px] fill-none stroke-current stroke-[2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        @endforeach
    @endif

    @if (($config['single_document_per_period'] ?? false) === true)
        <div class="fixed inset-0 z-[90] hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="reconciliation-document-preview" data-reconciliation-preview-modal role="presentation">
            <section class="w-[min(980px,100%)] max-h-[calc(100vh-48px)] overflow-hidden rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="reconciliation-preview-title">
                <header class="flex min-h-[72px] items-center justify-between gap-3 border-b border-[#edf1f5] px-[22px] py-[14px] max-[580px]:px-4">
                    <div class="min-w-0">
                        <h2 class="m-0 truncate text-[18px] font-extrabold text-[#0b2b50]" id="reconciliation-preview-title" data-reconciliation-preview-title>Preview PDF</h2>
                        <p class="m-0 mt-1 truncate text-[10px] font-semibold text-[#8290a3]" data-reconciliation-preview-file></p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a class="page-action-button inline-flex min-h-[34px] items-center justify-center rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="#" data-reconciliation-preview-download>Unduh PDF</a>
                        <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close data-reconciliation-preview-close aria-label="Tutup preview PDF"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                    </div>
                </header>
                <div class="relative h-[72vh] bg-[#f4f7fa]">
                    <div class="absolute inset-0 grid place-items-center p-6 text-center" data-reconciliation-preview-loading>
                        <div>
                            <span class="mx-auto mb-3 block h-8 w-8 animate-spin rounded-full border-[3px] border-[#d9e9f6] border-t-[#146bb5]"></span>
                            <p class="m-0 text-[11px] font-semibold text-[#66758a]">Memuat PDF...</p>
                        </div>
                    </div>
                    <div class="absolute inset-0 hidden place-items-center p-6 text-center" data-reconciliation-preview-error>
                        <div>
                            <p class="m-0 text-[12px] font-bold text-[#b42318]">PDF tidak dapat dimuat.</p>
                            <p class="m-0 mt-1 text-[10px] text-[#66758a]">Silakan tutup modal lalu coba lagi, atau gunakan tombol Unduh PDF.</p>
                        </div>
                    </div>
                    <iframe class="hidden h-full w-full border-0 bg-white" title="Preview PDF Laporan" data-reconciliation-preview-frame></iframe>
                </div>
                <footer class="flex justify-end border-t border-[#edf1f5] px-[22px] py-[13px] max-[580px]:px-4">
                    <button class="page-action-button inline-flex min-h-[34px] items-center justify-center rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close data-reconciliation-preview-close>Tutup</button>
                </footer>
            </section>
        </div>
    @endif

    @foreach ($documents as $document)
        @php
            $isAssetHolderArchive = ($config['asset_holder_archive'] ?? false) === true;
            $beritaAcaraPerson = $document->nama_pegawai_snapshot ?: 'Belum dilengkapi';
            $beritaAcaraDate = $document->tanggal_berita_acara?->translatedFormat('d F Y') ?: 'Tanggal belum dilengkapi';
            $previewTitle = $isAssetHolderArchive
                ? 'Berita Acara — '.$beritaAcaraPerson.' ('.$beritaAcaraDate.')'
                : ($document->judul ?: $config['title']);
        @endphp
        @if (! (($config['single_document_per_period'] ?? false) === true))
        <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="preview-report-document-{{ $document->id }}" role="presentation">
            <section class="w-[min(980px,100%)] max-h-[calc(100vh-48px)] overflow-hidden rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="preview-report-title-{{ $document->id }}">
                <header class="flex min-h-[72px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[14px]">
                    <div>
                        <h2 class="m-0 text-[18px] font-extrabold text-[#0b2b50]" id="preview-report-title-{{ $document->id }}">{{ $previewTitle }}</h2>
                        <p class="m-0 mt-1 text-[10px] font-semibold text-[#8290a3]">{{ $document->nama_file_asli }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a class="page-action-button inline-flex min-h-[34px] items-center justify-center rounded-[8px] border border-[#dbe3eb] bg-white px-3 text-[9px] font-bold text-[#5e7084] no-underline" href="{{ route('laporan-dokumen.download', $document) }}">Unduh PDF</a>
                        <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                    </div>
                </header>
                <iframe class="h-[72vh] w-full border-0 bg-[#f4f7fa]" src="{{ route('laporan-dokumen.preview', $document) }}" title="Preview {{ $previewTitle }}"></iframe>
            </section>
        </div>
        @endif

        @if ($canDelete)
            <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-report-document-{{ $document->id }}" role="presentation">
                <section class="w-[min(460px,100%)] overflow-hidden rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="delete-report-title-{{ $document->id }}">
                    <header class="flex min-h-[72px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[14px]">
                        <div>
                            <h2 class="m-0 text-[18px] font-extrabold text-[#0b2b50]" id="delete-report-title-{{ $document->id }}">{{ $isAssetHolderArchive ? 'Hapus Berita Acara?' : 'Hapus Dokumen Permanen?' }}</h2>
                            <p class="m-0 mt-1 text-[10px] font-semibold text-[#8290a3]">{{ $isAssetHolderArchive ? $beritaAcaraPerson.' — '.$beritaAcaraDate : 'Laporan Dokumen' }}</p>
                        </div>
                        <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                    </header>
                    <div class="grid gap-3 p-[22px] text-[12px] text-[#5e7084]">
                        <p class="m-0">Apakah Anda yakin ingin menghapus dokumen <strong class="text-[#0b2b50]">{{ $isAssetHolderArchive ? $beritaAcaraPerson.' ('.$beritaAcaraDate.')' : ($document->judul ?: $document->nama_file_asli) }}</strong>?</p>
                        <p class="m-0 rounded-[10px] border border-[#fecaca] bg-[#fef3f2] px-3 py-2 text-[11px] font-semibold text-[#b42318]">Dokumen ini akan dihapus secara permanen dan tidak dapat dipulihkan melalui aplikasi.</p>
                    </div>
                    <form class="flex justify-end gap-2 border-t border-[#edf1f5] px-[22px] py-[16px]" method="POST" action="{{ route('laporan-dokumen.destroy', $document) }}" data-submit-loading>
                        @csrf
                        @method('DELETE')
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#d92d20] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(217_45_32_/_16%)] transition hover:bg-[#b42318]" type="submit" data-loading-button data-loading-text="Menghapus..."><span data-loading-label>Ya, Hapus Permanen</span></button>
                    </form>
                </section>
            </div>
        @endif
    @endforeach
</main>
@endsection
