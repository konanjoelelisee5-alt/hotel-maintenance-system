<div class="bg-white rounded-xl border border-slate-200">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-navy-900 text-sm">Pièces réservées</h3>
    </div>

    <div class="p-5 space-y-3">
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
            <p class="text-sm text-slate-500">Aucune pièce réservée pour cet OT.</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($workOrder->partReservations as $reservation)
                    <div class="flex justify-between items-start gap-2 py-3 first:pt-0 text-sm">
                        <div>
                            <p class="text-slate-800">{{ $reservation->part->name }}</p>
                            <p class="text-xs text-slate-500">{{ $reservation->quantity }} {{ $reservation->part->unit }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 mt-1 text-xs rounded-full
                                {{ $reservation->status === 'reservee' ? 'bg-gold-100 text-gold-700' : '' }}
                                {{ $reservation->status === 'sortie' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $reservation->status === 'annulee' ? 'bg-slate-100 text-slate-500' : '' }}">
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
</div>
