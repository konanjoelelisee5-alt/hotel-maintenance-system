<x-app-layout crumb="Exploitation" page-title="Tableau de bord">
    @php
        // Les liens de la page conservent filtre et période l'un pour l'autre.
        $here = fn (array $params) => route('admin.dashboard', array_merge(['filter' => $filter, 'period' => $period], $params));
    @endphp

    <x-slot:primaryAction>
        <x-dashboard-actions>
            <div class="flex-shrink-0 flex items-center gap-0.5 p-[3px] rounded-[10px] bg-line-soft border border-line" role="group" aria-label="Période">
                @foreach ($periods as $key => [$short])
                    <a href="{{ $here(['period' => $key]) }}"
                       @if ($period === $key) aria-current="true" @endif
                       class="px-3 h-[30px] inline-flex items-center rounded-[7px] font-mono text-[12px] whitespace-nowrap {{ $period === $key ? 'bg-white text-navy font-semibold shadow-sm' : 'text-ink-grey hover:text-navy' }}">{{ $short }}</a>
                @endforeach
            </div>
        </x-dashboard-actions>
    </x-slot:primaryAction>

    @include('dashboards.partials._kpi-band')

    {{-- Tableau des ordres + colonne latérale ; la colonne passe dessous
         en dessous de 1100 px. --}}
    <div class="grid gap-5 items-start min-[1100px]:grid-cols-[minmax(0,1fr)_320px] min-[1500px]:grid-cols-[minmax(0,1fr)_380px]">
        @include('dashboards.partials._queue-table')

        <div class="flex flex-col gap-5 min-w-0">
            @include('dashboards.partials._bars-card', ['panel' => $sideA])
            @include('dashboards.partials._list-card', ['panel' => $sideB, 'link' => ['Journal →', route('activity-logs.index')]])

            {{-- Ce que seul l'admin peut corriger (comptes, astreinte, accès). Masqué quand tout va bien. --}}
            @if (! empty($systemAlerts))
                <section class="bg-white border border-line rounded-xl px-6 py-5">
                    <h2 class="text-[17px] font-semibold text-navy mb-3">Alertes système</h2>
                    <div class="flex flex-col gap-3">
                        @foreach ($systemAlerts as $alert)
                            <a href="{{ $alert['url'] }}" class="flex items-start gap-3 group">
                                <span class="w-[7px] h-[7px] rounded-full mt-[7px] flex-shrink-0 {{ \App\Support\Swatch::bg($alert['color']) }}"></span>
                                <span class="flex flex-col gap-0.5 min-w-0">
                                    <span class="text-[13.5px] leading-snug group-hover:underline">{{ $alert['label'] }}</span>
                                    <span class="text-[12px] text-ink-grey">{{ $alert['meta'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Une tâche automatique arrêtée ne se voit nulle part ailleurs :
                 plus d'escalade ni d'OT préventif, sans aucun message d'erreur. --}}
            <section class="bg-white border border-line rounded-xl px-6 py-5">
                <h2 class="text-[17px] font-semibold text-navy mb-3">Tâches automatiques</h2>
                <div class="flex flex-col gap-3">
                    @foreach ($schedulerHealth as $task)
                        @php($color = ['ok' => 'green', 'late' => 'amber', 'never' => 'red'][$task['state']])
                        <div class="flex items-start gap-3">
                            <span class="w-[7px] h-[7px] rounded-full mt-[7px] flex-shrink-0 {{ \App\Support\Swatch::bg($color) }}"></span>
                            <span class="flex flex-col gap-0.5 min-w-0">
                                <span class="text-[13.5px] leading-snug">{{ $task['label'] }}</span>
                                <span class="text-[12px] {{ $task['state'] === 'ok' ? 'text-ink-grey' : \App\Support\Swatch::text($color).' font-medium' }}">{{ $task['text'] }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    @include('dashboards.partials._timeline')
</x-app-layout>
