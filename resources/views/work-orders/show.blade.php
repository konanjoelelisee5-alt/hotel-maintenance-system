<x-app-layout crumb="Ordres de travail · {{ $workOrder->code() }}" page-title="{{ $workOrder->title }}" :back-route="route('work-orders.index')">
    {{-- Deux fiches selon le métier : le superviseur PILOTE (panneau « Pilotage »,
         qui regroupe planifier, requalifier, « je m'en charge »...), l'intervenant
         assigné EXÉCUTE (chrono, avancement, rapport). Mise en page :
         synthèse en tête, puis action et suivi à gauche, références à droite. --}}
    @cannot('pilot', $workOrder)
        @can('update', $workOrder)
            <x-slot:primaryAction>
                <a href="{{ route('work-orders.edit', $workOrder) }}" class="btn btn-secondary"><x-nav-icon name="pencil" /> Modifier</a>
            </x-slot:primaryAction>
        @endcan
    @endcannot

    {{-- .ui-form : champs et pastilles au style de la refonte (resources/css/app.css). --}}
    <div class="ui-form flex flex-col gap-5">
        @include('work-orders.partials.summary')

        {{-- Raccourcis de l'intervenant sur téléphone : les cartes s'empilent, ces
             boutons démarrent le chrono ou renvoient vers chaque section. --}}
        @can('perform', $workOrder)
            @php $activeSession = $workOrder->activeSession(); @endphp
            <div class="lg:hidden bg-white rounded-xl border border-line p-4 flex flex-col gap-3">
                <div class="text-[11px] font-semibold text-[#7D7768] uppercase tracking-wide">Mon intervention</div>
                <form method="POST" action="{{ route($activeSession ? 'work-orders.sessions.stop' : 'work-orders.sessions.start', $workOrder) }}">
                    @csrf
                    <button type="submit" class="btn btn-lg w-full {{ $activeSession ? 'btn-danger-solid' : 'btn-primary' }}">
                        <x-nav-icon :name="$activeSession ? 'pause' : 'play'" /> {{ $activeSession ? 'Arrêter le chrono' : 'Démarrer le chrono' }}
                    </button>
                </form>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ([['#status-form', 'status', 'Avancement'], ['#comments', 'comment', 'Commentaire'], ['#parts', 'part', 'Pièces'], ['#report', 'report', 'Rapport']] as [$href, $icon, $label])
                        <a href="{{ $href }}" class="flex flex-col items-center gap-1.5 py-3 rounded-[10px] border border-line bg-paper/60 text-[12.5px] font-semibold text-navy">
                            <x-nav-icon :name="$icon" class="w-5 h-5 text-gold" /> {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endcan

        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_340px] xl:grid-cols-[minmax(0,1fr)_380px] gap-5 items-start">

            {{-- Colonne principale : agir et suivre --}}
            <div class="flex flex-col gap-5 min-w-0">
                @can('pilot', $workOrder)
                    @include('work-orders.partials.pilot-panel')
                @endcan

                {{-- Intervenant : chrono, avancement, rapport. Un superviseur ne voit le
                     rapport que s'il y en a un à lire ; le temps passé est dans « Détails ». --}}
                @can('perform', $workOrder)
                    @include('work-orders.partials.intervention-tracking')
                    @include('work-orders.partials.status-form')
                    @include('work-orders.partials.intervention-report')
                @elsecan('work', $workOrder)
                    @if ($workOrder->interventionReport)
                        @include('work-orders.partials.intervention-report')
                    @endif
                @endcan

                {{-- Contrôles qualité (lancés depuis le panneau « Pilotage », à l'étape « Résolu »). --}}
                @can('reviewQuality', \App\Models\WorkOrder::class)
                    @if ($workOrder->qualityControls->isNotEmpty())
                        @include('work-orders.partials.quality-control')
                    @endif
                @endcan

                @if ($workOrder->correctionRequests->isNotEmpty())
                    @include('work-orders.partials.correction-requests')
                @endif

                @include('work-orders.partials.comments')
                @include('work-orders.partials.status-history-card')
            </div>

            {{-- Colonne latérale : références et ressources --}}
            <aside class="flex flex-col gap-5 min-w-0">
                @include('work-orders.partials.info-card')

                @if ($workOrder->room && ! $workOrder->room->isCommonArea())
                    @include('work-orders.partials.room-block-card')
                @endif

                @if ($workOrder->sla_policy_id)
                    @include('work-orders.partials.sla-card')
                @endif

                @include('work-orders.partials.parts-card')
                @include('work-orders.partials.attachments-card')
            </aside>
        </div>
    </div>
</x-app-layout>
