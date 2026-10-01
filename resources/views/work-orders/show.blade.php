<x-app-layout crumb="Ordres de travail · {{ $workOrder->code() }}" page-title="{{ $workOrder->title }}" :back-route="route('work-orders.index')">
    {{-- Deux fiches selon le métier : le superviseur PILOTE (panneau « Pilotage »
         ci-dessous, qui regroupe planifier, requalifier, « je m'en charge »...),
         l'intervenant assigné EXÉCUTE (chrono, avancement, rapport). --}}
    @cannot('pilot', $workOrder)
        @can('update', $workOrder)
            <x-slot:primaryAction>
                <a href="{{ route('work-orders.edit', $workOrder) }}" class="px-[15px] py-[9px] border border-line rounded-[9px] bg-white text-[#3d3a33] text-[13px] font-semibold inline-block">
                    Modifier
                </a>
            </x-slot:primaryAction>
        @endcan
    @endcannot

    {{-- Bandeau statut / priorité / description --}}
    <div class="bg-white rounded-xl border border-line p-6">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <x-work-order-status-badge :status="$workOrder->status" />
            <x-work-order-priority-badge :priority="$workOrder->priority" />
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-line-soft text-[#6C6658]">
                {{ $workOrder->type->label }}
            </span>
        </div>
        <p class="text-[#3d3a33] leading-relaxed">
            {{ $workOrder->description ?: 'Aucune description fournie.' }}
        </p>
    </div>

    @can('pilot', $workOrder)
        @include('work-orders.partials.pilot-panel')
    @endcan

    {{-- Raccourcis mobiles de l'intervenant : sur téléphone, les cartes s'empilent,
         ces boutons renvoient vers chaque section (ou démarrent le chrono). --}}
    @can('perform', $workOrder)
        @php $activeSession = $workOrder->activeSession(); @endphp
        <div class="lg:hidden bg-white rounded-xl border border-line p-4">
            <div class="text-[11px] font-semibold text-[#7D7768] uppercase tracking-wide mb-3">Mon intervention</div>
            <div class="flex flex-col gap-2">
                @if ($activeSession)
                    <form method="POST" action="{{ route('work-orders.sessions.stop', $workOrder) }}">
                        @csrf
                        <x-mobile-action-button variant="primary" icon="pause">Arrêter le chrono</x-mobile-action-button>
                    </form>
                @else
                    <form method="POST" action="{{ route('work-orders.sessions.start', $workOrder) }}">
                        @csrf
                        <x-mobile-action-button variant="primary" icon="play">Démarrer le chrono</x-mobile-action-button>
                    </form>
                @endif
                <x-mobile-action-button variant="secondary" icon="status" href="#status-form">Avancement</x-mobile-action-button>
                <x-mobile-action-button variant="secondary" icon="comment" href="#comments">Ajouter un commentaire</x-mobile-action-button>
                <x-mobile-action-button variant="secondary" icon="part" href="#parts">Réserver une pièce</x-mobile-action-button>
                <x-mobile-action-button variant="secondary" icon="report" href="#report">Rapport d'intervention</x-mobile-action-button>
            </div>
        </div>
    @endcan

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Colonne principale : actions et suivi --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Intervenant : avancement, chrono, rapport. Un superviseur ne voit le
                 rapport que s'il y en a un à lire ; le temps passé est dans « Informations ». --}}
            @can('perform', $workOrder)
                @include('work-orders.partials.status-form')
                @include('work-orders.partials.intervention-tracking')
                @include('work-orders.partials.intervention-report')
            @elsecan('work', $workOrder)
                @if ($workOrder->interventionReport)
                    @include('work-orders.partials.intervention-report')
                @endif
            @endcan

            {{-- Historique des contrôles qualité (le lancement se fait depuis le
                 panneau « Pilotage », à l'étape « Résolu »). --}}
            @can('reviewQuality', \App\Models\WorkOrder::class)
                @if ($workOrder->qualityControls->isNotEmpty())
                    @include('work-orders.partials.quality-control')
                @endif
            @endcan

            @if ($workOrder->correctionRequests->isNotEmpty())
                @include('work-orders.partials.correction-requests')
            @endif

            @include('work-orders.partials.comments')

        </div>

        {{-- Colonne latérale : informations et ressources --}}
        <div class="space-y-6">
            @include('work-orders.partials.info-card')

            @if ($workOrder->sla_policy_id)
                @include('work-orders.partials.sla-card')
            @endif

            @include('work-orders.partials.parts-card')
            @include('work-orders.partials.attachments-card')
            @include('work-orders.partials.status-history-card')
        </div>

    </div>
</x-app-layout>
