{{-- Dernier mot du service demandeur : il confirme la réparation, ou rouvre l'OT avec
     un motif (WorkOrderConfirmationController). Une fois confirmé, la mention reste
     visible pour tous dans l'aperçu. --}}
@can('confirmResolution', $workOrder)
    <section class="bg-white border border-green/30 rounded-xl p-5 flex flex-col gap-4" x-data="{ reopen: {{ $errors->has('reason') ? 'true' : 'false' }} }">
        <div class="flex items-start gap-3.5">
            <span class="w-10 h-10 flex-shrink-0 rounded-full bg-ok-bg text-green flex items-center justify-center">
                <x-nav-icon name="check" class="w-5 h-5" />
            </span>
            <div class="min-w-0">
                <h3 class="m-0 text-[16px] font-semibold text-navy">Le problème est-il réglé ?</h3>
                <p class="m-0 mt-1 text-[13.5px] text-ink-body leading-relaxed">
                    {{ $workOrder->assignee?->name ?? 'La maintenance' }} a déclaré la réparation terminée
                    {{ $workOrder->completed_at?->locale('fr')->diffForHumans() }}. Vérifiez sur place puis répondez.
                </p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2.5" x-show="! reopen">
            <form method="POST" action="{{ route('work-orders.confirm', $workOrder) }}">
                @csrf
                <button type="submit" class="btn btn-lg btn-primary w-full sm:w-auto"><x-nav-icon name="check" /> Oui, c'est réglé</button>
            </form>
            <button type="button" class="btn btn-lg btn-secondary" @click="reopen = true">Non, toujours en panne</button>
        </div>

        <form method="POST" action="{{ route('work-orders.reopen', $workOrder) }}" x-show="reopen" x-cloak class="flex flex-col gap-2.5">
            @csrf
            <label for="reopen-reason" class="text-[13px] font-semibold text-ink-body">Qu'est-ce qui ne va pas ?</label>
            <textarea id="reopen-reason" name="reason" rows="2" required maxlength="500"
                      placeholder="Ex. : la climatisation s'arrête toujours au bout d'une heure.">{{ old('reason') }}</textarea>
            <x-input-error :messages="$errors->get('reason')" />
            <div class="flex flex-col sm:flex-row gap-2.5">
                <button type="submit" class="btn btn-danger-solid">Rouvrir l'OT</button>
                <button type="button" class="btn btn-ghost" @click="reopen = false">Retour</button>
            </div>
        </form>
    </section>
@elseif ($workOrder->requester_confirmed_at)
    <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-ok-bg text-[13.5px] text-[#155C40]">
        <x-nav-icon name="check" class="w-[18px] h-[18px] flex-shrink-0" />
        <span>Réparation confirmée par <strong>{{ $workOrder->requesterConfirmedBy?->name ?? 'le demandeur' }}</strong>
            le {{ $workOrder->requester_confirmed_at->format('d/m/Y à H\hi') }}.</span>
    </div>
@endif
