{{-- Bilan du mois (gouvernante) : signalements de l'équipe, délais de réparation,
     catégories, chambres et agents, inspections. Comparaison avec le mois précédent ;
     imprimable pour la réunion avec le chef de maintenance.
     Données : HousekeepingSupervisionController::monthlyReport(). --}}
@php
    $label = ucfirst($month->locale('fr')->translatedFormat('F Y'));
    $delta = function ($now, $before, bool $lowerIsBetter = false) {
        if ($now === null || $before === null || $before == 0) return null;
        $diff = round(($now - $before) / $before * 100);
        if ($diff == 0) return ['text' => '= mois précédent', 'class' => 'text-ink-grey'];
        $good = $lowerIsBetter ? $diff < 0 : $diff > 0;
        return ['text' => ($diff > 0 ? '+' : '').$diff.' % vs mois précédent', 'class' => $good ? 'text-green' : 'text-red'];
    };
    $kpis = [
        ['Signalements', $stats['total'], $stats['urgent'].' urgent(s)', null],
        ['Réparés', $stats['repaired'], $stats['open'].' encore en cours', null],
        ['Délai moyen de réparation', $stats['avgHours'] !== null ? str_replace('.', ',', $stats['avgHours']).' h' : '—', 'du signalement à la réparation', $delta($stats['avgHours'], $previous['avgHours'], true)],
        ['Réparés dans le délai', $stats['onTime'] !== null ? $stats['onTime'].' %' : '—', 'délai garanti (SLA)', $delta($stats['onTime'], $previous['onTime'])],
    ];
    $bars = fn ($items) => $items->map(fn ($n, $name) => ['name' => $name, 'n' => $n, 'pct' => (int) round($n / max(1, $items->max()) * 100)])->values();
@endphp

<x-app-layout crumb="Outils de la gouvernante" :page-title="'Bilan · '.$label">
    <x-slot:primaryAction>
        <button type="button" onclick="window.print()" class="btn btn-secondary"><x-hk.icon name="note" :size="16" /> Imprimer</button>
    </x-slot:primaryAction>

    {{-- Choix du mois --}}
    <div class="flex items-center justify-between gap-3 print:hidden">
        <a href="{{ route('housekeeping.monthly-report', ['mois' => $month->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-secondary" aria-label="Mois précédent">
            <x-hk.icon name="arrow-left" :size="16" /> <span class="hidden min-[420px]:inline">{{ ucfirst($month->copy()->subMonth()->locale('fr')->translatedFormat('F')) }}</span>
        </a>
        <span class="text-[15px] font-semibold text-navy">{{ $label }}</span>
        @if ($canGoNext)
            <a href="{{ route('housekeeping.monthly-report', ['mois' => $month->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-secondary" aria-label="Mois suivant">
                <span class="hidden min-[420px]:inline">{{ ucfirst($month->copy()->addMonth()->locale('fr')->translatedFormat('F')) }}</span> <x-hk.icon name="arrow-right" :size="16" />
            </a>
        @else
            <span class="w-[88px]"></span>
        @endif
    </div>

    {{-- Chiffres clés --}}
    <section class="grid grid-cols-2 split:grid-cols-4 gap-3" aria-label="Chiffres clés">
        @foreach ($kpis as [$title, $value, $sub, $trend])
            <div class="bg-white border border-line rounded-xl px-4 py-4 flex flex-col gap-1">
                <span class="text-[12.5px] text-[#4A4639]">{{ $title }}</span>
                <span class="text-[26px] font-semibold text-navy tracking-tight leading-tight">{{ $value }}</span>
                <span class="text-[12px] text-[#6C6658]">{{ $sub }}</span>
                @if ($trend)<span class="text-[11.5px] font-semibold {{ $trend['class'] }}">{{ $trend['text'] }}</span>@endif
            </div>
        @endforeach
    </section>

    @if ($stats['total'] === 0)
        <div class="bg-white border border-line rounded-xl px-5 py-10 text-center text-[13.5px] text-ink-grey">Aucun signalement de l'équipe en {{ mb_strtolower($label) }}.</div>
    @else
        <div class="grid gap-5 split:grid-cols-3 items-start">
            @foreach (['Par catégorie' => $stats['byCategory'], 'Chambres les plus touchées' => $stats['byRoom'], 'Par agent' => $stats['byAgent']] as $title => $items)
                <section class="bg-white border border-line rounded-xl px-5 py-4 break-inside-avoid">
                    <h2 class="m-0 mb-3.5 text-[15px] font-semibold text-navy">{{ $title }}</h2>
                    <div class="flex flex-col gap-3">
                        @foreach ($bars($items) as $row)
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between gap-2 text-[13px]">
                                    <span class="truncate">{{ $row['name'] }}</span>
                                    <span class="font-mono text-[12px] text-[#4A4639]">{{ $row['n'] }}</span>
                                </div>
                                <div class="h-[5px] rounded-full bg-line-soft overflow-hidden"><div class="h-full bg-navy" style="width: {{ max(4, $row['pct']) }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    {{-- Inspections --}}
    <section class="bg-white border border-line rounded-xl px-5 py-4 flex flex-col gap-4 break-inside-avoid">
        <div class="flex items-baseline justify-between gap-3">
            <h2 class="m-0 text-[15px] font-semibold text-navy">Inspections</h2>
            <a href="{{ route('inspections.index') }}" class="text-[13px] font-semibold text-navy hover:underline print:hidden">Voir les inspections →</a>
        </div>
        @if ($inspections['count'] === 0)
            <p class="m-0 text-[13px] text-ink-grey">Aucune inspection terminée ce mois-ci.</p>
        @else
            <div class="grid grid-cols-3 gap-3 text-center">
                <div><div class="text-[22px] font-semibold text-navy">{{ $inspections['count'] }}</div><div class="text-[12px] text-[#6C6658]">inspection(s)</div></div>
                <div><div class="text-[22px] font-semibold text-navy">{{ $inspections['rooms'] }}</div><div class="text-[12px] text-[#6C6658]">chambre(s)</div></div>
                <div><div class="text-[22px] font-semibold text-navy">{{ $inspections['conformity'] !== null ? $inspections['conformity'].' %' : '—' }}</div><div class="text-[12px] text-[#6C6658]">conformité moyenne</div></div>
            </div>
            @if ($inspections['nokPoints']->isNotEmpty())
                <div>
                    <h3 class="m-0 mb-2 text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">Points le plus souvent non conformes</h3>
                    <ul class="m-0 p-0 list-none flex flex-col">
                        @foreach ($inspections['nokPoints'] as $point => $n)
                            <li class="flex justify-between gap-3 py-2 border-b border-line-soft last:border-b-0 text-[13px]"><span>{{ $point }}</span><span class="font-mono text-[12px] text-red font-semibold">{{ $n }} fois</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </section>

    <p class="m-0 text-[11.5px] text-ink-grey">Signalements de l'équipe Housekeeping créés en {{ mb_strtolower($label) }}. Délai moyen calculé sur les signalements réparés ; « dans le délai » : réparés avant l'échéance garantie.</p>
</x-app-layout>
