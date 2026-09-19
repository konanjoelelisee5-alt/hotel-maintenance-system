@php
    $dashboardRoute = auth()->user()->dashboardRoute();
    $dotColor = function (string $label) {
        return match (true) {
            str_contains($label, 'Urgent'), str_contains($label, 'SLA dépassés'), str_contains($label, 'Urgentes') => 'bg-red',
            str_contains($label, 'Non affect'), str_contains($label, 'attente'), str_contains($label, 'Blocages'), str_contains($label, 'Pièces à retirer'), str_contains($label, 'Problèmes qualité') => 'bg-gold',
            str_contains($label, 'En cours'), str_contains($label, 'Contrôle qualité'), str_contains($label, 'Temps saisi') => 'bg-amber',
            str_contains($label, 'Respect'), str_contains($label, 'Terminés'), str_contains($label, 'Résolus'), str_contains($label, 'Clôturées'), str_contains($label, 'actifs') => 'bg-green',
            default => 'bg-blue',
        };
    };
@endphp

@can('create', \App\Models\WorkOrder::class)
    @if (in_array(auth()->user()->role, [\App\Enums\UserRole::Housekeeping, \App\Enums\UserRole::Reception], true))
        {{-- Housekeeping/réception signalent plutôt qu'ils n'exécutent : un appel à
             l'action direct plutôt que la file d'OT en tête de page. --}}
        <div class="bg-navy rounded-xl p-5 flex flex-col gap-3">
            <div>
                <h2 class="text-white font-semibold text-[17px] leading-snug">Un problème dans une chambre ?</h2>
                <p class="text-[#B9C7D6] text-[13px] mt-1 leading-relaxed">Chambre, type de problème, description courte — ça part directement vers la maintenance.</p>
            </div>
            <a href="{{ route('work-orders.create') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 h-[46px] rounded-lg bg-gold text-navy font-semibold text-[14px]">
                Signaler un problème
            </a>
        </div>
    @endif
@endcan

