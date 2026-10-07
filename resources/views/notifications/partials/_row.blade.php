@props(['notification', 'color', 'unread'])

@php
    $hex = match ($color) {
        'red' => '#B3261E', 'gold' => '#B58435', 'amber' => '#B4740F', 'green' => '#1E7A55', default => '#26496B',
    };
@endphp

<form method="POST" action="{{ route('notifications.read', $notification->id) }}">
    @csrf
    <button type="submit" class="w-full min-h-[58px] flex items-start gap-3 text-left px-4 py-3 rounded-xl border {{ $unread ? 'bg-white border-line' : 'bg-paper border-line-soft' }}">
        <span class="w-2 h-2 mt-[6px] rounded-full flex-shrink-0" style="background-color: {{ $hex }}" aria-hidden="true"></span>
        <span class="flex-1 min-w-0">
            <span class="block text-[13.5px] leading-snug {{ $unread ? 'font-semibold text-navy' : 'text-ink-muted' }}">
                {{ $notification->data['message'] ?? ($notification->data['title'] ?? 'Notification') }}
            </span>
        </span>
        <span class="flex flex-col items-end gap-1.5 flex-shrink-0">
            <span class="font-mono text-[11px] text-ink-grey whitespace-nowrap">{{ $notification->created_at->locale('fr')->diffForHumans() }}</span>
            @if ($unread)
                <span class="w-1.5 h-1.5 rounded-full bg-gold"></span>
            @endif
        </span>
    </button>
</form>
