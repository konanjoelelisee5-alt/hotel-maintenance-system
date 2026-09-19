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
<body class="font-sans antialiased bg-canvas text-[#14202B]">
    <div class="min-h-screen flex items-stretch">

        {{-- Sidebar desktop --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-[252px] lg:flex-shrink-0 bg-navy text-white p-3.5 gap-5">
            <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="flex items-center gap-2.5 px-1.5 py-1">
                <span class="w-8 h-8 rounded-full bg-gold flex items-center justify-center text-[12px] font-bold text-navy">HP</span>
                <span class="text-[13.5px] font-semibold leading-tight">Hôtel Président<br>Maintenance</span>
            </a>

            <div class="flex items-center gap-2.5 px-1.5">
                <span class="w-[34px] h-[34px] rounded-[9px] bg-gold flex items-center justify-center text-[13px] font-bold text-navy flex-shrink-0">{{ auth()->user()->initialsOrGenerated() }}</span>
                <span class="flex flex-col gap-0.5 min-w-0">
                    <span class="text-[13.5px] font-semibold truncate">{{ auth()->user()->name }}</span>
                    <span class="text-[11px] text-[#8FA3B8] truncate">{{ auth()->user()->role_label }}</span>
                </span>
            </div>

            <nav class="flex flex-col gap-0.5">
                <div class="text-[10.5px] tracking-wide text-[#6E8399] font-semibold uppercase px-2 pb-1.5">Opérations</div>
                @foreach ($nav['ops'] as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="flex items-center justify-between gap-2 px-2.5 py-2 rounded-lg text-[13.5px] font-medium {{ $active ? 'bg-white/10 text-white' : 'text-[#DCE5EE] hover:bg-white/[.06]' }}">
                        <span class="flex items-center gap-2.5">
                            @if (! empty($item['icon']))
                                <x-nav-icon :name="$item['icon']" class="w-4 h-4 {{ $active ? 'text-gold' : 'text-[#8FA3B8]' }}" />
                            @else
                                <span class="w-1.5 h-1.5 rounded-full {{ $active ? 'bg-gold' : 'bg-[#3F5D7A]' }}"></span>
                            @endif
                            {{ $item['label'] }}
                        </span>
                        @if (! empty($item['badge']))
                            <span class="text-[11px] font-semibold text-gold">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            @if (! empty($nav['admin']))
                <nav class="flex flex-col gap-0.5">
                    <div class="text-[10.5px] tracking-wide text-[#6E8399] font-semibold uppercase px-2 pb-1.5">{{ $nav['adminTitle'] }}</div>
                    @foreach ($nav['admin'] as $item)
                        @php $active = request()->routeIs($item['route']); @endphp
                        <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13px] font-medium {{ $active ? 'bg-white/10 text-white' : 'text-[#B9C7D6] hover:bg-white/[.06] hover:text-white' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            @endif

            <div class="mt-auto flex flex-col gap-2">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg bg-white/[.06] text-[#C3D0DE] text-[12.5px] font-medium hover:bg-white/[.12]">
                    Mon profil
                </a>
                <div class="px-2.5 py-2.5 rounded-[9px] bg-white/[.06] text-[11px] text-[#9FB2C5] leading-relaxed">
                    Connecté en tant que {{ auth()->user()->role_label }}
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-2.5 py-2 rounded-lg text-[12.5px] font-medium text-[#E7B3AE] hover:bg-white/[.06]">Se déconnecter</button>
                </form>
            </div>
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

    {{-- Barre de navigation mobile --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-line flex gap-0.5 px-2.5 py-1.5">
        @foreach ($bottomNav as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="flex-1 flex flex-col items-center gap-0.5 py-1.5 rounded-[10px] {{ $active ? 'bg-paper' : '' }}">
                <span class="w-1 h-1 rounded-full {{ $active ? 'bg-gold' : 'bg-transparent' }}"></span>
                <x-nav-icon :name="$item['icon'] ?? 'home'" class="w-5 h-5 {{ $active ? 'text-navy' : 'text-[#A8A296]' }}" />
                <span class="text-[11px] whitespace-nowrap {{ $active ? 'font-semibold text-navy' : 'font-medium text-ink-grey' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
    @stack('scripts')
</body>
</html>
