<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Nouvelle règle d\'escalade') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('escalation-rules.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nom de la règle" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="trigger_type" value="Déclencheur" />
                        <select id="trigger_type" name="trigger_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="reponse_proche" @selected(old('trigger_type') === 'reponse_proche')>Réponse bientôt due</option>
                            <option value="reponse_depassee" @selected(old('trigger_type') === 'reponse_depassee')>Réponse dépassée</option>
                            <option value="resolution_proche" @selected(old('trigger_type') === 'resolution_proche')>Résolution bientôt due</option>
                            <option value="resolution_depassee" @selected(old('trigger_type') === 'resolution_depassee')>Résolution dépassée</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="offset_minutes" value="Décalage en minutes" />
                        <x-text-input id="offset_minutes" name="offset_minutes" type="number" class="mt-1 block w-full" :value="old('offset_minutes', 0)" required />
                        <p class="text-xs text-gray-500 mt-1">Positif = avant l'échéance (alerte préventive). Négatif = après l'échéance (retard confirmé).</p>
                        <x-input-error :messages="$errors->get('offset_minutes')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="notify_target" value="Qui notifier" />
                        <select id="notify_target" name="notify_target" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="technicien_assigne" @selected(old('notify_target') === 'technicien_assigne')>Technicien assigné</option>
                            <option value="manager" @selected(old('notify_target') === 'manager')>Managers</option>
                            <option value="admin" @selected(old('notify_target') === 'admin')>Administrateurs</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('escalation-rules.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>