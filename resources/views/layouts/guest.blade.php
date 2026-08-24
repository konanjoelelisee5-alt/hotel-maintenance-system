<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Hôtel Président') }} — Service Technique</title>
        <link rel="icon" type="image/jpeg" href="{{ asset('images/logo-hotel-president-icon.jpg') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex">

            <!-- Panneau visuel (masqué sur mobile) -->
            <div class="hidden lg:flex lg:w-1/2 bg-navy-800 relative overflow-hidden flex-col justify-between p-12">
                <!-- Motif décoratif discret -->
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px); background-size: 32px 32px;"></div>

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
                        © {{ date('Y') }} Hôtel Président — Usage interne uniquement<br>
                        Une propriété de la SONAPIE (Société Nationale de Gestion du Patrimoine Immobilier de l'État)
                    </p>
                </div>
            </div>

            <!-- Formulaire -->
            <div class="w-full lg:w-1/2 flex flex-col items-center justify-center p-6 bg-slate-50">
                <div class="w-full max-w-sm">

                    <!-- Logo visible uniquement sur mobile -->
                    <div class="flex lg:hidden items-center justify-center gap-3 mb-8">
                        <img src="{{ asset('images/logo-hotel-president-icon.jpg') }}" alt="Hôtel Président"
                             class="h-10 w-10 rounded-full object-cover shadow">
                        <span class="text-navy-800 font-semibold text-xl tracking-wide">
                            Hôtel Président
                        </span>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
                        {{ $slot }}
                    </div>

                </div>
            </div>

        </div>
    </body>
</html>