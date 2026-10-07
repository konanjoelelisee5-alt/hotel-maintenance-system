@php
    $reservationStyles = [
        'reservee' => 'bg-warn-bg text-warn-ink',
        'sortie' => 'bg-ok-bg text-green',
        'annulee' => 'bg-line-soft text-ink-grey',
    ];
@endphp

<x-panel id="parts" title="Pièces" icon="part" flush>
    <x-slot:badge>
        @if ($workOrder->partReservations->isNotEmpty())
            <span class="px-2 py-0.5 rounded-full bg-line-soft text-[11.5px] font-semibold text-ink-body">{{ $workOrder->partReservations->count() }}</span>
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
                        <p class="m-0 flex items-center gap-2 mt-0.5 text-[12px] text-ink-muted">
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

    {{-- Pièces absentes du magasin : demandées au manager (PartRequestController). --}}
    @if ($workOrder->partRequests->isNotEmpty())
        <div class="border-t border-line-soft">
            <p class="m-0 px-5 pt-3 text-[11px] font-semibold uppercase tracking-wide text-ink-grey">Demandées au manager</p>
            <ul class="m-0 p-0 list-none divide-y divide-line-soft">
                @foreach ($workOrder->partRequests as $partRequest)
                    @php $pending = $partRequest->status === 'demandee'; @endphp
                    <li class="px-5 py-3">
                        <p class="m-0 flex items-start justify-between gap-3">
                            <span class="text-[13.5px] font-semibold text-navy">{{ $partRequest->quantity }} × {{ $partRequest->description }}</span>
                            <span class="px-1.5 py-0.5 rounded-md text-[11px] font-semibold whitespace-nowrap {{ $pending ? 'bg-warn-bg text-warn-ink' : 'bg-ok-bg text-green' }}">{{ $pending ? 'Demandée' : 'Traitée' }}</span>
                        </p>
                        <p class="m-0 mt-0.5 text-[12px] text-ink-muted">
                            {{ $partRequest->requester?->name ?? '—' }} · {{ $partRequest->created_at->format('d/m H\hi') }}
                            @unless ($pending)
                                — traitée par {{ $partRequest->handler?->name ?? '—' }} le {{ $partRequest->handled_at->format('d/m H\hi') }}
                            @endunless
                        </p>
                        @if ($partRequest->handling_note)
                            <p class="m-0 mt-1 text-[12.5px] text-ink-strong">{{ $partRequest->handling_note }}</p>
                        @endif
                        @if ($pending && auth()->user()->role?->dispatchesWork())
                            <form method="POST" action="{{ route('part-requests.handle', $partRequest) }}" class="flex flex-col sm:flex-row gap-2 mt-2">
                                @csrf
                                <input type="text" name="handling_note" maxlength="300" aria-label="Réponse au technicien" placeholder="Ex. : commandée, livraison jeudi">
                                <button type="submit" class="btn btn-secondary whitespace-nowrap">Marquer traitée</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @can('requestPart', $workOrder)
        <details class="border-t border-line-soft group" @if ($errors->has('description')) open @endif>
            <summary class="flex items-center gap-2 px-5 py-3 cursor-pointer text-[13px] font-semibold text-navy list-none">
                <x-nav-icon name="send" class="w-4 h-4 text-gold" /> La pièce n'est pas au magasin ?
            </summary>
            <form method="POST" action="{{ route('work-orders.part-requests.store', $workOrder) }}" class="flex flex-col gap-2 px-5 pb-4">
                @csrf
                <input type="text" name="description" maxlength="200" required value="{{ old('description') }}" aria-label="Pièce demandée" placeholder="Nom, marque, dimension… (ex. : mitigeur Grohe 1/2)">
                <div class="flex gap-2">
                    <input type="number" name="quantity" min="1" max="999" value="{{ old('quantity', 1) }}" aria-label="Quantité" class="!w-24">
                    <button type="submit" class="btn btn-primary flex-1"><x-nav-icon name="send" /> Demander au manager</button>
                </div>
                @if (in_array($workOrder->status, ['ouvert', 'en_cours'], true))
                    <label class="!h-auto !py-2 !items-start">
                        <input type="hidden" name="suspend" value="0">
                        <input type="checkbox" name="suspend" value="1" checked>
                        <span class="text-[13px] font-normal">Mettre l'ordre en attente de la pièce</span>
                    </label>
                @endif
                <x-input-error :messages="$errors->get('description')" />
                <x-input-error :messages="$errors->get('quantity')" />
            </form>
        </details>
    @endcan
</x-panel>
