@php
    $po = $purchaseOrder;
    $status = $po->status;
    $receivable = in_array($status, ['envoyee', 'confirmee', 'reception_partielle'], true);
    $closed = in_array($status, ['receptionnee', 'facturee', 'annulee'], true);
    $ordered = $po->items->sum('quantity');
    $received = $po->items->sum('received_quantity');
    $invoiced = $po->invoices->sum('amount');
    $paid = $po->invoices->where('is_paid', true)->sum('amount');

    // Frise du cycle d'achat ; réception partielle = arrêt sur l'étape « Réceptionnée ».
    $steps = ['brouillon' => 'Brouillon', 'envoyee' => 'Envoyée', 'confirmee' => 'Confirmée', 'receptionnee' => 'Réceptionnée', 'facturee' => 'Facturée'];
    $reached = ['brouillon' => 0, 'envoyee' => 1, 'confirmee' => 2, 'reception_partielle' => 2, 'receptionnee' => 3, 'facturee' => 4, 'annulee' => -1][$status] ?? 0;

    // Prochaine étape : une seule action mise en avant.
    $next = match ($status) {
        'brouillon' => ['envoyee', 'Marquer comme envoyée', 'send'],
        'envoyee' => ['confirmee', 'Le fournisseur a confirmé', 'check'],
        default => null,
    };

    $kpis = [
        ['label' => 'Montant de la commande', 'value' => \App\Support\Money::format($po->total_amount), 'sub' => $po->items->count().' ligne(s) d’articles', 'dot' => 'bg-navy'],
        ['label' => 'Reçu', 'value' => $received.' / '.$ordered, 'sub' => $ordered ? round($received / $ordered * 100).' % des quantités' : 'aucun article', 'dot' => $received >= $ordered && $ordered ? 'bg-green' : 'bg-amber'],
        ['label' => 'Facturé', 'value' => \App\Support\Money::format($invoiced), 'sub' => $po->invoices->count().' facture(s)', 'dot' => 'bg-blue'],
        ['label' => 'Payé', 'value' => \App\Support\Money::format($paid), 'sub' => $invoiced > $paid ? 'reste '.\App\Support\Money::format($invoiced - $paid) : 'rien à payer', 'dot' => $invoiced > $paid ? 'bg-red' : 'bg-green'],
    ];
@endphp

