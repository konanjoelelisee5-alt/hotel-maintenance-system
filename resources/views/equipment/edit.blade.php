<x-app-layout :crumb="'Patrimoine / Équipements'" :page-title="'Modifier '.$equipment->name" :back-route="route('equipment.show', $equipment)">
    <div>
        <div class="w-full max-w-2xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('equipment.update', $equipment) }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    @include('equipment._form')

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('equipment.show', $equipment) }}" class="btn btn-ghost">Annuler</a>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
