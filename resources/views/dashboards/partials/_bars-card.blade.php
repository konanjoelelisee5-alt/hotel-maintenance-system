{{-- Carte latérale « une ligne + une barre par élément » ($panel : title, sub, items
     {name, meta, pct, color, dot?}). Ex. Santé du SLA (admin), Charge des techniciens (manager). --}}
<section class="bg-white border border-line rounded-xl px-6 py-5">
    <div class="flex items-baseline justify-between gap-3 mb-4">
        <h2 class="text-[17px] font-semibold text-navy whitespace-nowrap">{{ $panel['title'] }}</h2>
        <span class="text-[12.5px] text-ink-grey truncate min-w-0" title="{{ $panel['sub'] ?? '' }}">{{ $panel['sub'] ?? '' }}</span>
    </div>
    <div class="flex flex-col gap-4">
        @forelse ($panel['items'] as $item)
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="flex items-center gap-2.5 min-w-0">
                        <span class="w-[7px] h-[7px] rounded-[2px] flex-shrink-0 {{ empty($item['dot']) ? \App\Support\Swatch::bg($item['color']) : '' }}" @if (! empty($item['dot'])) style="background-color: {{ $item['dot'] }}" @endif></span>
                        <span class="text-[14px] truncate">{{ $item['name'] }}</span>
                    </span>
                    <span class="font-mono text-[12px] text-[#4A4639] whitespace-nowrap">{{ $item['meta'] }}</span>
                </div>
                <div class="h-[5px] rounded-full bg-line-soft overflow-hidden">
                    <div class="h-full {{ \App\Support\Swatch::bg($item['color']) }}" style="width: {{ $item['pct'] }}%"></div>
                </div>
            </div>
        @empty
            <div class="text-[12.5px] text-ink-grey">Rien à signaler.</div>
        @endforelse
    </div>
</section>