<x-app-layout :crumb="'Stock & achats / Bons de commande'" :page-title="'Bon de commande '.$po->number" :back-route="route('purchase-orders.index')">
    <x-slot:primaryAction>
        @if ($next)
            <form method="POST" action="{{ route('purchase-orders.status.update', $po) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $next[0] }}">
                <button type="submit" class="btn btn-primary"><x-nav-icon :name="$next[2]" /> {{ $next[1] }}</button>
            </form>
        @elseif ($receivable)
            <a href="#reception" class="btn btn-primary"><x-nav-icon name="truck" /> Enregistrer la réception</a>
        @elseif ($status === 'receptionnee')
            <a href="#factures" class="btn btn-primary"><x-nav-icon name="report" /> Ajouter la facture</a>
        @endif
        @unless ($closed)
            <x-more-menu>
                @if ($status === 'confirmee')
                    <x-more-menu.item :action="route('purchase-orders.status.update', $po)" method="PATCH" icon="back" name="status" value="envoyee">Revenir à « Envoyée »</x-more-menu.item>
                @endif
                @if ($status === 'envoyee')
                    <x-more-menu.item :action="route('purchase-orders.status.update', $po)" method="PATCH" icon="back" name="status" value="brouillon">Revenir au brouillon</x-more-menu.item>
                @endif
                @if ($po->supplier)
                    <x-more-menu.item :href="route('suppliers.show', $po->supplier)" icon="truck">Fiche du fournisseur</x-more-menu.item>
                @endif
                <x-more-menu.separator />
                <x-more-menu.item :action="route('purchase-orders.status.update', $po)" method="PATCH" icon="x" danger name="status" value="annulee"
                                  confirm="La commande reste consultable mais ne sera plus suivie. Prévenez le fournisseur."
                                  confirm-title="Annuler ce bon de commande ?" confirm-label="Annuler la commande">Annuler la commande</x-more-menu.item>
            </x-more-menu>
        @endunless
    </x-slot:primaryAction>

    {{-- Synthèse : qui, quand, pour quoi, et où en est la commande --}}
    <section class="bg-white border border-line rounded-xl overflow-hidden">
        <div class="px-5 tab:px-6 pt-5 pb-4 flex flex-col gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-mono text-[12px] text-ink-body px-2 py-0.5 rounded-md bg-paper border border-line">{{ $po->number }}</span>
                <x-purchase-order-status-badge :status="$status" />
                <span class="w-full sm:w-auto sm:ml-auto text-[12px] text-ink-muted">
                    Créé par <span class="font-semibold text-ink-body">{{ $po->creator?->name ?? '—' }}</span> le {{ $po->created_at->format('d/m/Y') }}
                </span>
            </div>
            @if ($po->notes)
                <p class="m-0 text-[14px] leading-relaxed text-ink-strong whitespace-pre-line">{{ $po->notes }}</p>
            @endif
        </div>

        <dl class="m-0 grid grid-cols-2 tab:grid-cols-4 border-t border-line-soft">
            @foreach ([
                ['truck', 'Fournisseur', $po->supplier?->name ?? '—', $po->supplier ? route('suppliers.show', $po->supplier) : null],
                ['calendar', 'Date de commande', $po->order_date->format('d/m/Y'), null],
                ['clock', 'Livraison prévue', $po->expected_delivery_date?->format('d/m/Y') ?? 'Non précisée', null],
                ['clipboard', 'Pour l’OT', $po->workOrder ? $po->workOrder->code().' · '.$po->workOrder->title : 'Réapprovisionnement', $po->workOrder ? route('work-orders.show', $po->workOrder) : null],
            ] as [$icon, $label, $value, $url])
                <div class="flex gap-3 px-5 tab:px-6 py-4 border-line-soft [&:nth-child(odd)]:border-r tab:border-r tab:last:border-r-0 [&:nth-child(-n+2)]:border-b tab:[&:nth-child(-n+2)]:border-b-0 min-w-0">
                    <x-nav-icon :name="$icon" class="w-[18px] h-[18px] text-gold flex-shrink-0 mt-0.5" />
                    <div class="min-w-0">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-muted">{{ $label }}</dt>
                        <dd class="m-0 mt-0.5 text-[14px] font-semibold text-navy truncate">
                            @if ($url)<a href="{{ $url }}" class="hover:underline">{{ $value }}</a>@else{{ $value }}@endif
                        </dd>
                    </div>
                </div>
            @endforeach
        </dl>

        @if ($status === 'annulee')
            <div class="px-5 tab:px-6 py-3.5 border-t border-line-soft bg-paper text-[13px] text-ink-muted">Commande annulée : elle reste consultable dans l’historique.</div>
        @else
            <ol class="m-0 list-none grid grid-cols-5 gap-1 px-3 tab:px-6 py-4 border-t border-line-soft bg-paper/50" aria-label="Avancement de la commande">
                @foreach (array_values($steps) as $i => $label)
                    @php $done = $i < $reached || ($i === $reached && $i === 4); $current = $i === $reached && ! $done; @endphp
                    <li class="flex flex-col items-center gap-1.5 text-center relative">
                        @if ($i > 0)
                            <span class="absolute top-[13px] right-1/2 w-full h-[2px] {{ $i <= $reached ? 'bg-navy' : 'bg-line' }} -z-0"></span>
                        @endif
                        <span class="relative z-10 w-[28px] h-[28px] rounded-full flex items-center justify-center text-[12px] font-semibold
                            {{ $done ? 'bg-navy text-white' : ($current ? 'bg-gold text-white ring-4 ring-gold/20' : 'bg-white border-2 border-line text-ink-faint') }}">
                            @if ($done)<x-nav-icon name="check" class="w-3.5 h-3.5" />@else{{ $i + 1 }}@endif
                        </span>
                        <span class="text-[11.5px] sm:text-[12.5px] {{ $done || $current ? 'font-semibold text-navy' : 'text-ink-muted' }}">{{ $label }}</span>
                        @if ($current && $status === 'reception_partielle')
                            <span class="text-[11px] text-amber font-medium -mt-1">partielle</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    <x-kpi-band :items="$kpis" tiles />

    {{-- Articles commandés + saisie de la réception (alimente le stock) --}}
    <section id="reception" class="bg-white border border-line rounded-xl overflow-hidden scroll-mt-24">
        <form method="POST" action="{{ route('purchase-orders.reception.store', $po) }}" class="ui-form">
            @csrf
            <div class="flex items-center gap-3 px-5 tab:px-6 py-4 border-b border-line-soft">
                <span class="w-8 h-8 flex-shrink-0 rounded-[8px] border border-line bg-paper flex items-center justify-center text-gold"><x-nav-icon name="part" class="w-4 h-4" /></span>
                <h3 class="m-0 flex-1 text-[14.5px] font-semibold text-navy">Articles commandés</h3>
                @if ($receivable)
                    <span class="text-[12px] text-ink-muted">Saisissez les quantités reçues</span>
                @endif
            </div>

            <div class="hidden split:grid grid-cols-[minmax(0,1fr)_110px_140px_150px_130px] gap-4 px-6 py-2.5 bg-paper border-b border-line text-[11.5px] font-semibold uppercase tracking-wide text-ink-muted">
                <span>Article</span><span class="text-right">Commandé</span><span class="text-right">Prix unitaire</span><span class="text-right">Sous-total</span><span class="text-right">Reçu</span>
            </div>
            <ul class="m-0 p-0 list-none divide-y divide-line-soft">
                @foreach ($po->items as $item)
                    <li class="grid gap-2 split:grid-cols-[minmax(0,1fr)_110px_140px_150px_130px] split:gap-4 split:items-center px-5 tab:px-6 py-3.5">
                        <div class="min-w-0">
                            <div class="text-[14px] font-semibold text-navy">{{ $item->description }}</div>
                            @if ($item->is_fully_received)
                                <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[11px] font-semibold {{ \App\Support\Swatch::pill('green') }}">Reçu en totalité</span>
                            @endif
                        </div>
                        <div class="flex justify-between split:block text-[13px] split:text-right"><span class="split:hidden text-ink-muted">Commandé</span><span class="font-mono">{{ $item->quantity }}</span></div>
                        <div class="flex justify-between split:block text-[13px] split:text-right"><span class="split:hidden text-ink-muted">Prix unitaire</span><span class="font-mono">{{ \App\Support\Money::format($item->unit_price) }}</span></div>
                        <div class="flex justify-between split:block text-[13px] split:text-right"><span class="split:hidden text-ink-muted">Sous-total</span><span class="font-mono font-semibold text-navy">{{ \App\Support\Money::format($item->subtotal) }}</span></div>
                        <div class="flex justify-between items-center split:block split:text-right">
                            <span class="split:hidden text-[13px] text-ink-muted">Reçu</span>
                            @if ($receivable)
                                <input type="number" name="received[{{ $item->id }}]" min="0" max="{{ $item->quantity }}" value="{{ $item->received_quantity }}"
                                       aria-label="Quantité reçue : {{ $item->description }}" class="!w-24 text-right font-mono">
                            @else
                                <span class="font-mono text-[13px]">{{ $item->received_quantity }} / {{ $item->quantity }}</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 tab:px-6 py-4 border-t border-line bg-paper/60">
                <span class="text-[14px] text-ink-body">Total <strong class="ml-1 font-mono text-[16px] text-navy">{{ \App\Support\Money::format($po->total_amount) }}</strong></span>
                @if ($receivable)
                    <button type="submit" class="btn btn-primary"><x-nav-icon name="check" /> Enregistrer la réception</button>
                @endif
            </div>
            <div class="px-5 tab:px-6"><x-input-error :messages="$errors->get('received')" class="pb-3" /></div>
        </form>
    </section>

    {{-- Factures du fournisseur --}}
    <section id="factures" class="scroll-mt-24">
        <x-panel title="Factures" icon="report" flush class="ui-form">
            <x-slot:badge><span class="font-mono text-[12px] text-ink-muted">{{ $po->invoices->count() }}</span></x-slot:badge>
            @forelse ($po->invoices as $invoice)
                <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 px-5 py-3.5 border-b border-line-soft">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-[13px] font-semibold text-navy">{{ $invoice->invoice_number }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ \App\Support\Swatch::pill($invoice->is_paid ? 'green' : 'amber') }}">{{ $invoice->is_paid ? 'Payée' : 'À payer' }}</span>
                        </div>
                        <div class="text-[12.5px] text-ink-muted mt-0.5">du {{ $invoice->invoice_date->format('d/m/Y') }}{{ $invoice->is_paid && $invoice->paid_at ? ' · payée le '.$invoice->paid_at->format('d/m/Y') : '' }}</div>
                    </div>
                    <span class="font-mono text-[14px] font-semibold text-navy">{{ \App\Support\Money::format($invoice->amount) }}</span>
                    <span class="flex items-center gap-2">
                        @if ($invoice->file_url)
                            <a href="{{ $invoice->file_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-secondary"><x-nav-icon name="report" /> Voir</a>
                        @endif
                        @unless ($invoice->is_paid)
                            <form method="POST" action="{{ route('purchase-orders.invoices.paid', [$po, $invoice]) }}"
                                  data-confirm="La facture {{ $invoice->invoice_number }} sera marquée payée à la date du jour." data-confirm-title="Marquer cette facture payée ?" data-confirm-label="Marquer payée">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-primary">Marquer payée</button>
                            </form>
                        @endunless
                    </span>
                </div>
            @empty
                <p class="m-0 px-5 py-4 text-[13px] text-ink-muted border-b border-line-soft">Aucune facture reçue pour cette commande.</p>
            @endforelse

            @if ($status !== 'annulee')
                <form method="POST" action="{{ route('purchase-orders.invoices.store', $po) }}" enctype="multipart/form-data" class="flex flex-col gap-3 px-5 py-4 bg-paper/50">
                    @csrf
                    <div class="text-[13px] font-semibold text-ink-body">Ajouter une facture</div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-ink-body">N° de facture
                            <input type="text" name="invoice_number" value="{{ old('invoice_number') }}" required>
                        </label>
                        <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-ink-body">Date
                            <input type="date" name="invoice_date" value="{{ old('invoice_date', now()->toDateString()) }}" required>
                        </label>
                        <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-ink-body">Montant (FCFA)
                            <input type="number" name="amount" step="1" min="0" value="{{ old('amount', max(0, round($po->total_amount - $invoiced))) }}" required>
                        </label>
                    </div>
                    <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-ink-body"><span>Fichier de la facture <span class="font-normal text-ink-muted">(PDF ou photo, facultatif)</span></span>
                        <input type="file" name="file" class="text-[13px]">
                    </label>
                    <x-input-error :messages="$errors->get('invoice_number')" />
                    <x-input-error :messages="$errors->get('amount')" />
                    <x-input-error :messages="$errors->get('file')" />
                    <div><button type="submit" class="btn btn-secondary">Ajouter la facture</button></div>
                </form>
            @endif
        </x-panel>
    </section>
</x-app-layout>
