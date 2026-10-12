{{-- Invitation « Installer l'application » (PWA), dans la pile du bas de layouts.sheet
     (au-dessus de la barre du bas, sous le message de mise à jour) :
     - Chrome, Edge, Samsung (Android, ordinateur) : bouton « Installer », qui ouvre la fenêtre
       d'installation du navigateur (évènement gardé par layouts.partials.pwa-head) ;
     - iPhone et iPad (Safari) : pas de bouton possible, on explique le geste « Partager ».
     Jamais dans l'application déjà installée. « Plus tard » : on ne la remontre pas avant
     DISMISS_DAYS jours (mémoire du navigateur). --}}
<div x-data="{
        DISMISS_DAYS: 30,
        KEY: 'pwa-install-dismissed',
        show: false,
        ios: false,
        init() {
            const installed = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
            if (installed || this.dismissedRecently()) return;
            const ua = navigator.userAgent;
            const iosSafari = (/iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1))
                && /Safari/.test(ua) && ! /CriOS|FxiOS|EdgiOS/.test(ua);
            if (iosSafari) { this.ios = true; setTimeout(() => this.show = true, 1500); return; }
            const ready = () => { this.show = true; };
            if (window.pwaInstallPrompt) ready(); else window.addEventListener('pwa-installable', ready, { once: true });
            window.addEventListener('appinstalled', () => { this.show = false; window.pwaInstallPrompt = null; });
        },
        dismissedRecently() {
            try { return Date.now() - Number(localStorage.getItem(this.KEY) || 0) < this.DISMISS_DAYS * 864e5; } catch (e) { return false; }
        },
        later() {
            this.show = false;
            try { localStorage.setItem(this.KEY, String(Date.now())); } catch (e) {}
        },
        async install() {
            const prompt = window.pwaInstallPrompt;
            if (! prompt) return;
            prompt.prompt();
            const { outcome } = await prompt.userChoice;
            window.pwaInstallPrompt = null;
            if (outcome === 'accepted') this.show = false; else this.later();
        },
     }"
     x-show="show" x-cloak
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     role="dialog" aria-labelledby="pwa-install-title"
     class="pointer-events-auto">
    <div class="bg-white border border-line rounded-[22px] shadow-[0_18px_44px_-12px_rgba(23,25,31,.35)] p-4 flex gap-3.5">
        <img src="{{ asset('images/pwa/icon-192.png') }}" alt="" class="w-12 h-12 flex-shrink-0 rounded-[14px]">
        <div class="min-w-0 flex-1">
            <p id="pwa-install-title" class="m-0 text-[15px] font-bold text-ink-deep">Installer l'application</p>
            <p x-show="! ios" class="m-0 mt-0.5 text-[13px] leading-snug text-ink-muted">Une icône sur l'écran d'accueil, en plein écran, sans chercher l'adresse.</p>
            <p x-show="ios" class="m-0 mt-0.5 text-[13px] leading-snug text-ink-muted">
                Touchez
                <svg viewBox="0 0 24 24" class="inline w-4 h-4 -mt-1 text-blue" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="Partager"><path d="M12 3v12M8 7l4-4 4 4M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/></svg>
                en bas de Safari, puis <strong class="text-ink-deep">« Sur l'écran d'accueil »</strong>.
            </p>
            <div class="mt-3 flex gap-2">
                <button type="button" x-show="! ios" @click="install()" class="btn btn-primary flex-1">Installer</button>
                <button type="button" @click="later()" class="btn btn-ghost" :class="ios && 'flex-1'" x-text="ios ? 'J\'ai compris' : 'Plus tard'">Plus tard</button>
            </div>
        </div>
    </div>
</div>
