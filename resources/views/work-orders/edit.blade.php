{{-- Requalifier un OT : corriger ce qui a été mal renseigné au signalement (titre,
     type, priorité, lieu, technicien, échéance). En fenêtre depuis le panneau
     « Pilotage », en page complète sinon (x-form-page). Chaque correction est
     journalisée ; priorité ou type modifiés recalculent le délai SLA. --}}
<x-form-page :title="'Modifier la priorité ou le type'" :crumb="'Ordres de travail / '.$workOrder->code()" icon="pencil"
             subtitle="Corrigez ce qui a été mal renseigné au signalement. Chaque modification est notée au journal.">
    <form method="POST" action="{{ route('work-orders.update', $workOrder) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="title" value="Titre" />
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $workOrder->title)" required autofocus />
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="3"
                class="mt-1 block w-full border-line rounded-md shadow-sm">{{ old('description', $workOrder->description) }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="type_id" value="Type" />
                <select id="type_id" name="type_id" class="mt-1 block w-full border-line rounded-md shadow-sm" required>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" @selected(old('type_id', $workOrder->type_id) == $type->id)>{{ $type->label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="priority_id" value="Priorité" />
                <select id="priority_id" name="priority_id" class="mt-1 block w-full border-line rounded-md shadow-sm" required>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->id }}" @selected(old('priority_id', $workOrder->priority_id) == $priority->id)>{{ $priority->label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('priority_id')" class="mt-2" />
            </div>
        </div>

        {{-- Ce que change une nouvelle priorité / un nouveau type : le délai SLA. --}}
        @unless (in_array($workOrder->status, \App\Models\WorkOrder::FINISHED_STATUSES, true))
            <div class="flex gap-2.5 px-3.5 py-3 rounded-[10px] bg-info-bg text-[12.5px] leading-snug text-navy">
                <x-nav-icon name="shield" class="w-4 h-4 flex-shrink-0 mt-px" />
                <span>
                    Changer la <strong>priorité</strong> ou le <strong>type</strong> recalcule le délai SLA depuis la création de l'OT.
                    @if ($workOrder->sla_resolution_due_at)
                        Résolution attendue actuellement : <strong>{{ $workOrder->sla_resolution_due_at->format('d/m/Y à H\hi') }}</strong>
                        ({{ $workOrder->slaPolicy?->name ?? 'politique SLA' }}).
                    @else
                        Aucun délai SLA pour l'instant.
                    @endif
                </span>
            </div>
        @endunless

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="room_id" value="Lieu (chambre ou espace commun)" />
                @include('rooms.partials.room-select', ['selected' => old('room_id', $workOrder->room_id), 'placeholder' => '-- Aucun --'])
                <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="equipment_id" value="Équipement" />
                <select id="equipment_id" name="equipment_id" class="mt-1 block w-full border-line rounded-md shadow-sm">
                    <option value="">-- Aucun --</option>
                    @foreach ($equipments as $equipment)
                        <option value="{{ $equipment->id }}" @selected(old('equipment_id', $workOrder->equipment_id) == $equipment->id)>{{ $equipment->label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('equipment_id')" class="mt-2" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="assigned_to" value="Technicien" />
                <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-line rounded-md shadow-sm">
                    <option value="">-- Non affecté --</option>
                    @foreach ($technicians as $technician)
                        <option value="{{ $technician->id }}" @selected(old('assigned_to', $workOrder->assigned_to) == $technician->id)>
                            {{ $technician->name }}{{ $technician->is_active ? '' : ' (a quitté l\'hôtel)' }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="due_date" value="Date d'échéance" />
                <x-text-input id="due_date" name="due_date" type="datetime-local" class="mt-1 block w-full"
                    :value="old('due_date', $workOrder->due_date?->format('Y-m-d\TH:i'))" />
                <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
            </div>
        </div>

        {{-- Plus de suppression : un OT inutile s'annule depuis le panneau
             « Pilotage » de sa fiche, avec un motif (l'historique est conservé). --}}
        <div class="flex justify-end items-center gap-3">
            <a href="{{ route('work-orders.show', $workOrder) }}" data-modal-close class="btn btn-ghost">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</x-form-page>
