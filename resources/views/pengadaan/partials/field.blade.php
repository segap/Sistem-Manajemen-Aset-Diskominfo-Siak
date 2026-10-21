@php
    $type = $type ?? 'text';
    $attrs = $attrs ?? '';
@endphp

<label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
    <span>{{ $label }}</span>
    <input class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition read-only:bg-[#f6f8fb] focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" type="{{ $type }}" name="{{ $name }}" value="{{ $value ?? '' }}" {{ ($required ?? false) ? 'required' : '' }} {{ ($readonly ?? false) ? 'readonly' : '' }} {!! $attrs !!}>
    @error($name) <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
</label>
