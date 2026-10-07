{{-- Menu de la feuille (layouts.sheet, tous les rôles) : sidebar sur ordinateur, panneau « Menu »
     sur téléphone et tablette. Entrées : App\Support\Navigation (une seule source) ; les sections
     (Exploitation, Stock & achats…) gardent leur titre quand il y en a plusieurs, les sections
     « secondaires » (référentiels) sont plus discrètes.
     Carte du bas (l'« Upgrade » de la maquette) : l'action qui lance le travail du rôle ; pas pour
     l'admin et le manager, dont le menu est long et qui ont déjà « + » et « Nouvel ordre ». --}}
@php
    $user = auth()->user();
    $role = $user->role;
    $isActive = fn (array $item) => \App\Support\Navigation::isActive($item);
    $items = collect($nav)->flatMap(fn (array $section) => $section['items']);
    $hasReportEntry = $items->contains('route', 'quick-reports.create');
    $creates = $role?->dispatchesWork() ?? false;
    $plus = $creates
        ? ['route' => route('work-orders.create'), 'label' => 'Nouvel ordre', 'modal' => true]
        : ['route' => route('quick-reports.create'), 'label' => 'Signaler une panne', 'modal' => false];
    $cta = match ($role) {
        \App\Enums\UserRole::Reception => ['Une panne en chambre', 'Signalez-la : la maintenance est prévenue tout de suite.', 'Signaler'],
        \App\Enums\UserRole::Housekeeping => ['Une panne à signaler', 'Lieu, problème, message vocal : la maintenance est prévenue tout de suite.', 'Signaler'],
        \App\Enums\UserRole::Technicien => ['Une autre panne', 'Trouvée sur place : signalez-la en un geste.', 'Signaler'],
        default => ['Un nouvel ordre', 'Créez-le et confiez-le à un technicien.', 'Créer'],
    };
    $multi = count($nav) > 1;
@endphp

<a href="{{ route($user->dashboardRoute()) }}" class="flex items-center gap-3 px-1">
    <img src="{{ asset('images/logo-hotel-president-icon.jpg') }}" alt="" class="w-10 h-10 rounded-xl object-cover flex-shrink-0">
    <span class="flex flex-col leading-tight min-w-0">
        <span class="text-[17px] font-extrabold tracking-[-0.02em] text-ink-deep truncate">Hôtel Président</span>
        <span class="text-[12px] font-medium text-ink-grey">{{ $role?->label() }}</span>
    </span>
</a>

<div class="{{ $multi ? 'mt-8 gap-6' : 'mt-11' }} flex flex-col">
    @foreach ($nav as $section)
        <nav class="flex flex-col gap-0.5" aria-label="{{ $section['title'] }}">
            @if ($multi)
                <div class="px-3 pb-1.5 text-[12px] font-semibold text-ink-grey">{{ $section['title'] }}</div>
            @endif
            @foreach ($section['items'] as $item)
                @php $active = $isActive($item); @endphp
                <div class="group flex items-center gap-1">
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" @if ($active) aria-current="page" @endif
                       class="flex-1 min-w-0 flex items-center gap-3.5 px-3 rounded-xl leading-snug transition-colors
                              {{ $section['secondary'] ? 'min-h-10 py-1.5 text-[14px]' : 'min-h-11 py-2 text-[15px]' }}
                              {{ $active ? 'bg-paper font-bold text-ink-deep' : 'font-medium text-ink-muted hover:text-ink-deep hover:bg-paper/70' }}">
                        <x-nav-icon :name="$item['icon'] ?? 'list'" class="w-5 h-5 flex-shrink-0 {{ $active ? 'text-ink-deep' : 'text-ink-grey group-hover:text-ink-deep' }}" />
                        <span class="min-w-0">{{ $item['label'] }}</span>
                        @if (! empty($item['badge']))
                            <span class="ml-auto min-w-[22px] h-[22px] px-1.5 rounded-full bg-[rgb(var(--rc-sky))] text-[rgb(var(--rc-sky-ink))] text-[11.5px] font-bold flex items-center justify-center tabular">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                    {{-- « + » de la maquette : un nouvel ordre (ou un signalement) depuis la liste. --}}
                    @if ($item['route'] === 'work-orders.index' && ! $hasReportEntry)
                        <a href="{{ $plus['route'] }}" @if ($plus['modal']) data-modal @endif title="{{ $plus['label'] }}" aria-label="{{ $plus['label'] }}"
                           class="w-7 h-7 flex-shrink-0 rounded-full bg-paper text-ink-deep flex items-center justify-center hover:bg-line transition-colors">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        </a>
                    @endif
                </div>
            @endforeach
        </nav>
    @endforeach
</div>

<div class="mt-auto pt-8">
    @unless ($creates)
    <div class="rounded-[22px] bg-gradient-to-b from-paper to-white px-5 pt-5 pb-5 flex flex-col items-center text-center gap-2.5">
        <span class="text-[15.5px] font-bold text-ink-deep">{{ $cta[0] }}&nbsp;?</span>
        <span class="text-[13px] leading-snug text-ink-muted">{{ $cta[1] }}</span>
        @if ($creates)
            <a href="{{ route('work-orders.create') }}" data-modal class="btn btn-gold mt-1 px-6">{{ $cta[2] }}</a>
        @else
            <a href="{{ route('quick-reports.create') }}" class="btn btn-gold mt-1 px-6">{{ $cta[2] }}</a>
        @endif
    </div>
    @endunless

    <div class="{{ $creates ? '' : 'mt-7' }} flex flex-col gap-1">
        <a href="{{ route('profile.edit') }}" @if (request()->routeIs('profile.*')) aria-current="page" @endif
           class="flex items-center gap-3.5 px-3 h-11 rounded-xl text-[15px] transition-colors {{ request()->routeIs('profile.*') ? 'bg-paper font-bold text-ink-deep' : 'font-medium text-ink-muted hover:text-ink-deep hover:bg-paper/70' }}">
            <x-nav-icon name="user" class="w-5 h-5 text-ink-grey" />
            <span class="truncate">{{ $user->name }}</span>
        </a>
        @if ($role === \App\Enums\UserRole::Reception)
            {{-- Poste du comptoir partagé : passer la main d'un geste. --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3.5 px-3 min-h-11 py-2 rounded-xl text-left text-[15px] leading-snug font-medium text-ink-muted hover:text-ink-deep hover:bg-paper/70 transition-colors">
                    <x-nav-icon name="swap" class="w-5 h-5 text-ink-grey" />
                    Changer de réceptionniste
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3.5 px-3 h-11 rounded-xl text-left text-[15px] font-medium text-ink-muted hover:text-red hover:bg-danger-bg/60 transition-colors">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 text-ink-grey" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 16l-4-4 4-4M6 12h10"/></svg>
                    Se déconnecter
                </button>
            </form>
        @endif
    </div>
</div>
