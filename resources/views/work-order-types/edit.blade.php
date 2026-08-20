<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Modifier le type d\'OT') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
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
                        <p class="mt-1 px-3 py-2 bg-gray-100 rounded-md text-sm text-gray-600 font-mono">{{ $workOrderType->code }}</p>
                        <p class="text-xs text-gray-500 mt-1">Le code ne peut pas être modifié après création.</p>
                    </div>

                    <div>
                        <x-input-label for="position" value="Position d'affichage" />
                        <x-text-input id="position" name="position" type="number" min="0" class="mt-1 block w-full" :value="old('position', $workOrderType->position)" />
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked($workOrderType->is_active) class="rounded border-gray-300">
                            Type actif
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('work-order-types.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>