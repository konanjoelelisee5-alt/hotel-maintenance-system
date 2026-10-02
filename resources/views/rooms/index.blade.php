<x-app-layout :crumb="'Patrimoine'" :page-title="'Lieux'">
    <x-slot:primaryAction>
        <a href="{{ route('rooms.create', ['type' => 'espace_commun']) }}" data-modal class="btn btn-secondary">+ Espace commun</a>
            <a href="{{ route('rooms.create') }}" data-modal class="btn btn-primary">+ Chambre</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full">

            <div class="bg-white p-4 rounded-lg shadow-sm mb-4">
                <form method="GET" class="flex flex-wrap gap-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Numéro, code ou nom…"
                        class="flex-1 min-w-[180px] border-gray-300 rounded-md shadow-sm text-sm">
                    <select name="type" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Tous les lieux</option>
                        <option value="chambre" @selected(request('type') === 'chambre')>Chambres</option>
                        <option value="espace_commun" @selected(request('type') === 'espace_commun')>Espaces communs</option>
                    </select>
                    <select name="floor" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Tous les étages</option>
                        @foreach ($floors as $floor)
                            <option value="{{ $floor }}" @selected(request('floor') === $floor)>{{ $floor }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Tous les états</option>
                        @foreach (\App\Models\Room::STATUS_LABELS as $value => $label)
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lieu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Étage</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">État</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Équipements</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">OT ouverts</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($rooms as $room)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                    {{ $room->label }}
                                    @if ($room->isCommonArea())
                                        <span class="ml-1 font-mono text-xs text-gray-400">{{ $room->number }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $room->isCommonArea() ? 'Espace commun' : 'Chambre' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $room->floor ?? '—' }}</td>
                                <td class="px-6 py-4">@include('rooms.partials.status-badge', ['status' => $room->status, 'label' => $room->status_label])</td>
                                <td class="px-6 py-4 text-sm text-gray-500 text-right">{{ $room->equipment_count }}</td>
                                <td class="px-6 py-4 text-sm text-right {{ $room->open_work_orders_count > 0 ? 'text-orange-700 font-semibold' : 'text-gray-400' }}">{{ $room->open_work_orders_count }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('rooms.show', $room) }}" class="text-indigo-600 hover:text-indigo-900">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">Aucun lieu trouvé.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">{{ $rooms->links() }}</div>
        </div>
    </div>
</x-app-layout>
