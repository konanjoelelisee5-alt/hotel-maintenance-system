<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $part->name }} <span class="text-gray-400 font-normal text-base">({{ $part->sku }})</span></h2>
            <a href="{{ route('parts.edit', $part) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                Modifier
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <!-- Aperçu du stock -->
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white p-4 shadow-sm rounded-lg">
                    <p class="text-sm text-gray-500">En stock</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $part->quantity_on_hand }}</p>
                </div>
                <div class="bg-white p-4 shadow-sm rounded-lg">
                    <p class="text-sm text-gray-500">Réservé</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $part->quantity_reserved }}</p>
                </div>
                <div class="bg-white p-4 shadow-sm rounded-lg">
                    <p class="text-sm text-gray-500">Disponible</p>
                    <p class="text-2xl font-bold {{ $part->is_below_threshold ? 'text-red-600' : 'text-green-600' }}">{{ $part->quantity_available }}</p>
                </div>
                <div class="bg-white p-4 shadow-sm rounded-lg">
                    <p class="text-sm text-gray-500">Seuil d'alerte</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $part->reorder_threshold }}</p>
                </div>
            </div>

            @if ($part->is_below_threshold)
                <div class="p-4 bg-red-100 text-red-800 rounded-md text-sm">
                    ⚠ Le stock de cette pièce est en dessous du seuil d'alerte. Pensez à réapprovisionner.
                </div>
            @endif

            <!-- Mouvement manuel -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Enregistrer un mouvement</h3>
                <form method="POST" action="{{ route('parts.movements.store', $part) }}" class="flex flex-wrap gap-3 items-end">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Type</label>
                        <select name="type" class="border-gray-300 rounded-md shadow-sm text-sm" required>
                            <option value="entree">Entrée</option>
                            <option value="sortie">Sortie</option>
                            <option value="ajustement">Ajustement (inventaire)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Quantité</label>
                        <input type="number" name="quantity" class="w-28 border-gray-300 rounded-md shadow-sm text-sm" required>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs text-gray-500 mb-1">Note (optionnel)</label>
                        <input type="text" name="note" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        Enregistrer
                    </button>
                </form>
                <x-input-error :messages="$errors->all()" class="mt-2" />
                <p class="text-xs text-gray-500 mt-2">Pour un ajustement d'inventaire, la quantité peut être négative (ex: -2 si le stock réel est inférieur).</p>
            </div>

            <!-- Historique des mouvements -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Historique des mouvements</h3>

                @forelse ($part->stockMovements as $movement)
                    <div class="flex justify-between items-center border-t py-3 text-sm">
                        <div>
                            <span class="font-medium">{{ $movement->type_label }}</span>
                            — {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }} {{ $part->unit }}
                            @if ($movement->workOrder)
                                — OT #{{ $movement->work_order_id }}
                            @endif
                            @if ($movement->note)
                                <span class="text-gray-500 italic">({{ $movement->note }})</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 text-right">
                            {{ $movement->created_at->format('d/m/Y H:i') }}<br>
                            {{ $movement->creator->name }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucun mouvement enregistré.</p>
                @endforelse
            </div>

            <!-- Réservations actives -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Réservations</h3>

                @forelse ($part->reservations as $reservation)
                    <div class="flex justify-between items-center border-t py-3 text-sm">
                        <div>
                            OT #{{ $reservation->work_order_id }} — {{ $reservation->quantity }} {{ $part->unit }}
                        </div>
                        <span class="px-2 py-0.5 text-xs rounded-full
                            {{ $reservation->status === 'reservee' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{ $reservation->status === 'sortie' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $reservation->status === 'annulee' ? 'bg-gray-100 text-gray-500' : '' }}">
                            {{ $reservation->status_label }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucune réservation pour cette pièce.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>