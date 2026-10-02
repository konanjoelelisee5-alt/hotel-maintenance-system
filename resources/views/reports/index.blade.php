@php
    // Périodes rapides : un clic remplit « du / au ».
    $today = today();
    $presets = [
        '7 derniers jours' => [$today->copy()->subDays(6), $today],
        '30 derniers jours' => [$today->copy()->subDays(29), $today],
        'Ce mois-ci' => [$today->copy()->startOfMonth(), $today],
        'Mois dernier' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
    ];
    $from = $filters['date_from'] ?? null;
    $to = $filters['date_to'] ?? null;
    $keep = array_filter(['technician_id' => $filters['technician_id'] ?? null, 'type_id' => $filters['type_id'] ?? null]);
    $period = match (true) {
        $from && $to => 'du '.\Carbon\Carbon::parse($from)->format('d/m/Y').' au '.\Carbon\Carbon::parse($to)->format('d/m/Y'),
        (bool) $from => 'depuis le '.\Carbon\Carbon::parse($from)->format('d/m/Y'),
        (bool) $to => "jusqu'au ".\Carbon\Carbon::parse($to)->format('d/m/Y'),
        default => 'toute la période',
    };
    $types = \App\Models\WorkOrderType::where('is_active', true)->orderBy('position')->get();
    $technicianName = $technicians->firstWhere('id', $filters['technician_id'] ?? null)?->name;
    $typeLabel = $types->firstWhere('id', $filters['type_id'] ?? null)?->label;

    // Couleurs des statuts : palette de la refonte (même sens que les badges).
    $statusColors = ['Ouvert' => '#26496B', 'En cours' => '#B58435', 'En attente' => '#B4740F', 'Résolu' => '#1E7A55', 'Rejeté' => '#B3261E', 'Fermé' => '#8A8578', 'Annulé' => '#CFC8B8'];
    $statusChartColors = $byStatus->keys()->map(fn ($label) => $statusColors[$label] ?? '#8A8578')->values();
    $slaColor = $slaRate === null ? 'grey' : ($slaRate >= 90 ? 'green' : ($slaRate >= 75 ? 'amber' : 'red'));
@endphp

