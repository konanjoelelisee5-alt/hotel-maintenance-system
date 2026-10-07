{{-- « Feuille » de l'application, pour tous les rôles (AppLayout) : une grande feuille blanche
     posée sur un fond gris perle, sidebar blanche avec le logo HP, encre presque noire, accent
     bleu ciel (couleurs : .theme-sheet dans resources/css/app.css).
     Réception : poste partagé (« Changer de réceptionniste », déconnexion après inactivité).
     Housekeeping : signalements gardés dans le téléphone faute de réseau (bandeau d'envoi).
     - ordinateur ≥ 1200 px : sidebar | contenu | colonne de droite ($aside, s'il y en a une) ;
     - téléphone et tablette : barre du haut + menu à gauche ; barre du bas sur téléphone ;
       la colonne de droite passe sous le contenu.
     $greeting remplace le titre de la page sur tablette et ordinateur (accueil : « Bonjour, … ») ;
     le titre reste dans la barre du haut sur téléphone et dans l'onglet.
     $focus (signalement en étapes) : barre du bas masquée, une croix ferme le parcours. --}}
@php
    $user = auth()->user();
    $isActive = fn (array $item) => \App\Support\Navigation::isActive($item);
    $mobileAction = isset($primaryAction) && ! $primaryAction->attributes->has('desktop-only');
    $isReception = $user->role === \App\Enums\UserRole::Reception;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#E9E9EE">

    <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ config('app.name', 'Hôtel Président') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo-hotel-president-icon.jpg') }}">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Navigation fluide entre les pages (fondu, sidebar immobile). --}}
    <style>@view-transition { navigation: auto; }</style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="theme-sheet antialiased bg-canvas text-ink-deep" x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
    <div class="min-h-screen desk:p-4 flex">
        <div class="flex-1 min-w-0 flex bg-white desk:rounded-[28px] desk:shadow-[0_40px_80px_-48px_rgba(23,25,31,.35)]">

            {{-- Sidebar (ordinateur) --}}
            <aside data-rc-sidebar class="hidden desk:flex print:!hidden flex-col w-[264px] flex-shrink-0 px-6 pt-9 pb-7 border-r border-line sticky top-4 h-[calc(100vh-32px)] overflow-y-auto">
                @include('layouts.partials.sheet-nav')
            </aside>

            <div class="flex-1 min-w-0 flex flex-col">
                {{-- Barre du haut (téléphone et tablette) --}}
                <div class="desk:hidden print:hidden sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-line px-4 py-3 flex items-center gap-3">
                    @unless ($focus)
                        <button type="button" @click="menuOpen = true" :aria-expanded="menuOpen" aria-controls="mobile-menu"
                                class="w-10 h-10 flex-shrink-0 rounded-full bg-paper flex items-center justify-center">
                            <x-nav-icon name="menu" class="w-5 h-5" />
                            <span class="sr-only">Ouvrir le menu</span>
                        </button>
                    @endunless
                    @isset($backRoute)
                        <a href="{{ $backRoute }}" data-back aria-label="{{ $focus ? 'Fermer' : 'Retour' }}" class="w-10 h-10 flex-shrink-0 rounded-full bg-paper flex items-center justify-center">
                            <x-nav-icon :name="$focus ? 'close' : 'back'" class="w-4 h-4" />
                        </a>
                    @else
                        <img src="{{ asset('images/logo-hotel-president-icon.jpg') }}" alt="" class="w-8 h-8 rounded-[10px] object-cover flex-shrink-0">
                    @endisset
                    <div data-phone-title class="text-[16px] font-bold truncate flex-1 min-w-0">{{ $pageTitle }}</div>
                    @unless ($focus)
                        @include('partials.notification-bell', ['round' => true])
                    @endunless
                </div>

                <div class="flex-1 min-w-0 flex flex-col desk:flex-row {{ $focus ? '' : ($mobileAction ? 'pb-[calc(136px+env(safe-area-inset-bottom))]' : 'pb-[calc(72px+env(safe-area-inset-bottom))]') }} tab:pb-0">
                    <div class="flex-1 min-w-0 flex flex-col">
                        {{-- En-tête de la page --}}
                        <header class="px-5 tab:px-10 pt-6 desk:pt-10 pb-2 flex items-start gap-4">
                            @isset($greeting)
                                <div class="flex-1 min-w-0">{{ $greeting }}</div>
                            @else
                                @isset($backRoute)
                                    <a href="{{ $backRoute }}" data-back aria-label="{{ $focus ? 'Fermer' : 'Retour' }}"
                                       class="hidden desk:flex w-11 h-11 flex-shrink-0 rounded-full bg-paper hover:bg-line items-center justify-center transition-colors print:hidden">
                                        <x-nav-icon :name="$focus ? 'close' : 'back'" class="w-4 h-4" />
                                    </a>
                                @endisset
                                <div class="flex-1 min-w-0 flex flex-col gap-1">
                                    @if ($crumb)<div class="text-[13px] font-medium text-ink-grey truncate">{{ $crumb }}</div>@endif
                                    <h1 class="m-0 text-[24px] tab:text-[28px] font-bold tracking-[-0.02em] leading-tight text-ink-deep">{{ $pageTitle }}</h1>
                                </div>
                            @endisset
                            <div class="hidden tab:flex items-center gap-2.5 flex-shrink-0 print:hidden">
                                @unless ($focus)
                                    <div class="hidden desk:block">@include('partials.notification-bell', ['round' => true])</div>
                                @endunless
                                @isset($primaryAction)
                                    {{ $primaryAction }}
                                @endisset
                            </div>
                        </header>

                        <main class="flex-1 min-w-0 px-5 tab:px-10 pt-4 pb-8 flex flex-col gap-6">
                            @if ($user->role === \App\Enums\UserRole::Housekeeping)
                                @include('layouts.partials.hk-outbox')
                            @endif
                            @if (session('warning'))
                                <div class="px-4 py-3 rounded-2xl bg-warn-bg text-warn-ink text-[13.5px] font-medium">{{ session('warning') }}</div>
                            @endif
                            @if (session('error'))
                                <div class="px-4 py-3 rounded-2xl bg-danger-bg text-danger-ink text-[13.5px] font-medium">{{ session('error') }}</div>
                            @endif
                            {{ $slot }}
                        </main>
                    </div>

                    {{-- Colonne de droite (accueil : astreinte, activité, blocages) --}}
                    @isset($aside)
                        <aside data-rc-aside class="desk:w-[360px] flex-shrink-0 border-t desk:border-t-0 desk:border-l border-line px-5 tab:px-10 desk:px-4 py-6 desk:py-4 flex flex-col gap-6">
                            {{ $aside }}
                        </aside>
                    @endisset
                </div>
            </div>
        </div>
    </div>

    {{-- Menu (téléphone et tablette) : la sidebar, ouverte depuis la gauche. --}}
    <div x-show="menuOpen" x-cloak class="desk:hidden fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Menu"
         x-init="window.matchMedia('(min-width: 1200px)').addEventListener('change', (e) => { if (e.matches) menuOpen = false })">
        <div class="absolute inset-0 bg-ink-deep/30" @click="menuOpen = false" x-show="menuOpen" x-transition.opacity></div>
        <aside id="mobile-menu" x-show="menuOpen"
               x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
               class="absolute inset-y-0 left-0 w-[min(300px,86vw)] bg-white rounded-r-[28px] px-6 pt-[max(28px,env(safe-area-inset-top))] pb-6 flex flex-col overflow-y-auto">
            <button type="button" @click="menuOpen = false" class="absolute top-5 right-5 w-10 h-10 rounded-full bg-paper flex items-center justify-center">
                <x-nav-icon name="close" class="w-4 h-4" />
                <span class="sr-only">Fermer le menu</span>
            </button>
            @include('layouts.partials.sheet-nav')
        </aside>
    </div>

    {{-- Fenêtre (modale) des liens [data-modal], remplie par resources/js/modal.js.
         Téléphone : feuille qui monte du bas ; ordinateur : fenêtre centrée en haut d'écran. --}}
    <div id="remote-modal" class="hidden fixed inset-0 z-[60] flex items-end sm:items-start sm:justify-center sm:px-6 sm:pt-[7vh]"
         data-state="closed" role="dialog" aria-modal="true" aria-labelledby="remote-modal-title">
        <div data-modal-close data-modal-overlay class="absolute inset-0 bg-ink-deep/40 backdrop-blur-[3px]"></div>
        <div data-modal-content
             class="relative w-full sm:max-w-[680px] max-h-[94dvh] sm:max-h-[86dvh] bg-white rounded-t-[28px] sm:rounded-[28px]
                    shadow-[0_32px_70px_-24px_rgba(23,25,31,.45)] overflow-y-auto overscroll-contain"></div>
    </div>

    <x-confirm-dialog />

    <x-toast :offset="$focus ? 'bottom-5' : ($mobileAction ? 'bottom-[calc(140px+env(safe-area-inset-bottom))] tab:bottom-5' : 'bottom-[calc(84px+env(safe-area-inset-bottom))] tab:bottom-5')" />
    <x-offline-banner />
    <x-role-switcher />

    {{-- Action principale sur téléphone : dans la zone du pouce, au-dessus de la barre du bas. --}}
    @if ($mobileAction)
        <div data-thumb-bar class="tab:hidden print:hidden fixed inset-x-0 z-30 px-4 py-2.5 bg-white/95 backdrop-blur border-t border-line flex items-center gap-2 overflow-x-auto
                    {{ $focus ? 'bottom-0 pb-[calc(10px+env(safe-area-inset-bottom))]' : 'bottom-[calc(64px+env(safe-area-inset-bottom))]' }}">
            {{ $primaryAction }}
        </div>
    @endif

    {{-- Barre du bas (téléphone) --}}
    @unless ($focus)
        <nav data-bottom-nav class="tab:hidden print:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-line pb-[env(safe-area-inset-bottom)]" aria-label="Navigation principale">
            <div class="h-16 flex gap-1 px-2.5 py-1.5">
                @foreach ($bottomNav as $item)
                    @php $active = $isActive($item); @endphp
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" @if ($active) aria-current="page" @endif
                       class="flex-1 min-w-0 flex flex-col items-center justify-center gap-1 rounded-2xl transition-colors {{ $active ? 'bg-paper' : '' }}">
                        <span class="relative">
                            <x-nav-icon :name="$item['icon'] ?? 'home'" class="w-5 h-5 {{ $active ? 'text-ink-deep' : 'text-ink-faint' }}" />
                            @if (! empty($item['badge']))
                                <span class="absolute -top-1.5 -right-2.5 min-w-[17px] h-[17px] px-1 rounded-full bg-blue text-white text-[10px] font-bold leading-none flex items-center justify-center"
                                      aria-label="{{ $item['badge'] }} en attente">{{ $item['badge'] }}</span>
                            @endif
                        </span>
                        <span class="text-[11px] truncate max-w-full {{ $active ? 'font-bold text-ink-deep' : 'font-medium text-ink-grey' }}">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    @endunless

    @if ($isReception)
    {{-- Poste du comptoir partagé : après ReceptionDesk::IDLE_LOGOUT_MINUTES sans geste, on
         prévient puis on déconnecte, pour que le signalement suivant ne parte pas au nom de la
         collègue précédente. La dernière activité est gardée dans le navigateur. --}}
    <form id="idle-logout" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
    <div id="idle-warning" hidden class="fixed inset-0 z-[80] bg-ink-deep/40 flex [&[hidden]]:hidden items-center justify-center p-4" role="alertdialog" aria-modal="true" aria-labelledby="idle-title">
        <div class="w-full max-w-[380px] bg-white rounded-[28px] p-7 flex flex-col items-center gap-3 text-center">
            <h2 id="idle-title" class="m-0 text-[18px] font-bold">Toujours là ?</h2>
            <p class="m-0 text-[14px] text-ink-muted">Poste partagé : déconnexion de <strong class="text-ink-deep">{{ $user->name }}</strong> dans <span id="idle-count" class="tabular font-semibold">60</span> s.</p>
            <button type="button" id="idle-stay" class="btn btn-primary btn-lg w-full">Je suis là</button>
        </div>
    </div>
    <script>
        (() => {
            const KEY = 'reception-last-activity';
            const IDLE = {{ \App\Support\ReceptionDesk::IDLE_LOGOUT_MINUTES }} * 60 * 1000;
            const GRACE = 60;
            const warning = document.getElementById('idle-warning');
            const count = document.getElementById('idle-count');
            const touch = () => { try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {} };
            const last = () => { try { return Number(localStorage.getItem(KEY)) || Date.now(); } catch (e) { return Date.now(); } };
            let known = null;
            try { known = localStorage.getItem(KEY); } catch (e) {}
            if (document.referrer.includes('/login') || ! known) touch();
            ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach((e) => document.addEventListener(e, () => { if (warning.hidden) touch(); }, { passive: true }));
            document.getElementById('idle-stay').addEventListener('click', () => { touch(); warning.hidden = true; });

            setInterval(() => {
                const idle = Date.now() - last();
                if (idle < IDLE) { warning.hidden = true; return; }
                const left = Math.max(0, GRACE - Math.floor((idle - IDLE) / 1000));
                warning.hidden = false;
                count.textContent = left;
                if (left === 0) { try { localStorage.removeItem(KEY); } catch (e) {} document.getElementById('idle-logout').submit(); }
            }, 1000);
        })();
    </script>
    @endif
    @stack('scripts')
</body>
</html>
