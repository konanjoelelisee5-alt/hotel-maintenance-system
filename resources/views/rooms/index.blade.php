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
                        class="flex-1 min-w-[180px] border-line rounded-md shadow-sm text-sm">
                    <select name="type" class="border-line rounded-md shadow-sm text-sm">
                        <option value="">Tous les lieux</option>
                        <option value="chambre" @selected(request('type') === 'chambre')>Chambres</option>
                        <option value="espace_commun" @selected(request('type') === 'espace_commun')>Espaces communs</option>
                    </select>
                    <select name="floor" class="border-line rounded-md shadow-sm text-sm">
                        <option value="">Tous les étages</option>
                        @foreach ($floors as $floor)
                            <option value="{{ $floor }}" @selected(request('floor') === $floor)>{{ $floor }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="border-line rounded-md shadow-sm text-sm">
                        <option value="">Tous les états</option>
                        @foreach (\App\Models\Room::STATUS_LABELS as $value => $label)
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Lieu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Étage</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">État</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-muted uppercase">Équipements</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-muted uppercase">OT ouverts</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($rooms as $room)
                            <tr class="hover:bg-paper">
                                <td class="px-6 py-4 text-sm text-navy font-medium">
                                    {{ $room->label }}
                                    @if ($room->isCommonArea())
                                        <span class="ml-1 font-mono text-xs text-ink-faint">{{ $room->number }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $room->isCommonArea() ? 'Espace commun' : 'Chambre' }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $room->floor ?? '—' }}</td>
                                <td class="px-6 py-4">@include('rooms.partials.status-badge', ['status' => $room->status, 'label' => $room->status_label])</td>
                                <td class="px-6 py-4 text-sm text-ink-muted text-right">{{ $room->equipment_count }}</td>
                                <td class="px-6 py-4 text-sm text-right {{ $room->open_work_orders_count > 0 ? 'text-warn-ink font-semibold' : 'text-ink-faint' }}">{{ $room->open_work_orders_count }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('rooms.show', $room) }}" class="text-blue hover:text-navy">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-ink-muted">Aucun lieu trouvé.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">{{ $rooms->links() }}</div>
        </div>
    </div>
</x-app-layout>
