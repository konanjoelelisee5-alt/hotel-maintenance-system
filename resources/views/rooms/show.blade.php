<x-app-layout :crumb="'Référentiels / Lieux'" :page-title="$room->label" :back-route="route('rooms.index')">
    <x-slot:primaryAction>
        <a href="{{ route('rooms.edit', $room) }}" class="btn btn-secondary">Modifier</a>
            @if ($room->status !== 'hors_service')
                <form method="POST" action="{{ route('rooms.destroy', $room) }}"
                      onsubmit="return confirm('Mettre ce lieu hors service ? Il ne sera plus proposé pour les nouveaux signalements.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Mettre hors service</button>
                </form>
            @endif
    </x-slot:primaryAction>

    <p class="flex items-center gap-1.5 flex-wrap text-[13.5px] text-ink-grey">
        {{ $room->isCommonArea() ? 'Espace commun · code '.$room->number : 'Chambre' }}
        @if ($room->floor) · {{ $room->floor }} @endif
        · @include('rooms.partials.status-badge', ['status' => $room->status, 'label' => $room->status_label])
    </p>

    <div>
        <div class="w-full space-y-6">

            {{-- Indicateurs : un lieu qui tombe souvent en panne est à rénover, pas à réparer encore. --}}
            <div class="grid grid-cols-3 gap-4">
                @foreach ([['OT ouverts', $stats['open']], ['OT sur 90 jours', $stats['last90']], ['OT au total', $stats['total']]] as [$label, $value])
                    <div class="bg-white p-4 shadow-sm rounded-lg">
                        <div class="text-xs text-gray-500 uppercase">{{ $label }}</div>
                        <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            <div class="bg-white p-6 shadow-sm rounded-lg">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-gray-800">Équipements ({{ $room->equipment->count() }})</h3>
                    <a href="{{ route('equipment.create', ['room_id' => $room->id]) }}" class="text-sm text-indigo-600 hover:underline">+ Ajouter un équipement</a>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($room->equipment as $item)
                        <li class="py-2 flex justify-between items-center gap-3 text-sm">
                            <a href="{{ route('equipment.show', $item) }}" class="text-gray-900 hover:underline">{{ $item->name }}</a>
                            <span class="flex items-center gap-3">
                                <span class="text-gray-500">{{ $item->type }}</span>
                                @include('rooms.partials.status-badge', ['status' => $item->status, 'label' => $item->status_label])
                            </span>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-gray-500">Aucun équipement enregistré dans ce lieu.</li>
                    @endforelse
                </ul>
            </div>

            @include('rooms.partials.work-order-history', ['workOrders' => $workOrders])
        </div>
    </div>
</x-app-layout>
