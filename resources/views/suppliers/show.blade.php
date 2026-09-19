<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $supplier->name }}</h2>
            <a href="{{ route('suppliers.edit', $supplier) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                Modifier
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

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
                        <span class="text-gray-500">{{ number_format($po->total_amount, 2) }} €</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucune commande pour ce fournisseur.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>