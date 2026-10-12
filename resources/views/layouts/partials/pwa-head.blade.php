{{-- Application installable (PWA), dans le <head> des deux mises en page (sheet, guest) :
     manifeste (public/manifest.json), icônes de l'écran d'accueil (public/images/pwa,
     iPhone compris) et service worker (public/sw.js : page « Pas de connexion », styles
     et images gardés dans le téléphone). Le service worker n'existe qu'en https (ou sur
     localhost). L'invitation « Installer l'application » est x-pwa-install. --}}
<meta name="app-version" content="{{ \App\Support\AppVersion::current() }}">
@auth
    {{-- Notifications push (resources/js/push.js) : clé publique, adresse d'abonnement, et
         l'appareil déjà rattaché à cette session (pour ne le renvoyer qu'au besoin). --}}
    @if (config('webpush.vapid.public_key'))
        <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
        <meta name="push-subscriptions-url" content="{{ route('push-subscriptions.store') }}">
        <meta name="push-endpoint" content="{{ session('push_endpoint') }}">
    @endif
@endauth
<link rel="manifest" href="{{ asset('manifest.json') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/pwa/favicon-32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}">
<meta name="application-name" content="Maintenance">
<meta name="apple-mobile-web-app-title" content="Maintenance">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<script>
    // Chrome propose l'installation par cet évènement, parfois avant qu'Alpine ne démarre :
    // on le garde pour le bouton « Installer » (x-pwa-install).
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        window.pwaInstallPrompt = event;
        window.dispatchEvent(new Event('pwa-installable'));
    });
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', () => navigator.serviceWorker.register(@js(asset('sw.js'))).catch(() => {}));
    }
</script>
