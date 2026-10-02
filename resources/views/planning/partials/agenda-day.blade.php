{{-- Programme d'un jour (vues Jour et Mois de l'agenda) : titre du jour, puis les
     interventions en cartes, ou un état vide. Dans le contexte x-data="agenda". --}}
<div class="flex flex-col gap-2.5">
    <div class="flex items-baseline justify-between gap-2 px-0.5">
        <h3 class="m-0 text-[15px] font-semibold text-navy" x-text="selectedLabel"></h3>
        <span class="text-[12px] text-ink-grey" x-text="eventsOn({{ $dayExpr }}).length ? eventsOn({{ $dayExpr }}).length + ' intervention(s)' : ''"></span>
    </div>

    <template x-if="eventsOn({{ $dayExpr }}).length === 0 && !loading">
        <div class="flex flex-col items-center gap-2 text-center px-6 py-10 bg-white border border-line rounded-xl">
            <span class="w-11 h-11 rounded-full bg-paper border border-line flex items-center justify-center text-gold">
                <x-nav-icon name="calendar" class="w-5 h-5" />
            </span>
            <span class="text-[14px] font-semibold text-navy">Aucune intervention prévue</span>
            <span class="text-[12.5px] text-ink-grey max-w-[280px] leading-relaxed">Glissez la bande des jours ou utilisez les flèches pour voir une autre période.</span>
        </div>
    </template>

    <div class="flex flex-col gap-2">
        <template x-for="e in eventsOn({{ $dayExpr }})" :key="e.id">
            @include('planning.partials.agenda-card')
        </template>
    </div>
</div>
