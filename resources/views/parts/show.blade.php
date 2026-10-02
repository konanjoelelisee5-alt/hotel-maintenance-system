@php
    $low = $part->is_active && $part->is_below_threshold;
    // Repère du seuil sur la jauge (en % du stock, ou du double du seuil si le stock est vide).
    $scale = max($part->quantity_on_hand, $part->reorder_threshold * 2, 1);
    $thresholdAt = min(100, round($part->reorder_threshold / $scale * 100));
    $availableAt = min(100, round(max($part->quantity_available, 0) / $scale * 100));
    $kpis = [
        ['label' => 'En stock', 'value' => $part->quantity_on_hand.' '.$part->unit, 'sub' => 'quantité physique au magasin', 'dot' => 'bg-navy'],
        ['label' => 'Réservé', 'value' => $part->quantity_reserved, 'sub' => 'promis à des OT en cours', 'dot' => 'bg-blue'],
        ['label' => 'Disponible', 'value' => $part->quantity_available, 'sub' => $low ? 'sous le seuil d’alerte' : 'peut être réservé', 'dot' => $low ? 'bg-red' : 'bg-green'],
        ['label' => 'Valeur en stock', 'value' => \App\Support\Money::format($part->quantity_on_hand * $part->unit_cost), 'sub' => \App\Support\Money::format($part->unit_cost).' l’unité', 'dot' => 'bg-gold'],
    ];
    $movementStyle = [
        'entree' => ['+', 'green', 'Entrée'],
        'sortie' => ['−', 'red', 'Sortie'],
        'ajustement' => ['±', 'blue', 'Ajustement'],
    ];
@endphp

