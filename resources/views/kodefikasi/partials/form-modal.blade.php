@php
    $codeColumn = $config['code'];
    $nameColumn = $config['name'];
    $value = fn (string $field, $default = '') => old($field, $item->{$field} ?? $default);
    $showDetail = $config['type'] !== 'kode-lokasi';
    $codeLabel = $config['type'] === 'kode-persediaan' ? 'Kode Barang' : $config['label'];
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6" data-modal="{{ $modalId }}">
    <section class="w-[min(900px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]">
        <header class="flex items-center justify-between gap-4 border-b border-[#edf1f5] px-6 py-5">
            <div>
                <p class="m-0 mb-1 text-[10px] font-extrabold uppercase tracking-[.18em] text-[#146bb5]">Kodefikasi</p>
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">{{ $title }}</h2>
            </div>
            <button class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border-0 bg-[#f2f5f8] text-[#8291a2] transition hover:bg-[#e8eef4] hover:text-[#0b2b50]" type="button" data-modal-close aria-label="Tutup">
                <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </header>

        <form class="grid gap-5 p-6" method="POST" action="{{ $action }}" data-submit-loading>
            @csrf
            @if ($method !== 'POST') @method($method) @endif

            <div class="rounded-2xl border border-[#e2e8f0] bg-[#fbfdff] p-5">
                <div class="grid items-start gap-5">
                    <label class="grid min-w-0 gap-2 text-[11px] font-bold text-[#34445a]">
                        <span>{{ $codeLabel }}</span>
                        <input class="h-[42px] w-full min-w-0 rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal outline-0 focus:border-[#6ca9d3] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="{{ $codeColumn }}" value="{{ $value($codeColumn) }}" required>
                    </label>
                </div>

                <label class="mt-4 grid gap-2 text-[11px] font-bold text-[#34445a]">
                    <span>{{ $config['name'] === 'nama_lokasi' ? 'Nama Lokasi / Unit Kerja' : 'Nama Barang' }}</span>
                    <input class="h-[42px] rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal outline-0 focus:border-[#6ca9d3] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="{{ $nameColumn }}" value="{{ $value($nameColumn) }}" required>
                </label>
            </div>

            @if ($showDetail)
                <div class="rounded-2xl border border-[#e2e8f0] p-5">
                    <div class="mb-4">
                        <h3 class="m-0 text-[13px] font-extrabold text-[#0b2b50]">Detail {{ $codeLabel }}</h3>
                        <p class="m-0 mt-1 text-[10px] font-medium text-[#7b8ca0]">Isi jika diperlukan untuk pemetaan kode yang lebih rinci.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        @foreach (($config['type'] === 'kode-persediaan'
                            ? ['akun' => 'Akun', 'kelompok' => 'Kelompok', 'jenis' => 'Jenis', 'objek' => 'Objek', 'rincian_objek' => 'Rincian Objek', 'sub_rincian_objek' => 'Sub Rincian Objek', 'sub_sub_rincian_objek' => 'Sub Sub Rincian Objek']
                            : ['akun' => 'Akun', 'kelompok' => 'Kelompok', 'jenis' => 'Jenis', 'objek' => 'Objek', 'rincian_objek' => 'Rincian Objek', 'sub_rincian_objek' => 'Sub Rincian Objek', 'sub_sub_rincian_objek' => 'Sub Sub Rincian Objek']) as $field => $label)
                            <label class="grid gap-2 text-[11px] font-bold text-[#34445a]">
                                <span>{{ $label }}</span>
                                <input class="h-[42px] rounded-[9px] border border-[#dce4ec] px-3 text-[10px] font-normal outline-0 focus:border-[#6ca9d3] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="{{ $field }}" value="{{ $value($field) }}">
                            </label>
                        @endforeach
                    </div>
                </div>
            @elseif ($config['type'] === 'kode-lokasi')
                <label class="grid gap-2 rounded-2xl border border-[#e2e8f0] p-5 text-[11px] font-bold text-[#34445a]">
                    <span>Keterangan</span>
                    <textarea class="min-h-24 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6ca9d3] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="keterangan">{{ $value('keterangan') }}</textarea>
                </label>
            @endif

            <footer class="mt-2 flex justify-end gap-2 border-t border-[#edf1f5] pt-5">
                <button class="page-action-button h-[38px] rounded-[9px] border border-[#dbe3eb] bg-white px-4 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                <button class="page-action-button h-[38px] rounded-[9px] border-0 bg-[#146bb5] px-4 text-[10px] font-bold text-white" type="submit" data-loading-button data-loading-text="Menyimpan..."><span data-loading-label>Simpan</span></button>
            </footer>
        </form>
    </section>
</div>
