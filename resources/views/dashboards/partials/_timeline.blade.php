@if ($timeline->isNotEmpty())
    <section class="border-t border-line pt-4">
        <div class="text-[13px] font-semibold text-[#6C6658] uppercase tracking-wide mb-3">Déroulé du jour — {{ now()->locale('fr')->translatedFormat('l j F') }}</div>
        <div class="flex gap-2.5 overflow-x-auto pb-1">
            @foreach ($timeline as $t)
                <a href="{{ route('work-orders.show', $t['id']) }}" class="flex-shrink-0 w-[196px] flex flex-col gap-1.5 px-[13px] py-3 border border-line-soft border-l-[3px] border-l-gold rounded-[10px] bg-white">
                    <span class="font-mono text-[12px] text-[#6C6658]">{{ $t['time'] }}</span>
                    <span class="text-[13px] font-semibold leading-snug">{{ $t['title'] }}</span>
                    <span class="text-[11.5px] text-ink-grey">{{ $t['who'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
