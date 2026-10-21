@php
    $type = $config['type'];
    $codeColumn = $config['code'];
    $nameColumn = $config['name'];
    $options = $config['options'];
    $closeIcon = '<svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>';
@endphp

@if ($canManage)
    @include('kodefikasi.partials.form-modal', ['modalId' => 'create-'.$activeTab, 'title' => 'Tambah '.$config['label'], 'action' => route('kodefikasi.store', $type), 'method' => 'POST', 'item' => null, 'config' => $config, 'options' => $options])

    <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="import-{{ $activeTab }}">
        <section class="w-[min(680px,100%)] rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]">
            <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Import {{ $config['label'] }}</h2>
                <button class="grid h-9 w-9 place-items-center rounded-lg border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup">{!! $closeIcon !!}</button>
            </header>
            <form class="grid gap-5 p-[22px]" method="POST" action="{{ route('kodefikasi.import', $type) }}" enctype="multipart/form-data" data-submit-loading>
                @csrf
                <div class="rounded-xl bg-[#f3f7fb] p-4 text-[11px] leading-6 text-[#66778d]">
                    <b class="text-[#34445a]">Format kolom Excel</b><br>
                    {{ match ($activeTab) {
                        'kode-lokasi' => 'kode_lokasi, nama_lokasi, keterangan.',
                        'kode-persediaan' => 'kode_barang, akun, kelompok, jenis, objek, rincian_objek, sub_rincian_objek, sub_sub_rincian_objek, nama_barang.',
                        default => $codeColumn.', akun, kelompok, jenis, objek, rincian_objek, sub_rincian_objek, sub_sub_rincian_objek, nama_barang.',
                    } }}
                    <br>Kode yang sudah ada akan dilewati, bukan ditimpa.
                </div>
                <div class="flex">
                    <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('kodefikasi.template', $type) }}">Unduh Template</a>
                </div>
                <input class="rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] file:mr-3 file:rounded-lg file:border-0 file:bg-[#e8f3ff] file:px-3 file:py-2 file:text-[10px] file:font-bold file:text-[#146bb5]" type="file" name="file" accept=".xlsx,.xls,.csv" required>
                @error('file') <small class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</small> @enderror
                <footer class="flex justify-end gap-2 border-t border-[#edf1f5] pt-5">
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white" type="submit" data-loading-button data-loading-text="Mengimport..."><span data-loading-label>Simpan</span></button>
                </footer>
            </form>
        </section>
    </div>
@endif

@foreach ($items as $item)
    <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="detail-{{ $activeTab }}-{{ $item->id }}">
        <section class="w-[min(760px,100%)] rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]">
            <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
                <div>
                    <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Detail {{ $config['label'] }}</h2>
                    <p class="m-0 mt-1 text-[11px] font-bold text-[#146bb5]">{{ $item->{$codeColumn} }}</p>
                </div>
                <button class="grid h-9 w-9 place-items-center rounded-lg border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup">{!! $closeIcon !!}</button>
            </header>
            <div class="grid gap-4 p-[22px] text-[11px] text-[#405267]">
                <div class="rounded-xl bg-[#f3f7fb] p-4"><b class="text-[#0b2b50]">{{ $item->{$nameColumn} }}</b></div>
                @if ($activeTab !== 'kode-lokasi')
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach (['akun' => 'Akun', 'kelompok' => 'Kelompok', 'jenis' => 'Jenis', 'objek' => 'Objek', 'rincian_objek' => 'Rincian Objek', 'sub_rincian_objek' => 'Sub Rincian Objek', 'sub_sub_rincian_objek' => 'Sub Sub Rincian Objek'] as $field => $label)
                            <div class="rounded-xl border border-[#dbe3eb] p-3"><small class="uppercase text-[#8290a3]">{{ $label }}</small><br><b>{{ $item->{$field} ?: '-' }}</b></div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-[#dbe3eb] p-3"><small class="uppercase text-[#8290a3]">Keterangan</small><br><b>{{ $item->keterangan ?: '-' }}</b></div>
                @endif
            </div>
        </section>
    </div>

    @if ($canManage)
        @include('kodefikasi.partials.form-modal', ['modalId' => 'edit-'.$activeTab.'-'.$item->id, 'title' => 'Edit '.$config['label'], 'action' => route('kodefikasi.update', [$type, $item->id]), 'method' => 'PUT', 'item' => $item, 'config' => $config, 'options' => $options])
        <div class="fixed inset-0 z-80 hidden place-items-center bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-{{ $activeTab }}-{{ $item->id }}">
            <section class="w-[min(430px,100%)] rounded-2xl bg-white p-[27px] text-center shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]">
                <div class="mx-auto mb-4 grid h-12 w-12 place-items-center rounded-full bg-red-50 text-red-600">
                    <svg class="h-6 w-6 fill-none stroke-current stroke-[1.9] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M9 7l1-3h4l1 3m3 0-1 14H7L6 7" /></svg>
                </div>
                <h2 class="m-0 text-xl font-extrabold text-[#0b2b50]">Hapus data?</h2>
                <p class="text-[12px] leading-relaxed text-[#66778d]">{{ $item->{$codeColumn} }} - {{ $item->{$nameColumn} }} akan dihapus.</p>
                <form class="mt-5 flex justify-center gap-2 border-t border-[#edf1f5] pt-5" method="POST" action="{{ route('kodefikasi.destroy', [$type, $item->id]) }}">
                    @csrf @method('DELETE')
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084]" type="button" data-modal-close>Batal</button>
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-red-600 px-3.5 text-[10px] font-bold text-white" type="submit">Hapus</button>
                </form>
            </section>
        </div>
    @endif
@endforeach
