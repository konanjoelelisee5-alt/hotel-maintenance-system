<x-panel title="Photos et documents" icon="camera">
    <x-slot:badge>
        @if ($workOrder->attachments->isNotEmpty())
            <span class="px-2 py-0.5 rounded-full bg-line-soft text-[11.5px] font-semibold text-ink-body">{{ $workOrder->attachments->count() }}</span>
        @endif
    </x-slot:badge>

    <div class="flex flex-col gap-4">
        @if ($workOrder->attachments->isEmpty())
            <p class="m-0 text-[13px] text-ink-grey">Aucune pièce jointe.</p>
        @else
            <div class="grid grid-cols-2 gap-2.5">
                @foreach ($workOrder->attachments as $attachment)
                    @php $isAudio = str_starts_with($attachment->mime_type, 'audio/'); @endphp
                    <div class="group relative flex flex-col rounded-[10px] border border-line overflow-hidden bg-paper {{ $isAudio ? 'col-span-2' : '' }}">
                        @if ($isAudio)
                            {{-- Message vocal du signalement rapide : sur toute la largeur pour un lecteur utilisable. --}}
                            <div class="flex items-center gap-2 p-2.5">
                                <x-nav-icon name="mic" class="w-5 h-5 text-gold flex-shrink-0" />
                                <audio controls preload="metadata" src="{{ $attachment->url }}" class="w-full h-9"></audio>
                            </div>
                        @elseif (str_starts_with($attachment->mime_type, 'image/'))
                            <a href="{{ $attachment->url }}" target="_blank" class="block aspect-[4/3] bg-line-soft">
                                <img src="{{ $attachment->url }}" alt="{{ $attachment->original_name }}" class="w-full h-full object-cover">
                            </a>
                        @else
                            <a href="{{ $attachment->url }}" target="_blank" class="flex flex-col items-center justify-center gap-1 aspect-[4/3] text-ink-grey hover:text-navy">
                                <x-nav-icon name="report" class="w-7 h-7" />
                                <span class="text-[11px] font-semibold uppercase">{{ pathinfo($attachment->original_name, PATHINFO_EXTENSION) }}</span>
                            </a>
                        @endif
                        <div class="flex items-center gap-1 px-2.5 py-1.5 bg-white border-t border-line-soft">
                            <span class="flex-1 min-w-0 text-[11.5px] text-ink-body truncate" title="{{ $attachment->original_name }}">{{ $attachment->original_name }}</span>
                            @can('deleteAttachment', [$workOrder, $attachment])
                                <form method="POST" action="{{ route('work-orders.attachments.destroy', [$workOrder, $attachment]) }}"
                                      data-confirm="Le retrait sera noté au journal d’activité." data-confirm-title="Retirer ce fichier de la fiche ?" data-confirm-label="Retirer" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-6 h-6 rounded-md flex items-center justify-center text-ink-grey hover:text-red hover:bg-danger-soft" title="Retirer">
                                        <x-nav-icon name="trash" class="w-3.5 h-3.5" />
                                        <span class="sr-only">Retirer {{ $attachment->original_name }}</span>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @can('intervene', $workOrder)
            {{-- Zone d'ajout : le champ fichier est caché derrière une zone cliquable. --}}
            <form method="POST" action="{{ route('work-orders.attachments.store', $workOrder) }}" enctype="multipart/form-data"
                  x-data="{ count: 0 }" class="flex flex-col gap-2">
                @csrf
                <label class="!flex !normal-case !tracking-normal flex-col items-center justify-center gap-1 !mb-0 px-4 py-4 rounded-[10px] border-2 border-dashed border-line bg-paper/60 text-center cursor-pointer hover:border-navy/40 hover:bg-paper">
                    <x-nav-icon name="upload" class="w-5 h-5 text-gold" />
                    <span class="text-[13px] font-semibold text-navy" x-text="count ? count + ' fichier(s) choisi(s)' : 'Ajouter des photos ou documents'">Ajouter des photos ou documents</span>
                    <span class="text-[11.5px] font-normal text-ink-grey">JPG, PNG, PDF, Word · 10 Mo max.</span>
                    <input type="file" name="files[]" multiple class="sr-only" @change="count = $event.target.files.length">
                </label>
                <button type="submit" class="btn btn-primary" x-show="count > 0" x-cloak><x-nav-icon name="upload" /> Envoyer</button>
                <x-input-error :messages="$errors->get('files')" />
            </form>
        @endcan
    </div>
</x-panel>
