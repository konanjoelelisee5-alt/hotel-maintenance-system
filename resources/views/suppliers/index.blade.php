<x-app-layout :crumb="'Stock & achats'" :page-title="'Fournisseurs'">
    <x-slot:primaryAction>
        <a href="{{ route('suppliers.create') }}" data-modal class="btn btn-primary">+ Nouveau fournisseur</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full">


            <div class="bg-white p-4 rounded-lg shadow-sm mb-4">
                <form method="GET" class="flex gap-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher un fournisseur..."
                        class="flex-1 border-gray-300 rounded-md shadow-sm text-sm">
                    <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                        Rechercher
                    </button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Téléphone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($suppliers as $supplier)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $supplier->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $supplier->contact_person ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $supplier->phone ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if ($supplier->is_active)
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Actif</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">Inactif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="text-indigo-600 hover:text-indigo-900">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Aucun fournisseur trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">{{ $suppliers->links() }}</div>

        </div>
    </div>
</x-app-layout>