{{-- Notifications du Housekeeping : non lues puis lues ; un clic marque comme lu et
     ouvre l'OT lié (NotificationController::markAsRead). --}}
@php
    $unread = $notifications->filter(fn ($n) => is_null($n->read_at));
    $read = $notifications->filter(fn ($n) => ! is_null($n->read_at));
    $icon = fn ($n) => match (true) {
        str_contains($n->type, 'WorkOrderProgress') => 'wrench',
        str_contains($n->type, 'SlaEscalation') => 'alert-triangle',
        str_contains($n->type, 'RoomBlock') => 'door',
        default => 'bell',
    };
@endphp

<x-app-layout crumb="Mon activité" page-title="Notifications">
    @if ($unread->isNotEmpty())
        <x-slot:primaryAction>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-secondary"><x-hk.icon name="check-check" :size="16" /> Tout marquer comme lu</button>
            </form>
        </x-slot:primaryAction>
    @endif

    <div class="w-full max-w-[760px] flex flex-col gap-5">
        @if ($notifications->isEmpty())
            <div class="bg-white border border-line rounded-xl px-5 py-14 flex flex-col items-center gap-2 text-center">
                <span class="w-[42px] h-[42px] rounded-full bg-line-soft text-ink-grey flex items-center justify-center"><x-hk.icon name="bell-off" :size="18" /></span>
                <span class="text-[14px] font-semibold">Aucune notification</span>
                <span class="text-[12.5px] text-ink-grey">Vous serez prévenu ici quand un technicien prend en charge ou répare vos signalements.</span>
            </div>
        @endif

        @foreach (['Non lues' => $unread, 'Plus anciennes' => $read] as $title => $group)
            @continue($group->isEmpty())
            <section class="bg-white border border-line rounded-xl overflow-hidden">
                <header class="px-5 py-3 border-b border-line-soft bg-paper/60 text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">
                    {{ $title }} · <span class="font-mono">{{ $group->count() }}</span>
                </header>
                @foreach ($group as $n)
                    @php $isUnread = is_null($n->read_at); @endphp
                    <form method="POST" action="{{ route('notifications.read', $n->id) }}" class="border-b border-line-soft last:border-b-0">
                        @csrf
                        <button type="submit" class="w-full min-h-[60px] flex items-start gap-3 text-left px-5 py-3.5 hover:bg-paper">
                            <span class="w-8 h-8 rounded-[8px] border flex items-center justify-center flex-shrink-0 {{ $isUnread ? 'bg-paper border-line text-gold' : 'bg-white border-line-soft text-ink-grey' }}">
                                <x-hk.icon :name="$icon($n)" :size="16" />
                            </span>
                            <span class="flex-1 min-w-0 flex flex-col gap-0.5">
                                <span class="text-[13.5px] leading-snug {{ $isUnread ? 'font-semibold text-navy' : 'text-[#6C6658]' }}">{{ $n->data['message'] ?? ($n->data['title'] ?? 'Notification') }}</span>
                                <span class="font-mono text-[11.5px] text-ink-grey">{{ $n->created_at->locale('fr')->diffForHumans() }}</span>
                            </span>
                            @if ($isUnread)
                                <span class="w-2 h-2 rounded-full bg-gold mt-2 flex-shrink-0" aria-label="Non lue"></span>
                            @endif
                            <x-hk.icon name="chevron-right" :size="16" class="text-[#C9C3B6] mt-1.5" />
                        </button>
                    </form>
                @endforeach
            </section>
        @endforeach

        @if ($notifications->hasPages())
            <div>{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>
