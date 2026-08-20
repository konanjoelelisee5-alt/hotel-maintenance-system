<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Modifier la priorité') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">

                <div class="mb-4">
                    <x-work-order-priority-badge :priority="$workOrderPriority" />
                </div>

                <form method="POST" action="{{ route('work-order-priorities.update', $workOrderPriority) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="label" value="Libellé affiché" />
                        <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" :value="old('label', $workOrderPriority->label)" required />
                        <x-input-error :messages="$errors->get('label')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Code technique" />
                        <p class="mt-1 px-3 py-2 bg-gray-100 rounded-md text-sm text-gray-600 font-mono">{{ $workOrderPriority->code }}</p>
                    </div>

                    <div>
                        <x-input-label for="color" value="Couleur" />
                        <input type="color" id="color" name="color" value="{{ old('color', $workOrderPriority->color) }}" class="mt-1 block w-24 h-10 border-gray-300 rounded-md shadow-sm">
                        <x-input-error :messages="$errors->get('color')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="position" value="Position d'affichage" />
                        <x-text-input id="position" name="position" type="number" min="0" class="mt-1 block w-full" :value="old('position', $workOrderPriority->position)" />
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked($workOrderPriority->is_active) class="rounded border-gray-300">
                            Priorité active
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('work-order-priorities.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>