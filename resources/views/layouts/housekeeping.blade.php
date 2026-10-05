{{-- Coquille Housekeeping (AppLayout choisit cette vue pour le rôle housekeeping), dans le
     langage visuel de l'admin (layouts.app) : mêmes menus (App\Support\Navigation), même
     sidebar sans logo, mêmes icônes au trait.
     - téléphone < 700 px : barre marine en haut, barre du bas de 64 px ;
     - tablette 700–1199 px : rail marine de 96 px ;
     - ordinateur ≥ 1200 px : sidebar de l'admin.
     $focus (signalement) : barre du bas et rail masqués, la sidebar reste sur ordinateur. --}}
@php
    $user = auth()->user();
    $isActive = fn (array $item) => request()->routeIs(...($item['match'] ?? [\Illuminate\Support\Str::beforeLast($item['route'], '.').'.*']));
    // Codes de Breeze (profil) traduits pour le toast.
    $statusMessages = [
        'profile-updated' => 'Profil enregistré.',
        'password-updated' => 'Mot de passe modifié.',
        'verification-link-sent' => 'Lien de vérification envoyé.',
    ];
    $toast = session('success') ?? (session('status') ? ($statusMessages[session('status')] ?? session('status')) : null);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0E2136">

    <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ config('app.name', 'Hôtel Président') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo-hotel-president-icon.jpg') }}">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-canvas text-[#14202B]">
    <div class="min-h-screen flex items-stretch">

        {{-- Ordinateur : la sidebar de l'admin (utilisateur, menu, profil et déconnexion). --}}
        <aside class="hidden desk:flex print:hidden flex-col w-[252px] flex-shrink-0 bg-navy text-white p-3.5 gap-5 sticky top-0 h-screen overflow-y-auto">
            <div class="flex items-center gap-2.5 px-1.5 pt-1">
                <span class="w-[34px] h-[34px] rounded-[9px] bg-gold flex items-center justify-center text-[13px] font-bold text-navy flex-shrink-0">{{ $user->initialsOrGenerated() }}</span>
                <span class="flex flex-col gap-0.5 min-w-0">
                    <span class="text-[13.5px] font-semibold truncate">{{ $user->name }}</span>
                    <span class="text-[11px] text-[#8FA3B8] truncate">{{ $user->role_label }}</span>
                </span>
            </div>

            @include('layouts.partials.sidebar-nav')
        </aside>

        {{-- Tablette : rail de 96 px, mêmes entrées que la barre du bas. --}}
        @unless ($focus)
            <nav class="hidden tab:flex desk:hidden print:hidden flex-col items-center w-[96px] flex-shrink-0 bg-navy text-white sticky top-0 h-screen overflow-y-auto py-4 gap-1.5" aria-label="Navigation principale">
                <a href="{{ route('profile.edit') }}" class="mb-3 w-[38px] h-[38px] rounded-[10px] bg-gold flex items-center justify-center text-[13px] font-bold text-navy" title="{{ $user->name }}">
                    {{ $user->initialsOrGenerated() }}
                </a>
                @foreach ($bottomNav as $item)
                    @continue($item['route'] === 'profile.edit')
                    @php $active = $isActive($item); @endphp
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" @if ($active) aria-current="page" @endif
                       class="relative w-[80px] min-h-[60px] rounded-[10px] flex flex-col items-center justify-center gap-1 px-1 text-[11px] font-medium text-center leading-tight
                              {{ $active ? 'bg-white/10 text-white' : 'text-[#B9C7D6] hover:bg-white/[.06] hover:text-white' }}">
                        <x-nav-icon :name="$item['icon'] ?? 'home'" class="w-5 h-5 {{ $active ? 'text-gold' : 'text-[#8FA3B8]' }}" />
                        {{ $item['label'] }}
                        @if (! empty($item['badge']))
                            <span class="absolute top-1.5 right-3 min-w-[17px] h-[17px] px-1 rounded-full bg-gold text-navy text-[10px] font-bold flex items-center justify-center">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
                {{-- Gouvernante : ses outils sous les entrées quotidiennes. --}}
                @if ($user->isDepartmentHead())
                    <span class="w-10 h-px bg-white/15 my-1.5" aria-hidden="true"></span>
                    @foreach (\App\Support\Housekeeping::headTools() as $tool)
                        @php $active = $isActive($tool); @endphp
                        <a href="{{ route($tool['route']) }}" @if ($active) aria-current="page" @endif
                           class="w-[80px] min-h-[60px] rounded-[10px] flex flex-col items-center justify-center gap-1 px-1 text-[11px] font-medium text-center leading-tight
                                  {{ $active ? 'bg-white/10 text-white' : 'text-[#B9C7D6] hover:bg-white/[.06] hover:text-white' }}">
                            <x-nav-icon :name="$tool['icon']" class="w-5 h-5 {{ $active ? 'text-gold' : 'text-[#8FA3B8]' }}" />
                            {{ $tool['label'] }}
                        </a>
                    @endforeach
                @endif
                <a href="{{ route('profile.edit') }}" @if (request()->routeIs('profile.*')) aria-current="page" @endif
                   class="mt-auto w-[80px] min-h-[60px] rounded-[10px] flex flex-col items-center justify-center gap-1 text-[11px] font-medium {{ request()->routeIs('profile.*') ? 'bg-white/10 text-white' : 'text-[#B9C7D6] hover:bg-white/[.06]' }}">
                    <x-nav-icon name="user" class="w-5 h-5 {{ request()->routeIs('profile.*') ? 'text-gold' : 'text-[#8FA3B8]' }}" />
                    Profil
                </a>
            </nav>
        @endunless

        {{-- Colonne principale --}}
        <div class="flex-1 min-w-0 flex flex-col {{ $focus ? '' : 'pb-[calc(64px+env(safe-area-inset-bottom))] tab:pb-0' }}">

            {{-- Téléphone : barre marine de l'admin --}}
            <div class="tab:hidden print:hidden sticky top-0 z-40 bg-navy text-white px-4 py-3 flex items-center gap-3">
                @isset($backRoute)
                    <a href="{{ $backRoute }}" class="w-9 h-9 flex-shrink-0 rounded-lg border border-white/20 flex items-center justify-center" aria-label="{{ $focus ? 'Fermer' : 'Retour' }}">
                        <x-nav-icon :name="$focus ? 'close' : 'back'" class="w-4 h-4" />
                    </a>
                @endisset
                <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                    <div class="text-[11px] text-[#8FA3B8] truncate">{{ $crumb }}</div>
                    <div class="text-[16px] font-semibold truncate">{{ $pageTitle }}</div>
                </div>
                @unless ($focus)
                    @include('partials.notification-bell', ['dark' => true])
                @endunless
            </div>

            {{-- Action de la page sur téléphone, sauf si la page la porte déjà dans son contenu (desktop-only). --}}
            @if (isset($primaryAction) && ! $primaryAction->attributes->has('desktop-only'))
                <div class="tab:hidden px-4 py-2.5 bg-white border-b border-line flex items-center gap-2 overflow-x-auto">{{ $primaryAction }}</div>
            @endif

            {{-- Tablette et ordinateur : en-tête blanc de l'admin --}}
            <header class="hidden tab:flex print:flex items-center gap-4 px-6 py-3.5 bg-white border-b border-line sticky top-0 z-30">
                @isset($backRoute)
                    <a href="{{ $backRoute }}" class="w-[38px] h-[38px] flex-shrink-0 rounded-[9px] border border-line flex items-center justify-center text-navy hover:bg-paper" aria-label="{{ $focus ? 'Fermer' : 'Retour' }}">
                        <x-nav-icon :name="$focus ? 'close' : 'back'" class="w-4 h-4" />
                    </a>
                @endisset
                <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                    <div class="text-[11px] text-ink-grey font-medium truncate">{{ $crumb }}</div>
                    <div class="truncate"><h1 class="m-0 text-[19px] font-semibold tracking-tight">{{ $pageTitle }}</h1></div>
                </div>
                <div class="flex items-center gap-2.5 ml-auto">
                    @unless ($focus)
                        @include('partials.notification-bell', ['dark' => false])
                    @endunless
                    @isset($primaryAction)
                        {{ $primaryAction }}
                    @endisset
                </div>
            </header>

            <main class="flex-1 w-full max-w-[1440px] px-4 py-5 tab:px-6 tab:py-6 flex flex-col gap-5">
                {{-- Signalements gardés dans le téléphone faute de réseau (resources/js/hk-outbox.js) :
                     renvoyés au retour du réseau, puis toutes les 30 s tant qu'il en reste. --}}
                <div x-data="{
                        items: [], errors: {}, busy: false,
                        async load() { this.items = window.hkOutbox?.supported ? await window.hkOutbox.all() : []; },
                        async flush() {
                            if (this.busy || ! this.items.length) return;
                            this.busy = true;
                            for (const entry of this.items) {
                                const result = await window.hkOutbox.send(entry);
                                if (result.ok) {
                                    await window.hkOutbox.remove(entry.id);
                                    window.dispatchEvent(new CustomEvent('hk-toast', { detail: 'Signalement envoyé : ' + (entry.label || 'en attente') }));
                                } else if (! result.retry) {
                                    this.errors[entry.id] = result.error;
                                }
                            }
                            this.busy = false;
                            await this.load();
                        },
                        async drop(id) { await window.hkOutbox.remove(id); await this.load(); },
                     }"
                     x-init="load().then(() => flush()); setInterval(() => flush(), 30000)"
                     @online.window="flush()" @hk-outbox-changed.window="load()"
                     x-show="items.length" x-cloak
                     class="bg-white border border-amber/40 rounded-xl overflow-hidden" role="status">
                    <div class="flex items-start gap-3 px-4 py-3 bg-[#FBF1DF]">
                        <x-hk.icon name="cloud-off" :size="18" class="text-amber mt-0.5" />
                        <div class="flex-1 min-w-0">
                            <p class="m-0 text-[13.5px] font-semibold text-[#7A5A16]" x-text="items.length > 1 ? items.length + ' signalements en attente d\'envoi' : 'Un signalement en attente d\'envoi'"></p>
                            <p class="m-0 text-[12.5px] text-[#7A5A16]/80">Gardé dans ce téléphone : il part tout seul dès que le réseau revient.</p>
                        </div>
                        <button type="button" @click="flush()" :disabled="busy" class="btn btn-sm btn-secondary flex-shrink-0" x-text="busy ? 'Envoi…' : 'Réessayer'"></button>
                    </div>
                    <template x-for="entry in items" :key="entry.id">
                        <div class="flex items-center gap-3 px-4 py-2.5 border-t border-line-soft">
                            <div class="flex-1 min-w-0">
                                <p class="m-0 text-[13px] font-medium text-navy truncate" x-text="entry.label || 'Signalement'"></p>
                                <p class="m-0 text-[12px]" :class="errors[entry.id] ? 'text-red' : 'text-ink-grey'"
                                   x-text="errors[entry.id] || ('Gardé à ' + new Date(entry.savedAt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }))"></p>
                            </div>
                            <button type="button" @click="drop(entry.id)" class="btn btn-sm btn-ghost !text-red flex-shrink-0">Abandonner</button>
                        </div>
                    </template>
                </div>

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

    {{-- Toast de confirmation en bas de l'écran : messages flash du serveur, ou
         window.dispatchEvent(new CustomEvent('hk-toast', { detail: 'Texte' })). --}}
    <div x-data="{ message: @js($toast), timer: null,
                   show(text) { this.message = text; clearTimeout(this.timer); this.timer = setTimeout(() => this.message = null, 4500); } }"
         x-init="if (message) show(message)" @hk-toast.window="show($event.detail)"
         class="fixed inset-x-0 z-[70] flex justify-center px-4 pointer-events-none {{ $focus ? 'bottom-5' : 'bottom-[calc(76px+env(safe-area-inset-bottom))] tab:bottom-5' }}"
         role="status" aria-live="polite">
        <div x-show="message" x-transition.opacity.duration.200ms @if (! $toast) x-cloak @endif
             class="pointer-events-auto max-w-[420px] w-full flex items-center gap-3 pl-4 pr-2 py-2.5 rounded-xl bg-navy text-white shadow-[0_16px_36px_-12px_rgba(14,33,54,.6)]">
            <span class="w-7 h-7 rounded-full bg-green flex items-center justify-center flex-shrink-0"><x-hk.icon name="check" :size="16" /></span>
            <span class="flex-1 text-[13.5px] font-medium" x-text="message">{{ $toast }}</span>
            <button type="button" @click="message = null" class="w-9 h-9 rounded-lg flex items-center justify-center text-[#B9C7D6] hover:bg-white/10" aria-label="Fermer">
                <x-hk.icon name="x" :size="16" />
            </button>
        </div>
    </div>

    {{-- Fenêtre (modale) des liens [data-modal] — même mécanique que layouts.app. --}}
    <div id="remote-modal" class="hidden fixed inset-0 z-[60] flex items-end sm:items-start sm:justify-center sm:px-6 sm:pt-[7vh]"
         data-state="closed" role="dialog" aria-modal="true" aria-labelledby="remote-modal-title">
        <div data-modal-close data-modal-overlay class="absolute inset-0 bg-navy-dark/55 backdrop-blur-[3px]"></div>
        <div data-modal-content
             class="relative w-full sm:max-w-[680px] max-h-[94dvh] sm:max-h-[86dvh] bg-white rounded-t-2xl sm:rounded-2xl border border-line
                    shadow-[0_28px_70px_-18px_rgba(11,27,44,.55)] overflow-y-auto overscroll-contain"></div>
    </div>

    <x-confirm-dialog />

    {{-- Téléphone : barre du bas de l'admin (64 px) --}}
    @unless ($focus)
        <nav class="tab:hidden print:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-line pb-[env(safe-area-inset-bottom)]" aria-label="Navigation principale">
            <div class="h-16 flex gap-0.5 px-2.5 py-1.5">
                @foreach ($bottomNav as $item)
                    @php $active = $isActive($item); @endphp
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" @if ($active) aria-current="page" @endif
                       class="flex-1 min-w-0 flex flex-col items-center justify-center gap-0.5 rounded-[10px] {{ $active ? 'bg-paper' : '' }}">
                        <span class="w-1 h-1 rounded-full {{ $active ? 'bg-gold' : 'bg-transparent' }}"></span>
                        <span class="relative">
                            <x-nav-icon :name="$item['icon'] ?? 'home'" class="w-5 h-5 {{ $active ? 'text-navy' : 'text-[#A8A296]' }}" />
                            @if (! empty($item['badge']))
                                <span class="absolute -top-1.5 -right-2.5 min-w-[17px] h-[17px] px-1 rounded-full bg-gold text-navy text-[10px] font-bold leading-none flex items-center justify-center"
                                      aria-label="{{ $item['badge'] }} en attente">{{ $item['badge'] }}</span>
                            @endif
                        </span>
                        <span class="text-[11px] truncate max-w-full {{ $active ? 'font-semibold text-navy' : 'font-medium text-ink-grey' }}">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    @endunless

    @stack('scripts')
</body>
</html>
