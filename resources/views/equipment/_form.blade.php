{{-- Formulaire commun création / modification d'un équipement. --}}
<div class="space-y-4">
    <div>
        <x-input-label for="name" value="Nom" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $equipment->name)" placeholder="Climatiseur Daikin 12000 BTU" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="type" value="Type" />
            {{-- Saisie libre avec suggestions : évite "Clim" / "Climatiseur" / "climatisation". --}}
            <x-text-input id="type" name="type" type="text" list="equipment-types" class="mt-1 block w-full" :value="old('type', $equipment->type)" placeholder="Climatiseur" />
            <datalist id="equipment-types">
                @foreach ($types as $type)
                    <option value="{{ $type }}"></option>
                @endforeach
            </datalist>
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="status" value="État" />
            <select id="status" name="status" class="mt-1 block w-full border-line rounded-md shadow-sm">
                @foreach (\App\Models\Equipment::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $equipment->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="room_id" value="Lieu" />
        @include('rooms.partials.room-select', ['selected' => old('room_id', $equipment->room_id), 'placeholder' => '— Sans lieu —'])
        <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
    </div>
</div>
