<x-app-layout :crumb="'Paramètres / Politiques SLA'" :page-title="'Modifier la politique SLA'" :back-route="route('sla-policies.index')">
    <div>
        <div class="w-full max-w-2xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('sla-policies.update', $slaPolicy) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Nom de la politique" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $slaPolicy->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="priority" value="Priorité concernée (optionnel)" />
                            <select id="priority" name="priority" class="mt-1 block w-full border-line rounded-md shadow-sm">
                                <option value="">-- Toutes les priorités --</option>
                                <option value="basse" @selected(old('priority', $slaPolicy->priority) === 'basse')>Basse</option>
                                <option value="moyenne" @selected(old('priority', $slaPolicy->priority) === 'moyenne')>Moyenne</option>
                                <option value="haute" @selected(old('priority', $slaPolicy->priority) === 'haute')>Haute</option>
                                <option value="urgente" @selected(old('priority', $slaPolicy->priority) === 'urgente')>Urgente</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="work_order_type" value="Type d'OT concerné (optionnel)" />
                            <select id="work_order_type" name="work_order_type" class="mt-1 block w-full border-line rounded-md shadow-sm">
                                <option value="">-- Tous les types --</option>
                                <option value="maintenance" @selected(old('work_order_type', $slaPolicy->work_order_type) === 'maintenance')>Maintenance</option>
                                <option value="demande_client" @selected(old('work_order_type', $slaPolicy->work_order_type) === 'demande_client')>Demande client</option>
                                <option value="preventif" @selected(old('work_order_type', $slaPolicy->work_order_type) === 'preventif')>Préventif</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="response_time_minutes" value="Délai de réponse (minutes)" />
                            <x-text-input id="response_time_minutes" name="response_time_minutes" type="number" min="1" class="mt-1 block w-full" :value="old('response_time_minutes', $slaPolicy->response_time_minutes)" required />
                        </div>
                        <div>
                            <x-input-label for="resolution_time_minutes" value="Délai de résolution (minutes)" />
                            <x-text-input id="resolution_time_minutes" name="resolution_time_minutes" type="number" min="1" class="mt-1 block w-full" :value="old('resolution_time_minutes', $slaPolicy->resolution_time_minutes)" required />
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked($slaPolicy->is_active) class="rounded border-line">
                            Politique active
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('sla-policies.index') }}" class="btn btn-ghost">Annuler</a>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>