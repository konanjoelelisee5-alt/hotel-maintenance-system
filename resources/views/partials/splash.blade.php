{{-- Écran d'ouverture : affiché une fois, à la première page de l'application dans l'onglet
     (connexion ou accueil), puis plus jusqu'à la prochaine ouverture (sessionStorage).
     Placé tout en haut de <body> : le script décide avant l'affichage, sans clignotement.

     Le texte est du vrai texte posé sur une photo seule (public/images/splash-photo, tirée de
     login.jpg sans ses inscriptions) : il se replace selon l'écran au lieu d'être recadré.
     - écran plus large que haut (ordinateur, tablette, téléphone couché) : texte à gauche sur
       le bleu nuit, photo à droite fondue dans le bleu ;
     - écran plus haut que large (téléphone debout) : texte en haut, centré, photo en bas ;
       petit téléphone : la phrase sous « Gestion des ordres de travail » est retirée.
     Toutes les tailles suivent une unité --u (hauteur ou largeur de l'écran, la plus petite).
     La barre avance avec le chargement réel de la page ; la ligne dorée dit l'étape. --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600&display=swap">
<style>
    #splash { display: none; }
    html.splash-on #splash {
        --navy: #0a1322; --gold: #d9b26a; --gold-soft: rgba(217,178,106,.55);
        /* Unité de taille : 1 % de la hauteur, sans dépasser 0,62 % de la largeur. */
        --u: min(1vh, .62vw);
        display: block; position: fixed; inset: 0; z-index: 100; overflow: hidden;
        background: var(--navy); color: #fff; transition: opacity .7s ease;
        font-family: Manrope, system-ui, -apple-system, 'Segoe UI', sans-serif;
    }
    html.splash-on body { overflow: hidden; }
    #splash.is-leaving { opacity: 0; pointer-events: none; }

    /* Photo : à droite, fondue vers la gauche dans le bleu nuit. */
    #splash .sp-photo { position: absolute; top: 0; bottom: 0; right: 0; width: 70%; }
    #splash .sp-photo img { width: 100%; height: 100%; object-fit: cover; object-position: 28% 55%; display: block; }
    #splash .sp-photo::before {
        content: ''; position: absolute; inset: 0; z-index: 1;
        background:
            linear-gradient(90deg, var(--navy) 0%, rgba(10,19,34,.92) 16%, rgba(10,19,34,.45) 38%, rgba(10,19,34,0) 62%),
            linear-gradient(0deg, rgba(10,19,34,.55) 0%, rgba(10,19,34,0) 30%);
    }

    /* Filets dorés décoratifs (coins), comme sur la maquette. */
    #splash .sp-arcs { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 2; pointer-events: none; }
    #splash .sp-arcs path { fill: none; stroke: var(--gold-soft); stroke-width: 1.2; vector-effect: non-scaling-stroke; }

    /* Mots en haut à droite. */
    #splash .sp-tags {
        position: absolute; z-index: 3; top: max(calc(4.5 * var(--u)), env(safe-area-inset-top)); right: calc(5 * var(--u));
        margin: 0; padding-bottom: calc(1.2 * var(--u)); border-bottom: 1px solid var(--gold-soft);
        font-size: max(8px, calc(1.05 * var(--u))); letter-spacing: .22em; text-transform: uppercase; color: rgba(255,255,255,.85);
    }

    /* Colonne de texte. */
    #splash .sp-panel {
        position: absolute; z-index: 3; left: max(20px, calc(7.5 * var(--u))); top: 50%; transform: translateY(-50%);
        width: min(46vw, max(320px, calc(58 * var(--u)))); display: flex; flex-direction: column; align-items: flex-start;
    }
    #splash .sp-logo { width: max(30px, calc(7.5 * var(--u))); height: auto; border-radius: 50%; box-shadow: 0 0 calc(3 * var(--u)) rgba(217,178,106,.35); }
    #splash .sp-eyebrow, #splash .sp-sub {
        margin: 0; font-size: max(8px, calc(1.1 * var(--u))); letter-spacing: .26em; text-transform: uppercase; color: var(--gold); white-space: nowrap;
    }
    #splash .sp-eyebrow { margin-top: calc(1.8 * var(--u)); }
    #splash .sp-title {
        margin: calc(1.6 * var(--u)) 0 0; font-family: Cinzel, 'Trajan Pro', 'Times New Roman', serif; font-weight: 600;
        font-size: max(22px, calc(6.2 * var(--u))); line-height: 1.05; letter-spacing: .02em; white-space: nowrap;
        background: linear-gradient(180deg, #fbe7b0 0%, #e2b866 45%, #b4863a 100%); -webkit-background-clip: text; background-clip: text; color: transparent;
    }
    #splash .sp-rule { width: 100%; display: flex; align-items: center; gap: calc(1 * var(--u)); margin-top: calc(1.4 * var(--u)); }
    #splash .sp-rule span { flex: 1; height: 1px; background: linear-gradient(90deg, transparent, var(--gold) 30%, var(--gold) 70%, transparent); }
    #splash .sp-rule i { width: max(6px, calc(.9 * var(--u))); aspect-ratio: 1; transform: rotate(45deg); border: 1px solid var(--gold); }
    #splash .sp-sub { margin-top: calc(1.4 * var(--u)); padding-left: calc(3 * var(--u)); color: #f1d9a3; }

    #splash .sp-feature { display: flex; align-items: flex-start; gap: calc(2.2 * var(--u)); margin-top: calc(4 * var(--u)); }
    #splash .sp-feature svg { width: max(26px, calc(5.6 * var(--u))); height: auto; flex-shrink: 0; color: var(--gold); }
    #splash .sp-feature div { padding-left: calc(2.2 * var(--u)); border-left: 1px solid var(--gold-soft); }
    #splash .sp-ft { margin: 0; font-family: Georgia, 'Times New Roman', serif; font-size: max(15px, calc(2.9 * var(--u))); line-height: 1.12; }
    #splash .sp-fd { margin: calc(1 * var(--u)) 0 0; font-size: max(10px, calc(1.45 * var(--u))); line-height: 1.5; color: rgba(255,255,255,.82); }

    #splash .sp-load { width: 100%; margin-top: calc(3.6 * var(--u)); padding-top: calc(2.8 * var(--u)); border-top: 1px solid rgba(217,178,106,.35); }
    #splash .sp-status { display: flex; align-items: center; gap: calc(1.6 * var(--u)); color: var(--gold); font-size: max(11px, calc(1.6 * var(--u))); }
    #splash .sp-status svg { width: max(16px, calc(3 * var(--u))); height: auto; flex-shrink: 0; animation: sp-turn 6s linear infinite; }
    #splash .sp-status span { padding-left: calc(1.6 * var(--u)); border-left: 1px solid var(--gold-soft); }
    #splash .splash-row { display: flex; align-items: center; gap: calc(1.4 * var(--u)); margin-top: calc(2.2 * var(--u)); }
    #splash .splash-track { flex: 1; height: max(6px, calc(1.1 * var(--u))); border-radius: 999px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.16); overflow: hidden; }
    #splash .splash-fill { height: 100%; width: 100%; transform-origin: left; transform: scaleX(0); border-radius: inherit;
        background: linear-gradient(90deg, #b8893a, #f3d58a 70%, #fff3cf); box-shadow: 0 0 calc(1.2 * var(--u)) rgba(243,213,138,.55); transition: transform .12s linear; }
    #splash .splash-pct { min-width: 3.2em; color: #f3d58a; font-variant-numeric: tabular-nums; font-size: max(12px, calc(1.7 * var(--u))); text-align: right; }
    #splash .splash-text { margin: calc(1.4 * var(--u)) 0 0; font-size: max(10px, calc(1.3 * var(--u))); color: rgba(255,255,255,.78); }
    @keyframes sp-turn { to { transform: rotate(360deg); } }

    /* Téléphone debout (et tablette debout) : texte en haut, centré ; photo en bas. */
    @media (max-aspect-ratio: 1/1) {
        html.splash-on #splash { --u: min(.92vh, 1.9vw); }
        #splash .sp-photo { top: auto; left: 0; width: 100%; height: 44vh; }
        #splash .sp-photo img { object-position: 30% 60%; }
        #splash .sp-photo::before { background: linear-gradient(180deg, var(--navy) 0%, rgba(10,19,34,.6) 22%, rgba(10,19,34,0) 50%), linear-gradient(0deg, rgba(10,19,34,.5), rgba(10,19,34,0) 30%); }
        #splash .sp-tags { display: none; }
        #splash .sp-panel { left: 24px; right: 24px; width: auto; top: calc(max(20px, env(safe-area-inset-top)) + 3 * var(--u)); transform: none; align-items: stretch; }
        #splash .sp-logo, #splash .sp-eyebrow, #splash .sp-title, #splash .sp-sub { align-self: center; text-align: center; }
        /* Largeur d'abord : « HÔTEL PRÉSIDENT » doit tenir sur une ligne. */
        #splash .sp-title { font-size: min(calc(5.2 * var(--u)), 6.6vw); }
        #splash .sp-eyebrow, #splash .sp-sub { font-size: min(max(8px, calc(1.1 * var(--u))), 2.6vw); letter-spacing: .18em; }
        #splash .sp-sub { padding-left: 0; }
        #splash .sp-feature { margin-top: calc(3.2 * var(--u)); }
        #splash .sp-load { margin-top: calc(2.8 * var(--u)); padding-top: calc(2.2 * var(--u)); }
    }
    /* Petit téléphone debout (360 × 640…) : on garde l'essentiel. */
    @media (max-aspect-ratio: 1/1) and (max-height: 700px) {
        #splash .sp-fd { display: none; }
        #splash .sp-photo { height: 36vh; }
    }
    /* Téléphone couché (844 × 390…) : la phrase de présentation tient en deux lignes. */
    @media (min-aspect-ratio: 1/1) and (max-height: 480px) {
        #splash .sp-fd { font-size: 10px; line-height: 1.35; }
        #splash .sp-feature { margin-top: calc(3 * var(--u)); }
    }
    @media (prefers-reduced-motion: reduce) {
        #splash, #splash .splash-fill { transition: none; }
        #splash .sp-status svg { animation: none; }
    }
</style>
<script>
    try { if (!sessionStorage.getItem('hp-splash')) document.documentElement.classList.add('splash-on'); } catch (e) {}
</script>
<div id="splash" aria-hidden="true">
    <div class="sp-photo">
        <picture>
            <source srcset="{{ asset('images/splash-photo.webp') }}" type="image/webp">
            <img src="{{ asset('images/splash-photo.jpg') }}" alt="" fetchpriority="high">
        </picture>
    </div>

    <svg class="sp-arcs" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
        <path d="M 0 22 C 2 10, 8 3, 20 0" />
        <path d="M 60 100 C 80 99, 95 92, 100 80" />
    </svg>

    <p class="sp-tags">Suivi des OT &nbsp;·&nbsp; Interventions &nbsp;·&nbsp; Contrôle qualité</p>

    <div class="sp-panel">
        <img class="sp-logo" src="{{ asset('images/pwa/icon-192.png') }}" alt="">
        <p class="sp-eyebrow">Maintenance &nbsp;·&nbsp; Technique &nbsp;·&nbsp; Performance</p>
        <p class="sp-title">Hôtel Président</p>
        <div class="sp-rule"><span></span><i></i><span></span></div>
        <p class="sp-sub">Service technique &nbsp;·&nbsp; Hôtellerie</p>

        <div class="sp-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 2.8h6v2.4H9z"/>
                <path d="M8 10l1.2 1.2L11.5 9M13.5 10.2H16M8 15l1.2 1.2 2.3-2.2M13.5 15.2H16"/>
            </svg>
            <div>
                <p class="sp-ft">Gestion des ordres<br>de travail</p>
                <p class="sp-fd">Anticiper les pannes. Coordonner les interventions.<br>Garantir la disponibilité des équipements.</p>
            </div>
        </div>

        <div class="sp-load">
            <div class="sp-status">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                <span class="sp-step">Initialisation du service technique…</span>
            </div>
            <div class="splash-row">
                <div class="splash-track"><div class="splash-fill"></div></div>
                <span class="splash-pct">0&nbsp;%</span>
            </div>
            <p class="splash-text">Chargement des données du service technique…</p>
        </div>
    </div>
</div>
<script>
    (() => {
        const root = document.documentElement;
        if (!root.classList.contains('splash-on')) return;
        const splash = document.getElementById('splash');
        const fill = splash.querySelector('.splash-fill');
        const pct = splash.querySelector('.splash-pct');
        const stepEl = splash.querySelector('.sp-step');
        const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        // La ligne dorée dit l'étape en cours, selon l'avancement.
        const STEPS = [[0, 'Initialisation du service technique…'], [35, 'Chargement des ordres de travail…'], [75, 'Préparation de votre espace…'], [100, 'Prêt']];
        // La barre met DURATION_MS à aller de 0 à 100 %, même si la page est déjà là : le temps
        // de voir l'écran. Si la page tarde, elle attend à 92 % puis termine à l'arrivée.
        const DURATION_MS = calm ? 1200 : 7000;
        const start = Date.now();
        let loaded = document.readyState === 'complete';
        window.addEventListener('load', () => { loaded = true; }, { once: true });
        setTimeout(() => { loaded = true; }, 10000); // filet de sécurité : la page tarde (image, police…)
        const show = (p) => {
            fill.style.transform = `scaleX(${p / 100})`;
            pct.textContent = Math.round(p) + ' %';
            const label = STEPS.filter(([from]) => p >= from).pop()[1];
            if (stepEl.textContent !== label) stepEl.textContent = label;
        };
        show(0);
        const tick = setInterval(() => {
            const t = Math.min(1, (Date.now() - start) / DURATION_MS);
            const eased = 1 - Math.pow(1 - t, 1.6); // départ franc, fin en douceur
            show(Math.min(loaded ? 100 : 92, eased * 100));
            if (t < 1 || !loaded) return;
            clearInterval(tick);
            show(100);
            // Un instant à 100 %, puis fondu.
            setTimeout(() => {
                try { sessionStorage.setItem('hp-splash', '1'); } catch (e) {}
                splash.classList.add('is-leaving');
                setTimeout(() => root.classList.remove('splash-on'), calm ? 0 : 700);
            }, 600);
        }, 50);
    })();
</script>
