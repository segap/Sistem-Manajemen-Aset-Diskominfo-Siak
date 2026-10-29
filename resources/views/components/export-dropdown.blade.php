@props(['dataset' => null, 'label' => 'Export', 'extra' => [], 'align' => 'right', 'formats' => ['word', 'pdf', 'excel'], 'links' => null])

@php
    $query = array_merge(request()->except(['page']), $extra);
    $formatLabels = ['word' => 'Export Word', 'pdf' => 'Export PDF', 'excel' => 'Export Excel'];
    $exportLinks = $links ?? collect($formats)
        ->mapWithKeys(fn (string $format): array => [
            $format => route('exports.download', ['dataset' => $dataset, 'format' => $format] + $query),
        ])
        ->all();
@endphp

<div class="relative inline-flex" data-export-dropdown>
    <button class="inline-flex min-h-[42px] items-center gap-2 rounded-[10px] border border-[#d6e0eb] bg-white px-4 text-[12px] font-extrabold text-[#405267] shadow-[0_6px_16px_rgb(27_55_90_/_4%)] transition hover:border-[#b8c9dc] hover:text-[#146bb5]" type="button" data-export-toggle>
        {{ $label }}
        <svg class="h-4 w-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <div class="absolute {{ $align === 'left' ? 'left-0' : 'right-0' }} top-[calc(100%+8px)] z-50 hidden min-w-[155px] overflow-hidden rounded-[12px] border border-[#dce4ee] bg-white py-1 shadow-[0_18px_45px_rgb(15_39_67_/_16%)]" data-export-menu>
        @foreach ($formats as $format)
            @php
                $formatLabel = $formatLabels[$format] ?? null;
                $url = $exportLinks[$format] ?? null;
            @endphp
            @if ($formatLabel && $url)
                <a class="block px-4 py-2.5 text-[11px] font-bold text-[#405267] no-underline hover:bg-[#f4f8fc] hover:text-[#146bb5]" href="{{ $url }}" @if ($format === 'pdf') target="_blank" rel="noopener" @endif>{{ $formatLabel }}</a>
            @endif
        @endforeach
    </div>
</div>

@once
    <script>
        document.addEventListener('click', (event) => {
            document.querySelectorAll('[data-export-dropdown]').forEach((dropdown) => {
                const menu = dropdown.querySelector('[data-export-menu]');
                if (!menu) return;

                if (dropdown.contains(event.target) && event.target.closest('[data-export-toggle]')) {
                    menu.classList.toggle('hidden');
                    return;
                }

                if (!dropdown.contains(event.target)) {
                    menu.classList.add('hidden');
                }
            });
        });
    </script>
@endonce
