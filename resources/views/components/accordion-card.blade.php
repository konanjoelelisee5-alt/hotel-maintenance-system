@props(['title', 'open' => false])

{{--
    Carte accordéon native (<details>/<summary>, sans JS) — pour les panneaux
    secondaires (SLA, pièces, photos, historique...) qui encombrent la page
    sur mobile si tout reste développé en permanence.
--}}
<details {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-line overflow-hidden group']) }} @if($open) open @endif>
    <summary class="px-5 py-4 flex items-center justify-between gap-2 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden">
        <h3 class="font-semibold text-navy text-sm">{{ $title }}</h3>
        <span class="flex items-center gap-2">
            {{ $badge ?? '' }}
            <span class="text-ink-grey text-[11px] transition-transform group-open:rotate-180">▾</span>
        </span>
    </summary>
    <div class="px-5 pb-5 border-t border-line-soft pt-4">
        {{ $slot }}
    </div>
</details>
