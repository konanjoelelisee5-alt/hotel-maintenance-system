/**
 * Fenêtres (modales) chargées depuis le serveur.
 *
 * Un lien <a href="..." data-modal> ouvre sa page dans une fenêtre au lieu de quitter
 * l'écran. La page répond avec son seul contenu (en-tête X-Modal, cf. create.blade.php).
 * Le formulaire de la fenêtre s'envoie sans recharger :
 *  - erreurs de saisie (422) : affichées sous les champs, la fenêtre reste ouverte ;
 *  - succès : le serveur renvoie { redirect } (HandleModalRequests) et on s'y rend,
 *    par ex. la fiche de l'OT créé.
 * Sans JavaScript, ou si quelque chose échoue, le lien mène à la page complète.
 */

const HEADER = { 'X-Modal': '1', 'X-Requested-With': 'XMLHttpRequest' };

const root = () => document.getElementById('remote-modal');
const content = () => root()?.querySelector('[data-modal-content]');

let opener = null;

function open(url) {
    const shell = root();
    if (!shell) {
        window.location.href = url;
        return;
    }

    content().innerHTML = '<div class="p-10 text-center text-sm text-ink-grey">Chargement…</div>';
    shell.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

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
            content().querySelector('[autofocus], input:not([type=hidden]), select, textarea')?.focus();
        })
        .catch(() => { window.location.href = url; });
}

function close() {
    const shell = root();
    if (!shell || shell.classList.contains('hidden')) return;

    shell.classList.add('hidden');
    content().innerHTML = '';
    document.body.classList.remove('overflow-hidden');
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
}

function errorLine(message) {
    const p = document.createElement('p');
    p.dataset.modalError = '';
    p.className = 'text-sm text-red-600 mt-2';
    p.textContent = message;
    return p;
}

function showErrors(form, errors) {
    let first = null;
    for (const [key, messages] of Object.entries(errors)) {
        const field = fieldFor(form, key);
        const line = errorLine(messages[0]);
        if (field) {
            (field.closest('div') ?? field.parentElement).appendChild(line);
            first ??= field;
        } else {
            form.prepend(line);
        }
    }
    first?.focus();
}

async function submit(form) {
    const button = form.querySelector('[type=submit]');
    button?.setAttribute('disabled', '');
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
            return;
        }
        if (response.status === 401 || response.status === 419) {
            form.prepend(errorLine('Votre session a expiré : la page va se recharger.'));
            setTimeout(() => window.location.reload(), 1500);
            return;
        }
        if (!response.ok) {
            form.prepend(errorLine(response.status === 403
                ? "Vous n'avez pas le droit de faire cette action."
                : `Une erreur est survenue (${response.status}). Réessayez.`));
            return;
        }

        const data = await response.json().catch(() => null);
        if (data?.redirect) {
            window.location.href = data.redirect;
        } else {
            window.location.reload();
        }
    } catch {
        form.prepend(errorLine('Connexion impossible. Vérifiez le réseau et réessayez.'));
    } finally {
        button?.removeAttribute('disabled');
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

    if (event.target.closest('#remote-modal [data-modal-close]')) {
        close();
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest('#remote-modal form');
    if (form) {
        event.preventDefault();
        submit(form);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') close();
});
