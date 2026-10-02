<x-app-layout :crumb="'Patrimoine'" :page-title="'Équipements'">
    <x-slot:primaryAction>
        <a href="{{ route('equipment.create') }}" data-modal class="btn btn-primary">+ Nouvel équipement</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full">

            <div class="bg-white p-4 rounded-lg shadow-sm mb-4">
                <form method="GET" class="flex flex-wrap gap-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher un équipement…"
                        class="flex-1 min-w-[180px] border-gray-300 rounded-md shadow-sm text-sm">
                    <select name="room_id" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Tous les lieux</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" @selected(request('room_id') == $room->id)>{{ $room->label }}</option>
                        @endforeach
                    </select>
                    <select name="type" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Tous les types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Tous les états</option>
                        @foreach (\App\Models\Equipment::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">Filtrer</button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Équipement</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lieu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">État</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">OT ouverts</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($equipment as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $item->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $item->type ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @if ($item->room)
                                        <a href="{{ route('rooms.show', $item->room) }}" class="hover:underline">{{ $item->room->label }}</a>
                                    @else
                                        <span class="text-orange-700">Sans lieu</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">@include('rooms.partials.status-badge', ['status' => $item->status, 'label' => $item->status_label])</td>
                                <td class="px-6 py-4 text-sm text-right {{ $item->open_work_orders_count > 0 ? 'text-orange-700 font-semibold' : 'text-gray-400' }}">{{ $item->open_work_orders_count }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('equipment.show', $item) }}" class="text-indigo-600 hover:text-indigo-900">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Aucun équipement trouvé.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">{{ $equipment->links() }}</div>
        </div>
    </div>
</x-app-layout>
