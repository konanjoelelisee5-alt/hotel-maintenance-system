@php
    $plan = $plan ?? null;
    $value = fn (string $field, $default = null) => old($field, $plan?->{$field} ?? $default);
@endphp

<div>
    <x-input-label for="name" value="Nom du plan" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$value('name')" required />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="description" value="Description / instructions (optionnel)" />
    <textarea id="description" name="description" rows="3"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ $value('description') }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="border-t pt-4">
    <p class="text-sm font-medium text-gray-700 mb-2">Cible du plan <span class="text-gray-400 font-normal">(au moins une des deux)</span></p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="room_id" value="Chambre" />
            <select id="room_id" name="room_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">-- Aucune --</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" @selected($value('room_id') == $room->id)>Chambre {{ $room->number }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="equipment_id" value="Équipement" />
            <select id="equipment_id" name="equipment_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">-- Aucun --</option>
                @foreach ($equipments as $equipment)
                    <option value="{{ $equipment->id }}" @selected($value('equipment_id') == $equipment->id)>{{ $equipment->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
</div>

<div class="border-t pt-4">
    <p class="text-sm font-medium text-gray-700 mb-2">OT généré automatiquement</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="work_order_type_id" value="Type d'OT" />
            <select id="work_order_type_id" name="work_order_type_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected($value('work_order_type_id', $types->firstWhere('code', 'preventif')?->id) == $type->id)>{{ $type->label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('work_order_type_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="work_order_priority_id" value="Priorité" />
            <select id="work_order_priority_id" name="work_order_priority_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                @foreach ($priorities as $priority)
                    <option value="{{ $priority->id }}" @selected($value('work_order_priority_id') == $priority->id)>{{ $priority->label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('work_order_priority_id')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
        <div>
            <x-input-label for="checklist_template_id" value="Checklist associée (optionnel)" />
            <select id="checklist_template_id" name="checklist_template_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">-- Aucune --</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected($value('checklist_template_id') == $template->id)>{{ $template->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="estimated_duration_minutes" value="Durée estimée (minutes, optionnel)" />
            <x-text-input id="estimated_duration_minutes" name="estimated_duration_minutes" type="number" min="1"
                          class="mt-1 block w-full" :value="$value('estimated_duration_minutes')" />
        </div>
    </div>
</div>

<div class="border-t pt-4">
    <p class="text-sm font-medium text-gray-700 mb-2">Assignation automatique</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="assigned_to" value="Technicien fixe (optionnel)" />
            <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">-- Aucun --</option>
                @foreach ($technicians as $technician)
                    <option value="{{ $technician->id }}" @selected($value('assigned_to') == $technician->id)>{{ $technician->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="skill_id" value="À défaut, compétence requise (optionnel)" />
            <select id="skill_id" name="skill_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">-- Aucune --</option>
                @foreach ($skills as $skill)
                    <option value="{{ $skill->id }}" @selected($value('skill_id') == $skill->id)>{{ $skill->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <p class="text-xs text-gray-400 mt-2">
        Si un technicien fixe est défini, il est toujours utilisé. Sinon, si une compétence est indiquée,
        le technicien disponible ayant cette compétence et la charge de travail la plus faible est choisi
        automatiquement à chaque génération. Sans les deux, l'OT est généré non assigné.
    </p>
</div>

<div class="border-t pt-4">
    <p class="text-sm font-medium text-gray-700 mb-2">Récurrence</p>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <x-input-label for="frequency_interval" value="Tous les" />
            <x-text-input id="frequency_interval" name="frequency_interval" type="number" min="1" class="mt-1 block w-full"
                          :value="$value('frequency_interval', 1)" required />
        </div>
        <div>
            <x-input-label for="frequency_unit" value="Unité" />
            <select id="frequency_unit" name="frequency_unit" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                @foreach (['jour' => 'Jour(s)', 'semaine' => 'Semaine(s)', 'mois' => 'Mois', 'trimestre' => 'Trimestre(s)', 'annee' => 'An(s)'] as $unit => $label)
                    <option value="{{ $unit }}" @selected($value('frequency_unit', 'mois') === $unit)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="lead_time_days" value="Préavis de génération (jours)" />
            <x-text-input id="lead_time_days" name="lead_time_days" type="number" min="0" class="mt-1 block w-full"
                          :value="$value('lead_time_days', 0)" required />
        </div>
    </div>
    <p class="text-xs text-gray-400 mt-2">
        Le préavis permet de faire apparaître l'OT dans le planning quelques jours avant l'échéance réelle
        (ex. générer 3 jours à l'avance une maintenance due le 1er du mois).
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
        <div>
            <x-input-label for="start_date" value="Date de début" />
            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full"
                          :value="$value('start_date', now()->toDateString())" required />
            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="end_date" value="Date de fin (optionnel)" />
            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="$value('end_date')" />
            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
        </div>
    </div>
</div>

<div class="border-t pt-4 flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded border-gray-300"
           @checked($value('is_active', true))>
    <label for="is_active" class="text-sm text-gray-700">Plan actif (génère des OT)</label>
</div>
