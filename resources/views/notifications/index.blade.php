@php
    $dotColor = fn ($n) => match (true) {
        str_contains($n->type, 'SlaEscalation') => 'red',
        str_contains($n->type, 'LowStock') => 'gold',
        str_contains($n->type, 'QualityControl') => 'amber',
        default => 'blue',
    };
    $unread = $notifications->filter(fn ($n) => is_null($n->read_at));
    $read = $notifications->filter(fn ($n) => ! is_null($n->read_at));
@endphp

<x-app-layout crumb="Mon activité" page-title="Notifications">
    @if ($unread->isNotEmpty())
        <x-slot:primaryAction>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="px-[15px] py-[9px] border border-line rounded-[9px] bg-white text-navy text-[13px] font-semibold">
                    Tout marquer lu
                </button>
            </form>
        </x-slot:primaryAction>
    @endif

    @if ($notifications->isEmpty())
        <div class="bg-white border border-line rounded-xl px-5 py-14 flex flex-col items-center gap-2 text-center">
            <div class="w-[42px] h-[42px] rounded-full bg-line-soft flex items-center justify-center text-[#A09A8C]"><x-nav-icon name="bell" class="w-5 h-5" /></div>
            <div class="text-[14px] font-semibold">Aucune notification</div>
            <div class="text-[12.5px] text-ink-grey">Tu seras prévenu ici des évènements qui te concernent.</div>
        </div>
    @else
        @if ($unread->isNotEmpty())
            <div>
                <div class="text-[11px] font-semibold text-[#7D7768] uppercase tracking-wide mb-2">Non lues · {{ $unread->count() }}</div>
                <div class="flex flex-col gap-2">
                    @foreach ($unread as $notification)
                        @include('notifications.partials._row', ['notification' => $notification, 'color' => $dotColor($notification), 'unread' => true])
                    @endforeach
                </div>
            </div>
        @endif

        @if ($read->isNotEmpty())
            <div>
                <div class="text-[11px] font-semibold text-[#7D7768] uppercase tracking-wide mb-2 {{ $unread->isNotEmpty() ? 'mt-2' : '' }}">Plus anciennes</div>
                <div class="flex flex-col gap-2">
                    @foreach ($read as $notification)
                        @include('notifications.partials._row', ['notification' => $notification, 'color' => $dotColor($notification), 'unread' => false])
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    <div class="mt-1">
        {{ $notifications->links() }}
    </div>
</x-app-layout>
