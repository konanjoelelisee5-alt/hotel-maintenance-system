<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Disponibilités de') }} {{ $technician->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <!-- Formulaire d'ajout -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Ajouter une disponibilité</h3>

                <form method="POST" action="{{ route('planning.availabilities.store', $technician) }}" class="space-y-4" x-data="{ type: 'disponible' }">
                    @csrf

                    <div>
                        <x-input-label for="type" value="Type" />
                        <select id="type" name="type" x-model="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="disponible">Créneau récurrent disponible</option>
                            <option value="conge">Congé</option>
                            <option value="absence">Absence</option>
                        </select>
                    </div>

                    <div x-show="type === 'disponible'" class="grid grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="day_of_week" value="Jour" />
                            <select id="day_of_week" name="day_of_week" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="1">Lundi</option>
                                <option value="2">Mardi</option>
                                <option value="3">Mercredi</option>
                                <option value="4">Jeudi</option>
                                <option value="5">Vendredi</option>
                                <option value="6">Samedi</option>
                                <option value="0">Dimanche</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="start_time" value="De" />
                            <x-text-input id="start_time" name="start_time" type="time" class="mt-1 block w-full" value="08:00" />
                        </div>
                        <div>
                            <x-input-label for="end_time" value="À" />
                            <x-text-input id="end_time" name="end_time" type="time" class="mt-1 block w-full" value="17:00" />
                        </div>
                    </div>

                    <div x-show="type !== 'disponible'" class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="date_start" value="Du" />
                            <x-text-input id="date_start" name="date_start" type="date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="date_end" value="Au" />
                            <x-text-input id="date_end" name="date_end" type="date" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="note" value="Note (optionnel)" />
                        <x-text-input id="note" name="note" type="text" class="mt-1 block w-full" />
                    </div>

                    <x-input-error :messages="$errors->all()" class="mt-2" />

                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        Ajouter
                    </button>
                </form>
            </div>

            <!-- Liste des disponibilités existantes -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Disponibilités enregistrées</h3>

                @forelse ($availabilities as $availability)
                    <div class="flex justify-between items-center border-t py-3 text-sm">
                        <div>
                            <span class="font-medium">{{ $availability->type_label }}</span>
                            @if ($availability->type === 'disponible')
                                — {{ $availability->day_name }} de {{ \Carbon\Carbon::parse($availability->start_time)->format('H:i') }}
                                à {{ \Carbon\Carbon::parse($availability->end_time)->format('H:i') }}
                            @else
                                — du {{ $availability->date_start->format('d/m/Y') }} au {{ $availability->date_end->format('d/m/Y') }}
                            @endif
                            @if ($availability->note)
                                <span class="text-gray-500 italic">({{ $availability->note }})</span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('planning.availabilities.destroy', [$technician, $availability]) }}"
                              onsubmit="return confirm('Supprimer cette disponibilité ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline text-xs">Supprimer</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucune disponibilité enregistrée.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>