/**
 * Fenêtres (modales) chargées depuis le serveur.
 *
 * Un lien <a href="..." data-modal> ouvre sa page dans une fenêtre au lieu de quitter
 * l'écran. La page répond avec son seul contenu (en-tête X-Modal, cf. x-form-page).
 * Le formulaire de la fenêtre s'envoie sans recharger :
 *  - erreurs de saisie (422) : champs entourés de rouge + message, la fenêtre reste ;
 *  - succès : le serveur renvoie { redirect } (HandleModalRequests) et on s'y rend,
 *    par ex. la fiche de l'OT créé.
 * Sans JavaScript, ou si quelque chose échoue, le lien mène à la page complète.
 * Mise en forme et animations : resources/css/app.css (#remote-modal, .modal-form).
 */

const HEADER = { 'X-Modal': '1', 'X-Requested-With': 'XMLHttpRequest' };
const CLOSE_DELAY = 220; // durée de l'animation de fermeture (cf. app.css)

const root = () => document.getElementById('remote-modal');
const content = () => root()?.querySelector('[data-modal-content]');

let opener = null;
let closeTimer = null;

const SKELETON = `
    <div class="modal-skeleton" aria-busy="true" aria-label="Chargement">
        <div class="flex items-start gap-3.5 px-6 pt-5 pb-4 border-b border-line">
            <span class="w-10 h-10 !rounded-[10px]"></span>
            <div class="flex-1 space-y-2 pt-0.5"><span class="h-2.5 w-24"></span><span class="h-4 w-56"></span></div>
        </div>
        <div class="px-6 py-6 space-y-5">
            ${'<div class="space-y-2"><span class="h-2.5 w-28"></span><span class="h-[42px] w-full !rounded-[9px]"></span></div>'.repeat(3)}
        </div>
    </div>`;

function open(url) {
    const shell = root();
    if (!shell) {
        window.location.href = url;
        return;
    }

    clearTimeout(closeTimer);
    content().innerHTML = SKELETON;
    shell.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    // Laisse le navigateur peindre l'état « fermé » avant d'animer l'ouverture.
    requestAnimationFrame(() => requestAnimationFrame(() => { shell.dataset.state = 'open'; }));

    fetch(url, { headers: { ...HEADER, Accept: 'text/html' }, credentials: 'same-origin' })
        .then(async (response) => {
            // Session expirée, mot de passe à confirmer... : la page complète s'en charge.
            if (response.headers.get('content-type')?.includes('application/json')) {
                const data = await response.json();
                window.location.href = data.redirect ?? url;
                return;
            }
            if (!response.ok || response.redirected) {
                window.location.href = url;
                return;
            }
            content().innerHTML = await response.text();
            content().scrollTop = 0;
            content().querySelector('[autofocus], .modal-form input:not([type=hidden]), .modal-form select, .modal-form textarea')
                ?.focus({ preventScroll: true });
        })
        .catch(() => { window.location.href = url; });
}

function close() {
    const shell = root();
    if (!shell || shell.classList.contains('hidden') || shell.dataset.state === 'closed') return;

    shell.dataset.state = 'closed';
    document.body.classList.remove('overflow-hidden');
    closeTimer = setTimeout(() => {
        shell.classList.add('hidden');
        content().innerHTML = '';
    }, CLOSE_DELAY);
    opener?.focus();
    opener = null;
}

/** "files.0" → champ "files[]" ; "a.b" → "a[b]" ; sinon le nom tel quel. */
function fieldFor(form, key) {
    const [base, ...rest] = key.split('.');
    const bracketed = base + rest.map((part) => `[${part}]`).join('');
    return form.querySelector(`[name="${key}"], [name="${bracketed}"], [name="${base}[]"], [name="${base}"]`);
}

function clearErrors(form) {
    form.querySelectorAll('[data-modal-error]').forEach((el) => el.remove());
    form.querySelectorAll('[aria-invalid="true"]').forEach((el) => el.removeAttribute('aria-invalid'));
}

