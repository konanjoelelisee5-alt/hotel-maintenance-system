<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Nouveau bon de commande') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">

                <!--
                    x-data initialise l'état réactif du formulaire :
                    - "items" est un tableau qui contient un objet par ligne d'article
                    - on démarre avec UNE ligne vide par défaut, pour ne pas montrer un formulaire nu
                -->
                <form method="POST" action="{{ route('purchase-orders.store') }}"
                      x-data="{
                          items: [
                              { description: '', quantity: 1, unit_price: 0 }
                          ],
                          addItem() {
                              this.items.push({ description: '', quantity: 1, unit_price: 0 });
                          },
                          removeItem(index) {
                              if (this.items.length > 1) {
                                  this.items.splice(index, 1);
                              }
                          },
                          get total() {
                              return this.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
                          }
                      }"
                      class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="supplier_id" value="Fournisseur" />
                            <select id="supplier_id" name="supplier_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">-- Sélectionner --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="work_order_id" value="Lié à un OT (optionnel)" />
                            <select id="work_order_id" name="work_order_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Aucun --</option>
                                @foreach ($workOrders as $workOrder)
                                    <option value="{{ $workOrder->id }}" @selected(old('work_order_id', $selectedWorkOrderId) == $workOrder->id)>
                                        #{{ $workOrder->id }} — {{ $workOrder->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="order_date" value="Date de commande" />
                            <x-text-input id="order_date" name="order_date" type="date" class="mt-1 block w-full" :value="old('order_date', now()->format('Y-m-d'))" required />
                        </div>
                        <div>
                            <x-input-label for="expected_delivery_date" value="Livraison prévue" />
                            <x-text-input id="expected_delivery_date" name="expected_delivery_date" type="date" class="mt-1 block w-full" :value="old('expected_delivery_date')" />
                        </div>
                    </div>

                    <!-- ===== Section des lignes d'articles (dynamique) ===== -->
                    <div>
                        <x-input-label value="Articles" />

                        <div class="mt-2 space-y-3">
                            <!--
                                x-for répète ce bloc pour chaque élément du tableau "items".
                                ":key" est obligatoire avec x-for, Alpine l'utilise pour suivre
                                quel bloc HTML correspond à quel élément du tableau.
                            -->
                            <template x-for="(item, index) in items" :key="index">
                                <div class="flex gap-3 items-start p-3 border border-gray-200 rounded-md">

                                    <div class="flex-1">
                                        <!--
                                            :name permet de générer dynamiquement le nom du champ,
                                            ex: items[0][description], items[1][description], etc.
                                            x-model relie le champ à la variable JS "item.description"
                                            en temps réel (à double sens).
                                        -->
                                        <input type="text" placeholder="Description de l'article"
                                               :name="`items[${index}][description]`"
                                               x-model="item.description"
                                               class="block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    </div>

                                    <div class="w-24">
                                        <input type="number" placeholder="Qté" min="1"
                                               :name="`items[${index}][quantity]`"
                                               x-model.number="item.quantity"
                                               class="block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    </div>

                                    <div class="w-32">
                                        <input type="number" placeholder="Prix unitaire" step="0.01" min="0"
                                               :name="`items[${index}][unit_price]`"
                                               x-model.number="item.unit_price"
                                               class="block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    </div>

                                    <div class="w-24 text-sm text-gray-600 pt-2 text-right" x-text="(item.quantity * item.unit_price).toFixed(2) + ' €'"></div>

                                    <button type="button" @click="removeItem(index)"
                                            class="text-red-500 hover:text-red-700 text-sm pt-2" x-show="items.length > 1">
                                        ✕
                                    </button>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="addItem()" class="mt-3 text-sm text-indigo-600 hover:underline">
                            + Ajouter un article
                        </button>

                        <x-input-error :messages="$errors->get('items')" class="mt-2" />

                        <!-- Total calculé en temps réel, uniquement visuel (pas envoyé au serveur) -->
                        <div class="mt-4 text-right text-lg font-semibold text-gray-800">
                            Total : <span x-text="total.toFixed(2) + ' €'"></span>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="notes" value="Notes" />
                        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('purchase-orders.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                            Créer le bon de commande
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>