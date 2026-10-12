// Notifications push : abonner cet appareil (téléphone, ordinateur) pour la personne
// connectée. Le serveur s'en sert pour copier la cloche en push
// (App\Support\PushNotifications) ; public/sw.js les affiche.
//
// - pushToggle : le bouton « Activer / Couper » (x-push-toggle) ;
// - à chaque page : un appareil déjà autorisé est rattaché à la personne connectée
//   (poste partagé : il passe à celle qui vient de se connecter).
//
// Le push n'existe qu'en https (ou sur localhost) ; sur iPhone, seulement depuis
// l'application installée sur l'écran d'accueil (iOS 16.4 et plus).

const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content || '';

const supported = () => window.isSecureContext && 'serviceWorker' in navigator
    && 'PushManager' in window && 'Notification' in window;

const isIos = () => /iPad|iPhone|iPod/.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

const installed = () => window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

/** Clé publique VAPID (base64 « url ») en octets, comme l'attend subscribe(). */
function keyBytes(base64) {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(padded), (c) => c.charCodeAt(0));
}

/** Le service worker prêt, ou null s'il ne s'enregistre pas (page sans https…). */
function worker() {
    return Promise.race([
        navigator.serviceWorker.ready,
        new Promise((resolve) => setTimeout(() => resolve(null), 8000)),
    ]);
}

async function send(method, body) {
    const response = await fetch(meta('push-subscriptions-url'), {
        method,
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': meta('csrf-token') },
        body: JSON.stringify(body),
    });
    if (!response.ok) throw new Error(`push ${method} ${response.status}`);
}

const save = (subscription) => send('POST', { ...subscription.toJSON(), contentEncoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0] });

// Rattachement à la personne connectée, une fois par page si nécessaire.
async function sync() {
    if (!supported() || !meta('vapid-public-key') || Notification.permission !== 'granted') return;
    const registration = await worker();
    const subscription = await registration?.pushManager.getSubscription();
    if (subscription && subscription.endpoint !== meta('push-endpoint')) {
        await save(subscription).catch(() => {});
    }
}
window.addEventListener('load', () => { sync(); });

export default function pushToggle() {
    return {
        // loading | unsupported | install (iPhone : installer d'abord) | denied | off | on
        state: 'loading',
        busy: false,
        failed: false,

        async init() {
            if (!supported()) {
                this.state = isIos() && !installed() ? 'install' : 'unsupported';
                return;
            }
            if (Notification.permission === 'denied') {
                this.state = 'denied';
                return;
            }
            const registration = await worker();
            const subscription = await registration?.pushManager.getSubscription();
            this.state = subscription && Notification.permission === 'granted' ? 'on' : 'off';
        },

        async enable() {
            this.busy = true;
            this.failed = false;
            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    this.state = permission === 'denied' ? 'denied' : 'off';
                    return;
                }
                const registration = await worker();
                if (!registration) throw new Error('service worker');
                const subscription = await registration.pushManager.getSubscription()
                    || await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(meta('vapid-public-key')) });
                await save(subscription);
                this.state = 'on';
                window.dispatchEvent(new CustomEvent('hk-toast', { detail: 'Notifications activées sur cet appareil.' }));
            } catch (e) {
                this.failed = true;
            } finally {
                this.busy = false;
            }
        },

        async disable() {
            this.busy = true;
            try {
                const registration = await worker();
                const subscription = await registration?.pushManager.getSubscription();
                if (subscription) {
                    await send('DELETE', { endpoint: subscription.endpoint }).catch(() => {});
                    await subscription.unsubscribe();
                }
                this.state = 'off';
                window.dispatchEvent(new CustomEvent('hk-toast', { detail: 'Notifications coupées sur cet appareil.' }));
            } finally {
                this.busy = false;
            }
        },
    };
}
