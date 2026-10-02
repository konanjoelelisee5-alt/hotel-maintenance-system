/**
 * Confirmation des actions sensibles (annuler, désactiver, mettre hors service...).
 *
 * <form data-confirm="Ce que l'action va provoquer." data-confirm-title="…"
 *       data-confirm-label="Mettre hors service" data-confirm-tone="danger">
 *
 * Le formulaire n'est envoyé qu'après « Confirmer » dans la fenêtre #confirm-dialog
 * (x-confirm-dialog, incluse par le layout). Écouté en phase de capture pour passer
 * avant l'envoi en fenêtre de modal.js. Sans la fenêtre (page sans layout), on
 * retombe sur le confirm() du navigateur : jamais d'envoi sans confirmation.
 */

const DANGER_BUTTON = 'btn-danger-solid';
const PRIMARY_BUTTON = 'btn-primary';

function ask(form) {
    const dialog = document.getElementById('confirm-dialog');
    const message = form.dataset.confirm;
    if (!dialog || typeof dialog.showModal !== 'function') {
        return Promise.resolve(window.confirm(message));
    }

    const danger = form.dataset.confirmTone === 'danger';
    dialog.querySelector('#confirm-dialog-title').textContent = form.dataset.confirmTitle || 'Confirmer l’action';
    dialog.querySelector('#confirm-dialog-message').textContent = message;
    const accept = dialog.querySelector('[data-confirm-accept]');
    accept.textContent = form.dataset.confirmLabel || 'Confirmer';
    accept.classList.toggle(DANGER_BUTTON, danger);
    accept.classList.toggle(PRIMARY_BUTTON, !danger);
    dialog.querySelector('[data-confirm-icon]').classList.toggle('hidden', !danger);

    dialog.returnValue = '';
    dialog.showModal();

    return new Promise((resolve) => {
        dialog.addEventListener('close', () => resolve(dialog.returnValue === 'confirm'), { once: true });
    });
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;

    if (form.dataset.confirmed === '1') {
        delete form.dataset.confirmed; // la prochaine fois, on redemande
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    const submitter = event.submitter;

    ask(form).then((ok) => {
        if (!ok) return;
        form.dataset.confirmed = '1';
        form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
    });
}, true);
