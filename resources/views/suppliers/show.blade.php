<x-app-layout :crumb="'Stock & achats / Fournisseurs'" :page-title="$supplier->name" :back-route="route('suppliers.index')">
    <x-slot:primaryAction>
        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-secondary">Modifier</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full space-y-6">


            <div class="bg-white p-6 shadow-sm rounded-lg grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600">
                <div><span class="font-medium">Contact :</span> {{ $supplier->contact_person ?? '—' }}</div>
                <div><span class="font-medium">Téléphone :</span> {{ $supplier->phone ?? '—' }}</div>
                <div><span class="font-medium">E-mail :</span> {{ $supplier->email ?? '—' }}</div>
                <div><span class="font-medium">Adresse :</span> {{ $supplier->address ?? '—' }}</div>
            </div>

            <div class="bg-white p-6 shadow-sm rounded-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-medium text-gray-800">Bons de commande récents</h3>
                    <a href="{{ route('purchase-orders.create') }}" class="text-sm text-indigo-600 hover:underline">+ Nouvelle commande</a>
                </div>

                @forelse ($supplier->purchaseOrders as $po)
                    <div class="flex justify-between items-center border-t py-3 text-sm">
                        <a href="{{ route('purchase-orders.show', $po) }}" class="text-indigo-600 hover:underline">{{ $po->number }}</a>
                        <x-purchase-order-status-badge :status="$po->status" />
                        <span class="text-gray-500">{{ \App\Support\Money::format($po->total_amount) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucune commande pour ce fournisseur.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>