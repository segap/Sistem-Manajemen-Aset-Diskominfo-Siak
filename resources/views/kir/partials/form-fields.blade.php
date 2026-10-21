@php
    $value = fn (string $key, $default = '') => old($key, $detail->{$key} ?? $default);
    $conditionCode = function ($condition): string {
        $raw = $condition instanceof \App\Enums\AssetCondition ? $condition->value : (string) $condition;

        return match ($raw) {
            'baik', 'Baik', 'B' => 'B',
            'rusak_ringan', 'Rusak Ringan', 'RR' => 'RR',
            'rusak_sedang', 'Rusak Sedang', 'RS' => 'RS',
            'rusak_berat', 'Rusak Berat', 'RB' => 'RB',
            default => '',
        };
    };
    $keteranganOptions = [
        'B' => 'B',
        'RR' => 'RR',
        'RS' => 'RS',
        'RB' => 'RB',
    ];
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <label class="grid min-w-0 gap-2 text-[11px] font-extrabold text-[#34445a]">
        Pilih aset dari Master Data *
        <select class="kir-asset-picker h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="aset_id" required>
            <option value="">Pilih aset terverifikasi</option>
            @foreach ($assets as $assetOption)
                <option
                    value="{{ $assetOption->id }}"
                    @selected((string) $value('aset_id') === (string) $assetOption->id)
                    data-kode-barang-id="{{ $assetOption->kode_barang_id }}"
                    data-kode-barang="{{ $assetOption->kodeBarang?->kode_barang }}"
                    data-nama-barang="{{ $assetOption->nama_aset }}"
                    data-spesifikasi-nama-barang="{{ $assetOption->spesifikasi }}"
                    data-merek-tipe="{{ $assetOption->merek_tipe }}"
                    data-tahun-perolehan="{{ $assetOption->tahun_perolehan }}"
                    data-jumlah="{{ $assetOption->jumlah }}"
                    data-keterangan="{{ $conditionCode($assetOption->kondisi) }}"
                >
                    {{ $assetOption->display_code }} - {{ $assetOption->nama_aset }}
                </option>
            @endforeach
        </select>
        @error('aset_id') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
    </label>

    <label class="grid min-w-0 gap-2 text-[11px] font-extrabold text-[#34445a]">
        NIBAR
        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="nibar" value="{{ $value('nibar') }}" placeholder="Contoh: NBR-001">
        @error('nibar') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
    </label>

    <label class="grid min-w-0 gap-2 text-[11px] font-extrabold text-[#34445a]">
        Nomor Register
        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="nomor_register" value="{{ $value('nomor_register') }}" placeholder="Contoh: REG-001">
        @error('nomor_register') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
    </label>

    <label class="grid min-w-0 gap-2 text-[11px] font-extrabold text-[#34445a]">
        Kode Lokasi
        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="kode_lokasi" value="{{ $value('kode_lokasi', $kir->kode_lokasi) }}" placeholder="Isi kode lokasi">
        @error('kode_lokasi') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
    </label>

    <label class="grid min-w-0 gap-2 text-[11px] font-extrabold text-[#34445a]">
        Kode Barang
        <input type="hidden" name="kode_barang_id" value="{{ $value('kode_barang_id') }}">
        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="kode_barang" value="{{ $value('kode_barang') }}" placeholder="Otomatis dari aset" readonly>
        @error('kode_barang') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
        @error('kode_barang_id') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
    </label>

    <label class="grid min-w-0 gap-2 text-[11px] font-extrabold text-[#34445a]">
        Nama Barang
        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="nama_barang" value="{{ $value('nama_barang') }}" placeholder="Otomatis dari aset" required readonly>
        @error('nama_barang') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
    </label>

    <label class="grid min-w-0 gap-2 text-[11px] font-extrabold text-[#34445a]">
        Merek/Tipe
        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="merek_tipe" value="{{ $value('merek_tipe') }}" placeholder="Otomatis dari aset" readonly>
        @error('merek_tipe') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
    </label>

    <label class="grid min-w-0 gap-2 text-[11px] font-extrabold text-[#34445a]">
        Tahun Perolehan
        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="tahun_perolehan" value="{{ $value('tahun_perolehan') }}" inputmode="numeric" placeholder="Otomatis dari aset" readonly>
        @error('tahun_perolehan') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
    </label>

    <label class="grid min-w-0 gap-2 text-[11px] font-extrabold text-[#34445a]">
        Jumlah
        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-[#f8fafc] px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="jumlah" value="{{ $value('jumlah', 1) }}" inputmode="numeric" required readonly>
        @error('jumlah') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
    </label>

    <label class="grid min-w-0 gap-2 text-[11px] font-extrabold text-[#34445a]">
        Satuan
        <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="satuan" required>
            @foreach ($satuanOptions as $satuan)
                <option value="{{ $satuan }}" @selected($value('satuan', 'Unit') === $satuan)>{{ $satuan }}</option>
            @endforeach
        </select>
        @error('satuan') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
    </label>
</div>

<label class="grid gap-2 text-[11px] font-extrabold text-[#34445a]">
    Spesifikasi Nama Barang
    <textarea class="min-h-[86px] w-full rounded-[9px] border border-[#dce4ee] bg-[#f8fafc] px-3 py-3 text-[10px] font-semibold text-[#34445a] outline-0" name="spesifikasi_nama_barang" placeholder="Otomatis dari aset" readonly>{{ $value('spesifikasi_nama_barang') }}</textarea>
    @error('spesifikasi_nama_barang') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
</label>

<label class="grid gap-2 text-[11px] font-extrabold text-[#34445a]">
    Keterangan
    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="keterangan">
        <option value="">Pilih keterangan</option>
        @foreach ($keteranganOptions as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($conditionCode($value('keterangan')) === $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error('keterangan') <span class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</span> @enderror
</label>
