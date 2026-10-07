<x-app-layout :crumb="'Paramètres / Règles d\'escalade'" page-title="Modifier la règle d'escalade" :back-route="route('escalation-rules.index')">
    <div>
        <div class="w-full max-w-2xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('escalation-rules.update', $escalationRule) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Nom de la règle" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $escalationRule->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="trigger_type" value="Déclencheur" />
                        <select id="trigger_type" name="trigger_type" class="mt-1 block w-full border-line rounded-md shadow-sm" required>
                            <option value="reponse_proche" @selected(old('trigger_type', $escalationRule->trigger_type) === 'reponse_proche')>Réponse bientôt due</option>
                            <option value="reponse_depassee" @selected(old('trigger_type', $escalationRule->trigger_type) === 'reponse_depassee')>Réponse dépassée</option>
                            <option value="resolution_proche" @selected(old('trigger_type', $escalationRule->trigger_type) === 'resolution_proche')>Résolution bientôt due</option>
                            <option value="resolution_depassee" @selected(old('trigger_type', $escalationRule->trigger_type) === 'resolution_depassee')>Résolution dépassée</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="offset_minutes" value="Décalage en minutes" />
                        <x-text-input id="offset_minutes" name="offset_minutes" type="number" class="mt-1 block w-full" :value="old('offset_minutes', $escalationRule->offset_minutes)" required />
                        <p class="text-xs text-ink-muted mt-1">Positif = avant l'échéance. Négatif = après l'échéance.</p>
                    </div>

                    <div>
                        <x-input-label for="notify_target" value="Qui notifier" />
                        <select id="notify_target" name="notify_target" class="mt-1 block w-full border-line rounded-md shadow-sm" required>
                            <option value="technicien_assigne" @selected(old('notify_target', $escalationRule->notify_target) === 'technicien_assigne')>Technicien assigné</option>
                            <option value="manager" @selected(old('notify_target', $escalationRule->notify_target) === 'manager')>Managers</option>
                            <option value="admin" @selected(old('notify_target', $escalationRule->notify_target) === 'admin')>Administrateurs</option>
                            <option value="astreinte" @selected(old('notify_target', $escalationRule->notify_target) === 'astreinte')>Astreinte (managers le jour, admins la nuit)</option>
                        </select>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked($escalationRule->is_active) class="rounded border-line">
                            Règle active
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('escalation-rules.index') }}" class="btn btn-ghost">Annuler</a>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>