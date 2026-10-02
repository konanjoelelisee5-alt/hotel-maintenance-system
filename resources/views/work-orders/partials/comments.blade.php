{{-- Fil de discussion de l'OT : messages façon messagerie, saisie en bas. --}}
<x-panel id="comments" title="Commentaires" icon="comment" flush>
    <x-slot:badge>
        @if ($workOrder->comments->isNotEmpty())
            <span class="px-2 py-0.5 rounded-full bg-line-soft text-[11.5px] font-semibold text-[#4A4639]">{{ $workOrder->comments->count() }}</span>
        @endif
    </x-slot:badge>

    <div class="flex flex-col gap-4 px-5 py-4">
        @forelse ($workOrder->comments as $comment)
            @php $mine = $comment->user_id === auth()->id(); @endphp
            <div class="flex gap-3 {{ $mine ? 'flex-row-reverse' : '' }}">
                <span class="w-8 h-8 flex-shrink-0 rounded-full {{ $mine ? 'bg-navy text-white' : 'bg-gold text-navy' }} flex items-center justify-center text-[11px] font-bold">
                    {{ $comment->user->initialsOrGenerated() }}
                </span>
                <div class="max-w-[85%] flex flex-col {{ $mine ? 'items-end' : 'items-start' }}">
                    <div class="flex items-baseline gap-2 mb-1 text-[12px]">
                        <span class="font-semibold text-navy">{{ $mine ? 'Vous' : $comment->user->name }}</span>
                        <span class="text-ink-grey" title="{{ $comment->created_at->format('d/m/Y H:i') }}">{{ $comment->created_at->locale('fr')->diffForHumans() }}</span>
                    </div>
                    <p class="m-0 px-3.5 py-2.5 text-[13.5px] leading-relaxed whitespace-pre-line
                        {{ $mine ? 'bg-[#EAF0F6] rounded-[14px] rounded-tr-[4px] text-navy' : 'bg-paper border border-line-soft rounded-[14px] rounded-tl-[4px] text-[#3d3a33]' }}">{{ $comment->content }}</p>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center gap-1.5 py-4 text-center">
                <x-nav-icon name="comment" class="w-6 h-6 text-line" />
                <p class="m-0 text-[13px] text-ink-grey">Aucun commentaire pour le moment.</p>
            </div>
        @endforelse
    </div>

    @can('intervene', $workOrder)
        <form method="POST" action="{{ route('work-orders.comments.store', $workOrder) }}" class="flex flex-col gap-2 px-5 py-4 border-t border-line-soft bg-paper/50">
            @csrf
            <textarea name="content" rows="2" aria-label="Nouveau commentaire" placeholder="Écrire un commentaire : avancement, consigne, information pour l'équipe…" required></textarea>
            <x-input-error :messages="$errors->get('content')" />
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary"><x-nav-icon name="send" /> Publier</button>
            </div>
        </form>
    @endcan
</x-panel>
