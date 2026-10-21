@php
    $toneClass = match ($tone ?? null) {
        'good' => 'bg-[#e8f8f1]',
        'danger' => 'bg-[#fff0ee]',
        default => 'bg-[#f5f8fb]',
    };
@endphp

<article class="grid gap-1 rounded-[10px] p-[13px] {{ $toneClass }}">
    <span class="text-[9px] text-[#8090a2]">{{ $label }}</span>
    <strong class="text-xl font-extrabold text-[#0b2b50]">{{ $value }}</strong>
</article>
