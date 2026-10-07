{{-- Tableau de bord de l'administrateur (chef de maintenance, informatique), dans layouts.sheet :
     - au centre : bonjour, recherche + nouvel ordre, les 5 repères (chacun ouvre son onglet),
       la courbe des ordres créés, la file des ordres triée par urgence, le déroulé du jour ;
     - à droite : l'astreinte, la santé du SLA (sur la période choisie), les alertes système
       et les tâches automatiques (ce que seul l'admin corrige), l'activité administrative,
       le fil d'activité des chambres. --}}
@php
    $here = fn (?string $f, array $extra = []) => route('admin.dashboard', array_merge(['filter' => $f, 'period' => $period], $extra));
    $icons = ['urgent' => 'alert', 'unassigned' => 'user', 'late' => 'clock', 'to_review' => 'shield', 'waiting' => 'pause'];
    $kpis = collect($pulse)->map(fn (array $p) => [
        'label' => $p['label'], 'value' => $p['value'], 'icon' => $icons[$p['filter']] ?? 'list',
        'trend' => $p['sub'], 'tone' => 'muted',
        'href' => $p['url'] ?? $here($p['filter']), 'active' => $p['filter'] !== null && $filter === $p['filter'],
    ])->all();
    $late = collect($pulse)->firstWhere('filter', 'late')['value'] ?? 0;
    $unassigned = collect($pulse)->firstWhere('filter', 'unassigned')['value'] ?? 0;
@endphp

<x-app-layout page-title="Tableau de bord">
    <x-slot:greeting>
        @include('dashboards.partials.sheet-greeting', ['summary' => $late || $unassigned
            ? trim(($unassigned ? $unassigned.' ordre'.($unassigned > 1 ? 's' : '').' à affecter' : '').($unassigned && $late ? ', ' : '').($late ? $late.' en retard' : '')).'.'
            : 'Tout est sous contrôle.'])
    </x-slot:greeting>

    @include('dashboards.partials.sheet-pilot-search')

    @include('dashboards.partials.sheet-kpis')

    @include('dashboards.partials.sheet-chart', ['chartTitle' => 'Ordres de travail créés', 'chartUnit' => ['ordre', 'ordres']])

    @include('dashboards.partials.sheet-queue', [
        'queueTitle' => match ($filter) { 'urgent' => 'Ordres urgents', 'unassigned' => 'Ordres non affectés', 'late' => 'Ordres en retard SLA', 'to_review' => 'Ordres à contrôler', 'waiting' => 'Ordres en attente', default => 'Tous les ordres' },
        'here' => fn ($f) => $here($f),
    ])

    @include('dashboards.partials.sheet-timeline')

    <x-slot:aside>
        @include('dashboards.partials.sheet-oncall')

        {{-- Santé du SLA, sur la période choisie --}}
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-1 p-1 rounded-full bg-paper self-start" role="group" aria-label="Période">
                @foreach ($periods as $key => [$short])
                    <a href="{{ $here($filter, ['period' => $key]) }}" @if ($period === $key) aria-current="true" @endif
                       class="h-7 px-3 rounded-full inline-flex items-center text-[12.5px] tabular transition-colors {{ $period === $key ? 'bg-white font-bold shadow-[0_4px_12px_-8px_rgba(23,25,31,.5)]' : 'font-semibold text-ink-muted hover:text-ink-deep' }}">{{ $short }}</a>
                @endforeach
            </div>
            @include('dashboards.partials.sheet-panel', ['panel' => $sideA])
        </div>

        {{-- Ce que seul l'admin peut corriger (comptes, astreinte, accès). Masqué quand tout va bien. --}}
        @if (! empty($systemAlerts))
            @include('dashboards.partials.sheet-panel', ['panel' => ['title' => 'Alertes système', 'items' => collect($systemAlerts)->map(fn ($a) => ['label' => $a['label'], 'meta' => $a['meta'], 'color' => $a['color'], 'url' => $a['url']])->all()]])
        @endif

        {{-- Une tâche automatique arrêtée ne se voit nulle part ailleurs : plus d'escalade ni
             d'OT préventif, sans aucun message d'erreur. --}}
        @include('dashboards.partials.sheet-panel', ['panel' => ['title' => 'Tâches automatiques', 'items' => collect($schedulerHealth)->map(fn ($t) => [
            'label' => $t['label'], 'meta' => $t['text'], 'color' => ['ok' => 'green', 'waiting' => 'blue', 'late' => 'amber', 'never' => 'red'][$t['state']],
        ])->all()]])

        @include('dashboards.partials.sheet-panel', ['panel' => $sideB, 'link' => ['Journal', route('activity-logs.index')]])

        @include('dashboards.partials.sheet-activity')
    </x-slot:aside>
</x-app-layout>
