{{-- Formulaire commun création / modification d'un lieu. Les champs affichés
     dépendent du type : numéro + étage pour une chambre, code + nom pour un espace commun. --}}
<div x-data="{ type: @js(old('type', $room->type)) }" class="space-y-4">
    <div>
        <x-input-label value="Type de lieu" />
        <div class="mt-2 flex gap-6 text-sm">
            <label class="flex items-center gap-2">
                <input type="radio" name="type" value="chambre" x-model="type" class="border-gray-300"> Chambre
            </label>
            <label class="flex items-center gap-2">
                <input type="radio" name="type" value="espace_commun" x-model="type" class="border-gray-300"> Espace commun
            </label>
        </div>
        <x-input-error :messages="$errors->get('type')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="number" x-text="type === 'chambre' ? 'Numéro de chambre' : 'Code court'" value="Numéro" />
            <x-text-input id="number" name="number" type="text" class="mt-1 block w-full" :value="old('number', $room->number)" required />
            <p class="text-xs text-gray-500 mt-1" x-show="type === 'espace_commun'">Ex. PISC, CHAUF, HALL — unique, en majuscules.</p>
            <x-input-error :messages="$errors->get('number')" class="mt-2" />
        </div>

        <div x-show="type === 'espace_commun'">
            <x-input-label for="name" value="Nom affiché" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $room->name)" placeholder="Piscine" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="floor" value="Étage / zone" />
            <x-text-input id="floor" name="floor" type="text" class="mt-1 block w-full" :value="old('floor', $room->floor)" placeholder="Étage 3, Sous-sol, Extérieur…" />
            <x-input-error :messages="$errors->get('floor')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="status" value="État" />
            {{-- Pas d'état « Occupée » pour un espace commun (revérifié côté serveur). --}}
            <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @foreach (\App\Models\Room::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $room->status) === $value)
                        @if (! in_array($value, \App\Models\Room::STATUSES['espace_commun'], true)) :disabled="type === 'espace_commun'" @endif>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>
    </div>
</div>
