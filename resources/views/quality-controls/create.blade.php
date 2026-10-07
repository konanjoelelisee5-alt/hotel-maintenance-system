<x-app-layout :crumb="'Ordres de travail / '.$workOrder->code()" :page-title="'Démarrer un contrôle qualité'" :back-route="route('work-orders.show', $workOrder)">
    <div>
        <div class="w-full max-w-2xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">

                <form method="POST" action="{{ route('quality-controls.store', $workOrder) }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="checklist_template_id" value="Checklist à utiliser (optionnel)" />
                        <select id="checklist_template_id" name="checklist_template_id" class="mt-1 block w-full border-line rounded-md shadow-sm">
                            <option value="">-- Aucune checklist, commentaire libre --</option>
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('checklist_template_id')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('work-orders.show', $workOrder) }}" class="btn btn-ghost">Annuler</a>
                        <button type="submit" class="btn btn-primary">
                            Démarrer le contrôle
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>