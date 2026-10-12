{{-- Tableau de bord de l'administrateur (chef de maintenance, informatique), dans layouts.sheet.
     « Comment va le service ? » ; le travail ordre par ordre se fait dans Ordres de travail :
     - au centre : bonjour, les 5 repères (chacun ouvre Ordres de travail sur le bon onglet),
       la courbe des ordres créés, les 3 ordres les plus urgents, le déroulé du jour ;
     - à droite : l'astreinte, la santé du SLA (sur la période choisie), les alertes système
       et les tâches automatiques (ce que seul l'admin corrige).
     Pas de fil d'activité ici (le bouton « onglets » façon Chrome a été retiré à la demande de
     l'utilisateur, le 12/10/2026, jugé encombrant) : l'activité se lit dans chaque fiche OT.
     Ce qui existe déjà dans Paramètres n'est pas répété ici : le journal d'activité
     (Paramètres › Sécurité) ; sur téléphone et tablette, la carte d'astreinte
     (Paramètres › Astreinte, « De garde maintenant »). --}}
@php
    $here = fn (?string $f, array $extra = []) => route('admin.dashboard', array_merge(['filter' => $f, 'period' => $period], $extra));
    $icons = ['urgent' => 'alert', 'unassigned' => 'user', 'late' => 'clock', 'to_review' => 'shield', 'waiting' => 'pause'];
    $kpis = collect($pulse)->map(fn (array $p) => [
        'label' => $p['label'], 'value' => $p['value'], 'icon' => $icons[$p['filter']] ?? 'list',
        'trend' => $p['sub'], 'tone' => 'muted',
        'href' => $p['url'] ?? route('work-orders.index', ['filter' => $p['filter']]),
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

    @include('dashboards.partials.sheet-kpis')

    @include('dashboards.partials.sheet-chart', ['chartTitle' => 'Ordres de travail créés', 'chartUnit' => ['ordre', 'ordres']])

    {{-- Pas d'onglets ici (ils sont dans Ordres de travail) : seulement les vrais feux. --}}
    @include('dashboards.partials.sheet-queue', [
        'queueTitle' => 'Urgences à traiter',
        'queue' => ($filter === 'urgent' ? $queue : \App\Support\WorkQueue::top(auth()->user(), 'urgent'))->take(3),
        'here' => fn ($f) => $here($f),
        'tabs' => false,
        'moreUrl' => route('work-orders.index', ['filter' => 'urgent']),
        'moreLabel' => 'Voir toutes les urgences',
    ])

    @include('dashboards.partials.sheet-timeline')

    <x-slot:aside>
        {{-- Téléphone et tablette : l'astreinte se consulte dans Paramètres › Astreinte. --}}
        <div class="hidden desk:block">@include('dashboards.partials.sheet-oncall')</div>

        {{-- Santé du SLA, sur la période choisie --}}
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-1 p-1 rounded-full bg-paper self-start" role="group" aria-label="Période">
                @foreach ($periods as $key => [$short])
                    <a href="{{ $here($filter, ['period' => $key]) }}" @if ($period === $key) aria-current="true" @endif
                       class="tap h-7 px-3 rounded-full inline-flex items-center text-[12.5px] tabular transition-colors {{ $period === $key ? 'bg-white font-bold shadow-[0_4px_12px_-8px_rgba(23,25,31,.5)]' : 'font-semibold text-ink-muted hover:text-ink-deep' }}">{{ $short }}</a>
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
    </x-slot:aside>
</x-app-layout>
