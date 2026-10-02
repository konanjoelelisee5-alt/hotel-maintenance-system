<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ config('app.name', 'Hôtel Président') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo-hotel-president-icon.jpg') }}">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-canvas text-[#14202B]" x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
    <div class="min-h-screen flex items-stretch">

        {{-- Sidebar desktop --}}
        {{-- Sans bloc logo « Hôtel Président Maintenance » (retiré à la demande de l'hôtel) :
             la sidebar commence par l'utilisateur connecté. --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-[252px] lg:flex-shrink-0 bg-navy text-white p-3.5 gap-5">
            <div class="flex items-center gap-2.5 px-1.5 pt-1">
                <span class="w-[34px] h-[34px] rounded-[9px] bg-gold flex items-center justify-center text-[13px] font-bold text-navy flex-shrink-0">{{ auth()->user()->initialsOrGenerated() }}</span>
                <span class="flex flex-col gap-0.5 min-w-0">
                    <span class="text-[13.5px] font-semibold truncate">{{ auth()->user()->name }}</span>
                    <span class="text-[11px] text-[#8FA3B8] truncate">{{ auth()->user()->role_label }}</span>
                </span>
            </div>

            @include('layouts.partials.sidebar-nav')
        </aside>

        {{-- Colonne principale --}}
        <div class="flex-1 min-w-0 flex flex-col pb-[64px] lg:pb-0">

            {{-- Barre mobile --}}
            <div class="lg:hidden sticky top-0 z-40 bg-navy text-white px-4 py-3 flex items-center gap-3">
                @isset($backRoute)
                    <a href="{{ $backRoute }}" class="w-9 h-9 flex-shrink-0 rounded-lg border border-white/20 flex items-center justify-center">
                        <x-nav-icon name="back" class="w-4 h-4" />
                    </a>
                @endisset
                <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                    <div class="text-[11px] text-[#8FA3B8] truncate">{{ $crumb }}</div>
                    <div class="text-[16px] font-semibold truncate">{{ $pageTitle }}</div>
                </div>
                @include('partials.notification-bell', ['dark' => true])
                {{-- La sidebar est masquée sur téléphone : sans ce bouton, la plupart des
                     écrans (référentiels, SLA, journal...) n'étaient atteignables que par l'URL. --}}
                <button type="button" @click="menuOpen = true" :aria-expanded="menuOpen" aria-controls="mobile-menu"
                        class="w-9 h-9 flex-shrink-0 rounded-lg border border-white/20 flex items-center justify-center">
                    <x-nav-icon name="menu" class="w-5 h-5" />
                    <span class="sr-only">Ouvrir le menu</span>
                </button>
            </div>

            {{-- L'action principale de la page (ex. "+ Nouvel ordre", "Planifier") n'a de
                 place que dans l'en-tête desktop ci-dessous ; sur mobile on la rejoue ici,
                 sous la barre du haut, sinon elle serait tout simplement inaccessible. --}}
            @isset($primaryAction)
                <div class="lg:hidden px-4 py-2.5 bg-white border-b border-line flex items-center gap-2 overflow-x-auto">
                    {{ $primaryAction }}
                </div>
            @endisset

            {{-- En-tête desktop : nouvelles pages (pageTitle/crumb) ou pages historiques ($header slot) --}}
            <header class="hidden lg:flex items-center gap-4 px-6 py-3.5 bg-white border-b border-line">
                <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                    @isset($header)
                        {{ $header }}
                    @else
                        <div class="text-[11px] text-ink-grey font-medium">{{ $crumb }}</div>
                        <h1 class="m-0 text-[19px] font-semibold tracking-tight">{{ $pageTitle }}</h1>
                    @endisset
                </div>
                <div class="flex items-center gap-2.5 ml-auto flex-wrap">
                    @include('partials.notification-bell', ['dark' => false])
                    @isset($primaryAction)
                        {{ $primaryAction }}
                    @endisset
                </div>
            </header>

            <main class="flex-1 px-4 py-5 lg:px-6 lg:py-6 flex flex-col gap-5">
                @if (session('status'))
                    <div class="px-4 py-3 rounded-lg bg-[#E6F3EC] text-[#123A2C] text-[13px] font-medium">{{ session('status') }}</div>
                @endif
                @if (session('success'))
                    <div class="px-4 py-3 rounded-lg bg-[#E6F3EC] text-[#123A2C] text-[13px] font-medium">{{ session('success') }}</div>
                @endif
                @if (session('warning'))
                    <div class="px-4 py-3 rounded-lg bg-[#FBF1DF] text-[#7A5A16] text-[13px] font-medium">{{ session('warning') }}</div>
                @endif
                @if (session('error'))
                    <div class="px-4 py-3 rounded-lg bg-[#FDECEA] text-[#8A1F16] text-[13px] font-medium">{{ session('error') }}</div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Menu complet sur téléphone : même contenu que la sidebar desktop. --}}
    <div x-show="menuOpen" x-cloak class="lg:hidden fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Menu">
        <div class="absolute inset-0 bg-black/40" @click="menuOpen = false"></div>
        <aside id="mobile-menu" x-show="menuOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
               class="absolute inset-y-0 right-0 w-[min(300px,85vw)] bg-navy text-white p-3.5 flex flex-col gap-5 overflow-y-auto">
            <div class="flex items-center justify-between gap-2.5 px-1.5">
                <span class="flex flex-col gap-0.5 min-w-0">
                    <span class="text-[13.5px] font-semibold truncate">{{ auth()->user()->name }}</span>
                    <span class="text-[11px] text-[#8FA3B8] truncate">{{ auth()->user()->role_label }}</span>
                </span>
                <button type="button" @click="menuOpen = false" class="w-9 h-9 flex-shrink-0 rounded-lg border border-white/20 flex items-center justify-center">
                    <x-nav-icon name="close" class="w-4 h-4" />
                    <span class="sr-only">Fermer le menu</span>
                </button>
            </div>
            @include('layouts.partials.sidebar-nav')
        </aside>
    </div>

    {{-- Fenêtre (modale) des liens [data-modal], remplie par resources/js/modal.js.
         Téléphone : feuille qui monte du bas ; ordinateur : fenêtre centrée en haut d'écran.
         Animations : resources/css/app.css (#remote-modal[data-state]). --}}
    <div id="remote-modal" class="hidden fixed inset-0 z-[60] flex items-end sm:items-start sm:justify-center sm:px-6 sm:pt-[7vh]"
         data-state="closed" role="dialog" aria-modal="true" aria-labelledby="remote-modal-title">
        <div data-modal-close data-modal-overlay class="absolute inset-0 bg-navy-dark/55 backdrop-blur-[3px]"></div>
        <div data-modal-content
             class="relative w-full sm:max-w-[680px] max-h-[94dvh] sm:max-h-[86dvh] bg-white rounded-t-2xl sm:rounded-2xl border border-line
                    shadow-[0_28px_70px_-18px_rgba(11,27,44,.55)] overflow-y-auto overscroll-contain"></div>
    </div>

    <x-confirm-dialog />

    {{-- Barre de navigation mobile --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-line flex gap-0.5 px-2.5 py-1.5">
        @foreach ($bottomNav as $item)
            @php $active = request()->routeIs(\Illuminate\Support\Str::beforeLast($item['route'], '.').'.*'); @endphp
            <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="flex-1 flex flex-col items-center gap-0.5 py-1.5 rounded-[10px] {{ $active ? 'bg-paper' : '' }}">
                <span class="w-1 h-1 rounded-full {{ $active ? 'bg-gold' : 'bg-transparent' }}"></span>
                <span class="relative">
                    <x-nav-icon :name="$item['icon'] ?? 'home'" class="w-5 h-5 {{ $active ? 'text-navy' : 'text-[#A8A296]' }}" />
                    @if (! empty($item['badge']))
                        <span class="absolute -top-1.5 -right-2.5 min-w-[17px] h-[17px] px-1 rounded-full bg-gold text-navy text-[10px] font-bold leading-none flex items-center justify-center"
                              aria-label="{{ $item['badge'] }} en attente">{{ $item['badge'] }}</span>
                    @endif
                </span>
                <span class="text-[11px] whitespace-nowrap {{ $active ? 'font-semibold text-navy' : 'font-medium text-ink-grey' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
    @stack('scripts')
</body>
</html>
