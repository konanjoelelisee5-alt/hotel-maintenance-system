<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bon de commande {{ $purchaseOrder->number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <!-- Informations générales -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm text-gray-500">Fournisseur</p>
                        <p class="font-medium text-gray-800">{{ $purchaseOrder->supplier->name }}</p>
                    </div>
                    <x-purchase-order-status-badge :status="$purchaseOrder->status" class="text-sm" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-gray-600">
                    <div><span class="font-medium">Date commande :</span> {{ $purchaseOrder->order_date->format('d/m/Y') }}</div>
                    <div><span class="font-medium">Livraison prévue :</span> {{ $purchaseOrder->expected_delivery_date?->format('d/m/Y') ?? '—' }}</div>
                    @if ($purchaseOrder->workOrder)
                        <div><span class="font-medium">OT lié :</span>
                            <a href="{{ route('work-orders.show', $purchaseOrder->workOrder) }}" class="text-indigo-600 hover:underline">
                                #{{ $purchaseOrder->workOrder->id }}
                            </a>
                        </div>
                    @endif
                </div>

                @if ($purchaseOrder->notes)
                    <p class="mt-4 text-sm text-gray-600 border-t pt-4">{{ $purchaseOrder->notes }}</p>
                @endif
            </div>

            <!-- Changement de statut -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Statut</h3>
                <form method="POST" action="{{ route('purchase-orders.status.update', $purchaseOrder) }}" class="flex gap-3">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="block flex-1 border-gray-300 rounded-md shadow-sm">
                        <option value="brouillon" @selected($purchaseOrder->status === 'brouillon')>Brouillon</option>
                        <option value="envoyee" @selected($purchaseOrder->status === 'envoyee')>Envoyée</option>
                        <option value="confirmee" @selected($purchaseOrder->status === 'confirmee')>Confirmée</option>
                        <option value="annulee" @selected($purchaseOrder->status === 'annulee')>Annulée</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                        Mettre à jour
                    </button>
                </form>
                <p class="text-xs text-gray-400 mt-2">
                    Les statuts "Réceptionnée" et "Facturée" se mettent à jour automatiquement via les sections ci-dessous.
                </p>
            </div>

            <!-- Articles + Réception -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Articles commandés</h3>

                <form method="POST" action="{{ route('purchase-orders.reception.store', $purchaseOrder) }}">
                    @csrf
                    <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase border-b">
                                <th class="py-2">Description</th>
                                <th class="py-2 text-right">Qté commandée</th>
                                <th class="py-2 text-right">Prix unitaire</th>
                                <th class="py-2 text-right">Sous-total</th>
                                <th class="py-2 text-right">Qté reçue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchaseOrder->items as $item)
                                <tr class="border-b">
                                    <td class="py-3">{{ $item->description }}</td>
                                    <td class="py-3 text-right">{{ $item->quantity }}</td>
                                    <td class="py-3 text-right">{{ number_format($item->unit_price, 2) }} €</td>
                                    <td class="py-3 text-right">{{ number_format($item->subtotal, 2) }} €</td>
                                    <td class="py-3 text-right">
                                        <input type="number" name="received[{{ $item->id }}]" min="0" max="{{ $item->quantity }}"
                                               value="{{ $item->received_quantity }}"
                                               class="w-20 border-gray-300 rounded-md shadow-sm text-sm text-right">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="py-3 text-right font-medium">Total</td>
                                <td class="py-3 text-right font-medium">{{ number_format($purchaseOrder->total_amount, 2) }} €</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                            Enregistrer la réception
                        </button>
                    </div>
                </form>
            </div>

            <!-- Factures -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Factures</h3>

                @foreach ($purchaseOrder->invoices as $invoice)
                    <div class="flex justify-between items-center border-b py-3 text-sm">
                        <div>
                            <span class="font-medium">{{ $invoice->invoice_number }}</span> —
                            {{ number_format($invoice->amount, 2) }} € —
                            {{ $invoice->invoice_date->format('d/m/Y') }}
                            @if ($invoice->file_path)
                                <a href="{{ $invoice->file_url }}" target="_blank" class="text-indigo-600 hover:underline ml-2">📄 Voir</a>
                            @endif
                        </div>
                        <div>
                            @if ($invoice->is_paid)
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Payée</span>
                            @else
                                <form method="POST" action="{{ route('purchase-orders.invoices.paid', [$purchaseOrder, $invoice]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs text-indigo-600 hover:underline">Marquer payée</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach

                <form method="POST" action="{{ route('purchase-orders.invoices.store', $purchaseOrder) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input type="text" name="invoice_number" placeholder="N° facture" class="border-gray-300 rounded-md shadow-sm text-sm" required>
                        <input type="date" name="invoice_date" class="border-gray-300 rounded-md shadow-sm text-sm" required>
                        <input type="number" name="amount" step="0.01" placeholder="Montant" class="border-gray-300 rounded-md shadow-sm text-sm" required>
                    </div>
                    <input type="file" name="file" class="text-sm">
                    <x-input-error :messages="$errors->get('invoice_number')" class="mt-2" />
                    <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                        Ajouter la facture
                    </button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>