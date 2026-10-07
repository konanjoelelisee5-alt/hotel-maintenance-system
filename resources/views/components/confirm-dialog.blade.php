{{-- Fenêtre de confirmation des actions sensibles, au style de l'application (remplace
     le confirm() du navigateur). Pilotée par resources/js/confirm.js : tout formulaire
     portant data-confirm="message" passe par elle avant d'être envoyé.
     Attributs facultatifs : data-confirm-title, data-confirm-label, data-confirm-tone="danger". --}}
<dialog id="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message"
        class="w-[min(440px,calc(100vw-32px))] p-0 rounded-2xl border border-line bg-white text-ink-deep
               shadow-[0_28px_70px_-18px_rgba(11,27,44,.55)] backdrop:bg-navy-dark/55 backdrop:backdrop-blur-[3px]">
    <form method="dialog" class="flex flex-col">
        <div class="flex items-start gap-3.5 px-6 pt-6 pb-2">
            <span data-confirm-icon class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 bg-danger-bg text-red">
                <x-nav-icon name="alert" class="w-5 h-5" />
            </span>
            <div class="flex flex-col gap-1.5 min-w-0 pt-1">
                <h2 id="confirm-dialog-title" class="m-0 text-[16.5px] font-semibold text-navy">Confirmer l'action</h2>
                <p id="confirm-dialog-message" class="m-0 text-[13.5px] leading-relaxed text-ink-body"></p>
            </div>
        </div>
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 px-6 pt-4 pb-6">
            <button type="submit" value="cancel" class="btn btn-secondary" autofocus>Annuler</button>
            <button type="submit" value="confirm" data-confirm-accept class="btn btn-danger-solid">Confirmer</button>
        </div>
    </form>
</dialog>
