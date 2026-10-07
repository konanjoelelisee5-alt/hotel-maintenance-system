@php
    $kpis = [
        ['label' => 'Commandes', 'value' => $stats['count'], 'sub' => 'depuis le début', 'dot' => 'bg-navy'],
        ['label' => 'En cours', 'value' => $stats['open'], 'sub' => 'à envoyer, confirmer ou réceptionner', 'dot' => 'bg-blue'],
        ['label' => 'Total acheté', 'value' => \App\Support\Money::format($stats['total']), 'sub' => 'hors commandes annulées', 'dot' => 'bg-gold'],
        ['label' => 'Dernière commande', 'value' => $stats['last'] ? \Illuminate\Support\Carbon::parse($stats['last'])->format('d/m/Y') : '—', 'sub' => $stats['last'] ? \Illuminate\Support\Carbon::parse($stats['last'])->locale('fr')->diffForHumans() : 'aucune pour l’instant', 'dot' => 'bg-green'],
    ];
@endphp

<x-app-layout :crumb="'Stock & achats / Fournisseurs'" :page-title="$supplier->name" :back-route="route('suppliers.index')">
    <x-slot:primaryAction>
        <a href="{{ route('suppliers.edit', $supplier) }}" data-modal class="btn btn-secondary"><x-nav-icon name="pencil" /> Modifier</a>
        @if ($supplier->is_active)
            <a href="{{ route('purchase-orders.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-primary">+ Nouvelle commande</a>
            <x-more-menu>
                <x-more-menu.item :action="route('suppliers.destroy', $supplier)" method="DELETE" icon="ban" danger
                                  confirm="Il ne sera plus proposé pour les nouvelles commandes. Ses commandes passées restent consultables."
                                  confirm-title="Désactiver ce fournisseur ?" confirm-label="Désactiver">Désactiver le fournisseur</x-more-menu.item>
            </x-more-menu>
        @endif
    </x-slot:primaryAction>

    {{-- Coordonnées : appeler ou écrire d'un geste depuis le téléphone --}}
    <section class="bg-white border border-line rounded-xl overflow-hidden">
        <div class="flex items-center gap-3.5 px-5 tab:px-6 py-5">
            <span class="w-12 h-12 rounded-[12px] bg-paper border border-line flex items-center justify-center text-gold flex-shrink-0"><x-nav-icon name="truck" class="w-6 h-6" /></span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="m-0 text-[18px] font-semibold text-navy">{{ $supplier->name }}</h2>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ \App\Support\Swatch::pill($supplier->is_active ? 'green' : 'muted') }}">{{ $supplier->is_active ? 'Actif' : 'Désactivé' }}</span>
                </div>
                <p class="m-0 mt-0.5 text-[13px] text-ink-muted">Contact : {{ $supplier->contact_person ?? 'non renseigné' }}</p>
            </div>
        </div>
        <dl class="m-0 grid sm:grid-cols-3 border-t border-line-soft divide-y sm:divide-y-0 sm:divide-x divide-line-soft">
            @foreach ([
                ['bell', 'Téléphone', $supplier->phone, $supplier->phone ? 'tel:'.preg_replace('/\s+/', '', $supplier->phone) : null],
                ['send', 'E-mail', $supplier->email, $supplier->email ? 'mailto:'.$supplier->email : null],
                ['pin', 'Adresse', $supplier->address, null],
            ] as [$icon, $label, $value, $url])
                <div class="flex gap-3 px-5 tab:px-6 py-4 min-w-0">
                    <x-nav-icon :name="$icon" class="w-[18px] h-[18px] text-gold flex-shrink-0 mt-0.5" />
                    <div class="min-w-0">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ $label }}</dt>
                        <dd class="m-0 mt-0.5 text-[14px] text-navy {{ $value ? 'font-semibold' : 'text-ink-muted' }} break-words">
                            @if ($url)<a href="{{ $url }}" class="hover:underline">{{ $value }}</a>@else{{ $value ?? 'Non renseigné' }}@endif
                        </dd>
                    </div>
                </div>
            @endforeach
        </dl>
        @if ($supplier->notes)
            <p class="m-0 px-5 tab:px-6 py-4 border-t border-line-soft text-[13.5px] text-ink-strong leading-relaxed whitespace-pre-line">{{ $supplier->notes }}</p>
        @endif
    </section>

    <x-kpi-band :items="$kpis" tiles />

    <x-panel title="Bons de commande récents" icon="truck" flush>
        <x-slot:actions>
            @if ($stats['count'] > 10)
                <a href="{{ route('purchase-orders.index', ['search' => $supplier->name]) }}" class="text-[12.5px] font-semibold text-navy hover:underline">Tout voir</a>
            @endif
        </x-slot:actions>
        @forelse ($supplier->purchaseOrders as $po)
            <a href="{{ route('purchase-orders.show', $po) }}" class="flex flex-col sm:flex-row sm:items-center gap-1.5 sm:gap-4 px-5 py-3.5 border-b border-line-soft last:border-b-0 hover:bg-paper/60">
                <span class="flex-1 min-w-0">
                    <span class="font-mono text-[13px] font-semibold text-navy">{{ $po->number }}</span>
                    <span class="block text-[12.5px] text-ink-muted truncate">du {{ $po->order_date->format('d/m/Y') }}{{ $po->workOrder ? ' · pour '.$po->workOrder->code() : '' }}</span>
                </span>
                <span class="flex items-center justify-between sm:justify-end gap-4">
                    <x-purchase-order-status-badge :status="$po->status" />
                    <span class="font-mono text-[13.5px] text-navy w-[130px] text-right">{{ \App\Support\Money::format($po->total_amount) }}</span>
                </span>
            </a>
        @empty
            <div class="px-5 py-8 flex flex-col items-center gap-2 text-center">
                <div class="text-[13.5px] font-semibold">Aucune commande pour ce fournisseur</div>
                @if ($supplier->is_active)
                    <a href="{{ route('purchase-orders.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-sm btn-secondary">+ Passer une première commande</a>
                @endif
            </div>
        @endforelse
    </x-panel>
</x-app-layout>
