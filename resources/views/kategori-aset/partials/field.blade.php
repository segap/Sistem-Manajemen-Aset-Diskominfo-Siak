<label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
    <span>{{ $label }}</span>
    <input
        class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)] @error($name) border-[#e08a84] @enderror"
        name="{{ $name }}"
        value="{{ $value ?? '' }}"
        placeholder="{{ $placeholder ?? '' }}"
        @if (! empty($required)) required @endif
        @if (($name ?? '') === 'kode_kategori') autofocus @endif
    >
    @error($name)
        <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small>
    @else
        @if (! empty($hint))
            <small class="text-[8px] font-medium text-[#97a3b1]">{{ $hint }}</small>
        @endif
    @enderror
</label>
