// Comportements communs à toutes les pages (chargé par app.js).
//
// 1. Envoi de formulaire : le bouton passe en « chargement » et un second clic est ignoré.
// 2. Bouton retour [data-back] : revient à l'écran précédent (filtres et défilement
//    conservés) quand il appartient à l'application, sinon suit son lien.
// 3. Clavier du téléphone ouvert : classe .kb-open sur <html> (barres du bas remises
//    dans le flux, navigation basse masquée — resources/css/app.css).
// 4. Connexion Internet perdue / retrouvée : bandeau #offline-banner des layouts.
// 5. window.hkMedia : outils photo et son partagés par les écrans de signalement.

// ---------------------------------------------------------------------------
// 1. Anti double envoi
// ---------------------------------------------------------------------------
// Écouté après confirm.js (capture) et modal.js : un envoi intercepté par l'un d'eux
// (confirmation, fenêtre, fetch Alpine avec @submit.prevent) arrive ici
// « defaultPrevented » et garde son propre affichage.
document.addEventListener('submit', (event) => {
    const form = event.target;
    // data-no-loading : formulaire qui télécharge un fichier sans quitter la page.
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || 'noLoading' in form.dataset) return;

    if (form.dataset.submitting === '1') {
        event.preventDefault(); // second clic pendant l'envoi
        return;
    }
    form.dataset.submitting = '1';

    const button = event.submitter ?? form.querySelector('[type=submit]');
    // Différé : désactiver le bouton tout de suite retirerait sa valeur de l'envoi.
    setTimeout(() => {
        if (button?.classList.contains('btn')) button.dataset.state = 'loading';
        form.querySelectorAll('[type=submit]').forEach((b) => b.setAttribute('aria-disabled', 'true'));
    }, 0);
});

// Retour par le bouton « Précédent » du navigateur (page restaurée depuis le cache) :
// les boutons ne doivent pas rester bloqués en chargement.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submitting]').forEach((f) => delete f.dataset.submitting);
    document.querySelectorAll('[data-state="loading"]').forEach((b) => delete b.dataset.state);
    document.querySelectorAll('form [type=submit][aria-disabled="true"]').forEach((b) => b.removeAttribute('aria-disabled'));
});

// ---------------------------------------------------------------------------
// 2. Retour logique
// ---------------------------------------------------------------------------
document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-back]');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;

    let previous = null;
    try { previous = document.referrer ? new URL(document.referrer) : null; } catch (e) { previous = null; }
    const sameApp = previous && previous.origin === location.origin;
    // Pas de retour vers la connexion, ni vers la même page (après l'envoi d'un formulaire).
    const useful = sameApp && previous.href !== location.href && ! previous.pathname.startsWith('/login');
    if (useful && history.length > 1) {
        event.preventDefault();
        history.back();
    }
});

// ---------------------------------------------------------------------------
// 3. Clavier du téléphone
// ---------------------------------------------------------------------------
if (window.visualViewport) {
    const root = document.documentElement;
    const update = () => {
        // Le clavier réduit la zone visible d'au moins un tiers de l'écran.
        const open = window.innerHeight - window.visualViewport.height > window.innerHeight / 3;
        root.classList.toggle('kb-open', open);
    };
    window.visualViewport.addEventListener('resize', update);
    // Champ choisi : on le garde visible au-dessus du clavier.
    document.addEventListener('focusin', (event) => {
        if (!event.target.matches?.('input, textarea, select')) return;
        setTimeout(() => {
            if (root.classList.contains('kb-open')) event.target.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }, 350);
    });
}

// ---------------------------------------------------------------------------
// 4. Connexion Internet
// ---------------------------------------------------------------------------
const offlineBanner = () => document.getElementById('offline-banner');
const showConnection = () => {
    const banner = offlineBanner();
    if (banner) banner.hidden = navigator.onLine;
};
window.addEventListener('offline', showConnection);
window.addEventListener('online', () => {
    showConnection();
    window.dispatchEvent(new CustomEvent('hk-toast', { detail: 'Connexion Internet retrouvée.' }));
});
document.addEventListener('DOMContentLoaded', showConnection);

// ---------------------------------------------------------------------------
// 5. Photo et son (signalement HK, précision, inspection, signalement de la réception)
// ---------------------------------------------------------------------------
window.hkMedia = {
    /** Photo réduite sur le téléphone (1600 px, JPEG) : envoi rapide en 3G ou en wifi faible. */
    shrink(file, max = 1600) {
        return new Promise((resolve) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                const scale = Math.min(1, max / Math.max(img.width, img.height));
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(img.width * scale);
                canvas.height = Math.round(img.height * scale);
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(url);
                canvas.toBlob((blob) => resolve(blob || file), 'image/jpeg', 0.8);
            };
            img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
            img.src = url;
        });
    },

    /** Extension du message vocal selon le format produit par le navigateur. */
    extensionFor(type) {
        return type.includes('mp4') ? 'm4a' : (type.includes('ogg') ? 'ogg' : 'webm');
    },

    /** Formats d'enregistrement par ordre de préférence (Chrome/Android, puis Safari/iPhone). */
    recorderType() {
        return ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus']
            .find((t) => window.MediaRecorder?.isTypeSupported(t));
    },

    /** Bip court de confirmation (ignoré si le navigateur bloque le son). */
    beep(times = 1) {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            for (let i = 0; i < times; i++) {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.frequency.value = 880;
                gain.gain.value = 0.15;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(ctx.currentTime + i * 0.25);
                osc.stop(ctx.currentTime + i * 0.25 + 0.15);
            }
        } catch (e) { /* son indisponible */ }
    },
};
