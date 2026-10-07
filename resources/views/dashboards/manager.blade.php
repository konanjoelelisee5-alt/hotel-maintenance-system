{{-- Tableau de bord du manager (répartit le travail le jour), dans layouts.sheet : même
     structure que l'admin ; à droite, la charge des techniciens et les achats, le stock, le
     préventif et les pièces demandées à traiter (pas d'outils d'administration). --}}
@php
    $here = fn (?string $f) => route('manager.dashboard', ['filter' => $f]);
    $icons = ['urgent' => 'alert', 'unassigned' => 'user', 'late' => 'clock', 'to_review' => 'shield', 'waiting' => 'pause'];
    $kpis = collect($pulse)->map(fn (array $p) => [
        'label' => $p['label'], 'value' => $p['value'], 'icon' => $icons[$p['filter']] ?? 'part',
        'trend' => $p['sub'], 'tone' => 'muted',
        'href' => $p['url'] ?? $here($p['filter']), 'active' => $p['filter'] !== null && $filter === $p['filter'],
    ])->all();
    $unassigned = collect($pulse)->firstWhere('filter', 'unassigned')['value'] ?? 0;
    $toReview = collect($pulse)->firstWhere('filter', 'to_review')['value'] ?? 0;
@endphp

<x-app-layout page-title="Tableau de bord">
    <x-slot:greeting>
        @include('dashboards.partials.sheet-greeting', ['summary' => $unassigned || $toReview
            ? trim(($unassigned ? $unassigned.' ordre'.($unassigned > 1 ? 's' : '').' à répartir' : '').($unassigned && $toReview ? ', ' : '').($toReview ? $toReview.' à contrôler' : '')).'.'
            : 'Tout le travail est réparti.'])
    </x-slot:greeting>

    @include('dashboards.partials.sheet-pilot-search')

    @include('dashboards.partials.sheet-kpis')

    @include('dashboards.partials.sheet-chart', ['chartTitle' => 'Ordres de travail créés', 'chartUnit' => ['ordre', 'ordres']])

    @include('dashboards.partials.sheet-queue', [
        'queueTitle' => match ($filter) { 'urgent' => 'Ordres urgents', 'unassigned' => 'Ordres à répartir', 'late' => 'Ordres en retard SLA', 'to_review' => 'Ordres à contrôler', 'waiting' => 'Ordres en attente', default => 'Tous les ordres' },
    ])

    @include('dashboards.partials.sheet-timeline')

    <x-slot:aside>
        @include('dashboards.partials.sheet-oncall')
        @include('dashboards.partials.sheet-panel', ['panel' => $sideA, 'link' => ['Planning', route('planning.index')]])
        @include('dashboards.partials.sheet-panel', ['panel' => $sideB, 'link' => ['Achats', route('purchase-orders.index', ['tab' => 'open'])]])
        @include('dashboards.partials.sheet-activity')
    </x-slot:aside>
</x-app-layout>
