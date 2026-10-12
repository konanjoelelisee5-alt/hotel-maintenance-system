{{-- Frise du parcours d'un OT (App\Support\WorkOrderJourney), la même pour tous les
     rôles : horizontale dans la fiche OT, verticale dans la fiche du Housekeeping.
     Couleur + icône + texte : une étape arrêtée (en attente, correction) change aussi
     de libellé, pas seulement de couleur. --}}
@props(['workOrder', 'vertical' => false])

@php
    $steps = \App\Support\WorkOrderJourney::steps($workOrder);
    $dot = fn (array $s) => match (true) {
        $s['state'] === 'done' => 'bg-navy text-white',
        $s['state'] === 'current' && ($s['alert'][1] ?? null) === 'red' => 'bg-red text-white ring-4 ring-red/20',
        $s['state'] === 'current' && $s['alert'] !== null => 'bg-amber text-white ring-4 ring-amber/20',
        $s['state'] === 'current' => 'bg-gold text-navy ring-4 ring-gold/25',
        $s['state'] === 'skipped' => 'bg-paper border-2 border-dashed border-line text-ink-grey',
        default => 'bg-white border-2 border-line text-ink-grey',
    };
    $reached = fn (array $s) => in_array($s['state'], ['done', 'current'], true);
@endphp

<ol {{ $attributes->class(['m-0 p-0 list-none', 'flex items-start' => ! $vertical]) }} aria-label="Avancement de l'ordre de travail">
    @foreach ($steps as $i => $s)
        @php $next = $steps[$i + 1] ?? null; @endphp
        <li @class([
                'relative min-w-0',
                'flex-1 flex flex-col items-center text-center' => ! $vertical,
                'flex gap-3 pb-4 last:pb-0' => $vertical,
            ])
            @if ($s['state'] === 'current') aria-current="step" @endif>
            @if ($vertical)
                @if ($next)
                    <span class="absolute left-[13px] top-7 bottom-0 w-[2px] {{ $reached($next) ? 'bg-navy' : 'bg-line' }}" aria-hidden="true"></span>
                @endif
            @elseif (! $loop->first)
                <span class="absolute top-[13px] right-1/2 w-full h-[2px] {{ $reached($s) ? 'bg-navy' : 'bg-line' }}" aria-hidden="true"></span>
            @endif

            <span class="relative z-10 w-7 h-7 flex-shrink-0 rounded-full flex items-center justify-center text-[12px] font-bold {{ $dot($s) }}">
                @if ($s['state'] === 'done')
                    <x-nav-icon name="check" class="w-3.5 h-3.5" />
                @elseif ($s['state'] === 'skipped')
                    –
                @else
                    {{ $i + 1 }}
                @endif
            </span>

            <span @class(['flex flex-col min-w-0', 'mt-1.5 items-center w-full px-0.5' => ! $vertical, 'gap-0.5 pt-0.5' => $vertical])>
                <span @class([
                    'font-semibold leading-tight',
                    'text-[11.5px] sm:text-[12.5px]' => ! $vertical,
                    'text-[13.5px]' => $vertical,
                    'text-navy' => $reached($s) && $s['alert'] === null,
                    'text-ink-grey' => ! $reached($s),
                    \App\Support\Swatch::text($s['alert'][1] ?? null) => $s['alert'] !== null,
                ])>{{ $s['alert'][0] ?? $s['label'] }}</span>
                <span class="font-mono text-[11px] text-ink-grey">{{ $s['at']?->format($vertical ? 'd/m/Y H:i' : 'd/m H\hi') ?? ($vertical ? '' : ' ') }}</span>
                @if ($s['meta'])
                    <span @class([
                        'text-ink-muted truncate',
                        'hidden sm:block text-[11.5px] max-w-full' => ! $vertical,
                        'text-[12.5px]' => $vertical,
                    ])>{{ $s['meta'] }}</span>
                @endif
            </span>
        </li>
    @endforeach
</ol>
