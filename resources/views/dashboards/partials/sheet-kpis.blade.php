{{-- Repères de l'accueil (feuille) : icône ronde, libellé, chiffre et tendance colorée, séparés
     par des filets. $kpis : [label, value, icon, trend, tone (up|down|neutral|muted), href?, active?] ;
     avec href, le repère ouvre la liste correspondante ; active : onglet affiché en dessous.
     Jusqu'à 3 repères : une bande (la maquette) ; au-delà : une grille de 3 (2 sur téléphone). --}}
@php
    $n = count($kpis);
    $grid = $n > 3;
    // Cases vides pour finir la dernière ligne (sinon le fond des filets se verrait).
    $fill2 = $n % 2;
    $fill3 = (3 - $n % 3) % 3;
    $tones = ['up' => 'text-green', 'down' => 'text-red', 'neutral' => 'text-blue', 'muted' => 'text-ink-grey font-medium'];
@endphp
<section aria-label="Repères du jour"
         class="{{ $grid ? 'grid grid-cols-2 sm:grid-cols-3 gap-px bg-line border-y border-line' : 'grid grid-cols-1 sm:grid-cols-3 border-y border-line divide-y sm:divide-y-0 sm:divide-x divide-line' }}">
    @foreach ($kpis as $kpi)
        @php
            $tag = isset($kpi['href']) ? 'a' : 'div';
            $active = $kpi['active'] ?? false;
        @endphp
        <{{ $tag }} @isset($kpi['href']) href="{{ $kpi['href'] }}" @endisset @if ($active) aria-current="true" @endif
            class="group flex items-center gap-3.5 min-w-0 transition-colors
                   {{ $grid ? 'px-3 sm:px-4 py-4 sm:py-5 '.($active ? 'bg-paper' : 'bg-white hover:bg-paper/60') : 'py-5 sm:px-4 first:sm:pl-0 last:sm:pr-0' }}">
            <span class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 transition-colors {{ $active ? 'bg-ink-deep text-white' : 'bg-paper '.(isset($kpi['href']) ? 'group-hover:bg-line' : '') }} {{ $grid ? 'hidden sm:flex' : '' }}">
                <x-nav-icon :name="$kpi['icon']" class="w-5 h-5" />
            </span>
            <div class="min-w-0">
                <div class="text-[14px] sm:text-[14.5px] font-semibold text-ink-body truncate">{{ $kpi['label'] }}</div>
                <div class="flex items-baseline gap-x-2.5 gap-y-0.5 mt-0.5 flex-wrap">
                    <span class="text-[22px] font-extrabold tabular leading-none">{{ $kpi['value'] }}</span>
                    @if ($kpi['trend'])
                        <span class="inline-flex items-center gap-1 text-[12.5px] sm:text-[13px] font-semibold {{ $tones[$kpi['tone']] }}">
                            @if (in_array($kpi['tone'], ['up', 'down'], true))
                                <svg viewBox="0 0 10 8" class="w-2.5 h-2 {{ $kpi['tone'] === 'up' ? '' : 'rotate-180' }}" aria-hidden="true"><path d="M5 0 10 8H0z" fill="currentColor"/></svg>
                            @endif
                            {{ $kpi['trend'] }}
                        </span>
                    @endif
                </div>
            </div>
        </{{ $tag }}>
    @endforeach
    @if ($grid)
        @for ($i = 0; $i < max($fill2, $fill3); $i++)
            <div class="bg-white {{ $i < $fill2 ? 'block' : 'hidden' }} {{ $i < $fill3 ? 'sm:block' : 'sm:hidden' }}" aria-hidden="true"></div>
        @endfor
    @endif
</section>
