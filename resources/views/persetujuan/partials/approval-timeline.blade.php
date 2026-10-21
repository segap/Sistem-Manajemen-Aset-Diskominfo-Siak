@php
    $timelineIcon = fn (string $state) => match ($state) {
        'created', 'done' => '✓',
        'waiting' => '⏳',
        'rejected' => '✕',
        default => '○',
    };
    $timelineTone = fn (string $state) => match ($state) {
        'created', 'done' => 'bg-[#dcfce7] text-[#08714e]',
        'waiting' => 'bg-[#dbeafe] text-[#075985]',
        'rejected' => 'bg-[#fee2e2] text-[#b42318]',
        default => 'bg-[#eef2f7] text-[#64748b]',
    };
@endphp

<section class="rounded-[12px] border border-[#dfe7f0] bg-white p-4">
    <h3 class="m-0 mb-3 text-[13px] font-extrabold text-[#20364f]">Riwayat Persetujuan</h3>

    @if (! ($item['approval']['has_history'] ?? false))
        <div class="mb-3 rounded-[10px] border border-[#fed7aa] bg-[#fff7ed] px-3 py-2 text-[10px] font-semibold leading-relaxed text-[#9a3412]">
            Riwayat aktivitas belum tersedia untuk proses sebelum fitur riwayat diterapkan.
        </div>
    @endif

    <div class="grid gap-0">
        @foreach (($item['approval']['timeline'] ?? []) as $step)
            <div class="grid grid-cols-[28px_1fr] gap-3">
                <div class="flex flex-col items-center">
                    <span class="grid h-7 w-7 place-items-center rounded-full text-[11px] font-extrabold {{ $timelineTone($step['state']) }}">{{ $timelineIcon($step['state']) }}</span>
                    @if (! $loop->last)
                        <span class="h-full min-h-7 w-px bg-[#dfe7f0]"></span>
                    @endif
                </div>
                <div class="pb-4">
                    <strong class="block text-[11px] text-[#0b2b50]">{{ $step['label'] }}</strong>
                    @if ($step['actor'])
                        <span class="mt-1 block text-[10px] font-semibold text-[#5f7085]">{{ $step['actor'] }}</span>
                    @else
                        <span class="mt-1 block text-[10px] font-semibold text-[#94a0af]">
                            @if ($step['state'] === 'waiting')
                                Belum diproses
                            @elseif (in_array($step['state'], ['done', 'created', 'rejected'], true))
                                Riwayat belum tersedia
                            @else
                                Belum sampai tahap ini
                            @endif
                        </span>
                    @endif
                    @if ($step['time'])
                        <span class="mt-0.5 block text-[9px] text-[#7d8ca0]">{{ $formatDateTime($step['time']) }}</span>
                    @endif
                    @if ($step['note'])
                        <p class="mt-2 mb-0 rounded-[9px] bg-[#f8fafc] px-3 py-2 text-[10px] font-semibold leading-relaxed text-[#66758a]">Catatan: {{ $step['note'] }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
