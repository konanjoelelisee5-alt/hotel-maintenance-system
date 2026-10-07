<x-app-layout :crumb="'Paramètres / Compétences'" :page-title="'Modifier la compétence'" :back-route="route('skills.index')">
    <div>
        <div class="w-full max-w-2xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('skills.update', $skill) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Nom de la compétence" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $skill->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked($skill->is_active) class="rounded border-line">
                            Compétence active
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('skills.index') }}" class="btn btn-ghost">Annuler</a>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>