<x-app-layout :crumb="'Stock & achats / Pièces & stock'" :page-title="$part->name" :back-route="route('parts.index')">
    <x-slot:primaryAction>
        <a href="{{ route('parts.edit', $part) }}" data-modal class="btn btn-secondary"><x-nav-icon name="pencil" /> Modifier</a>
        @if ($part->is_active)
            <x-more-menu>
                <x-more-menu.item :href="route('purchase-orders.create', ['part_id' => $part->id])" icon="truck">Commander chez un fournisseur</x-more-menu.item>
                <x-more-menu.separator />
                <x-more-menu.item :action="route('parts.destroy', $part)" method="DELETE" icon="ban" danger
                                  confirm="La pièce ne sera plus proposée aux réservations ni aux commandes. Son historique est conservé."
                                  confirm-title="Retirer cette pièce du catalogue ?" confirm-label="Retirer du catalogue">Retirer du catalogue</x-more-menu.item>
            </x-more-menu>
        @endif
    </x-slot:primaryAction>

    {{-- Fiche d'identité de la pièce + jauge de stock --}}
    <section class="bg-white border border-line rounded-xl px-5 lg:px-6 py-5 flex flex-col gap-4">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="font-mono text-[12px] text-[#4A4639] px-2 py-0.5 rounded-md bg-paper border border-line">{{ $part->sku }}</span>
            @if (! $part->is_active)
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ \App\Support\Swatch::pill('muted') }}">Retirée du catalogue</span>
            @elseif ($low)
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ \App\Support\Swatch::pill('red') }}">Sous le seuil d’alerte</span>
            @else
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ \App\Support\Swatch::pill('green') }}">Stock suffisant</span>
            @endif
            <span class="text-[12.5px] text-[#6C6658]">Unité : {{ $part->unit ?: '—' }} · Seuil d’alerte : {{ $part->reorder_threshold }}</span>
        </div>

        <div>
            <div class="flex items-baseline justify-between gap-3 text-[13px]">
                <span class="text-[#4A4639]"><strong class="text-[22px] text-navy">{{ $part->quantity_available }}</strong> disponible(s) sur {{ $part->quantity_on_hand }}</span>
                <span class="text-[12px] text-[#6C6658]">repère : seuil d’alerte</span>
            </div>
            <div class="relative mt-2 h-[8px] rounded-full bg-line-soft">
                <div class="h-full rounded-full {{ $low ? 'bg-red' : 'bg-green' }}" style="width: {{ $availableAt }}%"></div>
                <span class="absolute -top-1 w-[2px] h-[16px] bg-navy rounded" style="left: {{ $thresholdAt }}%" title="Seuil d’alerte : {{ $part->reorder_threshold }}"></span>
            </div>
        </div>

        @if ($low)
            <div class="flex items-start gap-2.5 px-3.5 py-3 rounded-[10px] bg-[#FDECEA] text-[13px] text-[#8A1F16]">
                <x-nav-icon name="alert" class="w-[18px] h-[18px] flex-shrink-0" />
                <span>Le stock est passé sous le seuil d’alerte : pensez à réapprovisionner (menu ⋮ → Commander).</span>
            </div>
        @endif
    </section>

    <x-kpi-band :items="$kpis" tiles />

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_380px] items-start">
        {{-- Historique : chaque entrée, sortie ou ajustement, le plus récent en haut --}}
        <x-panel title="Historique des mouvements" icon="history" flush>
            <x-slot:badge><span class="font-mono text-[12px] text-[#6C6658]">{{ $part->stockMovements->count() }}</span></x-slot:badge>
            @forelse ($part->stockMovements->sortByDesc('created_at') as $movement)
                @php
                    [$sign, $color, $label] = $movementStyle[$movement->type] ?? ['', 'grey', $movement->type_label];
                    // Une sortie est enregistrée en positif ; un ajustement porte son propre signe.
                    $qtySign = match (true) { $movement->type === 'sortie', $movement->quantity < 0 => '−', default => '+' };
                @endphp
                <div class="flex items-start gap-3 px-5 py-3.5 border-b border-line-soft last:border-b-0">
                    <span class="w-9 h-9 flex-shrink-0 rounded-full flex items-center justify-center font-mono text-[15px] font-semibold {{ \App\Support\Swatch::pill($color) }}">{{ $sign }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-[14px] font-semibold text-navy">
                                {{ $label }}
                                <span class="font-mono {{ \App\Support\Swatch::text($color) }}">
                                    {{ $qtySign }}{{ abs($movement->quantity) }} {{ $part->unit }}
                                </span>
                            </span>
                            <span class="font-mono text-[11.5px] text-[#6C6658] whitespace-nowrap">{{ $movement->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="text-[12.5px] text-[#6C6658] mt-0.5">
                            {{ $movement->creator?->name ?? '—' }}
                            @if ($movement->workOrder)
                                · <a href="{{ route('work-orders.show', $movement->workOrder) }}" class="text-navy font-medium hover:underline">{{ $movement->workOrder->code() }}</a>
                            @endif
                            @if ($movement->note) · <span class="italic">{{ $movement->note }}</span> @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="m-0 px-5 py-6 text-[13px] text-[#6C6658]">Aucun mouvement enregistré pour cette pièce.</p>
            @endforelse
        </x-panel>

        <div class="flex flex-col gap-5 min-w-0">
            {{-- Mouvement manuel : réception hors bon de commande, casse, inventaire --}}
            @if ($part->is_active)
                <x-panel title="Enregistrer un mouvement" icon="swap" class="ui-form">
                    <form method="POST" action="{{ route('parts.movements.store', $part) }}" class="flex flex-col gap-3.5"
                          x-data="{ type: '{{ old('type', 'entree') }}' }">
                        @csrf
                        {{-- Choix en pastilles (style .ui-form, comme l'avancement d'un OT). --}}
                        <div class="grid grid-cols-3 gap-2" role="radiogroup" aria-label="Type de mouvement">
                            @foreach (['entree' => 'Entrée', 'sortie' => 'Sortie', 'ajustement' => 'Inventaire'] as $value => $text)
                                <label class="!flex justify-center !px-2">
                                    <input type="radio" name="type" value="{{ $value }}" x-model="type" required>
                                    {{ $text }}
                                </label>
                            @endforeach
                        </div>
                        <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-[#4A4639]">Quantité ({{ $part->unit ?: 'unité' }})
                            <input type="number" name="quantity" value="{{ old('quantity') }}" required>
                        </label>
                        <p class="m-0 -mt-1.5 text-[12px] text-[#6C6658]" x-show="type === 'ajustement'" x-cloak>
                            Inventaire : quantité à ajouter (positive) ou à retirer (négative, ex. −2) pour coller au stock réel.
                        </p>
                        <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-[#4A4639]"><span>Note <span class="font-normal text-[#6C6658]">(facultatif)</span></span>
                            <input type="text" name="note" value="{{ old('note') }}" placeholder="Ex. : livraison sans bon, casse, inventaire mensuel">
                        </label>
                        <x-input-error :messages="$errors->all()" />
                        <button type="submit" class="btn btn-primary">Enregistrer le mouvement</button>
                    </form>
                </x-panel>
            @endif

            {{-- Réservations : pièces promises à des OT --}}
            <x-panel title="Réservations" icon="part" flush>
                <x-slot:badge><span class="font-mono text-[12px] text-[#6C6658]">{{ $part->reservations->where('status', 'reservee')->count() }} en cours</span></x-slot:badge>
                @forelse ($part->reservations->sortByDesc('created_at') as $reservation)
                    @php $resColor = ['reservee' => 'gold', 'sortie' => 'green', 'annulee' => 'muted'][$reservation->status] ?? 'grey'; @endphp
                    <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-line-soft last:border-b-0">
                        <span class="min-w-0 text-[13px]">
                            @if ($reservation->workOrder)
                                <a href="{{ route('work-orders.show', $reservation->workOrder) }}" class="font-mono font-semibold text-navy hover:underline">{{ $reservation->workOrder->code() }}</a>
                                <span class="block text-[12px] text-[#6C6658] truncate">{{ $reservation->workOrder->title }}</span>
                            @else
                                <span class="text-[#6C6658]">OT supprimé</span>
                            @endif
                        </span>
                        <span class="flex items-center gap-2 flex-shrink-0">
                            <span class="font-mono text-[13px] text-navy">{{ $reservation->quantity }} {{ $part->unit }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ \App\Support\Swatch::pill($resColor) }}">{{ $reservation->status_label }}</span>
                        </span>
                    </div>
                @empty
                    <p class="m-0 px-5 py-5 text-[13px] text-[#6C6658]">Aucune réservation pour cette pièce.</p>
                @endforelse
            </x-panel>
        </div>
    </div>
</x-app-layout>
