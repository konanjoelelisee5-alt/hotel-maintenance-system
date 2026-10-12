{{-- « Nouvelle version disponible », dans la pile du bas de layouts.sheet. Une page restée
     ouverte (écran du comptoir, application installée remise au premier plan) compare sa
     version (<meta name="app-version">) à celle du serveur (GET /version) : toutes les
     CHECK_MINUTES, au retour sur l'onglet et au retour du réseau. « Actualiser » recharge
     la page. Absent des parcours en étapes ($focus) : on ne coupe pas une saisie. --}}
<div x-data="{
        CHECK_MINUTES: 5,
        show: false,
        latest: null,
        mine: document.querySelector('meta[name=app-version]')?.content,
        lastCheck: 0,
        init() {
            setInterval(() => this.check(), this.CHECK_MINUTES * 60000);
            document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') this.check(); });
            window.addEventListener('online', () => this.check());
        },
        async check() {
            if (this.show || ! navigator.onLine || Date.now() - this.lastCheck < 30000) return;
            this.lastCheck = Date.now();
            try {
                const response = await fetch(@js(route('app.version')), { headers: { Accept: 'application/json' }, cache: 'no-store' });
                if (! response.ok) return;
                const { version } = await response.json();
                if (version && this.mine && version !== this.mine) { this.latest = version; this.show = true; }
            } catch (e) {}
        },
     }"
     x-show="show" x-cloak
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
     role="status" class="pointer-events-auto">
    <div class="bg-ink-deep text-white rounded-[22px] shadow-[0_18px_44px_-12px_rgba(23,25,31,.45)] p-4 flex items-center gap-3.5">
        <span class="w-10 h-10 flex-shrink-0 rounded-full bg-white/10 flex items-center justify-center" aria-hidden="true">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
        </span>
        <div class="min-w-0 flex-1">
            <p class="m-0 text-[14.5px] font-bold">Nouvelle version disponible</p>
            <p class="m-0 text-[12.5px] text-white/75">
                <span class="font-mono" x-text="latest"></span> ·
                <a href="{{ route('release-notes.index') }}" class="underline underline-offset-2 hover:text-white">Voir les nouveautés</a>
            </p>
        </div>
        <button type="button" @click="location.reload()" class="btn bg-white !text-ink-deep hover:bg-paper flex-shrink-0">Actualiser</button>
    </div>
</div>
