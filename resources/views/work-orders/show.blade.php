@php
    $user = auth()->user();
    $performs = $user->can('perform', $workOrder);
    $showValidation = $workOrder->qualityControls->isNotEmpty()
        || $workOrder->correctionRequests->isNotEmpty()
        || in_array($workOrder->status, ['resolu', 'rejete', 'ferme'], true);

    // Onglets de la fiche : chacun regroupe ce qu'un métier vient y faire. L'intervenant
    // arrive sur « Intervention » (chrono, avancement, rapport), les autres sur « Aperçu ».
    $tabs = array_values(array_filter([
        ['key' => 'apercu', 'label' => 'Aperçu'],
        ['key' => 'intervention', 'label' => 'Intervention'],
        $showValidation ? ['key' => 'validation', 'label' => 'Validation'] : null,
        ['key' => 'echanges', 'label' => 'Échanges', 'count' => $workOrder->comments->count() + $workOrder->attachments->count()],
        ['key' => 'historique', 'label' => 'Historique', 'count' => $workOrder->statusHistories->count()],
    ]));
    $defaultTab = $performs ? 'intervention' : 'apercu';
@endphp

<x-app-layout :crumb="'Ordres de travail / '.$workOrder->code()" :page-title="$workOrder->title" :back-route="route('work-orders.index')">
    {{-- Deux fiches selon le métier : le superviseur PILOTE (panneau « Pilotage » de
         l'aperçu : une prochaine étape + menu ⋮), l'intervenant assigné EXÉCUTE
         (onglet « Intervention » : chrono, avancement, rapport). --}}
    @cannot('pilot', $workOrder)
        @can('update', $workOrder)
            <x-slot:primaryAction>
                <a href="{{ route('work-orders.edit', $workOrder) }}" data-modal class="btn btn-secondary"><x-nav-icon name="pencil" /> Modifier</a>
            </x-slot:primaryAction>
        @endcan
    @endcannot

    {{-- .ui-form : champs et pastilles au style de la refonte (resources/css/app.css).
         L'onglet ouvert vit dans l'adresse (#intervention…) : il survit au rechargement,
         et chaque formulaire d'un onglet y ramène après envoi (le fragment d'un formulaire
         est conservé par la redirection du serveur). --}}
    <div class="ui-form flex flex-col gap-5"
         x-data="{ tab: @js($defaultTab) }"
         x-init="
            const keys = @js(array_column($tabs, 'key'));
            const fromHash = location.hash.slice(1);
            if (keys.includes(fromHash)) tab = fromHash;
            $watch('tab', (value) => {
                history.replaceState(null, '', '#' + value);
                $nextTick(() => window.dispatchEvent(new Event('work-order-tab')));
            });
            $el.addEventListener('submit', (event) => {
                const panel = event.target.closest('[data-tab-panel]');
                const action = event.target.getAttribute('action');
                if (panel && action && ! action.includes('#')) event.target.setAttribute('action', action + '#' + panel.dataset.tabPanel);
            }, true);
         ">
        @include('work-orders.partials.summary')

        {{-- Nouvel OT pour l'intervenant : un geste pour dire au manager qu'il l'a vu. --}}
        @can('acknowledge', $workOrder)
            <form method="POST" action="{{ route('work-orders.acknowledge', $workOrder) }}"
                  class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 rounded-xl border border-gold/50 bg-warn-bg">
                @csrf
                <div class="flex gap-3 flex-1 min-w-0">
                    <x-nav-icon name="bell" class="w-5 h-5 flex-shrink-0 mt-0.5 text-gold" />
                    <div class="min-w-0">
                        <p class="m-0 text-[14.5px] font-semibold text-ink-deep">Nouvel ordre pour vous</p>
                        <p class="m-0 text-[13px] text-ink-strong">Dites au manager que vous l'avez vu. Démarrer le chrono le fait aussi.</p>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-full sm:w-auto"><x-nav-icon name="check" /> J'ai vu, je m'en occupe</button>
            </form>
        @endcan

        <div class="bg-white border border-line rounded-xl px-2 sm:px-4 pt-2.5 -mb-2 tab:mb-0">
            <x-tabs :items="$tabs" model="tab" label="Sections de l'ordre de travail" />
        </div>

        {{-- Aperçu : piloter (superviseur) et les repères de l'OT --}}
        <div id="panel-apercu" data-tab-panel="apercu" role="tabpanel" aria-labelledby="tab-apercu" x-show="tab === 'apercu'" @if ($defaultTab !== 'apercu') x-cloak @endif
             class="grid grid-cols-1 split:grid-cols-[minmax(0,1fr)_320px] desk:grid-cols-[minmax(0,1fr)_340px] xl:grid-cols-[minmax(0,1fr)_380px] gap-5 items-start">
            <div class="flex flex-col gap-5 min-w-0">
                @include('work-orders.partials.requester-confirmation')
                @include('work-orders.partials.requester-actions')
                @can('pilot', $workOrder)
                    @include('work-orders.partials.pilot-panel')
                @endcan
                @include('work-orders.partials.info-card')
            </div>
            <aside class="flex flex-col gap-5 min-w-0">
                @if ($workOrder->sla_policy_id)
                    @include('work-orders.partials.sla-card')
                @endif
                @if ($workOrder->room && ! $workOrder->room->isCommonArea())
                    @include('work-orders.partials.room-block-card')
                @endif
                {{-- L'intervenant le retrouve dans son onglet « Intervention ». --}}
                @unless ($performs)
                    @include('work-orders.partials.previous-repairs')
                @endunless
            </aside>
        </div>

        {{-- Intervention : chrono, avancement et rapport de l'intervenant ; pièces pour tous.
             Un superviseur ne voit le rapport que s'il y en a un à lire. --}}
        <div id="panel-intervention" data-tab-panel="intervention" role="tabpanel" aria-labelledby="tab-intervention" x-show="tab === 'intervention'" @if ($defaultTab !== 'intervention') x-cloak @endif
             class="grid grid-cols-1 split:grid-cols-[minmax(0,1fr)_320px] desk:grid-cols-[minmax(0,1fr)_340px] xl:grid-cols-[minmax(0,1fr)_380px] gap-5 items-start">
            <div class="flex flex-col gap-5 min-w-0">
                @if ($performs)
                    @include('work-orders.partials.intervention-tracking')
                    @include('work-orders.partials.status-form')
                    @include('work-orders.partials.intervention-report')
                @else
                    {{-- Superviseur : les temps saisis (corrections signalées) et le rapport,
                         seulement s'il y a quelque chose à lire. --}}
                    @php $hasReport = $user->can('work', $workOrder) && $workOrder->interventionReport; @endphp
                    @if ($user->can('pilot', $workOrder) && $workOrder->interventionSessions->isNotEmpty())
                        @include('work-orders.partials.intervention-tracking', ['readonly' => true])
                    @endif
                    @if ($hasReport)
                        @include('work-orders.partials.intervention-report')
                    @elseif ($workOrder->interventionSessions->isEmpty())
                        <x-panel title="Intervention" icon="wrench">
                            <p class="m-0 text-[13.5px] text-ink-muted">
                                {{ $workOrder->assignee ? $workOrder->assignee->name.' n\'a encore saisi ni temps ni rapport.' : 'Aucun technicien n\'est encore affecté.' }}
                            </p>
                        </x-panel>
                    @endif
                @endif
            </div>
            <aside class="flex flex-col gap-5 min-w-0">
                @include('work-orders.partials.parts-card')
                @if ($performs)
                    @include('work-orders.partials.previous-repairs')
                @endif
            </aside>
        </div>

        {{-- Validation : contrôle qualité et corrections demandées --}}
        @if ($showValidation)
            <div id="panel-validation" data-tab-panel="validation" role="tabpanel" aria-labelledby="tab-validation" x-show="tab === 'validation'" x-cloak
                 class="flex flex-col gap-5 min-w-0 max-w-4xl">
                @if ($workOrder->correctionRequests->isNotEmpty())
                    @include('work-orders.partials.correction-requests')
                @endif
                @can('reviewQuality', \App\Models\WorkOrder::class)
                    @if ($workOrder->qualityControls->isNotEmpty())
                        @include('work-orders.partials.quality-control')
                    @endif
                @endcan
                @if ($workOrder->qualityControls->isEmpty() && $workOrder->correctionRequests->isEmpty())
                    <x-panel title="Validation" icon="shield">
                        <p class="m-0 text-[13.5px] text-ink-grey">
                            {{ $workOrder->status === 'resolu' ? 'Réparation déclarée terminée : le contrôle qualité n\'a pas encore été fait.' : 'Aucun contrôle qualité enregistré.' }}
                        </p>
                    </x-panel>
                @endif
            </div>
        @endif

        {{-- Échanges : commentaires et pièces jointes --}}
        <div id="panel-echanges" data-tab-panel="echanges" role="tabpanel" aria-labelledby="tab-echanges" x-show="tab === 'echanges'" x-cloak
             class="grid grid-cols-1 split:grid-cols-[minmax(0,1fr)_320px] desk:grid-cols-[minmax(0,1fr)_340px] xl:grid-cols-[minmax(0,1fr)_380px] gap-5 items-start">
            <div class="min-w-0">
                @include('work-orders.partials.comments')
            </div>
            <aside class="min-w-0">
                @include('work-orders.partials.attachments-card')
            </aside>
        </div>

        {{-- Historique : changements de statut --}}
        <div id="panel-historique" data-tab-panel="historique" role="tabpanel" aria-labelledby="tab-historique" x-show="tab === 'historique'" x-cloak
             class="min-w-0 max-w-4xl">
            @include('work-orders.partials.status-history-card')
        </div>
    </div>
</x-app-layout>
