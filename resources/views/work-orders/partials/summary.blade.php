{{-- En-tête de synthèse de la fiche OT : badges, description, les 4 repères qu'on
     cherche d'un coup d'œil (lieu, technicien, échéance, SLA), puis la frise
     d'avancement Ouvert → En cours → Résolu → Fermé. --}}
@php
    $status = $workOrder->status;
    $finished = in_array($status, \App\Models\WorkOrder::FINISHED_STATUSES, true);
    $late = $workOrder->due_date && $workOrder->due_date->isPast() && ! $finished;
    $assignee = $workOrder->assignee;
    // Échéance SLA passée = rouge, sans attendre que la tâche planifiée pose sla_breached.
    $slaColor = ! $finished && ($workOrder->sla_breached || $workOrder->sla_resolution_due_at?->isPast())
        ? 'red'
        : $workOrder->slaColorClass();
    $showPlanLink = $workOrder->maintenance_plan_id && auth()->user()->role->seesAllWorkOrders();

    // Frise : étape atteinte par statut ; « en attente » et « rejeté » se lisent
    // comme un arrêt sur l'étape en cours / résolue, signalés en couleur.
    $steps = ['ouvert' => 'Ouvert', 'en_cours' => 'En cours', 'resolu' => 'Résolu', 'ferme' => 'Fermé'];
    $reached = ['ouvert' => 0, 'en_cours' => 1, 'en_attente' => 1, 'resolu' => 2, 'rejete' => 2, 'ferme' => 3, 'annule' => -1][$status] ?? 0;
    $stepNote = match ($status) {
        'en_attente' => ['En attente', 'amber'],
        'rejete' => ['Correction demandée', 'red'],
        default => null,
    };
    $stepDate = fn (string $key) => match ($key) {
        'ouvert' => $workOrder->created_at,
        'en_cours' => $workOrder->started_at ?? $workOrder->statusHistories->firstWhere('new_status', 'en_cours')?->created_at,
        default => $workOrder->statusHistories->firstWhere('new_status', $key)?->created_at,
    };
@endphp

<section class="bg-white border border-line rounded-xl overflow-hidden">
    <div class="px-5 lg:px-6 pt-5 pb-4 flex flex-col gap-3">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="font-mono text-[12px] text-[#4A4639] px-2 py-0.5 rounded-md bg-paper border border-line">{{ $workOrder->code() }}</span>
            <x-work-order-status-badge :status="$status" />
            <x-work-order-priority-badge :priority="$workOrder->priority" />
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-line-soft text-[#6C6658]">
                <x-nav-icon name="tag" class="w-3.5 h-3.5" /> {{ $workOrder->type->label }}
            </span>
            @if ($workOrder->maintenance_plan_id)
                @if ($showPlanLink)
                    <a href="{{ route('maintenance-plans.show', $workOrder->maintenance_plan_id) }}"
                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#EAF0F6] text-blue hover:underline">
                        <x-nav-icon name="status" class="w-3.5 h-3.5" /> Maintenance préventive
                    </a>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#EAF0F6] text-blue">
                        <x-nav-icon name="status" class="w-3.5 h-3.5" /> Maintenance préventive
                    </span>
                @endif
            @endif
            <span class="w-full sm:w-auto sm:ml-auto text-[12px] text-ink-grey">
                Signalé par <span class="font-semibold text-[#4A4639]">{{ $workOrder->reporter?->name ?? '—' }}</span>
                le {{ $workOrder->created_at->format('d/m/Y à H\hi') }}
            </span>
        </div>

        @if ($workOrder->description)
            <p class="m-0 text-[14.5px] leading-relaxed text-[#3d3a33] whitespace-pre-line">{{ $workOrder->description }}</p>
        @else
            <p class="m-0 text-[13.5px] italic text-ink-grey">Aucune description fournie.</p>
        @endif
    </div>

    {{-- Repères clés --}}
    <dl class="m-0 grid grid-cols-2 lg:grid-cols-4 gap-px bg-line-soft border-t border-line-soft">
        <div class="bg-white px-5 py-3.5 flex gap-3 min-w-0">
            <x-nav-icon name="pin" class="w-[18px] h-[18px] text-gold mt-0.5 flex-shrink-0" />
            <div class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-grey">Lieu</dt>
                <dd class="m-0 text-[14px] font-semibold text-navy truncate">{{ $workOrder->room?->label ?? '—' }}</dd>
                @if ($workOrder->equipment)
                    <dd class="m-0 text-[12px] text-[#6C6658] truncate">{{ $workOrder->equipment->name }}</dd>
                @endif
            </div>
        </div>

        <div class="bg-white px-5 py-3.5 flex gap-3 min-w-0">
            <x-nav-icon name="user" class="w-[18px] h-[18px] text-gold mt-0.5 flex-shrink-0" />
            <div class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-grey">Technicien</dt>
                @if ($assignee)
                    <dd class="m-0 flex items-center gap-2 min-w-0">
                        <span class="w-6 h-6 flex-shrink-0 rounded-full bg-gold text-navy text-[10px] font-bold flex items-center justify-center">{{ $assignee->initialsOrGenerated() }}</span>
                        <span class="text-[14px] font-semibold text-navy truncate">{{ $assignee->name }}</span>
                    </dd>
                    @if ($workOrder->scheduled_at)
                        <dd class="m-0 text-[12px] text-[#6C6658]">Prévu le {{ $workOrder->scheduled_at->format('d/m à H\hi') }}</dd>
                    @endif
                @else
                    <dd class="m-0 text-[14px] font-semibold text-amber">Non affecté</dd>
                @endif
            </div>
        </div>

        <div class="bg-white px-5 py-3.5 flex gap-3 min-w-0">
            <x-nav-icon name="calendar" class="w-[18px] h-[18px] mt-0.5 flex-shrink-0 {{ $late ? 'text-red' : 'text-gold' }}" />
            <div class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-grey">Échéance</dt>
                <dd class="m-0 text-[14px] font-semibold {{ $late ? 'text-red' : 'text-navy' }}">{{ $workOrder->due_date?->format('d/m/Y à H\hi') ?? 'Non fixée' }}</dd>
                @if ($workOrder->due_date && ! $finished)
                    <dd class="m-0 text-[12px] {{ $late ? 'text-red font-medium' : 'text-[#6C6658]' }}">
                        {{ $late ? 'En retard de ' : 'Dans ' }}{{ $workOrder->due_date->locale('fr')->diffForHumans(null, true) }}
                    </dd>
                @endif
            </div>
        </div>

        <div class="bg-white px-5 py-3.5 flex gap-3 min-w-0">
            <x-nav-icon name="shield" class="w-[18px] h-[18px] mt-0.5 flex-shrink-0 {{ \App\Support\Swatch::text($slaColor) }}" />
            <div class="min-w-0 flex-1">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-grey">Délai SLA</dt>
                @if ($workOrder->sla_resolution_due_at && ! $finished)
                    <dd class="m-0 text-[14px] font-semibold {{ \App\Support\Swatch::text($slaColor) }}">{{ $workOrder->slaRemainingLabel() }}</dd>
                    <dd class="m-0 mt-1.5 h-[5px] rounded-full bg-line-soft overflow-hidden">
                        <span class="block h-full {{ \App\Support\Swatch::bg($slaColor) }}" style="width: {{ $slaColor === 'red' ? 100 : $workOrder->slaProgressPercent() }}%"></span>
                    </dd>
                @elseif ($workOrder->sla_resolution_due_at)
                    <dd class="m-0 text-[14px] font-semibold {{ $workOrder->sla_breached ? 'text-red' : 'text-green' }}">{{ $workOrder->sla_breached ? 'Dépassé' : 'Respecté' }}</dd>
                @else
                    <dd class="m-0 text-[14px] font-semibold text-ink-grey">Pas de SLA</dd>
                @endif
            </div>
        </div>
    </dl>

    {{-- Frise d'avancement --}}
    <div class="border-t border-line-soft bg-paper/50 px-5 lg:px-6 py-4">
        @if ($status === 'annule')
            <div class="flex items-center gap-2.5 text-[13px] text-[#6C6658]">
                <x-nav-icon name="x" class="w-[18px] h-[18px] text-ink-grey" />
                <span><strong class="text-navy">OT annulé.</strong> Il reste consultable ; le motif figure dans l'historique.</span>
            </div>
        @else
            <ol class="m-0 p-0 list-none flex items-start">
                @foreach ($steps as $key => $label)
                    @php
                        $index = $loop->index;
                        $done = $index < $reached || ($index === $reached && $status === 'ferme');
                        $current = $index === $reached && $status !== 'ferme';
                        $date = $index <= $reached ? $stepDate($key) : null;
                    @endphp
                    <li class="flex-1 flex flex-col items-center text-center relative min-w-0">
                        @unless ($loop->first)
                            <span class="absolute top-[13px] right-1/2 w-full h-[2px] {{ $index <= $reached ? 'bg-navy' : 'bg-line' }}" aria-hidden="true"></span>
                        @endunless
                        <span @class([
                            'relative z-10 w-7 h-7 rounded-full flex items-center justify-center text-[12px] font-bold',
                            'bg-navy text-white' => $done,
                            'bg-gold text-navy ring-4 ring-gold/25' => $current && ! $stepNote,
                            'bg-amber text-white ring-4 ring-amber/20' => $current && ($stepNote[1] ?? null) === 'amber',
                            'bg-red text-white ring-4 ring-red/20' => $current && ($stepNote[1] ?? null) === 'red',
                            'bg-white border-2 border-line text-ink-grey' => ! $done && ! $current,
                        ])>
                            @if ($done)
                                <x-nav-icon name="check" class="w-3.5 h-3.5" />
                            @else
                                {{ $index + 1 }}
                            @endif
                        </span>
                        <span class="mt-1.5 text-[12px] sm:text-[12.5px] font-semibold {{ $index <= $reached ? 'text-navy' : 'text-ink-grey' }}">
                            {{ $current && $stepNote ? $stepNote[0] : $label }}
                        </span>
                        <span class="text-[11px] text-ink-grey">{{ $date?->format('d/m H\hi') ?? ' ' }}</span>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</section>
