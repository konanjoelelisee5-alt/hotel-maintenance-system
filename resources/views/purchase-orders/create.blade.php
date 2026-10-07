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
    // Même parcours en étapes que le signalement (x-wizard) ; après une erreur du serveur,
    // on rouvre l'étape concernée.
    $steps = ['Fournisseur et livraison', 'Articles', 'Vérifier et créer'];
    $startStep = $errors->hasAny(['supplier_id', 'order_date', 'expected_delivery_date', 'work_order_id']) ? 1 : ($errors->any() ? 2 : 1);
@endphp

<x-app-layout :crumb="'Stock & achats / Bons de commande'" :page-title="'Nouveau bon de commande'" :back-route="route('purchase-orders.index')" focus>
    <form method="POST" action="{{ route('purchase-orders.store') }}" class="ui-form grid gap-5 desk:grid-cols-[minmax(0,1fr)_320px] items-start pb-28 desk:pb-0"
          x-data="{
              step: {{ $startStep }},
              btnState: '',
              items: @js($initialItems),
              parts: @js($partsData),
              supplierId: @js((string) old('supplier_id', $selectedSupplierId ?? '')),
              orderDate: @js(old('order_date', now()->format('Y-m-d'))),
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
              get supplierName() { return this.$refs.supplier?.selectedOptions[0]?.value ? this.$refs.supplier.selectedOptions[0].text : null; },
              get itemsOk() { return this.items.every((i) => String(i.description || '').trim() !== '' && i.quantity >= 1 && i.unit_price >= 0 && i.unit_price !== ''); },
              get stepValid() { return [!!this.supplierId && !!this.orderDate, this.itemsOk, this.canSend][this.step - 1]; },
              get canSend() { return !!this.supplierId && !!this.orderDate && this.itemsOk; },
              get hint() { return this.step === 1 ? 'Choisissez le fournisseur et la date de commande.' : 'Chaque article a besoin d\'une description et d\'une quantité.'; },
              next() { if (! this.stepValid || this.step >= 3) return; this.step++; history.pushState({ step: this.step }, '', '#etape-' + this.step); window.scrollTo({ top: 0 }); },
              back() { history.back(); },
              goTo(n) { if (n < this.step) history.go(n - this.step); },
              init() {
                  history.replaceState({ step: this.step }, '', '#etape-' + this.step);
                  window.addEventListener('popstate', (e) => { this.step = Math.min(e.state?.step ?? 1, this.step); });
              },
          }">
        @csrf

        <div class="flex flex-col gap-5 min-w-0 w-full max-w-[760px]">
            <x-wizard.progress :steps="$steps" />

            {{-- ① À qui, quand, pour quoi --}}
            <section x-show="step === 1" @if ($startStep !== 1) x-cloak @endif class="flex flex-col gap-4">
                <div>
                    <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Fournisseur et livraison</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-ink-muted">À qui commander, et pour quel ordre de travail.</p>
                </div>
                <div class="bg-white border border-line rounded-xl p-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="supplier_id" value="Fournisseur" />
                        <select id="supplier_id" name="supplier_id" class="mt-1 block w-full" x-model="supplierId" x-ref="supplier" required>
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
                        <x-text-input id="order_date" name="order_date" type="date" class="mt-1 block w-full" x-model="orderDate" required />
                    </div>
                    <div>
                        <x-input-label for="expected_delivery_date" value="Livraison prévue (facultatif)" />
                        <x-text-input id="expected_delivery_date" name="expected_delivery_date" type="date" class="mt-1 block w-full" :value="old('expected_delivery_date')" />
                        <x-input-error :messages="$errors->get('expected_delivery_date')" class="mt-2" />
                    </div>
                </div>
            </section>

            {{-- ② Les articles : une pièce du stock (la réception l'ajoutera au magasin) ou un article libre --}}
            <section x-show="step === 2" @if ($startStep !== 2) x-cloak @endif class="flex flex-col gap-4">
                <div>
                    <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Articles</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-ink-muted" x-text="items.length + ' ligne(s) · total ' + fcfa(total)"></p>
                </div>
                <div class="bg-white border border-line rounded-xl overflow-hidden">
                    <div class="flex flex-col divide-y divide-line-soft">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="grid gap-3 px-5 py-4 sm:grid-cols-[minmax(0,1fr)_90px_140px] sm:items-end">
                                <div class="sm:col-span-3 flex items-center justify-between gap-3">
                                    <span class="text-[12px] font-semibold uppercase tracking-wide text-ink-muted" x-text="'Article ' + (index + 1)"></span>
                                    <button type="button" class="btn btn-sm btn-ghost text-red" @click="removeItem(index)" x-show="items.length > 1">Retirer</button>
                                </div>
                                <label class="sm:col-span-3 flex flex-col gap-1 text-[12.5px] font-semibold text-ink-body"><span>Pièce du stock <span class="font-normal text-ink-muted">(facultatif : la réception l'ajoutera au magasin)</span></span>
                                    <select :name="`items[${index}][part_id]`" x-model="item.part_id" @change="pickPart(item)">
                                        <option value="">Article hors stock</option>
                                        @foreach ($parts as $part)
                                            <option value="{{ $part->id }}">{{ $part->name }} ({{ $part->sku }}) — {{ $part->quantity_on_hand }} en stock</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-ink-body">Description
                                    <input type="text" :name="`items[${index}][description]`" x-model="item.description" placeholder="Ex. : compresseur de climatiseur 12 000 BTU" required>
                                </label>
                                <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-ink-body">Quantité
                                    <input type="number" min="1" :name="`items[${index}][quantity]`" x-model.number="item.quantity" required>
                                </label>
                                <label class="flex flex-col gap-1 text-[12.5px] font-semibold text-ink-body">Prix unitaire (FCFA)
                                    <input type="number" min="0" step="1" :name="`items[${index}][unit_price]`" x-model.number="item.unit_price" required>
                                </label>
                                <div class="sm:col-span-3 text-right text-[13px] text-ink-muted">Sous-total : <strong class="font-mono text-navy" x-text="fcfa(item.quantity * item.unit_price)"></strong></div>
                            </div>
                        </template>
                    </div>
                    <div class="px-5 py-3.5 border-t border-line-soft bg-paper/50">
                        <button type="button" class="btn btn-sm btn-secondary" @click="addItem()">+ Ajouter un article</button>
                        <x-input-error :messages="$errors->get('items')" class="mt-2" />
                        @foreach ($errors->getMessages() as $key => $messages)
                            @if (str_starts_with($key, 'items.'))
                                <x-input-error :messages="$messages" class="mt-2" />
                            @endif
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ③ Notes et vérification --}}
            <section x-show="step === 3" x-cloak class="flex flex-col gap-4">
                <div>
                    <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Vérifiez avant de créer</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-ink-muted">Le bon est créé en <strong>brouillon</strong> : vous le marquerez « envoyé » après l'avoir transmis au fournisseur.</p>
                </div>
                <div class="desk:hidden">@include('purchase-orders.partials.create-recap')</div>
                <div class="bg-white border border-line rounded-xl p-5">
                    <label for="notes">Notes (facultatif)</label>
                    <textarea id="notes" name="notes" rows="3" class="block w-full" placeholder="Conditions, contact chez le fournisseur, urgence…">{{ old('notes') }}</textarea>
                </div>
            </section>

            <x-wizard.actions :last="3" submit-label="Créer le bon de commande" submit-icon="check" submit-class="btn-primary" />
        </div>

        {{-- Ordinateur : récapitulatif fixé à droite pendant tout le parcours --}}
        <aside class="hidden desk:block sticky top-[88px]">@include('purchase-orders.partials.create-recap')</aside>
    </form>
</x-app-layout>
