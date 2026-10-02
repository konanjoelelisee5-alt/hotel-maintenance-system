{{-- Carte latérale « liste à pastilles » ($panel : title, items {label, color,
     who?, meta?, time?}) ; $link optionnel [libellé, url] dans l'en-tête.
     Ex. Activité administrative (admin), Achats, stock et préventif à suivre (manager). --}}
<section class="bg-white border border-line rounded-xl px-6 py-5">
    <div class="flex items-center justify-between gap-3 pb-3.5 border-b border-line-soft">
        <h2 class="text-[17px] font-semibold text-navy">{{ $panel['title'] }}</h2>
        @isset($link)
            <a href="{{ $link[1] }}" class="text-[13px] font-semibold text-navy whitespace-nowrap hover:underline">{{ $link[0] }}</a>
        @endisset
    </div>
    <div class="flex flex-col">
        @forelse ($panel['items'] as $item)
            <div class="flex items-start gap-3 py-3 border-b border-line-soft last:border-b-0 last:pb-0">
                <span class="w-[7px] h-[7px] rounded-full {{ \App\Support\Swatch::bg($item['color']) }} mt-[7px] flex-shrink-0"></span>
                <span class="flex flex-col gap-0.5 min-w-0 flex-1">
                    <span class="text-[13.5px] leading-snug">{{ $item['label'] }}</span>
                    {{-- Sous-ligne : l'auteur quand l'heure est à droite, sinon le détail. --}}
                    @if (! empty($item['who']) || (empty($item['time']) && ! empty($item['meta'])))
                        <span class="text-[12px] text-ink-grey truncate">{{ $item['who'] ?? $item['meta'] }}</span>
                    @endif
                </span>
                @if (! empty($item['time']))
                    <span class="font-mono text-[12px] text-ink-grey whitespace-nowrap mt-0.5">{{ $item['time'] }}</span>
                @endif
            </div>
        @empty
            <div class="text-[12.5px] text-ink-grey pt-3">Rien à signaler.</div>
        @endforelse
    </div>
</section>
