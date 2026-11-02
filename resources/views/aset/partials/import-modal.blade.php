<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="import-assets" role="presentation">
    <section class="w-[min(1180px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="import-assets-title">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div><span class="hidden">IMPORT DATA</span><h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="import-assets-title">Import Aset dari Excel</h2></div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
        </header>
        <div class="grid gap-5 p-[22px]">
            <div class="grid gap-4 lg:grid-cols-2">
                <form class="grid gap-3 rounded-xl border border-[#dce4ec] p-4" method="POST" action="{{ route('web.aset.impor') }}" enctype="multipart/form-data" data-submit-loading>
                    @csrf
                    <div><h3 class="m-0 text-sm font-extrabold text-[#0b2b50]">Template SIMIVA</h3><p class="mt-1 text-[11px] text-[#6e7d91]">Format lama tetap tersedia. Aset import langsung disetujui otomatis.</p></div>
                    <p class="m-0 text-[10px] leading-relaxed text-[#6e7d91]">Header: kategori, kode_barang_opsional, kode_internal_opsional, nama_aset, merek_tipe, spesifikasi, jumlah, tahun_perolehan, kondisi, pemasok, lokasi.</p>
                    <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('web.aset.template-impor') }}">Unduh Template</a>
                    <label class="grid gap-1.5 text-[11px] font-bold text-[#405267]"><span>Excel / CSV (maks. 10 MB)</span><input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[11px] file:font-bold file:text-[#146bb5]" type="file" name="file" accept=".xlsx,.xls,.csv" required></label>
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Mengimport..."><span data-loading-label>Import Template</span></button>
                </form>
                <form class="grid gap-3 rounded-xl border border-[#bcd8ef] bg-[#f8fbff] p-4" method="POST" action="{{ route('web.aset.impor.diskominfo.preview') }}" enctype="multipart/form-data" data-submit-loading>
                    @csrf
                    <div><h3 class="m-0 text-sm font-extrabold text-[#0b2b50]">Inventaris Diskominfo</h3><p class="mt-1 text-[11px] text-[#6e7d91]">Khusus XLSX multi-sheet. File diperiksa dahulu; aset belum disimpan pada tahap preview.</p></div>
                    <p class="m-0 text-[10px] leading-relaxed text-[#6e7d91]">Sheet: Meja, Kursi, Personal Computer, Laptop, Printer, Scanner, Kamera. Sheet Ringkasan dilewati.</p>
                    <label class="grid gap-1.5 text-[11px] font-bold text-[#405267]"><span>File XLSX (maks. 10 MB)</span><input class="h-auto w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 py-2 text-[10px] font-normal text-[#34465a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[11px] file:font-bold file:text-[#146bb5]" type="file" name="file" accept=".xlsx" required>@error('file') <small class="text-[9px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror</label>
                    <p class="m-0 rounded-lg bg-[#fff8df] px-3 py-2 text-[10px] leading-relaxed text-[#79622c]">Hash file mencegah import ulang workbook yang sama, tetapi tidak menggantikan pemeriksaan potensi duplikasi isi inventaris.</p>
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Memeriksa..."><span data-loading-label>Preview Inventaris</span></button>
                </form>
            </div>

            @if ($diskominfoImportPreview && $diskominfoImportToken)
                @php
                    $preview = $diskominfoImportPreview;
                    $blockingErrors = collect($preview['rows'])->contains(fn ($row) => $row['blocking_error'] ?? false);
                    $sheetNames = ['Meja', 'Kursi', 'Personal Computer', 'Laptop', 'Printer', 'Scanner', 'Kamera'];
                    $conditionOptions = ['baik' => 'Baik', 'rusak_ringan' => 'Rusak Ringan', 'rusak_sedang' => 'Rusak Sedang', 'rusak_berat' => 'Rusak Berat', 'hilang' => 'Hilang'];
                @endphp
                <form class="grid gap-4 border-t border-[#edf1f5] pt-5" method="POST" action="{{ route('web.aset.impor.diskominfo.confirm') }}" data-submit-loading>
                    @csrf
                    <input type="hidden" name="token" value="{{ $diskominfoImportToken }}">
                    <div><h3 class="m-0 text-base font-extrabold text-[#0b2b50]">Preview Inventaris Diskominfo</h3><p class="mt-1 text-[11px] text-[#6e7d91]">Pilih kategori setiap worksheet, lalu selesaikan koreksi yang ditandai. Tidak ada aset yang telah disimpan.</p></div>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach (['total' => 'Total Baris', 'valid' => 'Valid Otomatis', 'corrected' => 'Terkoreksi Manual', 'warning' => 'Peringatan', 'error' => 'Error Tersisa', 'skipped' => 'Dilewati', 'units' => 'Unit Dibuat'] as $key => $label)
                            <div class="rounded-lg border border-[#e0e8f0] bg-[#f8fafc] px-3 py-2"><span class="block text-[9px] font-bold uppercase tracking-wide text-[#718197]">{{ $label }}</span><strong class="text-sm text-[#16395e]">{{ $preview['summary'][$key] ?? 0 }}</strong></div>
                        @endforeach
                    </div>
                    @if ($preview['warnings'])<div class="rounded-lg bg-[#fff8df] px-3 py-2 text-[10px] text-[#79622c]">{{ implode(' ', $preview['warnings']) }}</div>@endif
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($sheetNames as $sheetName)
                            <label class="grid gap-1 text-[10px] font-bold text-[#405267]">Kategori untuk {{ $sheetName }}<select class="h-9 rounded-lg border border-[#dce4ec] bg-white px-2 text-[11px] font-medium" name="categories[{{ $sheetName }}]" required><option value="">-- Pilih kategori --</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old("categories.$sheetName") == $category->id)>{{ $category->nama_kategori }}</option>@endforeach</select></label>
                        @endforeach
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-[#dce4ec]"><table class="min-w-[1120px] w-full border-collapse text-left text-[10px] text-[#405267]"><thead class="bg-[#f2f6fa] text-[9px] font-extrabold uppercase tracking-wide text-[#60738a]"><tr><th class="border-b border-r border-[#dce4ec] px-3 py-2">Sheet / Baris</th><th class="border-b border-r border-[#dce4ec] px-3 py-2">Aset</th><th class="border-b border-r border-[#dce4ec] px-3 py-2">Pegawai / Unit</th><th class="border-b border-r border-[#dce4ec] px-3 py-2 text-center">Jumlah</th><th class="border-b border-r border-[#dce4ec] px-3 py-2">Kondisi</th><th class="border-b border-r border-[#dce4ec] px-3 py-2">Catatan</th><th class="border-b border-[#dce4ec] px-3 py-2">Validasi</th></tr></thead><tbody>
                        @foreach ($preview['rows'] as $row)
                            <tr class="align-top {{ $row['status'] === 'error' ? 'bg-[#fff8f7]' : '' }}"><td class="border-b border-r border-[#e6edf3] px-3 py-2">{{ $row['sheet'] }}<br><span class="text-[#8190a2]">Baris {{ $row['row'] }}</span></td><td class="border-b border-r border-[#e6edf3] px-3 py-2 font-semibold">{{ $row['asset_name'] }}</td><td class="border-b border-r border-[#e6edf3] px-3 py-2">{{ $row['nama_pegawai'] ?: '-' }}<br><span class="text-[#718197]">{{ $row['unit_kerja'] ?: '-' }}</span>@if($row['requires_holder_mapping'])<select class="mt-2 h-8 w-full rounded border border-[#e2b0aa] bg-white px-2 text-[10px]" name="holders[{{ $row['key'] }}]" required><option value="">-- Pilih pegawai --</option>@foreach($assetHolderOptions as $holder)<option value="{{ $holder->id }}" @selected(old("holders.{$row['key']}") == $holder->id)>{{ $holder->nama_pegawai }}{{ $holder->nip ? ' — '.$holder->nip : '' }}</option>@endforeach</select>@endif</td><td class="border-b border-r border-[#e6edf3] px-3 py-2 text-center">{{ $row['jumlah'] }}</td><td class="border-b border-r border-[#e6edf3] px-3 py-2">@if($row['requires_condition_correction'])<select class="h-8 w-full rounded border border-[#e2b0aa] bg-white px-2 text-[10px]" name="conditions[{{ $row['key'] }}]" required><option value="">-- Koreksi kondisi --</option>@foreach($conditionOptions as $value => $label)<option value="{{ $value }}" @selected(old("conditions.{$row['key']}") === $value)>{{ $label }}</option>@endforeach</select>@else {{ $conditionOptions[$row['kondisi']] ?? '-' }} @endif</td><td class="border-b border-r border-[#e6edf3] px-3 py-2">{{ $row['catatan'] ?: '-' }}</td><td class="border-b border-[#e6edf3] px-3 py-2"><span class="font-bold {{ $row['status'] === 'error' ? 'text-[#b8433b]' : ($row['status'] === 'warning' ? 'text-[#9a6a11]' : 'text-[#287653]') }}">{{ $row['status'] === 'warning' ? 'Peringatan' : ucfirst($row['status']) }}</span>@if($row['messages'])<div class="mt-1 leading-relaxed text-[#718197]">{{ implode(' ', $row['messages']) }}</div>@endif</td></tr>
                        @endforeach
                    </tbody></table></div>
                    @foreach ($preview['rows'] as $row)
                        @if (!empty($row['manual_holder_id']))
                            <input type="hidden" name="holders[{{ $row['key'] }}]" value="{{ $row['manual_holder_id'] }}">
                        @endif
                    @endforeach
                    @php($suggestionRows = collect($preview['rows'])->filter(fn ($row) => !empty($row['suggestions'])))
                    @if ($suggestionRows->isNotEmpty())
                        <div class="rounded-lg bg-[#fff8df] px-3 py-2 text-[10px] leading-relaxed text-[#79622c]">
                            @foreach ($suggestionRows as $row)
                                <div><strong>{{ $row['sheet'] }} baris {{ $row['row'] }}:</strong> saran pegawai {{ collect($row['suggestions'])->pluck('name')->implode(', ') }}. Pilih secara manual bila identitasnya benar.</div>
                            @endforeach
                        </div>
                    @endif
                    @if ($blockingErrors)<p class="m-0 rounded-lg bg-[#fff0ef] px-3 py-2 text-[10px] text-[#a43b35]">Masih ada error data yang tidak dapat dikoreksi melalui preview. Perbaiki workbook lalu upload ulang.</p>@endif
                    <footer class="flex justify-end gap-2 border-t border-[#edf1f5] pt-4"><button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button><button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#0c568f] disabled:cursor-not-allowed disabled:bg-[#9cb9d1]" type="submit" @disabled($blockingErrors) data-loading-button data-loading-text="Mengimport..."><span data-loading-label>Konfirmasi Import</span></button></footer>
                </form>
            @endif
        </div>
    </section>
</div>
