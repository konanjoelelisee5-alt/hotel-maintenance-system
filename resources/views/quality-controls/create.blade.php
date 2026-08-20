<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Démarrer un contrôle qualité') }} — OT #{{ $workOrder->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">

                <form method="POST" action="{{ route('quality-controls.store', $workOrder) }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="checklist_template_id" value="Checklist à utiliser (optionnel)" />
                        <select id="checklist_template_id" name="checklist_template_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Aucune checklist, commentaire libre --</option>
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('checklist_template_id')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('work-orders.show', $workOrder) }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                            Démarrer le contrôle
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>