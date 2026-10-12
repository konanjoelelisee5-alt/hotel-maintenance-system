{{-- Menu « ⋮ » du téléphone, à la manière de Google Chrome (administrateur, manager, technicien) :
     une carte qui s'ouvre depuis le coin en haut à droite, par-dessus la page sans l'assombrir.
     En haut, une rangée d'actions rapides en icônes (retour, accueil, nouvel ordre, actualiser) ;
     dessous, les entrées de App\Support\Navigation, sections séparées par un trait ; en bas, le
     profil et la déconnexion. Se ferme d'un toucher à côté, par Échap ou en choisissant une entrée.
     Hors de la barre du haut : son flou (backdrop-blur) enfermerait une carte « fixed ». --}}
@php
    $user = auth()->user();
    $isActive = fn (array $item) => \App\Support\Navigation::isActive($item);
    $quick = 'w-11 h-11 rounded-full flex items-center justify-center text-ink-deep hover:bg-paper active:bg-line transition-colors';
    // Admin et manager : menu long. À la place de la barre de défilement, un rond bleu animé
    // qui envoie en bas du menu (flèche vers le bas), puis en haut une fois en bas.
    $scrollHelper = $user->role?->dispatchesWork() ?? false;
@endphp
<div x-show="moreOpen" x-cloak class="tab:hidden fixed inset-0 z-[75]" role="dialog" aria-modal="true" aria-label="Menu"
     x-init="window.matchMedia('(min-width: 700px)').addEventListener('change', (e) => { if (e.matches) moreOpen = false })">
    {{-- Toucher à côté : ferme, sans voile (comme Chrome). --}}
    <div class="absolute inset-0" @click="moreOpen = false"></div>
    <div id="more-menu" x-show="moreOpen"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         x-data="{
            overflow: false,
            atEnd: false,
            measure() {
                const el = $refs.scroll;
                this.overflow = el.scrollHeight > el.clientHeight + 4;
                this.atEnd = el.scrollTop + el.clientHeight >= el.scrollHeight - 8;
            },
            jump() {
                const el = $refs.scroll;
                const smooth = ! window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                el.scrollTo({ top: this.atEnd ? 0 : el.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
            },
         }"
         x-effect="if (moreOpen) $nextTick(() => measure())" @resize.window="measure()"
         class="absolute right-2 top-[max(8px,env(safe-area-inset-top))] w-[min(272px,calc(100vw-16px))] max-h-[calc(100dvh-16px)] flex flex-col
                origin-top-right bg-white rounded-[20px] border border-line shadow-[0_18px_44px_-12px_rgba(23,25,31,.35)] overflow-hidden">
        <div x-ref="scroll" @scroll.passive="measure()"
             class="flex-1 min-h-0 overflow-y-auto overscroll-contain py-1.5 {{ $scrollHelper ? '[scrollbar-width:none] [&::-webkit-scrollbar]:hidden' : '' }}">

        {{-- Actions rapides --}}
        <div class="flex items-center justify-between px-2 pb-1.5">
            <button type="button" onclick="history.back()" class="{{ $quick }}" title="Retour" aria-label="Retour">
                <x-nav-icon name="back" class="w-5 h-5" />
            </button>
            <a href="{{ route($user->dashboardRoute()) }}" class="{{ $quick }}" title="Accueil" aria-label="Accueil">
                <x-nav-icon name="home" class="w-5 h-5" />
            </a>
            @if ($user->role?->dispatchesWork())
                <a href="{{ route('work-orders.create') }}" data-modal @click="moreOpen = false" class="{{ $quick }}" title="Nouvel ordre" aria-label="Nouvel ordre">
                    <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                </a>
            @else
                {{-- Technicien : une autre panne trouvée sur place. --}}
                <a href="{{ route('quick-reports.create') }}" @click="moreOpen = false" class="{{ $quick }}" title="Signaler une panne" aria-label="Signaler une panne">
                    <x-nav-icon name="mic" class="w-5 h-5" />
                </a>
            @endif
            <button type="button" onclick="location.reload()" class="{{ $quick }}" title="Actualiser" aria-label="Actualiser">
                <x-nav-icon name="refresh" class="w-5 h-5" />
            </button>
        </div>

        @foreach ($nav as $section)
            <div class="h-px bg-line my-1"></div>
            <nav aria-label="{{ $section['title'] }}">
                @foreach ($section['items'] as $item)
                    @php $active = $isActive($item); @endphp
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" @click="moreOpen = false" @if ($active) aria-current="page" @endif
                       class="flex items-center gap-4 min-h-12 px-4 text-[15px] transition-colors {{ $active ? 'bg-paper font-bold text-ink-deep' : 'font-medium text-ink-deep active:bg-paper' }}">
                        <x-nav-icon :name="$item['icon'] ?? 'list'" class="w-5 h-5 flex-shrink-0 {{ $active ? 'text-ink-deep' : 'text-ink-grey' }}" />
                        <span class="min-w-0 truncate">{{ $item['label'] }}</span>
                        @if (! empty($item['badge']))
                            <span class="ml-auto min-w-[22px] h-[22px] px-1.5 rounded-full bg-[rgb(var(--rc-sky))] text-[rgb(var(--rc-sky-ink))] text-[11.5px] font-bold flex items-center justify-center tabular">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        @endforeach

        <div class="h-px bg-line my-1"></div>
        <a href="{{ route('profile.edit') }}" @click="moreOpen = false"
           class="flex items-center gap-4 min-h-12 px-4 text-[15px] font-medium text-ink-deep active:bg-paper">
            <x-nav-icon name="user" class="w-5 h-5 flex-shrink-0 text-ink-grey" />
            <span class="min-w-0 flex flex-col leading-tight">
                <span class="truncate">{{ $user->name }}</span>
                <span class="text-[12px] text-ink-grey">{{ $user->role?->label() }}</span>
            </span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-4 min-h-12 px-4 text-left text-[15px] font-medium text-ink-deep active:bg-danger-bg/60">
                <svg viewBox="0 0 24 24" class="w-5 h-5 flex-shrink-0 text-ink-grey" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 16l-4-4 4-4M6 12h10"/></svg>
                Se déconnecter
            </button>
        </form>
        <div class="h-px bg-line my-1"></div>
        <x-app-version class="px-4" x-on:click="moreOpen = false" />
        </div>

        @if ($scrollHelper)
            {{-- Rond bleu : vers le bas tant qu'il reste des entrées dessous, vers le haut une fois en bas. --}}
            <button type="button" x-show="overflow" x-cloak @click="jump()"
                    :aria-label="atEnd ? 'Revenir en haut du menu' : 'Aller en bas du menu'"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-3 bottom-3 w-11 h-11 rounded-full bg-blue text-white flex items-center justify-center
                           shadow-[0_10px_24px_-8px_rgba(38,73,107,.7)] ring-4 ring-white active:scale-95 transition-transform">
                {{-- Deux transformations séparées : le va-et-vient (enveloppe) et le sens (flèche). --}}
                <span class="flex motion-safe:animate-[menu-nudge_1.4s_ease-in-out_infinite]" :style="{ '--nudge': atEnd ? '-3px' : '3px' }">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 transition-transform duration-300" :class="atEnd && 'rotate-180'"
                         fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 5v14M6 13l6 6 6-6"/>
                    </svg>
                </span>
            </button>
        @endif
    </div>
</div>
