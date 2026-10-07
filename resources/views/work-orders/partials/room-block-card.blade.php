{{-- Chambre de l'OT : occupation déclarée au signalement et blocage à la vente. --}}
@php
    $room = $workOrder->room;
    $activeBlock = $room->activeBlock();
    $isOpen = in_array($workOrder->status, ['ouvert', 'en_cours', 'en_attente'], true);
    [$saleLabel, $saleDot] = $activeBlock
        ? [$activeBlock->status_label, $activeBlock->status === \App\Models\RoomBlock::BLOCKED ? 'bg-red' : 'bg-amber']
        : ['En vente', 'bg-green'];
@endphp

<x-panel title="Chambre et client" icon="building">
    <div class="flex flex-col gap-3 text-[13px]">
        <div class="flex justify-between gap-3">
            <span class="text-ink-grey">Occupation</span>
            <span class="font-medium text-right">{{ $workOrder->room_occupancy?->label() ?? 'Non précisée' }}</span>
        </div>
        <div class="flex justify-between gap-3">
            <span class="text-ink-grey">Vente</span>
            <span class="inline-flex items-center gap-1.5 font-medium text-right">
                <span class="w-2 h-2 rounded-full {{ $saleDot }}"></span> {{ $saleLabel }}
            </span>
        </div>

        @if ($workOrder->room_occupancy === \App\Enums\RoomOccupancy::ClientAbsent && $workOrder->due_date)
            @php $overdue = $workOrder->due_date->isPast() && $isOpen; @endphp
            <div class="flex gap-2.5 px-3.5 py-2.5 rounded-[10px] {{ $overdue ? 'bg-danger-soft text-danger-ink' : 'bg-warn-bg text-warn-ink' }}">
                <x-nav-icon name="clock" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>
                    À réparer avant le retour du client : <strong>{{ $workOrder->due_date->format('H\hi') }}</strong>
                    @if ($workOrder->reception_alerted_at) · réception prévenue @endif
                </span>
            </div>
        @endif

        @if (! $activeBlock && $isOpen)
            @can('request', [\App\Models\RoomBlock::class, $workOrder])
                <form method="POST" action="{{ route('room-blocks.store', $workOrder) }}"
                      data-confirm="{{ $workOrder->room_occupancy?->isOccupied() ? 'Un client occupe cette chambre : la réception devra le déloger avant de la bloquer.' : 'La réception décidera de retirer la chambre de la vente.' }}"
                      data-confirm-title="Demander le blocage de la chambre ?" data-confirm-label="Envoyer la demande">
                    @csrf
                    <button type="submit" class="btn btn-secondary w-full"><x-nav-icon name="ban" /> Demander le blocage</button>
                </form>
            @endcan
        @endif

        @can('viewAny', \App\Models\RoomBlock::class)
            <a href="{{ route('room-blocks.index') }}" class="text-center text-[12.5px] text-blue font-semibold hover:underline">Voir toutes les chambres bloquées</a>
        @endcan
    </div>
</x-panel>
