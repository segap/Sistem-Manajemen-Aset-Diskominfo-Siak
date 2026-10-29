<form class="filter-toolbar flex min-h-[72px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[780px]:flex-wrap max-[560px]:items-stretch" method="GET" action="{{ route('laporan-dokumen.index', $jenis) }}">
        <input type="hidden" name="per_page" value="{{ $selectedPerPage }}">
        <label class="sr-only" for="report-document-search">Cari dokumen</label>
        <div class="relative min-w-0 max-w-[430px] flex-1 max-[560px]:basis-full max-[560px]:max-w-none">
            <input id="report-document-search" class="h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-4 py-2 text-[10px] font-medium text-[#34445a] outline-none transition placeholder:text-[#78879a] focus:border-[#6ca9d3] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" style="padding-left: 1rem" type="search" name="search" value="{{ $search }}" placeholder="Cari nama dokumen...">
        </div>
        <label class="sr-only" for="report-document-year">Filter tahun</label>
        <select id="report-document-year" class="h-[42px] w-[128px] shrink-0 rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-none focus:border-[#6ca9d3] max-[560px]:flex-1" name="tahun">
            @foreach ($yearOptions as $option)
                <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <button class="page-action-button inline-flex h-[42px] min-w-[88px] shrink-0 items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>

    @if ($canExport && $schemaReady)
        <div class="shrink-0">
            <x-export-dropdown
                :formats="['word', 'pdf']"
                :links="[
                    'word' => route('laporan-dokumen.export-word', ['jenis' => $jenis] + $exportQuery),
                    'pdf' => route('laporan-dokumen.export-pdf', ['jenis' => $jenis] + $exportQuery),
                ]"
            />
        </div>
    @endif
</form>
