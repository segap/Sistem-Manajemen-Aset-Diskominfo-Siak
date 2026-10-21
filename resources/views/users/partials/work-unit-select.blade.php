<label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
    <span>Unit Kerja</span>
    <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="work_unit" required>
        <option value="">Pilih Unit Kerja</option>
        @foreach ($workUnits as $unitValue => $unitLabel)
            <option value="{{ $unitValue }}" @selected($value === $unitValue)>{{ $unitLabel }}</option>
        @endforeach
    </select>
    @error('work_unit') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
</label>
