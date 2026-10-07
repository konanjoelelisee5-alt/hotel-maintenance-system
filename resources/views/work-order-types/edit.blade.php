<x-app-layout :crumb="'Paramètres / Types d\'OT'" :page-title="'Modifier le type d\'OT'" :back-route="route('work-order-types.index')">
    <div>
        <div class="w-full max-w-2xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('work-order-types.update', $workOrderType) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="label" value="Libellé affiché" />
                        <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" :value="old('label', $workOrderType->label)" required />
                        <x-input-error :messages="$errors->get('label')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Code technique" />
                        <p class="mt-1 px-3 py-2 bg-line-soft rounded-md text-sm text-ink-body font-mono">{{ $workOrderType->code }}</p>
                        <p class="text-xs text-ink-muted mt-1">Le code ne peut pas être modifié après création.</p>
                    </div>

                    <div>
                        <x-input-label for="position" value="Position d'affichage" />
                        <x-text-input id="position" name="position" type="number" min="0" class="mt-1 block w-full" :value="old('position', $workOrderType->position)" />
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked($workOrderType->is_active) class="rounded border-line">
                            Type actif
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('work-order-types.index') }}" class="btn btn-ghost">Annuler</a>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>