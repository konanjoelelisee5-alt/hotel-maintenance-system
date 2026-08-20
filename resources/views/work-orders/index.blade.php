<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Ordres de travail') }}
            </h2>
            <a href="{{ route('work-orders.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Nouvel OT
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Filtres -->
            <div class="bg-white p-4 rounded-lg shadow-sm mb-4">
                <form method="GET" action="{{ route('work-orders.index') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Statut</label>
                        <select name="status" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Tous</option>
                            <option value="ouvert" @selected(request('status') === 'ouvert')>Ouvert</option>
                            <option value="en_cours" @selected(request('status') === 'en_cours')>En cours</option>
                            <option value="en_attente" @selected(request('status') === 'en_attente')>En attente</option>
                            <option value="resolu" @selected(request('status') === 'resolu')>Résolu</option>
                            <option value="ferme" @selected(request('status') === 'ferme')>Fermé</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Priorité</label>
                        <select name="priority" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Toutes</option>
                            <option value="basse" @selected(request('priority') === 'basse')>Basse</option>
                            <option value="moyenne" @selected(request('priority') === 'moyenne')>Moyenne</option>
                            <option value="haute" @selected(request('priority') === 'haute')>Haute</option>
                            <option value="urgente" @selected(request('priority') === 'urgente')>Urgente</option>
                        </select>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                        Filtrer
                    </button>

                    @if (request()->hasAny(['status', 'priority']))
                        <a href="{{ route('work-orders.index') }}" class="text-sm text-gray-500 underline">
                            Réinitialiser
                        </a>
                    @endif
                </form>
            </div>

            <!-- Tableau -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Titre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lieu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigné à</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priorité</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Créé le</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($workOrders as $workOrder)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $workOrder->title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $workOrder->room?->number ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $workOrder->assignee?->name ?? 'Non assigné' }}</td>
                                <td class="px-6 py-4">
                                    <x-work-order-priority-badge :priority="$workOrder->priority" />
                                </td>
                                <td class="px-6 py-4">
                                    <x-work-order-status-badge :status="$workOrder->status" />
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $workOrder->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('work-orders.show', $workOrder) }}" class="text-indigo-600 hover:text-indigo-900">
                                        Voir
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Aucun ordre de travail trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $workOrders->links() }}
            </div>

        </div>
    </div>
</x-app-layout>