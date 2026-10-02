{{-- Indicateurs du tableau de bord (admin, manager) : bande de la Supervision, tuiles 2 × 2
     sur téléphone. Couleur fournie par DashboardController::pulse() ; chaque indicateur
     ouvre son onglet ($here) ou sa propre page ('url'). --}}
<x-kpi-band tiles :items="collect($pulse)->map(fn (array $p) => [
    'label' => $p['label'],
    'value' => $p['value'],
    'sub' => $p['sub'],
    'dot' => \App\Support\Swatch::bg($p['color'] ?? 'navy'),
    'href' => $p['url'] ?? $here(['filter' => $p['filter']]),
    'active' => $p['filter'] !== null && $filter === $p['filter'],
])->all()" />