<x-app-layout crumb="Exploitation" page-title="Rapports & indicateurs">
    <x-slot:primaryAction>
        {{-- Les exports reprennent les filtres de l'URL : ils correspondent à ce qui est affiché. --}}
        <a href="{{ route('reports.export.csv', request()->query()) }}" class="btn btn-secondary"><x-nav-icon name="list" /> Export CSV</a>
        <a href="{{ route('reports.export.pdf', request()->query()) }}" class="btn btn-primary"><x-nav-icon name="report" /> Export PDF</a>
    </x-slot:primaryAction>

    <div class="ui-form flex flex-col gap-5">

        {{-- ===== Filtres ===== --}}
        <section class="bg-white border border-line rounded-xl">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3 px-5 py-3.5 border-b border-line-soft">
                <div class="flex gap-1.5 overflow-x-auto -mx-5 px-5 lg:mx-0 lg:px-0 pb-0.5" role="group" aria-label="Période rapide">
                    @foreach ($presets as $label => [$start, $end])
                        @php $active = $from === $start->toDateString() && $to === $end->toDateString(); @endphp
                        <a href="{{ route('reports.index', [...$keep, 'date_from' => $start->toDateString(), 'date_to' => $end->toDateString()]) }}"
                           @class(['flex-shrink-0 h-[32px] px-3.5 inline-flex items-center rounded-full border text-[12.5px] font-semibold transition',
                                   'bg-navy text-white border-navy' => $active, 'bg-white text-[#4A4639] border-line hover:bg-paper' => ! $active])>{{ $label }}</a>
                    @endforeach
                </div>
                <p class="m-0 lg:ml-auto flex items-center gap-1.5 text-[12.5px] text-[#6C6658]">
                    <x-nav-icon name="calendar" class="w-4 h-4 text-gold" />
                    <span>
                        Analyse <strong class="text-navy">{{ $period }}</strong>
                        @if ($technicianName) · {{ $technicianName }} @endif
                        @if ($typeLabel) · {{ $typeLabel }} @endif
                    </span>
                </p>
            </div>

            <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-2 lg:grid-cols-[1fr_1fr_1.3fr_1.3fr_auto] gap-3 items-end px-5 py-4">
                <div>
                    <label for="date_from">Du</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $from }}">
                </div>
                <div>
                    <label for="date_to">Au</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $to }}">
                </div>
                <div>
                    <label for="technician_id">Technicien</label>
                    <select id="technician_id" name="technician_id">
                        <option value="">Tous les techniciens</option>
                        @foreach ($technicians as $technician)
                            <option value="{{ $technician->id }}" @selected(($filters['technician_id'] ?? null) == $technician->id)>{{ $technician->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="type_id">Type d'intervention</label>
                    <select id="type_id" name="type_id">
                        <option value="">Tous les types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(($filters['type_id'] ?? null) == $type->id)>{{ $type->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 lg:col-span-1 flex gap-2">
                    <button type="submit" class="btn btn-primary flex-1 lg:flex-none h-[44px]"><x-nav-icon name="search" /> Filtrer</button>
                    @if (request()->hasAny(['date_from', 'date_to', 'technician_id', 'type_id']))
                        <a href="{{ route('reports.index') }}" class="btn btn-ghost h-[44px]">Réinitialiser</a>
                    @endif
                </div>
            </form>
        </section>

        {{-- ===== Indicateurs ===== --}}
        <section class="bg-white border border-line rounded-xl overflow-hidden">
            <dl class="m-0 grid grid-cols-2 lg:grid-cols-5 gap-px bg-line-soft">
                <div class="bg-white px-5 py-4 flex flex-col gap-1">
                    <dt class="flex items-center gap-2 text-[12.5px] text-[#4A4639]"><span class="w-[7px] h-[7px] rounded-full bg-navy"></span> Ordres de travail</dt>
                    <dd class="m-0 text-[28px] leading-tight font-semibold tracking-tight text-navy">{{ $totalCount }}</dd>
                    <dd class="m-0 text-[12px] text-ink-grey">{{ $openCount }} ouvert(s) · {{ $resolvedCount }} terminé(s)</dd>
                </div>
                <div class="bg-white px-5 py-4 flex flex-col gap-1">
                    <dt class="flex items-center gap-2 text-[12.5px] text-[#4A4639]"><span class="w-[7px] h-[7px] rounded-full bg-blue"></span> Temps moyen de résolution</dt>
                    <dd class="m-0 text-[28px] leading-tight font-semibold tracking-tight text-navy">{{ $avgResolutionMinutes !== null ? \App\Support\Duration::human($avgResolutionMinutes) : '—' }}</dd>
                    <dd class="m-0 text-[12px] text-ink-grey">de la création à la réparation</dd>
                </div>
                <div class="bg-white px-5 py-4 flex flex-col gap-1">
                    <dt class="flex items-center gap-2 text-[12.5px] text-[#4A4639]"><span class="w-[7px] h-[7px] rounded-full {{ \App\Support\Swatch::bg($slaColor) }}"></span> Respect du SLA</dt>
                    <dd class="m-0 text-[28px] leading-tight font-semibold tracking-tight {{ $slaRate === null ? 'text-navy' : \App\Support\Swatch::text($slaColor) }}">{{ $slaRate !== null ? $slaRate.' %' : '—' }}</dd>
                    @if ($slaRate !== null)
                        <dd class="m-0 h-[5px] rounded-full bg-line-soft overflow-hidden"><span class="block h-full {{ \App\Support\Swatch::bg($slaColor) }}" style="width: {{ $slaRate }}%"></span></dd>
                    @endif
                    <dd class="m-0 text-[12px] text-ink-grey">sur {{ $slaEligibleCount }} OT soumis à un délai</dd>
                </div>
                <div class="bg-white px-5 py-4 flex flex-col gap-1">
                    <dt class="flex items-center gap-2 text-[12.5px] text-[#4A4639]"><span class="w-[7px] h-[7px] rounded-full bg-gold"></span> Rejets au contrôle qualité</dt>
                    <dd class="m-0 text-[28px] leading-tight font-semibold tracking-tight {{ $qualityRejectionRate !== null && $qualityRejectionRate > 20 ? 'text-red' : 'text-navy' }}">{{ $qualityRejectionRate !== null ? $qualityRejectionRate.' %' : '—' }}</dd>
                    <dd class="m-0 text-[12px] text-ink-grey">{{ $qualityRejectionRate !== null ? 'des contrôles réalisés' : 'aucun contrôle sur la période' }}</dd>
                </div>
                <div class="bg-white px-5 py-4 flex flex-col gap-1 col-span-2 lg:col-span-1">
                    <dt class="flex items-center gap-2 text-[12.5px] text-[#4A4639]"><span class="w-[7px] h-[7px] rounded-full bg-green"></span> Coût des achats</dt>
                    <dd class="m-0 text-[24px] leading-tight font-semibold tracking-tight text-navy whitespace-nowrap">{{ \App\Support\Money::format($totalPurchaseCost) }}</dd>
                    <dd class="m-0 text-[12px] text-ink-grey">bons de commande de la période</dd>
                </div>
            </dl>
        </section>

        {{-- ===== Graphiques ===== --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
            <x-panel title="Répartition par statut" icon="status">
                @if ($byStatus->isEmpty())
                    @include('reports.partials.empty')
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,220px)_1fr] gap-5 items-center">
                        <div class="relative mx-auto w-full max-w-[220px] aspect-square">
                            <canvas id="statusChart" aria-label="Répartition des ordres de travail par statut" role="img"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-[26px] font-semibold text-navy leading-none">{{ $totalCount }}</span>
                                <span class="text-[11.5px] text-ink-grey mt-1">ordres</span>
                            </div>
                        </div>
                        <ul class="m-0 p-0 list-none flex flex-col divide-y divide-line-soft">
                            @foreach ($byStatus as $label => $count)
                                <li class="flex items-center gap-3 py-2 text-[13px]">
                                    <span class="w-2.5 h-2.5 rounded-[3px] flex-shrink-0" style="background-color: {{ $statusColors[$label] ?? '#8A8578' }}"></span>
                                    <span class="flex-1 text-[#3d3a33]">{{ $label }}</span>
                                    <span class="font-semibold text-navy">{{ $count }}</span>
                                    <span class="w-12 text-right font-mono text-[12px] text-ink-grey">{{ round($count / max($totalCount, 1) * 100) }} %</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-panel>

            <x-panel title="Charge par technicien" icon="users">
                <x-slot:badge>
                    <span class="text-[12px] text-ink-grey">OT affectés sur la période</span>
                </x-slot:badge>
                @if ($byTechnician->isEmpty())
                    @include('reports.partials.empty')
                @else
                    <div class="relative" style="height: {{ max(180, $byTechnician->count() * 42 + 40) }}px">
                        <canvas id="technicianChart" aria-label="Nombre d'ordres de travail par technicien" role="img"></canvas>
                    </div>
                @endif
            </x-panel>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (! window.Chart) return;
                // Police et couleurs de la refonte.
                Chart.defaults.font.family = 'Archivo, Figtree, sans-serif';
                Chart.defaults.color = '#6C6658';

                const statusEl = document.getElementById('statusChart');
                if (statusEl) {
                    new Chart(statusEl, {
                        type: 'doughnut',
                        data: {
                            labels: @json($byStatus->keys()),
                            datasets: [{
                                data: @json($byStatus->values()),
                                backgroundColor: @json($statusChartColors),
                                borderColor: '#fff',
                                borderWidth: 3,
                                hoverOffset: 6,
                            }],
                        },
                        options: {
                            cutout: '70%',
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                        },
                    });
                }

                const technicianEl = document.getElementById('technicianChart');
                if (technicianEl) {
                    new Chart(technicianEl, {
                        type: 'bar',
                        data: {
                            labels: @json($byTechnician->keys()),
                            datasets: [{
                                label: "Ordres de travail",
                                data: @json($byTechnician->values()),
                                backgroundColor: '#0E2136',
                                hoverBackgroundColor: '#B58435',
                                borderRadius: 6,
                                barThickness: 22,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            maintainAspectRatio: false,
                            scales: {
                                x: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: '#F3EFE6' }, border: { display: false } },
                                y: { grid: { display: false }, border: { display: false }, ticks: { color: '#0E2136', font: { weight: 600 } } },
                            },
                            plugins: { legend: { display: false } },
                        },
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
