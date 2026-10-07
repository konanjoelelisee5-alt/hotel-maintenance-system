{{-- Gabarit des pages d'erreur (403, 404, 419, 429, 500, 503) : autonome (pas de
     menu, la personne n'est pas forcément connectée), au style de l'application.
     Variables : $code, $title, $message, $icon (clé de x-hk.icon). --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name', 'Hôtel Président') }}</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-canvas text-ink-deep min-h-screen flex items-center justify-center p-4">
    <main class="w-full max-w-[460px] bg-white border border-line rounded-xl px-6 py-8 flex flex-col items-center gap-3 text-center">
        <span class="w-14 h-14 rounded-full bg-paper border border-line text-gold flex items-center justify-center">
            <x-hk.icon :name="$icon" :size="26" />
        </span>
        <span class="font-mono text-[12px] text-ink-grey">Erreur {{ $code }}</span>
        <h1 class="m-0 text-[20px] font-semibold text-navy tracking-tight">{{ $title }}</h1>
        <p class="m-0 text-[13.5px] text-ink-muted leading-relaxed">{{ $message }}</p>
        <div class="flex flex-wrap justify-center gap-2.5 mt-3">
            @if (url()->previous() !== url()->current())
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Retour</a>
            @endif
            {{-- « / » mène à la connexion, qui renvoie une personne connectée vers son accueil :
                 juste même quand la page d'erreur ne connaît pas la session (adresse inconnue). --}}
            <a href="{{ url('/') }}" class="btn btn-primary">Accueil</a>
        </div>
    </main>
</body>
</html>
