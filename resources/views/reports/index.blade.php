<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Rapports & Indicateurs') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Filtres -->
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Du</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                               class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Au</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                               class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Technicien</label>
                        <select name="technician_id" class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Tous</option>
                            @foreach ($technicians as $technician)
                                <option value="{{ $technician->id }}" @selected(($filters['technician_id'] ?? null) == $technician->id)>
                                    {{ $technician->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="type_id" class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Tous</option>
                            @foreach (\App\Models\WorkOrderType::where('is_active', true)->get() as $type)
                                <option value="{{ $type->id }}" @selected(($filters['type_id'] ?? null) == $type->id)>{{ $type->label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                        Filtrer
                    </button>

                    @if (request()->hasAny(['date_from', 'date_to', 'technician_id', 'type']))
                        <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 underline">Réinitialiser</a>
                    @endif

                    <div class="ml-auto flex gap-2">
                        <!--
                            Les liens d'export reprennent automatiquement les mêmes filtres
                            actuellement dans l'URL (grâce à request()->query()), donc l'export
                            correspond toujours exactement à ce qui est affiché à l'écran.
                        -->
                        <a href="{{ route('reports.export.csv', request()->query()) }}"
                           class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                            Export CSV
                        </a>
                        <a href="{{ route('reports.export.pdf', request()->query()) }}"
                           class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                            Export PDF
                        </a>
                    </div>
                </form>
            </div>

            <!-- Cartes KPI -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 shadow-sm rounded-lg">
                    <p class="text-sm text-gray-500">Total OT</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalCount }}</p>
                </div>
                <div class="bg-white p-4 shadow-sm rounded-lg">
                    <p class="text-sm text-gray-500">Temps moyen résolution</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $avgResolutionHours }} h</p>
                </div>
                <div class="bg-white p-4 shadow-sm rounded-lg">
                    <p class="text-sm text-gray-500">Taux respect SLA</p>
                    <p class="text-2xl font-bold {{ $slaRate !== null && $slaRate < 80 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $slaRate !== null ? $slaRate . ' %' : '—' }}
                    </p>
                </div>
                <div class="bg-white p-4 shadow-sm rounded-lg">
                    <p class="text-sm text-gray-500">Coût achats</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($totalPurchaseCost, 2) }} €</p>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 shadow-sm rounded-lg">
                    <h3 class="font-medium text-gray-800 mb-4">Répartition par statut</h3>
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="bg-white p-6 shadow-sm rounded-lg">
                    <h3 class="font-medium text-gray-800 mb-4">Charge par technicien</h3>
                    <canvas id="technicianChart"></canvas>
                </div>
            </div>

            @if ($qualityRejectionRate !== null)
                <div class="bg-white p-4 shadow-sm rounded-lg">
                    <p class="text-sm text-gray-500">Taux de rejet qualité</p>
                    <p class="text-xl font-bold {{ $qualityRejectionRate > 20 ? 'text-red-600' : 'text-gray-800' }}">
                        {{ $qualityRejectionRate }} %
                    </p>
                </div>
            @endif

        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Les données PHP sont converties en JSON directement dans le JS,
                // grâce à la fonction json de Blade qui échappe correctement les caractères.
                const statusLabels = @json($byStatus->keys());
                const statusValues = @json($byStatus->values());

                new Chart(document.getElementById('statusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusValues,
                            backgroundColor: ['#2563eb', '#eab308', '#f97316', '#22c55e', '#6b7280', '#dc2626'],
                        }],
                    },
                    options: {
                        plugins: { legend: { position: 'bottom' } },
                    },
                });

                const technicianLabels = @json($byTechnician->keys());
                const technicianValues = @json($byTechnician->values());

                new Chart(document.getElementById('technicianChart'), {
                    type: 'bar',
                    data: {
                        labels: technicianLabels,
                        datasets: [{
                            label: 'Nombre d\'OT',
                            data: technicianValues,
                            backgroundColor: '#2563eb',
                        }],
                    },
                    options: {
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                        plugins: { legend: { display: false } },
                    },
                });
            });
        </script>
    @endpush
</x-app-layout>