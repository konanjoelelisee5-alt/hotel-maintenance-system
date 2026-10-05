// Boîte d'envoi du signalement Housekeeping : quand le réseau coupe au moment
// d'envoyer, le signalement (champs, message vocal, photo) est gardé dans le
// téléphone (IndexedDB, qui accepte les fichiers) et repart dès que le réseau
// revient. Rien n'est gardé côté serveur tant que l'envoi n'a pas réussi.

const DB_NAME = 'hk-outbox';
const STORE = 'reports';

function openDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, 1);
        request.onupgradeneeded = () => request.result.createObjectStore(STORE, { keyPath: 'id', autoIncrement: true });
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function run(mode, action) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, mode);
        const request = action(tx.objectStore(STORE));
        tx.oncomplete = () => { db.close(); resolve(request?.result); };
        tx.onerror = () => { db.close(); reject(tx.error); };
    });
}

const outbox = {
    supported: typeof indexedDB !== 'undefined',

    /** entry : { action, label, fields: {nom: valeur}, audio?: Blob, audioName?, photo?: Blob } */
    save(entry) {
        return run('readwrite', (store) => store.add({ ...entry, savedAt: Date.now() }));
    },
    all() {
        return run('readonly', (store) => store.getAll()).then((rows) => rows || []);
    },
    remove(id) {
        return run('readwrite', (store) => store.delete(id));
    },

    /**
     * Envoie une entrée. Résultat : { ok, redirect } ; { ok: false, retry: true } si
     * le réseau manque encore ; { ok: false, retry: false, error } si le serveur refuse.
     */
    async send(entry) {
        const data = new FormData();
        Object.entries(entry.fields || {}).forEach(([name, value]) => data.append(name, value));
        // Jeton du moment : celui enregistré a pu expirer pendant la coupure.
        data.set('_token', document.querySelector('meta[name=csrf-token]')?.content || '');
        if (entry.audio) data.append('audio', entry.audio, entry.audioName || 'message-vocal.webm');
        if (entry.photo) data.append('photo', entry.photo, 'photo.jpg');
        try {
            const res = await fetch(entry.action, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-HK-Outbox': '1' },
            });
            if (res.ok) return { ok: true, redirect: (await res.json()).redirect };
            if (res.status === 422) {
                const json = await res.json();
                return { ok: false, retry: false, error: Object.values(json.errors || {})[0]?.[0] || json.message };
            }
            // Session expirée, serveur indisponible… : on garde et on réessaiera.
            return { ok: false, retry: true };
        } catch (e) {
            return { ok: false, retry: true };
        }
    },
};

export default outbox;
