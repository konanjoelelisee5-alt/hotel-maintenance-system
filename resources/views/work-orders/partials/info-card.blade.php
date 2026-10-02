{{-- Planification et temps (colonne latérale). Type, priorité, signalement, lieu,
     technicien, échéance et SLA sont dans l'en-tête de synthèse (summary.blade.php) :
     on ne les répète pas ici. --}}
@php
    $rows = [];
    if ($workOrder->scheduled_at) {
        $rows[] = ['Planifié le', $workOrder->scheduled_at->format('d/m/Y à H\hi')];
    }
    if ($workOrder->estimated_duration_minutes) {
        $rows[] = ['Durée estimée', \App\Support\Duration::human($workOrder->estimated_duration_minutes)];
    }
    if ($workOrder->started_at) {
        $rows[] = ['Démarré le', $workOrder->started_at->format('d/m/Y à H\hi')];
    }
    if ($workOrder->completed_at) {
        $rows[] = ['Terminé le', $workOrder->completed_at->format('d/m/Y à H\hi')];
    }

    // Résumé du chrono pour qui n'a pas le bloc « Suivi de l'intervention »
    // (superviseurs) : seul l'intervenant assigné chronomètre.
    $showTime = auth()->user()->cannot('perform', $workOrder);
    $activeSession = $showTime ? $workOrder->activeSession() : null;
    $showTime = $showTime && ($activeSession || $workOrder->total_worked_minutes > 0);
@endphp

@if ($rows || $showTime)
    <x-panel title="Planification et temps" icon="clock" flush>
        <dl class="m-0 divide-y divide-line-soft text-[13px]">
            @foreach ($rows as [$label, $value])
                <div class="flex justify-between gap-3 px-5 py-2.5">
                    <dt class="text-ink-grey">{{ $label }}</dt>
                    <dd class="m-0 text-right font-medium text-[#14202B]">{{ $value }}</dd>
                </div>
            @endforeach

            @if ($showTime)
                <div class="flex justify-between gap-3 px-5 py-2.5">
                    <dt class="text-ink-grey">Temps passé</dt>
                    <dd class="m-0 text-right font-medium text-[#14202B]">
                        {{ \App\Support\Duration::human($workOrder->total_worked_minutes) }}
                        @if ($activeSession)
                            <span class="flex items-center justify-end gap-1.5 text-[11.5px] text-green font-semibold mt-0.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-green animate-pulse"></span>
                                en cours depuis {{ $activeSession->started_at->format('H\hi') }}
                            </span>
                        @endif
                    </dd>
                </div>
            @endif
        </dl>
    </x-panel>
@endif
