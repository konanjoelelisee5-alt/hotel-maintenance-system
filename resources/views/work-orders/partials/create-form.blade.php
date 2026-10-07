{{-- Formulaire de création d'un OT, affiché en page complète ou dans une fenêtre
     ($inModal) : dans la fenêtre, « Annuler » la ferme au lieu de changer de page. --}}
@php $inModal = $inModal ?? false; @endphp

<form method="POST" action="{{ route('work-orders.store') }}" class="space-y-6">
    @csrf

    <div>
        <x-input-label for="title" value="Titre" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required autofocus />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="4"
            class="mt-1 block w-full border-line rounded-md shadow-sm">{{ old('description') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="type_id" value="Type" />
            <select id="type_id" name="type_id" class="mt-1 block w-full border-line rounded-md shadow-sm" required>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected(old('type_id') == $type->id)>{{ $type->label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('type_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="priority_id" value="Priorité" />
            <select id="priority_id" name="priority_id" class="mt-1 block w-full border-line rounded-md shadow-sm" required>
                @foreach ($priorities as $priority)
                    <option value="{{ $priority->id }}" @selected(old('priority_id') == $priority->id)>{{ $priority->label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('priority_id')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="room_id" value="Lieu (chambre ou espace commun)" />
            @include('rooms.partials.room-select', ['selected' => old('room_id'), 'placeholder' => '-- Aucun --'])
            <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="equipment_id" value="Équipement" />
            <select id="equipment_id" name="equipment_id" class="mt-1 block w-full border-line rounded-md shadow-sm">
                <option value="">-- Aucun --</option>
                @foreach ($equipments as $equipment)
                    <option value="{{ $equipment->id }}" @selected(old('equipment_id') == $equipment->id)>
                        {{ $equipment->label }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('equipment_id')" class="mt-2" />
        </div>
    </div>

    @if (auth()->user()->role->dispatchesWork())
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="assigned_to" value="Assigner à (technicien)" />
            <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-line rounded-md shadow-sm">
                <option value="">-- Non assigné --</option>
                @foreach ($technicians as $technician)
                    <option value="{{ $technician->id }}" @selected(old('assigned_to') == $technician->id)>
                        {{ $technician->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="due_date" value="Date d'échéance" />
            <x-text-input id="due_date" name="due_date" type="datetime-local" class="mt-1 block w-full" :value="old('due_date')" />
            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
        </div>
    </div>
    @endif

    {{-- Page : collé au-dessus de la barre de navigation mobile pour rester visible
         clavier ouvert ; fenêtre : collé en bas de la fenêtre. --}}
    {{-- Dans la fenêtre, le pied est mis en forme par .modal-form (resources/css/app.css). --}}
    <div class="{{ $inModal ? '' : 'sticky bottom-[64px] tab:static -mx-6 tab:mx-0 px-6 tab:px-0 py-3 tab:py-0 bg-white/95 backdrop-blur tab:bg-transparent border-t border-line tab:border-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-3' }}">
        @if ($inModal)
            <button type="button" data-modal-close class="text-center px-4 py-2 text-sm text-ink-grey hover:underline">
                Annuler
            </button>
        @else
            <a href="{{ route('work-orders.index') }}" class="text-center px-4 py-2 text-sm text-ink-grey hover:underline">
                Annuler
            </a>
        @endif
        <x-mobile-action-button type="submit" class="sm:w-auto sm:px-6">
            Créer l'ordre de travail
        </x-mobile-action-button>
    </div>
</form>
