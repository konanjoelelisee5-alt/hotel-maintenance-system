{{-- « Ma journée » du technicien (sur téléphone d'abord), dans layouts.sheet :
     - au centre : bonjour + ce qui l'attend, son prochain passage planifié, ses repères (ordres
       du jour, urgents, temps saisi, terminés, pièces à retirer), la courbe de ses réparations,
       sa file d'ordres, son déroulé du jour ;
     - à droite (dessous sur téléphone) : l'astreinte à appeler, ce qu'il faut savoir avant de
       partir (corrections, clients à ménager, pièces à prendre), sa journée heure par heure,
       l'activité de ses ordres. --}}
@php
    $here = fn (?string $f) => route('technicien.dashboard', ['filter' => $f]);
    $icons = ['Mes ordres du jour' => 'clipboard', 'Urgents' => 'alert', 'Temps saisi' => 'clock', 'Terminés aujourd\'hui' => 'check', 'Pièces à retirer' => 'part'];
    $kpis = collect($pulse)->map(fn (array $p) => [
        'label' => $p['label'], 'value' => $p['value'], 'icon' => $icons[$p['label']] ?? 'list',
        'trend' => $p['sub'], 'tone' => 'muted', 'href' => $p['url'] ?? $here($p['filter']),
    ])->all();
    $today = $pulse[0]['value'] ?? 0;
    $next = $timeline->first();
@endphp

<x-app-layout page-title="Ma journée">
    <x-slot:greeting>
        @include('dashboards.partials.sheet-greeting', ['summary' => $today
            ? $today.' ordre'.($today > 1 ? 's' : '').' pour vous aujourd\'hui.'
            : 'Aucun ordre prévu pour vous aujourd\'hui.'])
    </x-slot:greeting>

    {{-- Prochain passage planifié : la barre de la maquette, en un geste. --}}
    @if ($next)
        <a href="{{ route('work-orders.show', $next['id']) }}"
           class="group flex items-center gap-4 min-h-[68px] py-2 pl-3 pr-4 sm:pr-6 rounded-[34px] bg-[rgb(var(--rc-sky))] hover:bg-[rgb(205_225_251)] transition-colors">
            <span class="w-12 h-12 rounded-full bg-ink-deep text-white flex flex-col items-center justify-center flex-shrink-0 leading-none">
                <span class="text-[13px] font-bold tabular">{{ $next['time'] }}</span>
            </span>
            <span class="flex-1 min-w-0">
                <span class="block text-[12.5px] font-semibold text-[rgb(var(--rc-sky-ink))]/80">Prochain passage</span>
                <span class="block text-[16px] font-bold text-[rgb(var(--rc-sky-ink))] truncate">{{ $next['title'] }}</span>
            </span>
            <svg viewBox="0 0 24 24" class="w-5 h-5 text-[rgb(var(--rc-sky-ink))] group-hover:translate-x-0.5 transition-transform flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
    @endif

    @include('dashboards.partials.sheet-kpis')

    @include('dashboards.partials.sheet-queue', [
        'queueTitle' => match ($filter) { 'urgent' => 'Urgents', 'all' => 'Tous mes ordres', default => 'Mes ordres' },
    ])

    @include('dashboards.partials.sheet-chart', ['chartTitle' => 'Mes réparations terminées', 'chartUnit' => ['réparation', 'réparations']])

    @include('dashboards.partials.sheet-timeline')

    <x-slot:aside>
        @include('dashboards.partials.sheet-oncall')
        @include('dashboards.partials.sheet-panel', ['panel' => $sideB])
        @include('dashboards.partials.sheet-panel', ['panel' => $sideA, 'link' => ['Mon planning', route('planning.technician', auth()->user())]])
        @include('dashboards.partials.sheet-activity')
    </x-slot:aside>
</x-app-layout>
