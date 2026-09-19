<x-accordion-card title="Photos / Documents">
    <div class="space-y-4">
        @can('intervene', $workOrder)
            <form method="POST" action="{{ route('work-orders.attachments.store', $workOrder) }}"
                  enctype="multipart/form-data" class="space-y-2">
                @csrf
                <input type="file" name="files[]" multiple
                       class="block w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                <button type="submit" class="px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-medium rounded-md hover:bg-slate-200">
                    Envoyer
                </button>
                <x-input-error :messages="$errors->get('files')" />
            </form>
        @endcan

        @if ($workOrder->attachments->isEmpty())
            <p class="text-sm text-ink-grey">Aucune pièce jointe.</p>
        @else
            <div class="grid grid-cols-2 gap-3">
                @foreach ($workOrder->attachments as $attachment)
                    <div class="border border-line rounded-md p-2 text-center">
                        @if (str_starts_with($attachment->mime_type, 'image/'))
                            <a href="{{ $attachment->url }}" target="_blank">
                                <img src="{{ $attachment->url }}" class="h-20 w-full object-cover rounded mb-1.5">
                            </a>
                        @else
                            <a href="{{ $attachment->url }}" target="_blank"
                               class="h-20 flex items-center justify-center bg-paper rounded mb-1.5 text-2xl">
                                📄
                            </a>
                        @endif
                        <p class="text-xs text-slate-600 truncate" title="{{ $attachment->original_name }}">
                            {{ $attachment->original_name }}
                        </p>

                        @can('intervene', $workOrder)
                            <form method="POST" action="{{ route('work-orders.attachments.destroy', [$workOrder, $attachment]) }}"
                                  onsubmit="return confirm('Supprimer ce fichier ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:underline mt-1">Supprimer</button>
                            </form>
                        @endcan
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-accordion-card>
