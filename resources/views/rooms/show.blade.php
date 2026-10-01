<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $room->label }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $room->isCommonArea() ? 'Espace commun · code '.$room->number : 'Chambre' }}
                    @if ($room->floor) · {{ $room->floor }} @endif
                    · @include('rooms.partials.status-badge', ['status' => $room->status, 'label' => $room->status_label])
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('rooms.edit', $room) }}" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Modifier</a>
                @if ($room->status !== 'hors_service')
                    <form method="POST" action="{{ route('rooms.destroy', $room) }}"
                          onsubmit="return confirm('Mettre ce lieu hors service ? Il ne sera plus proposé pour les nouveaux signalements.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-white border border-red-300 text-red-700 text-sm font-medium rounded-md hover:bg-red-50">Mettre hors service</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
