<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Hôtel Président') }} — Service Technique</title>
        <link rel="icon" type="image/jpeg" href="{{ asset('images/logo-hotel-president-icon.jpg') }}">
        <meta name="theme-color" content="#E9E9EE">
        @include('layouts.partials.pwa-head')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @include('partials.splash')
        <div class="min-h-screen flex">

            <!-- Panneau visuel (masqué sur mobile) -->
            <div class="hidden lg:flex lg:w-1/2 bg-navy-800 relative overflow-hidden flex-col justify-between p-12">
                <!-- Photo de l'Hôtel Président (public/images/login.*), découpée depuis la droite :
                     le titre peint à gauche de l'image sort du cadre, le bâtiment reste entier. -->
                <picture>
                    <source srcset="{{ asset('images/login.webp') }}" type="image/webp">
                    <img src="{{ asset('images/login.jpg') }}" alt="" class="absolute inset-0 w-full h-full object-cover object-[70%_center]">
                </picture>
                <!-- Voiles sombres (haut, bas et côté gauche) : la présentation reste lisible sur la photo. -->
                <div class="absolute inset-0 bg-gradient-to-b from-black/55 via-transparent to-black/80"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/15 to-transparent"></div>
                <!-- Bas plus sombre : le pied de page se lit, et la barre peinte dans l'image
                     (« 68 % ») disparaît. -->
                <!-- Bord gauche : la fin du titre peint dans l'image (« …NT ») s'efface. -->
                <div class="absolute inset-y-0 left-0 w-[24%] bg-gradient-to-r from-[#07090d] via-[#07090d]/80 to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 h-[30%] bg-gradient-to-t from-[#07090d] via-[#07090d]/85 to-transparent"></div>

                <div class="relative z-10">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/logo-hotel-president-icon.jpg') }}" alt="Hôtel Président"
                             class="h-12 w-12 rounded-full object-cover shadow">
                        <span class="text-white font-semibold text-2xl tracking-wide">
                            Hôtel Président
                        </span>
                    </div>
                </div>

                <div class="relative z-10 text-white">
                    <h1 class="text-3xl font-semibold leading-tight mb-4">
                        Plateforme de gestion<br>du service technique
                    </h1>
                    <p class="text-navy-200 text-sm leading-relaxed max-w-md">
                        Suivi des interventions, planification, maintenance et qualité de service —
                        centralisés pour une exploitation fluide et réactive.
                    </p>
                </div>

                <div class="relative z-10 flex items-center gap-3">
                    <img src="{{ asset('images/logo-sonapie.jpg') }}" alt="SONAPIE"
                         class="h-8 rounded bg-white p-1">
                    <p class="text-navy-300 text-xs leading-snug">
                        © {{ date('Y') }} Hôtel Président — Usage interne uniquement · Version {{ \App\Support\AppVersion::current() }}<br>
                        Une propriété de la SONAPIE (Société Nationale de Gestion du Patrimoine Immobilier de l'État)
                    </p>
                </div>
            </div>

            <!-- Formulaire -->
            <div class="w-full lg:w-1/2 flex flex-col items-center justify-center p-4 sm:p-6 bg-canvas">
                <div class="w-full max-w-sm">

                    <!-- Logo + titre : visibles uniquement sur mobile (le panneau desktop a déjà son propre logo) -->
                    <div class="flex lg:hidden flex-col items-center gap-3 mb-8">
                        <span class="w-20 h-20 rounded-full bg-navy flex items-center justify-center shadow-sm">
                            <span class="text-gold text-2xl font-bold">HP</span>
                        </span>
                        <h1 class="text-navy text-[21px] font-semibold text-center leading-tight">
                            Hôtel Président — Maintenance
                        </h1>
                    </div>

                    <div class="bg-paper rounded-2xl shadow-sm border border-line p-6 sm:p-8">
                        {{ $slot }}
                    </div>

                    {{-- Téléphone : le pied du panneau de gauche (avec la version) n'y est pas. --}}
                    <p class="lg:hidden mt-5 mb-0 flex items-center justify-center gap-2 text-[12px] text-ink-grey">
                        <span class="font-mono">Version {{ \App\Support\AppVersion::current() }}</span>
                        @if (\App\Support\AppVersion::isPreRelease())
                            <span class="px-1.5 py-px rounded-md text-[10.5px] font-bold uppercase tracking-wide bg-warn-bg text-warn-ink">Test</span>
                        @endif
                    </p>

                </div>
            </div>

        </div>
    </body>
</html>