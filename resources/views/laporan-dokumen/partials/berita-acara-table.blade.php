<section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
    <div class="flex min-h-[72px] items-center justify-between gap-5 border-b border-[#edf1f5] px-[18px] py-3.5 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <strong class="block text-[12px] text-[#0b2b50]">Berita Acara tahun {{ $year }}</strong>
            <small class="text-[10px] text-[#8290a3]">{{ $documents->count() ? $documents->count().' data tersedia' : 'Belum ada Berita Acara pada tahun ini' }}</small>
        </div>
        @if ($canUpload && $schemaReady)
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="button" data-modal-open="upload-berita-acara">+ Tambah Berita Acara</button>
        @endif
    </div>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="number-column">No</th>
                    <th>Tanggal Berita Acara</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Status</th>
                    <th>Unit Kerja</th>
                    <th class="action-column">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $index => $document)
                    @php($isLegacy = ! $document->tanggal_berita_acara || ! $document->asset_holder_id)
                    <tr>
                        <td class="number-column">{{ $index + 1 }}</td>
                        <td>
                            @if ($document->tanggal_berita_acara)
                                {{ $document->tanggal_berita_acara->translatedFormat('d F Y') }}
                            @else
                                <span class="text-[10px] font-semibold text-[#9a3412]">Belum dilengkapi</span>
                            @endif
                        </td>
                        <td>{{ $document->nama_pegawai_snapshot ?: 'Belum dilengkapi' }}</td>
                        <td>{{ $document->jabatan_snapshot ?: 'Belum dilengkapi' }}</td>
                        <td class="status-column">
                            @if ($document->status_pegawai_snapshot === null)
                                <span class="loan-status status-diajukan">Belum dilengkapi</span>
                            @else
                                <span class="loan-status {{ $document->status_pegawai_snapshot ? 'status-approved' : 'status-rejected' }}">{{ $document->status_pegawai_snapshot ? 'Aktif' : 'Tidak Aktif' }}</span>
                            @endif
                        </td>
                        <td>{{ $document->unit_kerja_snapshot ?: 'Belum dilengkapi' }}</td>
                        <td class="action-column">
                            <div class="row-actions">
                                <button type="button" title="Lihat PDF" data-modal-open="preview-report-document-{{ $document->id }}" aria-label="Lihat Berita Acara {{ $document->nama_pegawai_snapshot ?: $document->nama_file_asli }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                </button>
                                <a href="{{ route('laporan-dokumen.download', $document) }}" title="Unduh PDF" aria-label="Unduh Berita Acara {{ $document->nama_pegawai_snapshot ?: $document->nama_file_asli }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 21h14" /></svg>
                                </a>
                                @if ($canUpload && $isLegacy)
                                    <button type="button" title="Lengkapi data" data-modal-open="complete-berita-acara-{{ $document->id }}" aria-label="Lengkapi data Berita Acara">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" /></svg>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <button class="delete-action" type="button" title="Hapus Berita Acara" data-modal-open="delete-report-document-{{ $document->id }}" aria-label="Hapus Berita Acara">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" /></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="table-empty"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6V3Zm9 0v5h4M9 13h6m-6 4h6" /></svg></span><strong>Berita Acara belum tersedia</strong><p>Belum ada data Berita Acara untuk tahun {{ $year }}.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if ($canUpload && $schemaReady)
    @foreach ($documents->filter(fn ($document) => ! $document->tanggal_berita_acara || ! $document->asset_holder_id) as $document)
        @include('laporan-dokumen.partials.berita-acara-modal', ['modalId' => 'complete-berita-acara-'.$document->id, 'document' => $document])
    @endforeach
@endif
