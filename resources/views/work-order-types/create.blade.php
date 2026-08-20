<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Nouveau type d\'OT') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('work-order-types.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="label" value="Libellé affiché" />
                        <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" :value="old('label')" required />
                        <x-input-error :messages="$errors->get('label')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="code" value="Code technique (unique, ex: inspection)" />
                        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code')" required />
                        <p class="text-xs text-gray-500 mt-1">Sans espace ni accent, utilisé en interne. Ne pourra plus être modifié après création.</p>
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="position" value="Position d'affichage" />
                        <x-text-input id="position" name="position" type="number" min="0" class="mt-1 block w-full" :value="old('position', 0)" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('work-order-types.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>