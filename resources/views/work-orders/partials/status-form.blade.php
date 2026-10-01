{{-- Avancement déclaré par l'intervenant assigné (cf. UpdateWorkOrderStatusRequest).
     Les superviseurs ont leur propre panneau « Pilotage ». --}}
<div id="status-form" class="bg-white rounded-xl border border-line">
    <div class="px-5 py-4 border-b border-line-soft">
        <h3 class="font-semibold text-navy-900 text-sm">Avancement de mon intervention</h3>
    </div>
    <div class="p-5">
        <form method="POST" action="{{ route('work-orders.status.update', $workOrder) }}" class="flex flex-col sm:flex-row gap-3">
            @csrf
            @method('PATCH')

            <select name="status" class="sm:w-44 border-slate-300 rounded-md shadow-sm text-sm" required>
                <option value="en_cours" @selected($workOrder->status === 'en_cours')>En cours</option>
                <option value="en_attente" @selected($workOrder->status === 'en_attente')>En attente</option>
                <option value="resolu" @selected($workOrder->status === 'resolu')>Résolu</option>
            </select>

            <input type="text" name="note" placeholder="Note (obligatoire si « En attente »)"
                   class="flex-1 border-slate-300 rounded-md shadow-sm text-sm">

            <button type="submit" class="px-4 py-2 bg-navy-800 text-white text-sm font-medium rounded-md hover:bg-navy-900">
                Mettre à jour
            </button>
        </form>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
        <x-input-error :messages="$errors->get('note')" class="mt-2" />
    </div>
</div>
