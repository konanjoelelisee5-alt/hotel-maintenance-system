<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pièces & Stock') }}</h2>
            <a href="{{ route('parts.create') }}" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Nouvelle pièce
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <div class="bg-white p-4 rounded-lg shadow-sm mb-4">
                <form method="GET" class="flex flex-wrap gap-4 items-center">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom ou SKU..."
                        class="flex-1 border-gray-300 rounded-md shadow-sm text-sm">

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="low_stock" value="1" @checked(request('low_stock')) class="rounded border-gray-300">
                        Stock bas uniquement
                    </label>

                    <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                        Filtrer
                    </button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">En stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Réservé</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disponible</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seuil</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($parts as $part)
                            <tr class="hover:bg-gray-50 {{ $part->is_below_threshold ? 'bg-red-50' : '' }}">
                                <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ $part->sku }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $part->name }}
                                    @if ($part->is_below_threshold)
                                        <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">⚠ Stock bas</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $part->quantity_on_hand }} {{ $part->unit }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $part->quantity_reserved }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $part->quantity_available }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $part->reorder_threshold }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('parts.show', $part) }}" class="text-indigo-600 hover:text-indigo-900">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">Aucune pièce trouvée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">{{ $parts->links() }}</div>

        </div>
    </div>
</x-app-layout>