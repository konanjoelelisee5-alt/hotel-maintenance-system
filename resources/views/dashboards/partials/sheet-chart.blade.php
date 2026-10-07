{{-- Courbe de l'accueil (feuille) : pannes signalées sur les 7 derniers jours face aux 7 jours
     d'avant (App\Support\RoomActivity::weekChart), bulle sombre au survol, tracé à l'arrivée.
     $chart, $chartTitle, $chartUnit (facultatif : [singulier, pluriel], « panne » par défaut). --}}
@php
// Courbe : 7 points lissés (Catmull-Rom → Bézier), bornés pour ne jamais passer sous zéro.
[$W, $H, $padT, $padB] = [700, 190, 14, 10];
$xs = collect(range(0, 6))->map(fn ($i) => ($i + 0.5) * $W / 7);
$step = max(1, (int) ceil($chart['max'] / 4));
$top = $step * 4;
$yOf = fn ($v) => $padT + (1 - $v / $top) * ($H - $padT - $padB);
$points = fn (string $key) => collect($chart['days'])->values()->map(fn ($d, $i) => [$xs[$i], $yOf($d[$key])]);
$smooth = function ($pts) use ($padT, $H, $padB) {
    $clamp = fn ($y) => max($padT, min($H - $padB, $y));
    $d = 'M'.round($pts[0][0], 1).' '.round($pts[0][1], 1);
    for ($i = 0; $i < count($pts) - 1; $i++) {
        $p0 = $pts[max(0, $i - 1)]; $p1 = $pts[$i]; $p2 = $pts[$i + 1]; $p3 = $pts[min(count($pts) - 1, $i + 2)];
        $c1 = [$p1[0] + ($p2[0] - $p0[0]) / 6, $clamp($p1[1] + ($p2[1] - $p0[1]) / 6)];
        $c2 = [$p2[0] - ($p3[0] - $p1[0]) / 6, $clamp($p2[1] - ($p3[1] - $p1[1]) / 6)];
        $d .= ' C'.round($c1[0], 1).' '.round($c1[1], 1).' '.round($c2[0], 1).' '.round($c2[1], 1).' '.round($p2[0], 1).' '.round($p2[1], 1);
    }

    return $d;
};
$nowPts = $points('now');
$beforePts = $points('before');
$nowPath = $smooth($nowPts);
$areaPath = $nowPath.' L'.round($nowPts->last()[0], 1).' '.($H - $padB).' L'.round($nowPts->first()[0], 1).' '.($H - $padB).' Z';
$ticks = collect([4, 3, 2, 1, 0])->map(fn ($k) => $k * $step);
@endphp
@php $unit = $chartUnit ?? ['panne', 'pannes']; @endphp
{{-- 7 derniers jours face aux 7 jours d'avant --}}
<section aria-labelledby="chart-title"
         x-data="{ i: {{ count($chart['days']) - 1 }}, hover: false }" @mouseleave="hover = false">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 id="chart-title" class="m-0 text-[20px] font-bold tracking-[-0.015em]">{{ $chartTitle }}</h2>
        <div class="flex items-center gap-4">
            <span class="hidden sm:inline-flex items-center gap-2 text-[12.5px] font-medium text-ink-muted"><span class="w-2.5 h-2.5 rounded-full bg-blue"></span>7 derniers jours</span>
            <span class="hidden sm:inline-flex items-center gap-2 text-[12.5px] font-medium text-ink-muted"><span class="w-2.5 h-2.5 rounded-full bg-[rgb(var(--rc-orange))]"></span>7 jours avant</span>
            <span class="h-9 px-4 rounded-full bg-paper inline-flex items-center text-[13px] font-semibold tabular">{{ $chart['range'] }}</span>
        </div>
    </div>
    <p class="m-0 mt-1 text-[13.5px] text-ink-muted">
        {{ $chart['total'] }} {{ $chart['total'] > 1 ? $unit[1] : $unit[0] }} en 7 jours, contre {{ $chart['totalBefore'] }} la semaine d'avant.
    </p>

    <div class="mt-5 grid grid-cols-[28px_minmax(0,1fr)] gap-x-3">
        {{-- Graduations --}}
        <div class="relative h-[190px] text-[12px] text-ink-grey tabular" aria-hidden="true">
            @foreach ($ticks as $t)
                <span class="absolute left-0" style="top: {{ round($yOf($t) / $H * 100, 2) }}%; transform: translateY(-50%)">{{ $t }}</span>
            @endforeach
        </div>

        <div class="relative">
            <svg viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="none" class="block w-full h-[190px] overflow-visible" role="img"
                 aria-label="{{ ucfirst($unit[1]) }} par jour sur 7 jours : {{ collect($chart['days'])->map(fn ($d) => $d['label'].' '.$d['now'])->join(', ') }}">
                <defs>
                    <linearGradient id="rc-area" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="rgb(47 111 214)" stop-opacity=".16" />
                        <stop offset="100%" stop-color="rgb(47 111 214)" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <path d="{{ $areaPath }}" fill="url(#rc-area)" class="rc-area" />
                <path d="{{ $smooth($beforePts) }}" fill="none" stroke="rgb(var(--rc-orange))" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke" pathLength="1200" class="rc-draw rc-draw-late" />
                <path d="{{ $nowPath }}" fill="none" stroke="rgb(var(--c-blue))" stroke-width="2.5" stroke-linecap="round" vector-effect="non-scaling-stroke" pathLength="1200" class="rc-draw" />
            </svg>

            {{-- Repère du jour survolé : trait, points, bulle sombre --}}
            @foreach ($chart['days'] as $k => $d)
                @php $left = round($xs[$k] / $W * 100, 3); @endphp
                <div x-show="i === {{ $k }}" x-cloak class="pointer-events-none absolute inset-y-0" style="left: {{ $left }}%">
                    <span class="absolute inset-y-0 -translate-x-1/2 border-l border-dashed border-ink-faint/60"></span>
                    <span class="absolute w-3 h-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue ring-4 ring-white" style="top: {{ round($yOf($d['now']) / $H * 100, 2) }}%"></span>
                    <span class="absolute w-3 h-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-[rgb(var(--rc-orange))] ring-4 ring-white" style="top: {{ round($yOf($d['before']) / $H * 100, 2) }}%"></span>
                    <div class="absolute top-0 w-[176px] rounded-2xl bg-navy-dark text-white px-3.5 py-3 shadow-[0_18px_36px_-14px_rgba(15,19,30,.6)]
                                {{ $k >= 4 ? 'right-3' : 'left-3' }}">
                        <div class="text-[13px] font-semibold first-letter:uppercase">{{ $d['date'] }}</div>
                        <div class="mt-2 flex items-center justify-between gap-3 text-[12.5px]">
                            <span class="flex items-center gap-2 text-white/70"><span class="w-0.5 h-3.5 rounded bg-[#8DB8F5]"></span>7 derniers jours</span>
                            <span class="font-bold tabular">{{ $d['now'] }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between gap-3 text-[12.5px]">
                            <span class="flex items-center gap-2 text-white/70"><span class="w-0.5 h-3.5 rounded bg-[rgb(var(--rc-orange))]"></span>Semaine d'avant</span>
                            <span class="font-bold tabular">{{ $d['before'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Zones de survol (souris, clavier, doigt) --}}
            <div class="absolute inset-0 grid grid-cols-7">
                @foreach ($chart['days'] as $k => $d)
                    <button type="button" class="h-full focus:outline-none" @mouseenter="i = {{ $k }}; hover = true" @focus="i = {{ $k }}" @click="i = {{ $k }}"
                            aria-label="{{ $d['date'] }} : {{ $d['now'] }} sur 7 derniers jours, {{ $d['before'] }} la semaine d'avant"></button>
                @endforeach
            </div>
        </div>

        {{-- Jours --}}
        <div></div>
        <div class="mt-3 grid grid-cols-7 text-center text-[12.5px] tabular" aria-hidden="true">
            @foreach ($chart['days'] as $k => $d)
                <span class="justify-self-center px-2.5 py-1 rounded-full transition-colors"
                      :class="i === {{ $k }} ? 'bg-paper font-bold text-ink-deep' : 'font-medium text-ink-grey'"><span class="hidden sm:inline">{{ rtrim($d['label'], '.') }} </span>{{ $d['day'] }}</span>
            @endforeach
        </div>
    </div>
</section>
