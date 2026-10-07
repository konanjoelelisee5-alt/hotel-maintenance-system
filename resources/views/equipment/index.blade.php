<x-app-layout :crumb="'Patrimoine'" :page-title="'Équipements'">
    <x-slot:primaryAction>
        <a href="{{ route('equipment.create') }}" data-modal class="btn btn-primary">+ Nouvel équipement</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full">

            <div class="bg-white p-4 rounded-lg shadow-sm mb-4">
                <form method="GET" class="flex flex-wrap gap-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher un équipement…"
                        class="flex-1 min-w-[180px] border-line rounded-md shadow-sm text-sm">
                    <select name="room_id" class="border-line rounded-md shadow-sm text-sm">
                        <option value="">Tous les lieux</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" @selected(request('room_id') == $room->id)>{{ $room->label }}</option>
                        @endforeach
                    </select>
                    <select name="type" class="border-line rounded-md shadow-sm text-sm">
                        <option value="">Tous les types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="border-line rounded-md shadow-sm text-sm">
                        <option value="">Tous les états</option>
                        @foreach (\App\Models\Equipment::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-secondary">Filtrer</button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-paper">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Équipement</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Lieu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">État</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-muted uppercase">OT ouverts</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($equipment as $item)
                            <tr class="hover:bg-paper">
                                <td class="px-6 py-4 text-sm text-navy font-medium">{{ $item->name }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $item->type ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">
                                    @if ($item->room)
                                        <a href="{{ route('rooms.show', $item->room) }}" class="hover:underline">{{ $item->room->label }}</a>
                                    @else
                                        <span class="text-warn-ink">Sans lieu</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">@include('rooms.partials.status-badge', ['status' => $item->status, 'label' => $item->status_label])</td>
                                <td class="px-6 py-4 text-sm text-right {{ $item->open_work_orders_count > 0 ? 'text-warn-ink font-semibold' : 'text-ink-faint' }}">{{ $item->open_work_orders_count }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('equipment.show', $item) }}" class="text-blue hover:text-navy">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-ink-muted">Aucun équipement trouvé.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">{{ $equipment->links() }}</div>
        </div>
    </div>
</x-app-layout>
