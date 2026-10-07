{{-- Retirer un signalement fait par erreur (WorkOrderPolicy::withdraw : housekeeping et
     réception, auteur, 15 min, sans technicien). Motif obligatoire ; l'OT passe
     « annulé » sans être supprimé. Ouvert par l'événement hk-withdraw-open. --}}
<section x-data="{ open: {{ $errors->has('reason') ? 'true' : 'false' }} }" @hk-withdraw-open.window="open = true" x-show="open" x-cloak
         class="bg-white border border-red/30 rounded-xl overflow-hidden">
    <form method="POST" action="{{ route('quick-reports.withdraw', $workOrder) }}" class="ui-form flex flex-col gap-4 px-5 py-4">
        @csrf
        <div>
            <h3 class="m-0 text-[14.5px] font-semibold text-navy">Retirer ce signalement ?</h3>
            <p class="m-0 mt-0.5 text-[12.5px] text-ink-muted">La maintenance ne se déplacera pas. Le signalement reste visible dans votre historique, avec le motif.</p>
        </div>
        <fieldset class="m-0 p-0 border-0 grid grid-cols-1 min-[480px]:grid-cols-2 gap-2">
            <legend class="sr-only">Motif</legend>
            @foreach (\App\Support\Housekeeping::WITHDRAW_REASONS as $key => $label)
                <label class="!flex !normal-case !tracking-normal !mb-0 items-center gap-2.5 min-h-[44px] px-3 rounded-[10px] border border-line text-[13.5px] font-medium text-navy cursor-pointer has-[:checked]:border-navy has-[:checked]:bg-paper">
                    <input type="radio" name="reason" value="{{ $key }}" class="text-navy focus:ring-navy/30" @checked(old('reason') === $key)>
                    {{ $label }}
                </label>
            @endforeach
        </fieldset>
        <x-input-error :messages="$errors->get('reason')" />
        <input type="text" name="detail" maxlength="300" value="{{ old('detail') }}" placeholder="Précision (facultatif)" aria-label="Précision sur le motif"
               class="w-full h-10 rounded-[10px] border-line text-[13.5px]">
        <div class="flex flex-wrap gap-2.5">
            <button type="submit" class="btn btn-danger-solid">Retirer le signalement</button>
            <button type="button" class="btn btn-ghost" @click="open = false">Garder</button>
        </div>
    </form>
</section>
