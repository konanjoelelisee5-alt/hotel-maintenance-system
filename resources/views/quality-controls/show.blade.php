<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Contrôle qualité') }} — OT #{{ $qualityControl->workOrder->id }}
            </h2>
            <x-quality-control-status-badge :status="$qualityControl->status" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">

                <p class="text-sm text-gray-600 mb-6">
                    <span class="font-medium">OT :</span> {{ $qualityControl->workOrder->title }}
                </p>

                <form method="POST" action="{{ route('quality-controls.review', $qualityControl) }}" class="space-y-6">
                    @csrf

                    @if ($qualityControl->items->isNotEmpty())
                        <div>
                            <x-input-label value="Points de vérification" />

                            <div class="mt-2 space-y-4">
                                @foreach ($qualityControl->items as $item)
                                    <!--
                                        Point important : le nom du champ utilise directement l'ID RÉEL
                                        de la ligne en base ($item->id), et non un index séquentiel.
                                        C'est ce qui permet au contrôleur de savoir exactement quelle
                                        ligne existante mettre à jour (voir Étape 5).
                                    -->
                                    <div class="border border-gray-200 rounded-md p-4">
                                        <p class="text-sm font-medium text-gray-800 mb-2">{{ $item->label }}</p>

                                        <div class="flex gap-4 mb-2">
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="radio" name="items[{{ $item->id }}][is_compliant]" value="1"
                                                    @checked($item->is_compliant === true)
                                                    class="text-green-600">
                                                Conforme
                                            </label>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="radio" name="items[{{ $item->id }}][is_compliant]" value="0"
                                                    @checked($item->is_compliant === false)
                                                    class="text-red-600">
                                                Non conforme
                                            </label>
                                        </div>

                                        <input type="text" name="items[{{ $item->id }}][comment]" placeholder="Commentaire (optionnel)"
                                            value="{{ $item->comment }}"
                                            class="block w-full text-sm border-gray-300 rounded-md shadow-sm">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <x-input-label for="overall_comment" value="Commentaire général" />
                        <textarea id="overall_comment" name="overall_comment" rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                            placeholder="Obligatoire en cas de rejet">{{ old('overall_comment', $qualityControl->overall_comment) }}</textarea>
                        <x-input-error :messages="$errors->get('overall_comment')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="submit" name="decision" value="rejete"
                            class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                            ✕ Rejeter
                        </button>
                        <button type="submit" name="decision" value="approuve"
                            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                            ✓ Approuver
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('decision')" class="mt-2" />

                </form>
            </div>
        </div>
    </div>
</x-app-layout>