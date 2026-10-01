{{-- Chambre de l'OT : occupation déclarée au signalement et blocage à la vente. --}}
@php
    $room = $workOrder->room;
    $activeBlock = $room->activeBlock();
    $isOpen = in_array($workOrder->status, ['ouvert', 'en_cours', 'en_attente'], true);
@endphp
<div class="bg-white rounded-xl border border-line">
    <div class="px-5 py-4 border-b border-line-soft">
        <h3 class="font-semibold text-navy-900 text-sm">Chambre et client</h3>
    </div>
    <div class="p-5 space-y-3 text-[13px]">
        <div class="flex justify-between gap-3">
            <span class="text-ink-grey">Occupation</span>
            <span class="font-medium text-right">{{ $workOrder->room_occupancy ? $workOrder->room_occupancy->emoji().' '.$workOrder->room_occupancy->label() : 'Non précisée' }}</span>
        </div>
        @if ($workOrder->room_occupancy === \App\Enums\RoomOccupancy::ClientAbsent && $workOrder->due_date)
            <div class="px-3 py-2 rounded-lg {{ $workOrder->due_date->isPast() && $isOpen ? 'bg-[#FDECEA] text-[#8A1F16]' : 'bg-[#FBF1DF] text-[#7A5A16]' }}">
                🧳 À réparer avant le retour du client : <strong>{{ $workOrder->due_date->format('H\hi') }}</strong>
                @if ($workOrder->reception_alerted_at) · réception prévenue @endif
            </div>
        @endif

        <div class="flex justify-between gap-3">
            <span class="text-ink-grey">Vente</span>
            <span class="font-medium text-right">
                @if ($activeBlock)
                    {{ $activeBlock->status === \App\Models\RoomBlock::BLOCKED ? '🔴' : '🟠' }} {{ $activeBlock->status_label }}
                @else
                    🟢 En vente
                @endif
            </span>
        </div>

        @if (! $activeBlock && $isOpen)
            @can('request', [\App\Models\RoomBlock::class, $workOrder])
                <form method="POST" action="{{ route('room-blocks.store', $workOrder) }}"
                      onsubmit="return confirm(@js($workOrder->room_occupancy?->isOccupied() ? 'Un client occupe cette chambre : la réception devra le déloger avant de la bloquer. Envoyer la demande ?' : 'Demander à la réception de retirer cette chambre de la vente ?'));">
                    @csrf
                    <button type="submit" class="w-full h-11 rounded-lg bg-navy text-white text-[13px] font-semibold">🚫 Demander le blocage</button>
                </form>
            @endcan
        @endif

        @can('viewAny', \App\Models\RoomBlock::class)
            <a href="{{ route('room-blocks.index') }}" class="block text-center text-[12px] text-blue font-semibold">Voir toutes les chambres bloquées</a>
        @endcan
    </div>
</div>
