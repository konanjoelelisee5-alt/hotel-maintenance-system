<x-accordion-card id="parts" title="Pièces réservées" :open="$workOrder->partReservations->isNotEmpty()">
    <div class="space-y-3">
        @can('intervene', $workOrder)
            <form method="POST" action="{{ route('work-orders.reservations.store', $workOrder) }}" class="space-y-2">
                @csrf
                <select name="part_id" class="w-full border-slate-300 rounded-md shadow-sm text-sm" required>
                    <option value="">-- Choisir une pièce --</option>
                    @foreach (\App\Models\Part::where('is_active', true)->orderBy('name')->get() as $part)
                        <option value="{{ $part->id }}">{{ $part->name }} (dispo : {{ $part->quantity_available }})</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <input type="number" name="quantity" min="1" value="1"
                           class="w-20 border-slate-300 rounded-md shadow-sm text-sm">
                    <button type="submit" class="flex-1 px-3 py-2 bg-navy-800 text-white text-xs font-medium rounded-md hover:bg-navy-900">
                        Réserver
                    </button>
                </div>
                <x-input-error :messages="$errors->get('part_id')" />
            </form>
        @endcan

        @if ($workOrder->partReservations->isEmpty())
            <p class="text-sm text-ink-grey">Aucune pièce réservée pour cet OT.</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($workOrder->partReservations as $reservation)
                    <div class="flex justify-between items-start gap-2 py-3 first:pt-0 text-sm">
                        <div>
                            <p class="text-slate-800">{{ $reservation->part->name }}</p>
                            <p class="text-xs text-ink-grey">{{ $reservation->quantity }} {{ $reservation->part->unit }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 mt-1 text-xs rounded-full
                                {{ $reservation->status === 'reservee' ? 'bg-gold-100 text-gold-700' : '' }}
                                {{ $reservation->status === 'sortie' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $reservation->status === 'annulee' ? 'bg-slate-100 text-ink-grey' : '' }}">
                                {{ $reservation->status_label }}
                            </span>
                        </div>

                        @can('intervene', $workOrder)
                            @if ($reservation->status === 'reservee')
                                <div class="flex flex-col gap-1 text-xs whitespace-nowrap">
                                    <form method="POST" action="{{ route('work-orders.reservations.withdraw', [$workOrder, $reservation]) }}">
                                        @csrf
                                        <button type="submit" class="text-emerald-600 hover:underline">Sortir</button>
                                    </form>
                                    <form method="POST" action="{{ route('work-orders.reservations.cancel', [$workOrder, $reservation]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Annuler</button>
                                    </form>
                                </div>
                            @endif
                        @endcan
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-accordion-card>
