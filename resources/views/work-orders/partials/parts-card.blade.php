@php
    $reservationStyles = [
        'reservee' => 'bg-[#FBF1DF] text-[#7A5A16]',
        'sortie' => 'bg-[#E6F3EC] text-green',
        'annulee' => 'bg-line-soft text-ink-grey',
    ];
@endphp

<x-panel id="parts" title="Pièces réservées" icon="part" flush>
    <x-slot:badge>
        @if ($workOrder->partReservations->isNotEmpty())
            <span class="px-2 py-0.5 rounded-full bg-line-soft text-[11.5px] font-semibold text-[#4A4639]">{{ $workOrder->partReservations->count() }}</span>
        @endif
    </x-slot:badge>

    @if ($workOrder->partReservations->isEmpty())
        <p class="m-0 px-5 py-4 text-[13px] text-ink-grey">Aucune pièce réservée pour cet OT.</p>
    @else
        <ul class="m-0 p-0 list-none divide-y divide-line-soft">
            @foreach ($workOrder->partReservations as $reservation)
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="min-w-0">
                        <p class="m-0 text-[13.5px] font-semibold text-navy truncate">{{ $reservation->part->name }}</p>
                        <p class="m-0 flex items-center gap-2 mt-0.5 text-[12px] text-[#6C6658]">
                            {{ $reservation->quantity }} {{ $reservation->part->unit }}
                            <span class="px-1.5 py-0.5 rounded-md text-[11px] font-semibold {{ $reservationStyles[$reservation->status] ?? '' }}">{{ $reservation->status_label }}</span>
                        </p>
                    </div>

                    @can('intervene', $workOrder)
                        @if ($reservation->status === 'reservee')
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                {{-- Sortie physique du magasin : par l'intervenant assigné uniquement. --}}
                                @can('perform', $workOrder)
                                    <form method="POST" action="{{ route('work-orders.reservations.withdraw', [$workOrder, $reservation]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary">Sortir</button>
                                    </form>
                                @endcan
                                <form method="POST" action="{{ route('work-orders.reservations.cancel', [$workOrder, $reservation]) }}"
                                      data-confirm="La pièce redevient disponible au magasin." data-confirm-title="Annuler cette réservation ?" data-confirm-label="Annuler la réservation" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-ghost text-red" aria-label="Annuler la réservation">Annuler</button>
                                </form>
                            </div>
                        @endif
                    @endcan
                </li>
            @endforeach
        </ul>
    @endif

    @can('intervene', $workOrder)
        <form method="POST" action="{{ route('work-orders.reservations.store', $workOrder) }}" class="flex flex-col gap-2 px-5 py-4 border-t border-line-soft bg-paper/50">
            @csrf
            <select name="part_id" required aria-label="Pièce à réserver">
                <option value="">Choisir une pièce…</option>
                @foreach (\App\Models\Part::where('is_active', true)->orderBy('name')->get() as $part)
                    <option value="{{ $part->id }}">{{ $part->name }} (dispo : {{ $part->quantity_available }})</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <input type="number" name="quantity" min="1" value="1" aria-label="Quantité" class="!w-24">
                <button type="submit" class="btn btn-primary flex-1"><x-nav-icon name="part" /> Réserver</button>
            </div>
            <x-input-error :messages="$errors->get('part_id')" />
        </form>
    @endcan
</x-panel>
