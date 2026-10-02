/**
 * Agenda du planning (planning/partials/agenda.blade.php), à la manière des agendas
 * de smartphone :
 *  - Jour : bande des 7 jours (glisser pour changer de semaine) + programme du jour ;
 *  - Semaine : grille horaire, interventions placées selon leur heure et leur durée ;
 *  - Mois : grille du mois avec pastilles, puis programme du jour choisi.
 * Les interventions viennent de la route planning.events (PlanningController::events),
 * chargées par période (semaine ou mois) et gardées en mémoire.
 */

const HOUR_PX = 56;          // hauteur d'une heure dans la grille Semaine
const DEFAULT_MINUTES = 60;  // durée affichée quand l'OT n'a pas de durée estimée

const startOfDay = (d) => { const x = new Date(d); x.setHours(0, 0, 0, 0); return x; };
const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
const startOfWeek = (d) => addDays(startOfDay(d), -((d.getDay() + 6) % 7)); // semaine du lundi
const sameDay = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
const isoDate = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const capitalize = (s) => s.charAt(0).toUpperCase() + s.slice(1);

/** Vue gardée dans l'adresse (?vue=mois) : elle survit au rechargement et se partage. */
const VIEW_PARAM = { day: 'jour', week: 'semaine', month: 'mois' };
const initialView = () => {
    const fromUrl = new URLSearchParams(window.location.search).get('vue');
    const view = Object.keys(VIEW_PARAM).find((key) => VIEW_PARAM[key] === fromUrl);
    return view ?? (window.matchMedia('(min-width: 1024px)').matches ? 'week' : 'day');
};