function errorLine(message) {
    const p = document.createElement('p');
    p.dataset.modalError = '';
    p.textContent = message;
    return p;
}

/** Bandeau en tête du formulaire (résumé des erreurs, problème réseau, droits...). */
function banner(form, message) {
    const box = document.createElement('div');
    box.dataset.modalError = '';
    box.dataset.modalBanner = ''; // mise en forme : .modal-form [data-modal-banner] (app.css)
    box.setAttribute('role', 'alert');
    box.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5h.01"/></svg><span></span>';
    box.querySelector('span').textContent = message;
    form.prepend(box);
    box.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

function showErrors(form, errors) {
    let first = null;
    const entries = Object.entries(errors);
    for (const [key, messages] of entries) {
        const field = fieldFor(form, key);
        if (field) {
            field.setAttribute('aria-invalid', 'true');
            (field.closest('div') ?? field.parentElement).appendChild(errorLine(messages[0]));
            first ??= field;
        }
    }
    banner(form, entries.length > 1
        ? `${entries.length} champs sont à corriger, signalés en rouge ci-dessous.`
        : 'Un champ est à corriger, signalé en rouge ci-dessous.');
    first?.focus({ preventScroll: true });
    first?.scrollIntoView({ block: 'center', behavior: 'smooth' });
}

/** Bouton d'envoi : « Enregistrement… » avec un cercle qui tourne, puis « Enregistré ». */
function setBusy(button, state) {
    if (!button) return;
    if (state === 'busy') {
        button.dataset.label ??= button.innerHTML;
        button.setAttribute('disabled', '');
        button.innerHTML = '<span class="modal-spinner" aria-hidden="true"></span> Enregistrement…';
    } else if (state === 'done') {
        button.innerHTML = '✓ Enregistré';
    } else {
        button.removeAttribute('disabled');
        if (button.dataset.label) button.innerHTML = button.dataset.label;
    }
}

async function submit(form, button) {
    setBusy(button, 'busy');
    clearErrors(form);

    try {
        const response = await fetch(form.action, {
            method: 'POST', // _method (PUT, PATCH...) voyage dans le FormData
            body: new FormData(form),
            headers: { ...HEADER, Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (response.status === 422) {
            showErrors(form, (await response.json()).errors ?? {});
            setBusy(button, 'idle');
            return;
        }
        if (response.status === 401 || response.status === 419) {
            banner(form, 'Votre session a expiré : la page va se recharger.');
            setTimeout(() => window.location.reload(), 1500);
            return;
        }
        if (!response.ok) {
            banner(form, response.status === 403
                ? "Vous n'avez pas le droit de faire cette action."
                : `Une erreur est survenue (${response.status}). Réessayez.`);
            setBusy(button, 'idle');
            return;
        }

        const data = await response.json().catch(() => null);
        setBusy(button, 'done');
        if (data?.redirect) {
            window.location.href = data.redirect;
        } else {
            window.location.reload();
        }
    } catch {
        banner(form, 'Connexion impossible. Vérifiez le réseau et réessayez.');
        setBusy(button, 'idle');
    }
}

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-modal]');
    // Ctrl/Cmd/Maj-clic ou clic molette : l'utilisateur veut un nouvel onglet.
    if (link && !(event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0)) {
        event.preventDefault();
        opener = link;
        open(link.href);
        return;
    }

    // Un « Annuler » qui est un lien (vers la liste, en page complète) ferme seulement la fenêtre.
    if (event.target.closest('#remote-modal [data-modal-close]')) {
        event.preventDefault();
        close();
    }
});

document.addEventListener('submit', (event) => {
    // defaultPrevented : la question « Êtes-vous sûr ? » (onsubmit) a reçu « Annuler ».
    const form = event.target.closest('#remote-modal form');
    if (form && !event.defaultPrevented) {
        event.preventDefault();
        submit(form, event.submitter ?? form.querySelector('[type=submit]'));
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') close();
});
