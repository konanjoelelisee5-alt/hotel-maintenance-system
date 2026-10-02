@props([
    'items',          // [['label', 'value', 'sub', 'dot' (classe bg-…), 'href'?, 'active'?], …]
    'tiles' => false, // téléphone : tuiles 2 × 2 au lieu d'une bande qui défile
])

{{-- Bande d'indicateurs de la Supervision : séparateurs verticaux, chiffre en grand,
     chaque indicateur ouvre la vue filtrée correspondante. --}}
<section {{ $attributes->merge(['class' => ($tiles ? 'grid grid-cols-2 gap-3 lg:gap-0 lg:flex lg:divide-x lg:divide-line lg:bg-white lg:border lg:border-line lg:rounded-xl lg:overflow-hidden' : 'bg-white border border-line rounded-xl overflow-x-auto')]) }}>
    @unless ($tiles)<div class="flex divide-x divide-line min-w-max lg:min-w-0">@endunless
    @foreach ($items as $item)
        @php
            $active = $item['active'] ?? false;
            $tag = isset($item['href']) ? 'a' : 'div';
            $tileClasses = $tiles
                ? 'p-4 rounded-2xl bg-white border lg:rounded-none lg:border-0 lg:px-6 lg:py-5 '.($active ? 'border-navy ring-1 ring-navy lg:ring-0' : 'border-line')
                : 'min-w-[170px] px-6 py-5';
        @endphp
        <{{ $tag }} @if ($tag === 'a') href="{{ $item['href'] }}" @endif @if ($active) aria-current="true" @endif
           class="flex-1 flex flex-col gap-1 transition {{ $tileClasses }} {{ $active ? 'lg:bg-paper lg:shadow-[inset_0_-2px_0_theme(colors.navy)]' : ($tag === 'a' ? 'hover:bg-paper' : '') }}">
            <span class="flex items-center gap-2 text-[13px] text-[#4A4639] whitespace-nowrap">
                <span class="w-[7px] h-[7px] rounded-full {{ $item['dot'] ?? 'bg-navy' }} flex-shrink-0"></span>
                <span class="truncate">{{ $item['label'] }}</span>
            </span>
            <span class="text-[26px] lg:text-[30px] leading-tight font-semibold tracking-tight text-navy whitespace-nowrap">{{ $item['value'] }}</span>
            @if (! empty($item['sub']))
                <span class="text-[12px] lg:text-[12.5px] text-[#6C6658] leading-snug">{{ $item['sub'] }}</span>
            @endif
        </{{ $tag }}>
    @endforeach
    @unless ($tiles)</div>@endunless
</section>
