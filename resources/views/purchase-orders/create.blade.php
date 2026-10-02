@php
    // Lignes de départ : celles saisies avant une erreur, sinon une pièce demandée depuis sa
    // fiche (?part_id=), sinon une ligne vide.
    $prefillPart = $parts->firstWhere('id', (int) request('part_id'));
    $initialItems = old('items') ? array_values(old('items')) : [[
        'part_id' => $prefillPart?->id ?? '',
        'description' => $prefillPart?->name ?? '',
        'quantity' => $prefillPart ? max(1, $prefillPart->reorder_threshold * 2 - $prefillPart->quantity_on_hand) : 1,
        'unit_price' => $prefillPart ? (int) round($prefillPart->unit_cost) : 0,
    ]];
    $partsData = $parts->mapWithKeys(fn ($p) => [$p->id => ['name' => $p->name, 'sku' => $p->sku, 'unit' => $p->unit, 'cost' => (int) round($p->unit_cost)]]);
@endphp

<x-app-layout :crumb="'Stock & achats / Bons de commande'" :page-title="'Nouveau bon de commande'" :back-route="route('purchase-orders.index')">
    <form method="POST" action="{{ route('purchase-orders.store') }}" class="ui-form grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px] items-start"
          x-data="{
              items: @js($initialItems),
              parts: @js($partsData),
              addItem() { this.items.push({ part_id: '', description: '', quantity: 1, unit_price: 0 }); },
              removeItem(index) { if (this.items.length > 1) this.items.splice(index, 1); },
              // Choisir une pièce du stock propose son nom et son coût unitaire.
              pickPart(item) {
                  const part = this.parts[item.part_id];
                  if (! part) return;
                  item.description = part.name;
                  if (! item.unit_price) item.unit_price = part.cost;
              },
              get total() { return this.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0); },
              // Franc CFA : sans centimes, espace entre les milliers (cf. App\Support\Money).
              fcfa(amount) { return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Math.round(amount || 0)) + ' FCFA'; },
          }">
        @csrf

        <div class="flex flex-col gap-5 min-w-0">
            {{-- 1. À qui, quand, pour quoi --}}
            <x-panel title="Fournisseur et livraison" icon="truck">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="supplier_id" value="Fournisseur" />
                        <select id="supplier_id" name="supplier_id" class="mt-1 block w-full" required>
                            <option value="">Choisir un fournisseur…</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $selectedSupplierId) == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                        @if ($suppliers->isEmpty())
                            <p class="mt-1.5 text-[12.5px] text-amber">Aucun fournisseur actif : <a href="{{ route('suppliers.create') }}" class="underline">ajoutez-en un</a>.</p>
                        @endif
                    </div>
                    <div>
                        <x-input-label for="work_order_id" value="Pour un ordre de travail (facultatif)" />
                        <select id="work_order_id" name="work_order_id" class="mt-1 block w-full">
                            <option value="">Réapprovisionnement du stock</option>
                            @foreach ($workOrders as $workOrder)
                                <option value="{{ $workOrder->id }}" @selected(old('work_order_id', $selectedWorkOrderId) == $workOrder->id)>{{ $workOrder->code() }} · {{ $workOrder->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="order_date" value="Date de commande" />
                        <x-text-input id="order_date" name="order_date" type="date" class="mt-1 block w-full" :value="old('order_date', now()->format('Y-m-d'))" required />
                    </div>
                    <div>
                        <x-input-label for="expected_delivery_date" value="Livraison prévue (facultatif)" />
                        <x-text-input id="expected_delivery_date" name="expected_delivery_date" type="date" class="mt-1 block w-full" :value="old('expected_delivery_date')" />
                        <x-input-error :messages="$errors->get('expected_delivery_date')" class="mt-2" />
                    </div>
                </div>
            </x-panel>

            {{-- 2. Les articles : une pièce du stock (la réception l'ajoutera au magasin) ou un article libre --}}
            <x-panel title="Articles" icon="part" flush>
                <x-slot:badge><span class="text-[12px] text-[#6C6658]" x-text="items.length + ' ligne(s)'"></span></x-slot:badge>
                <div class="flex flex-col divide-y divide-line-soft">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid gap-3 px-5 py-4 sm:grid-cols-[minmax(0,1fr)_90px_140px] sm:items-end">
                            <div class="sm:col-span-3 flex items-center justify-between gap-3">
                                <span class="text-[12px] font-semibold uppercase tracking-wide text-[#6C6658]" x-text="'Article ' + (index + 1)"></span>
                                <button type="button" class="btn btn-sm btn-ghost text-red" @click="removeItem(index)" x-show="items.length > 1">Retirer</button>
                            </div>
                            <label class="sm:col-span-3 flex flex-col gap-1 text-[12.5px] font-semibold text-[#4A4639]"><span>Pièce du stock <span class="font-normal text-[#6C6658]">(facultatif : la réception l'ajoutera au magasin)</span></span>
                                <select :name="`items[${index}][part_id]`" x-model="item.part_id" @change="pickPart(item)">
                                    <option value="">Article hors stock</option>
                                    @foreach ($parts as $part)
                                        <option value="{{ $part->id }}">{{ $part->name }} ({{ $part->sku }}) — {{ $part->quantity_on_hand }} en stock</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-[#4A4639]">Description
                                <input type="text" :name="`items[${index}][description]`" x-model="item.description" placeholder="Ex. : compresseur de climatiseur 12 000 BTU" required>
                            </label>
                            <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-[#4A4639]">Quantité
                                <input type="number" min="1" :name="`items[${index}][quantity]`" x-model.number="item.quantity" required>
                            </label>
                            <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-[#4A4639]">Prix unitaire (FCFA)
                                <input type="number" min="0" step="1" :name="`items[${index}][unit_price]`" x-model.number="item.unit_price" required>
                            </label>
                            <div class="sm:col-span-3 text-right text-[13px] text-[#6C6658]">Sous-total : <strong class="font-mono text-navy" x-text="fcfa(item.quantity * item.unit_price)"></strong></div>
                        </div>
                    </template>
                </div>
                <div class="px-5 py-3.5 border-t border-line-soft bg-paper/50">
                    <button type="button" class="btn btn-sm btn-secondary" @click="addItem()">+ Ajouter un article</button>
                    <x-input-error :messages="$errors->get('items')" class="mt-2" />
                </div>
            </x-panel>

            <x-panel title="Notes" icon="comment">
                <textarea id="notes" name="notes" rows="3" class="block w-full" placeholder="Conditions, contact chez le fournisseur, urgence…">{{ old('notes') }}</textarea>
            </x-panel>
        </div>

        {{-- Récapitulatif : reste visible à côté du formulaire sur ordinateur --}}
        <aside class="lg:sticky lg:top-6 flex flex-col gap-4 p-5 rounded-xl bg-white border border-line">
            <div class="text-[12px] font-semibold uppercase tracking-wide text-[#6C6658]">Récapitulatif</div>
            <div>
                <div class="text-[13px] text-[#6C6658]">Total de la commande</div>
                <div class="mt-0.5 font-mono text-[26px] font-semibold text-navy" x-text="fcfa(total)"></div>
                <div class="text-[12.5px] text-[#6C6658]" x-text="items.length + ' article(s), ' + items.filter(i => i.part_id).length + ' relié(s) au stock'"></div>
            </div>
            <p class="m-0 text-[12.5px] text-[#6C6658] leading-relaxed">Le bon est créé en <strong>brouillon</strong> : vous le marquerez « envoyé » après l'avoir transmis au fournisseur.</p>
            <button type="submit" class="btn btn-primary w-full">Créer le bon de commande</button>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-ghost w-full">Annuler</a>
        </aside>
    </form>
</x-app-layout>