export default (config) => ({
    eventsUrl: config.eventsUrl,
    technicianId: config.technicianId ?? '',
    view: initialView(),
    selected: startOfDay(new Date()),
    now: new Date(),
    events: [],
    loadedKey: null,
    loading: false,
    failed: false,
    touchX: null,
    HOUR_PX,

    init() {
        this.load();
        // La ligne « maintenant » avance toute seule.
        setInterval(() => { this.now = new Date(); }, 60_000);
    },

    // ===== Périodes =====

    get weekDays() {
        const start = startOfWeek(this.selected);
        return Array.from({ length: 7 }, (_, i) => addDays(start, i));
    },

    /** Semaines complètes couvrant le mois du jour choisi (5 ou 6 lignes). */
    get monthDays() {
        const first = new Date(this.selected.getFullYear(), this.selected.getMonth(), 1);
        const last = new Date(this.selected.getFullYear(), this.selected.getMonth() + 1, 0);
        const days = [];
        for (let d = startOfWeek(first); d <= last || days.length % 7; d = addDays(d, 1)) days.push(d);
        return days;
    },

    range() {
        if (this.view === 'month') {
            const days = this.monthDays;
            return [days[0], addDays(days[days.length - 1], 1)];
        }
        const start = startOfWeek(this.selected);
        return [start, addDays(start, 7)];
    },

    get title() {
        if (this.view === 'month') {
            return capitalize(this.selected.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }));
        }
        const [start, end] = this.range();
        const last = addDays(end, -1);
        if (start.getMonth() === last.getMonth()) {
            return capitalize(start.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }));
        }
        const short = (d) => d.toLocaleDateString('fr-FR', { month: 'short' });
        return capitalize(`${short(start)} – ${short(last)} ${last.getFullYear()}`);
    },

    get selectedLabel() {
        const label = this.selected.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
        return (this.isToday(this.selected) ? "Aujourd'hui · " : '') + capitalize(label);
    },

    // ===== Navigation =====

    setView(view) {
        this.view = view;
        const url = new URL(window.location.href);
        url.searchParams.set('vue', VIEW_PARAM[view]);
        window.history.replaceState(null, '', url);
        this.load();
    },

    go(step) {
        if (this.view === 'month') {
            const d = new Date(this.selected.getFullYear(), this.selected.getMonth() + step, 1);
            this.selected = this.isSameMonth(new Date(), d) ? startOfDay(new Date()) : d;
        } else {
            this.selected = addDays(this.selected, 7 * step);
        }
        this.load();
    },

    today() {
        this.selected = startOfDay(new Date());
        this.load();
    },

    pick(day) {
        this.selected = startOfDay(day);
        this.load();
    },

    /** Glisser le doigt vers la gauche / la droite : semaine (ou mois) suivante / précédente. */
    swipeStart(event) { this.touchX = event.touches[0].clientX; },
    swipeEnd(event) {
        if (this.touchX === null) return;
        const dx = event.changedTouches[0].clientX - this.touchX;
        this.touchX = null;
        if (Math.abs(dx) > 60) this.go(dx < 0 ? 1 : -1);
    },

    filter(id) {
        this.technicianId = id;
        this.loadedKey = null;
        this.load();
    },

    // ===== Données =====

    async load() {
        const [start, end] = this.range();
        const key = `${isoDate(start)}|${isoDate(end)}|${this.technicianId}`;
        if (key === this.loadedKey) return;
        this.loadedKey = key;
        this.loading = true;
        this.failed = false;

        const url = new URL(this.eventsUrl, window.location.origin);
        url.searchParams.set('start', isoDate(start));
        url.searchParams.set('end', isoDate(end));
        if (this.technicianId) url.searchParams.set('technician_id', this.technicianId);

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error(response.status);
            const data = await response.json();
            if (key !== this.loadedKey) return; // une période plus récente a été demandée entre-temps
            this.events = data.map((e) => {
                const startAt = new Date(e.start);
                const endAt = e.end ? new Date(e.end) : new Date(startAt.getTime() + DEFAULT_MINUTES * 60_000);
                return { ...e, startAt, endAt };
            }).sort((a, b) => a.startAt - b.startAt);
        } catch {
            this.failed = true;
            this.loadedKey = null;
        } finally {
            this.loading = false;
        }
    },

    eventsOn(day) {
        return this.events.filter((e) => sameDay(e.startAt, day));
    },

    // ===== Affichage =====

    isToday(day) { return sameDay(day, this.now); },
    isSelected(day) { return sameDay(day, this.selected); },
    isSameMonth(a, b = this.selected) { return a.getMonth() === b.getMonth() && a.getFullYear() === b.getFullYear(); },
    dayLabel(day) { return capitalize(day.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })); },
    weekdayShort(day) { return capitalize(day.toLocaleDateString('fr-FR', { weekday: 'short' }).replace('.', '')); },
    weekdayLetter(day) { return day.toLocaleDateString('fr-FR', { weekday: 'narrow' }); },
    time(date) { return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }); },
    duration(e) {
        const minutes = Math.round((e.endAt - e.startAt) / 60_000);
        return minutes >= 60 ? `${Math.floor(minutes / 60)} h${minutes % 60 ? String(minutes % 60).padStart(2, '0') : ''}` : `${minutes} min`;
    },
    isNow(e) { return this.now >= e.startAt && this.now < e.endAt; },
    isPast(e) { return e.endAt < this.now; },

    /** Fond teinté et filet de la couleur de la priorité. */
    cardStyle(e) { return `border-left-color: ${e.color}; background-color: ${e.color}14;`; },

    /** Heures affichées dans la grille : 7 h – 20 h, élargies aux interventions de la semaine. */
    get hours() {
        let first = 7;
        let last = 20;
        for (const day of this.weekDays) {
            for (const e of this.eventsOn(day)) {
                first = Math.min(first, e.startAt.getHours());
                last = Math.max(last, sameDay(e.endAt, e.startAt) ? e.endAt.getHours() : 23);
            }
        }
        return Array.from({ length: last - first + 1 }, (_, i) => first + i);
    },

    /**
     * Position d'une intervention dans sa colonne. Celles qui se chevauchent se
     * partagent la largeur (couloirs), comme dans un agenda de téléphone.
     */
    layout(day) {
        const firstHour = this.hours[0];
        const items = this.eventsOn(day);
        const lanes = [];
        const placed = items.map((e) => {
            let lane = lanes.findIndex((endAt) => endAt <= e.startAt);
            if (lane === -1) { lane = lanes.length; lanes.push(e.endAt); } else { lanes[lane] = e.endAt; }
            return { e, lane };
        });
        return placed.map(({ e, lane }) => {
            const overlapping = placed.filter((p) => p.e.startAt < e.endAt && p.e.endAt > e.startAt);
            const columns = Math.max(...overlapping.map((p) => p.lane)) + 1;
            const top = ((e.startAt.getHours() - firstHour) * 60 + e.startAt.getMinutes()) / 60 * HOUR_PX;
            const end = sameDay(e.endAt, e.startAt) ? e.endAt : new Date(e.startAt).setHours(24, 0, 0, 0);
            const height = Math.max((end - e.startAt) / 3_600_000 * HOUR_PX, 26);
            return {
                ...e,
                style: `top:${top}px;height:${height - 2}px;left:calc(${(lane / columns) * 100}% + 2px);width:calc(${100 / columns}% - 4px);`
                    + this.cardStyle(e),
                compact: height < 52,
            };
        });
    },

    /** Position de la ligne « maintenant » dans la grille (null hors des heures affichées). */
    get nowTop() {
        const hours = this.hours;
        const h = this.now.getHours() + this.now.getMinutes() / 60;
        if (h < hours[0] || h > hours[hours.length - 1] + 1) return null;
        return (h - hours[0]) * HOUR_PX;
    },
});
