<x-app-layout :crumb="'Ordres de travail / '.$qualityControl->workOrder->code()" :page-title="'Contrôle qualité'" :back-route="route('work-orders.show', $qualityControl->workOrder)">
    <x-slot:primaryAction>
        <x-quality-control-status-badge :status="$qualityControl->status" />
    </x-slot:primaryAction>

    <div>
        <div class="w-full max-w-3xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">

                <p class="text-sm text-ink-muted mb-6">
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
                                    <div class="border border-line rounded-md p-4">
                                        <p class="text-sm font-medium text-ink-deep mb-2">{{ $item->label }}</p>

                                        <div class="flex gap-4 mb-2">
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="radio" name="items[{{ $item->id }}][is_compliant]" value="1"
                                                    @checked($item->is_compliant === true)
                                                    class="text-green">
                                                Conforme
                                            </label>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="radio" name="items[{{ $item->id }}][is_compliant]" value="0"
                                                    @checked($item->is_compliant === false)
                                                    class="text-red">
                                                Non conforme
                                            </label>
                                        </div>

                                        <input type="text" name="items[{{ $item->id }}][comment]" placeholder="Commentaire (optionnel)"
                                            value="{{ $item->comment }}"
                                            class="block w-full text-sm border-line rounded-md shadow-sm">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <x-input-label for="overall_comment" value="Commentaire général" />
                        <textarea id="overall_comment" name="overall_comment" rows="3"
                            class="mt-1 block w-full border-line rounded-md shadow-sm"
                            placeholder="Obligatoire en cas de rejet">{{ old('overall_comment', $qualityControl->overall_comment) }}</textarea>
                        <x-input-error :messages="$errors->get('overall_comment')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="submit" name="decision" value="rejete"
                            class="btn btn-danger-solid">
                            <x-nav-icon name="x" class="w-4 h-4" /> Rejeter
                        </button>
                        <button type="submit" name="decision" value="approuve"
                            class="btn btn-success">
                            <x-nav-icon name="check" class="w-4 h-4" /> Approuver
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('decision')" class="mt-2" />

                </form>
            </div>
        </div>
    </div>
</x-app-layout>