{{-- « Réparation envoyée » : la fin de l'assistant du technicien (mission), sur le modèle du
     « Signalement envoyé » du Housekeeping (quick-reports.sent). Le rapport est signé, l'OT est
     « Réparé » : on montre le récapitulatif, puis ce qui reste à vérifier — le contrôle qualité
     par un responsable, puis la confirmation du service demandeur s'il y en a un (la frise
     commune, x-work-order-journey) — et on sort : retour à sa journée. Le technicien est
     prévenu du résultat (validé, ou correction demandée). InterventionReportController::sent. --}}
@php
    $report = $workOrder->interventionReport;
    $withdrawn = $workOrder->partReservations->where('status', 'sortie');
    $closed = $workOrder->status === 'ferme';
    $rejected = $workOrder->status === 'rejete';
@endphp

<x-app-layout crumb="Ordres de travail" page-title="Réparation envoyée">
    <div class="w-full max-w-[560px] mx-auto tab:mt-6 flex flex-col gap-4">
        <section class="bg-white border border-line rounded-xl overflow-hidden">
            <div class="px-6 pt-8 pb-6 flex flex-col items-center text-center gap-3 border-b border-line-soft">
                <span class="w-16 h-16 rounded-full bg-ok-bg text-green flex items-center justify-center ring-8 ring-ok-bg/50">
                    <x-hk.icon name="check" :size="30" />
                </span>
                <h2 class="m-0 mt-2 text-[20px] font-semibold text-navy tracking-tight">Réparation envoyée</h2>
                <p class="m-0 text-[13.5px] text-ink-muted max-w-[400px] leading-relaxed">
                    @if ($closed)
                        La réparation a été vérifiée et l'ordre est clôturé. Merci !
                    @elseif ($rejected)
                        Le contrôle qualité demande une correction : reprenez l'intervention depuis la fiche.
                    @else
                        Le rapport est signé. La réparation attend maintenant d'être <strong class="text-ink-deep">vérifiée</strong> :
                        vous serez prévenu du résultat.
                    @endif
                </p>
            </div>

            <dl class="m-0 px-6 py-2 text-[13.5px]">
                @foreach (array_filter([
                    'Référence' => $workOrder->code(),
                    'Lieu' => $workOrder->room?->label ?? 'Parties communes',
                    'Temps passé' => \App\Support\Duration::human($workOrder->total_worked_minutes),
                    'Pièces sorties' => $withdrawn->isNotEmpty() ? $withdrawn->map(fn ($r) => $r->quantity.' × '.$r->part->name)->implode(', ') : 'Aucune',
                    'Signé par' => $report->signed_by_name.' · '.$report->signed_at->format('d/m/Y à H\hi'),
                ]) as $label => $value)
                    <div class="flex items-start justify-between gap-4 py-3 {{ $loop->last ? '' : 'border-b border-line-soft' }}">
                        <dt class="text-ink-grey flex-shrink-0">{{ $label }}</dt>
                        <dd class="m-0 font-medium text-navy text-right {{ $label === 'Référence' ? 'font-mono text-[13px]' : '' }}">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Ce qui reste à faire, et par qui : la frise commune à tous les rôles. --}}
        <section class="bg-white border border-line rounded-xl px-6 py-5" aria-labelledby="verification-title">
            <h3 id="verification-title" class="m-0 mb-4 text-[15px] font-semibold text-navy">La vérification</h3>
            <x-work-order-journey :work-order="$workOrder" vertical />
        </section>

        <div class="flex flex-col gap-2.5">
            <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="btn btn-primary btn-lg w-full"><x-hk.icon name="home" :size="16" /> Retour à ma journée</a>
            <a href="{{ route('work-orders.show', $workOrder) }}" class="btn btn-secondary btn-lg w-full"><x-hk.icon name="activity" :size="16" /> Voir la fiche</a>
        </div>
    </div>
</x-app-layout>