{{-- Bandeau de metrics : chips indépendantes en défilement horizontal
     (jamais coupées/écrasées, quelle que soit la largeur d'écran). --}}
<div class="flex gap-3 overflow-x-auto pb-1 -mx-4 px-4 lg:mx-0 lg:px-0">
    @foreach ($pulse as $p)
        <x-mobile-metric-chip
            :label="$p['label']"
            :value="$p['value']"
            :sub="$p['sub']"
            :color="str_replace('bg-', '', $dotColor($p['label']))"
            :href="route($dashboardRoute, ['filter' => $p['filter']])"
            :active="$filter === $p['filter']"
        />
    @endforeach
</div>

{{-- Contenu principal (file d'OT, ~2/3) + panneau latéral (~1/3) : hiérarchie
     claire au lieu de deux colonnes de même largeur. --}}
<div class="grid gap-4 items-start lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
    <section class="bg-white border border-line rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 flex-wrap px-[18px] py-[15px] border-b border-line-soft">
            <div class="flex flex-col gap-0.5">
                <div class="text-[14.5px] font-semibold">
                    {{ match($filter) { 'urgent' => 'Ordres urgents', 'unassigned' => 'Ordres non affectés', 'late' => 'Ordres en retard SLA', 'mine' => 'Mes ordres', default => 'Tous les ordres' } }}
                </div>
                <div class="text-[11.5px] text-ink-grey">{{ $queue->count() }} ordre(s) affiché(s) · triés par urgence SLA</div>
            </div>
            <div class="flex gap-1.5 ml-auto flex-wrap">
                @foreach ($filters as $f)
                    <a href="{{ route($dashboardRoute, ['filter' => $f['key']]) }}" class="px-3 py-1.5 rounded-full border text-[12px] font-semibold {{ $filter === $f['key'] ? 'border-navy bg-[#EAF0F6] text-navy' : 'border-line text-[#6C6658]' }}">{{ $f['label'] }}</a>
                @endforeach
            </div>
        </div>

        <div class="flex flex-col gap-2.5 p-3">
            @forelse ($queue as $w)
                @include('work-orders.partials._ot-card', ['w' => $w])
            @empty
                <div class="px-5 py-11 flex flex-col items-center gap-2 text-center">
                    <div class="w-[42px] h-[42px] rounded-full bg-[#E6F3EC] flex items-center justify-center text-green text-[17px]">✓</div>
                    <div class="text-[14px] font-semibold">Aucun ordre dans ce filtre</div>
                    <div class="text-[12.5px] text-ink-grey max-w-[340px] leading-relaxed">Rien à traiter dans cette vue. Changez de filtre ci-dessus pour voir le reste des ordres.</div>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Un seul panneau latéral, deux sections internes séparées par un simple
         trait — au lieu de deux cartes identiques empilées avec un vide entre elles. --}}
    <section class="bg-white border border-line rounded-xl overflow-hidden">
        <div class="px-[18px] py-4">
            <div class="flex items-center justify-between gap-2.5 mb-3.5">
                <div class="text-[14px] font-semibold">{{ $sideA['title'] }}</div>
                <span class="text-[11.5px] text-ink-grey">{{ $sideA['sub'] ?? '' }}</span>
            </div>
            <div class="flex flex-col gap-3">
                @forelse ($sideA['items'] as $item)
                    <div class="flex items-center gap-2.5">
                        <span class="w-[7px] h-[7px] rounded-full {{ \App\Support\Swatch::bg($item['color']) }} flex-shrink-0"></span>
                        <span class="flex flex-col gap-1 min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-[13px] font-medium truncate">{{ $item['name'] }}</span>
                                <span class="font-mono text-[11.5px] text-[#6C6658]">{{ $item['meta'] }}</span>
                            </span>
                            <span class="h-[5px] rounded-[3px] bg-line-soft overflow-hidden block">
                                <span class="block h-full {{ \App\Support\Swatch::bg($item['color']) }}" style="width: {{ $item['pct'] }}%"></span>
                            </span>
                        </span>
                    </div>
                @empty
                    <div class="text-[12.5px] text-ink-grey">Rien à signaler.</div>
                @endforelse
            </div>
        </div>

        <div class="px-[18px] py-4 border-t border-line-soft bg-paper/40">
            <div class="text-[14px] font-semibold mb-3">{{ $sideB['title'] }}</div>
            <div class="flex flex-col gap-0.5">
                @forelse ($sideB['items'] as $item)
                    <div class="flex items-start gap-2.5 px-2 py-2 rounded-lg">
                        <span class="w-1.5 h-1.5 rounded-full {{ \App\Support\Swatch::bg($item['color']) }} mt-1.5 flex-shrink-0"></span>
                        <span class="flex flex-col gap-0.5 min-w-0">
                            <span class="text-[12.8px] font-medium leading-snug">{{ $item['label'] }}</span>
                            <span class="text-[11.5px] text-ink-grey">{{ $item['meta'] }}</span>
                        </span>
                    </div>
                @empty
                    <div class="text-[12.5px] text-ink-grey px-2">Rien à signaler.</div>
                @endforelse
            </div>
        </div>
    </section>
</div>

@if ($timeline->isNotEmpty())
    {{-- Bandeau de pied de page, traitement plus léger (pas de carte pleine
         bordure identique aux blocs ci-dessus) pour ne pas empiler un 4e bloc pareil. --}}
    <section class="border-t border-line pt-4">
        <div class="flex items-center gap-3 flex-wrap mb-3">
            <div class="text-[13px] font-semibold text-[#6C6658] uppercase tracking-wide">Déroulé du jour — {{ now()->locale('fr')->translatedFormat('l j F') }}</div>
        </div>
        <div class="flex gap-2.5 overflow-x-auto pb-1">
            @foreach ($timeline as $t)
                <a href="{{ route('work-orders.show', $t['id']) }}" class="flex-shrink-0 w-[196px] flex flex-col gap-1.5 px-[13px] py-3 border border-line-soft border-l-[3px] border-l-gold rounded-[10px] bg-white">
                    <span class="font-mono text-[12px] text-[#6C6658]">{{ $t['time'] }}</span>
                    <span class="text-[13px] font-semibold leading-snug">{{ $t['title'] }}</span>
                    <span class="text-[11.5px] text-ink-grey">{{ $t['who'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
