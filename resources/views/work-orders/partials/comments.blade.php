<div id="comments" class="bg-white rounded-xl border border-line">
    <div class="px-5 py-4 border-b border-line-soft">
        <h3 class="font-semibold text-navy-900 text-sm">
            Commentaires
            @if ($workOrder->comments->isNotEmpty())
                <span class="text-ink-grey font-normal">({{ $workOrder->comments->count() }})</span>
            @endif
        </h3>
    </div>

    <div class="p-5">
        @can('intervene', $workOrder)
            <form method="POST" action="{{ route('work-orders.comments.store', $workOrder) }}" class="mb-5 space-y-2">
                @csrf
                <textarea name="content" rows="3" placeholder="Ajouter un commentaire..."
                    class="block w-full border-slate-300 rounded-md shadow-sm text-sm"></textarea>
                <x-input-error :messages="$errors->get('content')" class="mt-2" />
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-navy-800 text-white text-sm font-medium rounded-md hover:bg-navy-900">
                        Publier
                    </button>
                </div>
            </form>
        @endcan

        <div class="space-y-4">
            @forelse ($workOrder->comments as $comment)
                <div class="flex gap-3 {{ ! $loop->first ? 'border-t border-line-soft pt-4' : '' }}">
                    <span class="flex items-center justify-center h-8 w-8 shrink-0 rounded-full bg-navy-100 text-navy-700 text-xs font-semibold">
                        {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                    </span>
                    <div class="flex-1">
                        <div class="flex justify-between items-baseline">
                            <span class="font-medium text-sm text-slate-800">{{ $comment->user->name }}</span>
                            <span class="text-xs text-ink-grey">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="text-sm text-slate-700 mt-1">{{ $comment->content }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-ink-grey">Aucun commentaire pour le moment.</p>
            @endforelse
        </div>
    </div>
</div>
