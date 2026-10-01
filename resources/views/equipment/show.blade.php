<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $equipment->name }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $equipment->type ?? 'Équipement' }} ·
                    @if ($equipment->room)
                        <a href="{{ route('rooms.show', $equipment->room) }}" class="hover:underline">{{ $equipment->room->label }}</a>
                    @else
                        <span class="text-orange-700">sans lieu</span>
                    @endif
                    · @include('rooms.partials.status-badge', ['status' => $equipment->status, 'label' => $equipment->status_label])
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('equipment.edit', $equipment) }}" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Modifier</a>
                @if ($equipment->status !== 'hors_service')
                    <form method="POST" action="{{ route('equipment.destroy', $equipment) }}"
                          onsubmit="return confirm('Mettre cet équipement hors service ? Son historique est conservé.');">
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

            <div class="grid grid-cols-3 gap-4">
                @foreach ([['OT ouverts', $stats['open']], ['OT sur 90 jours', $stats['last90']], ['OT au total', $stats['total']]] as [$label, $value])
                    <div class="bg-white p-4 shadow-sm rounded-lg">
                        <div class="text-xs text-gray-500 uppercase">{{ $label }}</div>
                        <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            @if ($equipment->maintenancePlans->isNotEmpty())
                <div class="bg-white p-6 shadow-sm rounded-lg">
                    <h3 class="font-semibold text-gray-800 mb-3">Maintenance préventive</h3>
                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach ($equipment->maintenancePlans as $plan)
                            <li class="py-2 flex justify-between gap-3">
                                <a href="{{ route('maintenance-plans.show', $plan) }}" class="text-gray-900 hover:underline">{{ $plan->name }}</a>
                                <span class="text-gray-500">
                                    {{ $plan->is_active ? 'Prochaine échéance : '.($plan->next_due_at?->format('d/m/Y') ?? '—') : 'Plan inactif' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('rooms.partials.work-order-history', ['workOrders' => $workOrders])
        </div>
    </div>
</x-app-layout>
