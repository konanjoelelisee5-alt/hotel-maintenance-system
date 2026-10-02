@props(['title', 'icon' => null, 'collapsible' => false, 'open' => true, 'tone' => null, 'flush' => false])

{{-- Carte de section des fiches (style de la refonte) : en-tête « icône dorée + titre »,
     badge et actions à droite, puis le contenu. collapsible : repliable (<details>,
     sans JavaScript) ; tone="danger" : filet rouge ; flush : contenu sans marge
     intérieure (listes bord à bord). --}}
@php
    $frame = 'bg-white rounded-xl border overflow-hidden '.($tone === 'danger' ? 'border-red/30' : 'border-line');
    $iconTile = 'w-8 h-8 flex-shrink-0 rounded-[8px] border flex items-center justify-center '
        .($tone === 'danger' ? 'bg-[#FBE4E1] border-red/20 text-red' : 'bg-paper border-line text-gold');
    $body = $flush ? '' : 'px-5 py-4';
    // Titre affiché comme texte : < > & échappés, l'apostrophe gardée telle quelle (« Pilotage de l'OT »).
@endphp

@if ($collapsible)
    <details {{ $attributes->merge(['class' => $frame.' group']) }} @if ($open) open @endif>
        <summary class="flex items-center gap-3 px-5 py-3.5 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden hover:bg-paper/60">
            @if ($icon)<span class="{{ $iconTile }}"><x-nav-icon :name="$icon" class="w-4 h-4" /></span>@endif
            <h3 class="m-0 flex-1 text-[14.5px] font-semibold text-navy">{!! htmlspecialchars($title, ENT_NOQUOTES) !!}</h3>
            {{ $badge ?? '' }}
            <x-nav-icon name="back" class="w-4 h-4 text-ink-grey -rotate-90 transition-transform group-open:rotate-90" />
        </summary>
        <div class="border-t border-line-soft {{ $body }}">
            {{ $slot }}
        </div>
    </details>
@else
    <section {{ $attributes->merge(['class' => $frame]) }}>
        <header class="flex items-center gap-3 px-5 py-3.5 border-b border-line-soft">
            @if ($icon)<span class="{{ $iconTile }}"><x-nav-icon :name="$icon" class="w-4 h-4" /></span>@endif
            <h3 class="m-0 flex-1 min-w-0 text-[14.5px] font-semibold text-navy">{!! htmlspecialchars($title, ENT_NOQUOTES) !!}</h3>
            {{ $badge ?? '' }}
            {{ $actions ?? '' }}
        </header>
        <div class="{{ $body }}">
            {{ $slot }}
        </div>
    </section>
@endif
