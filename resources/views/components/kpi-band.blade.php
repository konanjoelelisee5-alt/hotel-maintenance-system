@props([
    'items',          // [['label', 'value', 'sub', 'dot' (classe bg-…), 'href'?, 'active'?], …]
    'tiles' => false, // téléphone : tuiles 2 × 2 au lieu d'une bande qui défile
])

{{-- Bande d'indicateurs de la Supervision : séparateurs verticaux, chiffre en grand,
     chaque indicateur ouvre la vue filtrée correspondante.
     Avec tiles : tuiles sur téléphone et tablette (2 colonnes, 3 au-delà de 4 indicateurs),
     puis la bande quand elle a la place : dès 1000 px jusqu'à 4 indicateurs, dès 1200 px
     au-delà (les montants en FCFA sont larges). La bande défile plutôt que de couper un
     chiffre si sa colonne est étroite. Classes écrites en entier pour Tailwind. --}}
@php
    $wide = count($items) > 4;
    $sectionClasses = match (true) {
        ! $tiles => 'bg-white border border-line rounded-xl overflow-x-auto',
        $wide => 'grid grid-cols-2 tab:grid-cols-3 gap-3 desk:gap-0 desk:flex desk:divide-x desk:divide-line desk:bg-white desk:border desk:border-line desk:rounded-xl desk:overflow-x-auto',
        default => 'grid grid-cols-2 gap-3 split:gap-0 split:flex split:divide-x split:divide-line split:bg-white split:border split:border-line split:rounded-xl split:overflow-x-auto',
    };
@endphp
<section {{ $attributes->merge(['class' => $sectionClasses]) }}>
    @unless ($tiles)<div class="flex divide-x divide-line min-w-max desk:min-w-0">@endunless
    @foreach ($items as $item)
        @php
            $active = $item['active'] ?? false;
            $tag = isset($item['href']) ? 'a' : 'div';
            $tileClasses = match (true) {
                ! $tiles => 'min-w-[170px] px-6 py-5',
                $wide => 'min-w-0 desk:min-w-[150px] p-4 rounded-2xl bg-white border desk:rounded-none desk:border-0 desk:px-6 desk:py-5 '
                    .($active ? 'border-navy ring-1 ring-navy desk:ring-0 desk:bg-paper desk:shadow-[inset_0_-2px_0_#0E2136]' : 'border-line'),
                default => 'min-w-0 split:min-w-[150px] p-4 rounded-2xl bg-white border split:rounded-none split:border-0 split:px-5 desk:px-6 split:py-5 '
                    .($active ? 'border-navy ring-1 ring-navy split:ring-0 split:bg-paper split:shadow-[inset_0_-2px_0_#0E2136]' : 'border-line'),
            };
        @endphp
        <{{ $tag }} @if ($tag === 'a') href="{{ $item['href'] }}" @endif @if ($active) aria-current="true" @endif
           class="flex-1 flex flex-col gap-1 transition {{ $tileClasses }} {{ ! $tiles && $active ? 'bg-paper shadow-[inset_0_-2px_0_#0E2136]' : '' }} {{ ! $active && $tag === 'a' ? 'hover:bg-paper' : '' }}">
            <span class="flex items-center gap-2 text-[13px] text-ink-body whitespace-nowrap min-w-0">
                <span class="w-[7px] h-[7px] rounded-full {{ $item['dot'] ?? 'bg-navy' }} flex-shrink-0"></span>
                <span class="truncate" title="{{ $item['label'] }}">{{ $item['label'] }}</span>
            </span>
            <span class="text-[26px] desk:text-[30px] leading-tight font-semibold tracking-tight text-navy whitespace-nowrap">{{ $item['value'] }}</span>
            @if (! empty($item['sub']))
                <span class="text-[12px] desk:text-[12.5px] text-ink-muted leading-snug">{{ $item['sub'] }}</span>
            @endif
        </{{ $tag }}>
    @endforeach
    @unless ($tiles)</div>@endunless
</section>
