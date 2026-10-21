@php
    use App\Enums\RuanganStatus;

    $value = fn (string $field, mixed $default = null) => old($field, $item?->{$field} ?? $default);
    $selectedUser = old('penanggung_jawab_id', $item?->penanggung_jawab_id);
    $selectedStatus = old('status', ($item?->status instanceof RuanganStatus ? $item->status->value : $item?->status) ?? RuanganStatus::Active->value);
@endphp

<div class="grid grid-cols-2 gap-5 max-[580px]:grid-cols-1">
    <label class="grid min-w-0 gap-2 text-[11px] font-bold text-[#34445a]">
        <span>Kode Ruangan</span>
        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] px-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="kode_ruangan" value="{{ $value('kode_ruangan') }}" placeholder="Contoh: R-TIK" required>
        @error('kode_ruangan') <small class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</small> @enderror
    </label>
    <label class="grid min-w-0 gap-2 text-[11px] font-bold text-[#34445a]">
        <span>Kode Lokasi</span>
        <select class="h-[42px] w-full min-w-0 rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="kode_lokasi_id">
            <option value="">Tidak memilih master lokasi</option>
            @foreach (($kodeLokasiOptions ?? collect()) as $kodeLokasi)
                <option value="{{ $kodeLokasi->id }}" @selected((string) $value('kode_lokasi_id') === (string) $kodeLokasi->id)>{{ $kodeLokasi->kode_lokasi }} - {{ $kodeLokasi->nama_lokasi }}</option>
            @endforeach
        </select>
        @error('kode_lokasi_id') <small class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</small> @enderror
    </label>
</div>

<div class="grid grid-cols-2 gap-5 max-[580px]:grid-cols-1">
    <label class="grid min-w-0 gap-2 text-[11px] font-bold text-[#34445a]">
        <span>Nama Ruangan</span>
        <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] px-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="nama_ruangan" value="{{ $value('nama_ruangan') }}" placeholder="Contoh: Ruang Bidang TIK" required>
        @error('nama_ruangan') <small class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</small> @enderror
    </label>
    <label class="grid min-w-0 gap-2 text-[11px] font-bold text-[#34445a]">
        <span>Penanggung Jawab</span>
        <select class="h-[42px] w-full min-w-0 rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="penanggung_jawab_id">
            <option value="">Belum ditentukan</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) $selectedUser === (string) $user->id)>{{ $user->name }} · {{ $user->email }}</option>
            @endforeach
        </select>
        @error('penanggung_jawab_id') <small class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</small> @enderror
    </label>
    <label class="grid min-w-0 gap-2 text-[11px] font-bold text-[#34445a]">
        <span>Status</span>
        <select class="h-[42px] w-full min-w-0 rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        @error('status') <small class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</small> @enderror
    </label>
</div>

<label class="grid min-w-0 gap-2 text-[11px] font-bold text-[#34445a]">
    <span>Keterangan</span>
    <textarea class="min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="keterangan" placeholder="Keterangan tambahan ruangan...">{{ $value('keterangan') }}</textarea>
    @error('keterangan') <small class="text-[10px] font-semibold text-[#b42318]">{{ $message }}</small> @enderror
</label>
