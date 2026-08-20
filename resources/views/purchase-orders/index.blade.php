<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Bons de commande') }}</h2>
            <a href="{{ route('purchase-orders.create') }}"
               class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Nouveau bon de commande
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Numéro</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fournisseur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppase">Date</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($purchaseOrders as $po)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $po->number }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $po->supplier->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ number_format($po->total_amount, 2) }} €</td>
                                <td class="px-6 py-4"><x-purchase-order-status-badge :status="$po->status" /></td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $po->order_date->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('purchase-orders.show', $po) }}" class="text-indigo-600 hover:text-indigo-900">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Aucun bon de commande.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $purchaseOrders->links() }}</div>

        </div>
    </div>
</x-app-layout>