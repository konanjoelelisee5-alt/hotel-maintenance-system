// Service worker de l'application installée (PWA).
//
// Ce qu'il garde dans le téléphone : seulement ce qui est public et ne change pas
// (styles et scripts de public/build, dont le nom change à chaque version ; images ;
// polices) et la page « Pas de connexion ». Jamais les pages de l'application : elles
// contiennent des données de l'hôtel et un jeton de sécurité qui expire.
//
// - page demandée sans réseau : la page « Pas de connexion » (offline.html) ;
// - envois (POST…), pièces jointes, messages vocaux : rien n'est intercepté. Les
//   signalements HK faits sans réseau sont gardés par hk-outbox.js, pas ici.
// - notifications push (App\Notifications\BellPushNotification) : affichées même
//   application fermée ; le clic ouvre la page de la notification.
//
// Changer VERSION à chaque modification de ce fichier ou de offline.html.

const VERSION = 'v3';
const CACHE = `hp-maintenance-${VERSION}`;
const BASE = new URL(self.registration.scope).pathname; // « / » ou « /sous-dossier/ »
const OFFLINE = `${BASE}offline.html`;

const PRECACHE = [
    OFFLINE,
    `${BASE}images/pwa/icon-192.png`,
    `${BASE}images/logo-hotel-president-icon.jpg`,
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k.startsWith('hp-maintenance-') && k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

/** Copie du cache d'abord, réseau ensuite (fichiers dont le nom change à chaque version). */
async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;
    const response = await fetch(request);
    if (response.ok || response.type === 'opaque') {
        const cache = await caches.open(CACHE);
        cache.put(request, response.clone());
    }
    return response;
}

/** Copie du cache tout de suite, mise à jour en arrière-plan (images, polices). */
async function staleWhileRevalidate(request, event) {
    const cache = await caches.open(CACHE);
    const cached = await cache.match(request);
    const network = fetch(request)
        .then((response) => {
            if (response.ok || response.type === 'opaque') cache.put(request, response.clone());
            return response;
        })
        .catch(() => cached);
    if (cached) {
        event.waitUntil(network);
        return cached;
    }
    return network;
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);

    // Pages : toujours le réseau ; sans réseau, la page « Pas de connexion ».
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE)));
        return;
    }

    if (url.origin === self.location.origin) {
        if (url.pathname.startsWith(`${BASE}build/`)) {
            event.respondWith(cacheFirst(request));
        } else if (url.pathname.startsWith(`${BASE}images/`)) {
            event.respondWith(staleWhileRevalidate(request, event));
        }
        return;
    }

    if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
        event.respondWith(staleWhileRevalidate(request, event));
    }
});

// ---------------------------------------------------------------------------
// Notifications push
// ---------------------------------------------------------------------------
self.addEventListener('push', (event) => {
    let message = {};
    try { message = event.data ? event.data.json() : {}; } catch (e) { message = { body: event.data?.text() }; }

    event.waitUntil(self.registration.showNotification(message.title || 'Maintenance', {
        body: message.body || '',
        icon: message.icon,
        badge: message.badge,
        tag: message.tag,
        data: message.data || {},
        vibrate: [200, 100, 200],
    }));
});

// Clic : on reprend une fenêtre de l'application déjà ouverte, sinon on en ouvre une.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || BASE;

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const open = windows.find((w) => new URL(w.url).origin === self.location.origin);
        if (open) {
            await open.focus();
            // navigate() échoue sur une fenêtre pas encore gérée par ce service worker.
            return open.navigate(url).catch(() => self.clients.openWindow(url));
        }
        return self.clients.openWindow(url);
    })());
});
