@props(['dark' => false])

@php
    // Pas de modèle de "niveau" de notification chez nous : une couleur de repli
    // simple dérivée du type, juste pour distinguer visuellement le point.
    $dotClass = fn ($n) => match (true) {
        str_contains($n->type, 'SlaEscalation') => 'bg-red',
        str_contains($n->type, 'LowStock') => 'bg-gold',
        str_contains($n->type, 'QualityControl') => 'bg-amber',
        default => 'bg-blue',
    };
@endphp

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button @click="open = !open" type="button"
        class="relative w-[38px] h-[38px] rounded-[9px] border {{ $dark ? 'border-white/20 text-white' : 'border-line bg-white text-navy' }} flex items-center justify-center">
        <x-nav-icon name="bell" class="w-[18px] h-[18px]" />
        @if ($unreadCount > 0)
            <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-gold text-white text-[10.5px] font-semibold flex items-center justify-center">{{ $unreadCount }}</span>
        @endif
    </button>

    <div x-show="open" @click.outside="open = false" x-transition
        class="absolute right-0 mt-2 w-[320px] max-w-[90vw] bg-white border border-line rounded-xl shadow-lg z-50 p-3 flex flex-col gap-2.5 text-[#14202B]"
        style="display: none;">
        <div class="flex items-center justify-between px-1">
            <span class="text-[13.5px] font-semibold">Notifications</span>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-[11.5px] font-semibold text-blue">Tout marquer comme lu</button>
                </form>
            @endif
        </div>

        @forelse ($notifications as $notification)
            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                @csrf
                <button type="submit" class="w-full flex gap-2.5 px-2.5 py-2.5 border border-line-soft rounded-[10px] hover:bg-paper text-left {{ $notification->read_at ? 'opacity-60' : '' }}">
                    <span class="w-2 h-2 rounded-full {{ $dotClass($notification) }} mt-1.5 flex-shrink-0"></span>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[13px] font-medium leading-snug">{{ $notification->data['message'] ?? ($notification->data['title'] ?? 'Notification') }}</span>
                        <span class="text-[11px] text-ink-grey">{{ $notification->created_at->locale('fr')->diffForHumans() }}</span>
                    </div>
                </button>
            </form>
        @empty
            <div class="px-2.5 py-6 text-center text-[12.5px] text-ink-grey">Aucune notification.</div>
        @endforelse

        <a href="{{ route('notifications.index') }}" class="text-center text-[12px] font-semibold text-blue pt-1">Voir tout l'historique</a>
    </div>
</div>
