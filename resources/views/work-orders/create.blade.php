<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nouvel ordre de travail') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">

                <form method="POST" action="{{ route('work-orders.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="title" value="Titre" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Description" />
                        <textarea id="description" name="description" rows="4"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="type_id" value="Type" />
                            <select id="type_id" name="type_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected(old('type_id') == $type->id)>{{ $type->label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('type_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="priority_id" value="Priorité" />
                            <select id="priority_id" name="priority_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->id }}" @selected(old('priority_id') == $priority->id)>{{ $priority->label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('priority_id')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="room_id" value="Lieu (chambre / zone)" />
                            <select id="room_id" name="room_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Aucun --</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>
                                        {{ $room->number }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="equipment_id" value="Équipement" />
                            <select id="equipment_id" name="equipment_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Aucun --</option>
                                @foreach ($equipments as $equipment)
                                    <option value="{{ $equipment->id }}" @selected(old('equipment_id') == $equipment->id)>
                                        {{ $equipment->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('equipment_id')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="assigned_to" value="Assigner à (technicien)" />
                            <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
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

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('work-orders.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">
                            Annuler
                        </a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                            Créer l'OT
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>