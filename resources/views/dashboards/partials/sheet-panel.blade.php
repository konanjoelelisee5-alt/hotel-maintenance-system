{{-- Panneau de la colonne de droite (feuille), données de DashboardPanels::sideA / sideB :
     - éléments avec 'pct' : une ligne + une barre (santé du SLA, charge des techniciens…) ;
     - sinon : une liste à pastilles (activité administrative, achats à suivre, à savoir…).
     $panel : title, sub?, items (url? : la ligne devient un lien) ; $link facultatif : [libellé, url]. --}}
@php $bars = collect($panel['items'])->contains(fn ($i) => array_key_exists('pct', $i)); @endphp
<section class="rounded-[22px] border border-line px-4 py-4">
    <div class="flex items-baseline justify-between gap-3">
        <h2 class="m-0 text-[15px] font-bold">{{ $panel['title'] }}</h2>
        @isset($link)
            <a href="{{ $link[1] }}" class="text-[12.5px] font-semibold text-blue whitespace-nowrap hover:underline">{{ $link[0] }}</a>
        @endisset
    </div>
    @if (! empty($panel['sub']))<p class="m-0 text-[12.5px] text-ink-grey">{{ $panel['sub'] }}</p>@endif

    @forelse ($panel['items'] as $item)
        @if ($bars)
            <div class="mt-3">
                <div class="flex items-baseline justify-between gap-3 text-[13.5px]">
                    <span class="font-semibold truncate">{{ $item['name'] }}</span>
                    <span class="text-[12.5px] font-semibold text-ink-body whitespace-nowrap tabular">{{ $item['meta'] }}</span>
                </div>
                <div class="mt-1.5 h-1.5 rounded-full bg-paper overflow-hidden">
                    <div class="h-full rounded-full {{ empty($item['dot']) ? \App\Support\Swatch::bg($item['color']) : '' }}"
                         style="width: {{ $item['pct'] }}%; @if (! empty($item['dot'])) background-color: {{ $item['dot'] }} @endif"></div>
                </div>
            </div>
        @else
            @php $tag = empty($item['url']) ? 'div' : 'a'; @endphp
            <{{ $tag }} @unless (empty($item['url'])) href="{{ $item['url'] }}" @endunless class="mt-3 flex items-start gap-3 {{ $tag === 'a' ? 'group' : '' }}">
                <span class="w-2 h-2 rounded-full mt-[7px] flex-shrink-0 {{ \App\Support\Swatch::bg($item['color']) }}"></span>
                <span class="flex-1 min-w-0">
                    <span class="block text-[13.5px] font-medium leading-snug group-hover:underline">{{ $item['label'] }}</span>
                    @if (! empty($item['who']) || ! empty($item['meta']))
                        <span class="block text-[12.5px] text-ink-grey truncate">{{ $item['who'] ?? $item['meta'] }}</span>
                    @endif
                </span>
                @if (! empty($item['time']))
                    <span class="text-[12.5px] text-ink-grey whitespace-nowrap tabular">{{ $item['time'] }}</span>
                @endif
            </{{ $tag }}>
        @endif
    @empty
        <p class="m-0 mt-2 text-[13px] text-ink-grey">Rien à signaler.</p>
    @endforelse
</section>
