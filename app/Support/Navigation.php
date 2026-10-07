<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Part;
use App\Models\RoomBlock;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Str;

/**
 * Construit la sidebar / bottom-nav de la nouvelle coquille, par rôle.
 * Utilise nos noms de route existants (aucune route renommée pour la refonte).
 *
 * Principe : le menu ne contient que des destinations ; les actions vivent dans les
 * pages (bouton principal + menu ⋮). La configuration de l'admin est regroupée dans
 * l'espace « Paramètres » (settings()), hors du travail quotidien.
 */
class Navigation
{
    /**
     * Sections de la sidebar, dans l'ordre. "secondary" : entrées plus discrètes, sans icône
     * (référentiels et réglages, consultés moins souvent que l'exploitation).
     *
     * @return array<int, array{title: string, secondary: bool, items: array}>
     */
    public static function forSidebar(User $user): array
    {
        // Admin et manager : le travail quotidien (Exploitation), le stock, le patrimoine.
        // Les écrans de configuration de l'admin sont regroupés derrière « Paramètres »,
        // en bas, pour ne pas encombrer le quotidien du chef technique.
        if (in_array($user->role, [UserRole::Admin, UserRole::Manager], true)) {
            $isAdmin = $user->role === UserRole::Admin;

            return array_values(array_filter([
                self::section('Exploitation', [
                    ['label' => 'Tableau de bord', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                    ['label' => 'Ordres de travail', 'route' => 'work-orders.index', 'badge' => WorkOrder::visibleTo($user)->open()->count(), 'icon' => 'clipboard'],
                    ['label' => 'Validation', 'route' => 'quality-controls.index', 'badge' => self::toReviewCount(), 'icon' => 'shield'],
                    ['label' => 'Planning', 'route' => 'planning.index', 'icon' => 'calendar'],
                    // Admin : demandes à décider ; manager : chambres retirées de la vente.
                    ['label' => 'Chambres bloquées', 'route' => 'room-blocks.index', 'badge' => self::roomBlockCount($isAdmin ? RoomBlock::REQUESTED : RoomBlock::BLOCKED), 'icon' => 'building'],
                    ['label' => 'Maintenance préventive', 'route' => 'maintenance-plans.index', 'icon' => 'status'],
                    ['label' => 'Rapports', 'route' => 'reports.index', 'icon' => 'list'],
                ]),
                self::section('Stock & achats', [
                    ['label' => 'Pièces & stock', 'route' => 'parts.index', 'badge' => self::lowStockCount(), 'icon' => 'part'],
                    ['label' => 'Bons de commande', 'route' => 'purchase-orders.index', 'icon' => 'tag'],
                    ['label' => 'Fournisseurs', 'route' => 'suppliers.index', 'icon' => 'truck'],
                ], secondary: true),
                self::section('Patrimoine', [
                    ['label' => 'Lieux', 'route' => 'rooms.index', 'icon' => 'pin'],
                    ['label' => 'Équipements', 'route' => 'equipment.index', 'icon' => 'wrench'],
                ], secondary: true),
                $isAdmin ? self::section('Administration', [
                    ['label' => 'Paramètres', 'route' => 'settings.index', 'icon' => 'settings', 'match' => self::settingsRoutePatterns()],
                ]) : null,
            ]));
        }

        $nav = self::legacySidebar($user);

        return array_values(array_filter([
            self::section('Opérations', $nav['ops']),
            $nav['admin'] ? self::section($nav['adminTitle'], $nav['admin'], secondary: true) : null,
        ]));
    }

    /**
     * Écrans de l'espace « Paramètres » (admin), par thème : accueil en cartes
     * (settings.index) et entrée « Paramètres » active sur chacun d'eux.
     *
     * @return array<string, array<int, array{label: string, route: string, icon: string, description: string}>>
     */
    public static function settings(): array
    {
        return [
            'Organisation' => [
                ['label' => 'Utilisateurs & rôles', 'route' => 'users.index', 'icon' => 'users', 'description' => 'Comptes, rôles, responsables de service, mots de passe provisoires, départs.'],
                ['label' => 'Compétences', 'route' => 'skills.index', 'icon' => 'star', 'description' => 'Savoir-faire des techniciens, utilisés pour proposer qui affecter.'],
                ['label' => 'Astreinte', 'route' => 'on-call.edit', 'icon' => 'bell', 'description' => 'Horaires de jour et de nuit, téléphones qui reçoivent les alertes.'],
            ],
            'Ordres de travail' => [
                ['label' => "Types d'OT", 'route' => 'work-order-types.index', 'icon' => 'tag', 'description' => 'Maintenance, préventif, demande client… proposés à la création.'],
                ['label' => 'Priorités', 'route' => 'work-order-priorities.index', 'icon' => 'flag', 'description' => "Niveaux d'urgence, leur couleur et leur ordre."],
                ['label' => 'Politiques SLA', 'route' => 'sla-policies.index', 'icon' => 'clock', 'description' => 'Délais de réponse et de résolution par priorité et par type.'],
                ['label' => "Règles d'escalade", 'route' => 'escalation-rules.index', 'icon' => 'alert', 'description' => "Qui prévenir, et quand, à l'approche ou au dépassement d'un délai."],
            ],
            'Sécurité' => [
                ['label' => "Journal d'activité", 'route' => 'activity-logs.index', 'icon' => 'history', 'description' => 'Connexions, exports, changements sensibles : qui a fait quoi, et quand.'],
            ],
        ];
    }

    /** @return array<int, string> motifs routeIs() des écrans de l'espace « Paramètres » */
    public static function settingsRoutePatterns(): array
    {
        return collect(self::settings())->flatten(1)
            ->map(fn (array $item) => Str::beforeLast($item['route'], '.').'.*')
            ->push('settings.*')
            ->values()
            ->all();
    }

    private static function section(string $title, array $items, bool $secondary = false): array
    {
        return ['title' => $title, 'secondary' => $secondary, 'items' => $items];
    }

    /**
     * Menus des rôles de terrain (technicien, housekeeping, réception) : une section d'opérations.
     *
     * @return array{ops: array, adminTitle: string, admin: array}
     */
    private static function legacySidebar(User $user): array
    {
        $openCount = fn () => WorkOrder::visibleTo($user)->open()->count();

        return match ($user->role) {
            UserRole::Technicien => [
                'ops' => [
                    ['label' => 'Ma journée', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                    ['label' => 'Mes ordres', 'route' => 'work-orders.index', 'badge' => $openCount(), 'icon' => 'clipboard'],
                    // Une autre panne trouvée en intervention : la signaler sur place.
                    ['label' => 'Signaler une panne', 'route' => 'quick-reports.create', 'icon' => 'mic'],
                    ['label' => 'Mon planning', 'route' => 'planning.technician', 'params' => ['technician' => $user->id], 'icon' => 'calendar'],
                ],
                'adminTitle' => '',
                'admin' => [],
            ],
            UserRole::Housekeeping => [
                'ops' => [
                    // Un nom par page, le même partout (menu, barre du bas, titre).
                    // 'match' exact : les outils de la gouvernante sont aussi en « housekeeping.* ».
                    ['label' => 'Accueil', 'route' => $user->dashboardRoute(), 'match' => [$user->dashboardRoute()], 'icon' => 'home'],
                    ['label' => 'Signaler un problème', 'route' => 'quick-reports.create', 'icon' => 'mic'],
                    ['label' => self::housekeepingListLabel($user), 'route' => 'work-orders.index', 'badge' => $openCount(), 'icon' => 'clipboard'],
                    // La gouvernante demande les blocages et remet les chambres en vente,
                    // et a ses outils de supervision (Housekeeping::headTools).
                    ...($user->isDepartmentHead()
                        ? [['label' => 'Chambres bloquées', 'route' => 'room-blocks.index', 'badge' => self::roomBlockCount(RoomBlock::BLOCKED), 'icon' => 'building'], ...Housekeeping::headTools()]
                        : []),
                ],
                'adminTitle' => '',
                'admin' => [],
            ],
            UserRole::Reception => [
                'ops' => [
                    // 'match' exact : les autres pages de la réception sont aussi en « reception.* ».
                    // Accueil : recherche de chambre, clients concernés, astreinte, activité.
                    ['label' => 'Accueil', 'route' => $user->dashboardRoute(), 'match' => [$user->dashboardRoute()], 'icon' => 'home'],
                    ['label' => 'Demandes', 'route' => 'work-orders.index', 'badge' => $openCount(), 'icon' => 'clipboard'],
                    // Badge = demandes de blocage à valider.
                    ['label' => 'Chambres bloquées', 'route' => 'room-blocks.index', 'badge' => self::roomBlockCount(RoomBlock::REQUESTED), 'icon' => 'building'],
                    // Responsable de réception : le bilan du mois de son équipe.
                    ...($user->isDepartmentHead()
                        ? [['label' => 'Bilan du mois', 'route' => 'reception.monthly-report', 'match' => ['reception.monthly-report'], 'icon' => 'report']]
                        : []),
                ],
                'adminTitle' => '',
                'admin' => [],
            ],
        };
    }

    /**
     * Barre du bas (téléphone) : les 3 à 5 destinations quotidiennes du rôle. Ce qui
     * attend une décision du rôle porte un compteur (badge), comme dans la sidebar.
     *
     * @return array<int, array{label: string, route: string, icon: string, params?: array, badge?: int}>
     */
    public static function forBottomNav(User $user): array
    {
        $profile = ['label' => 'Profil', 'route' => 'profile.edit', 'icon' => 'user'];

        return match ($user->role) {
            UserRole::Technicien => [
                ['label' => 'Ma journée', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                ['label' => 'Mes ordres', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                ['label' => 'Signaler', 'route' => 'quick-reports.create', 'icon' => 'mic'],
                ['label' => 'Planning', 'route' => 'planning.technician', 'params' => ['technician' => $user->id], 'icon' => 'calendar'],
                $profile,
            ],
            UserRole::Housekeeping => [
                ['label' => 'Accueil', 'route' => $user->dashboardRoute(), 'match' => [$user->dashboardRoute()], 'icon' => 'home'],
                ['label' => 'Signaler', 'route' => 'quick-reports.create', 'icon' => 'mic'],
                // « Signalements » : version courte de « Mes signalements », la barre est étroite.
                ['label' => 'Signalements', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                // La gouvernante remet les chambres en vente : son travail quotidien, pas un menu caché.
                ...($user->isDepartmentHead()
                    ? [['label' => 'Blocages', 'route' => 'room-blocks.index', 'icon' => 'building', 'badge' => self::roomBlockCount(RoomBlock::BLOCKED)]]
                    : []),
                $profile,
            ],
            UserRole::Reception => [
                ['label' => 'Accueil', 'route' => $user->dashboardRoute(), 'match' => [$user->dashboardRoute()], 'icon' => 'home'],
                ['label' => 'Demandes', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                // Accepter ou refuser un blocage est la décision clé de la réception.
                ['label' => 'Blocages', 'route' => 'room-blocks.index', 'icon' => 'building', 'badge' => self::roomBlockCount(RoomBlock::REQUESTED)],
                $profile,
            ],
            UserRole::Manager => [
                ['label' => 'Accueil', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                ['label' => 'Ordres', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                ['label' => 'Validation', 'route' => 'quality-controls.index', 'icon' => 'shield', 'badge' => self::toReviewCount()],
                ['label' => 'Planning', 'route' => 'planning.index', 'icon' => 'calendar'],
                $profile,
            ],
            UserRole::Admin => [
                ['label' => 'Accueil', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                ['label' => 'Ordres', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                ['label' => 'Planning', 'route' => 'planning.index', 'icon' => 'calendar'],
                ['label' => 'Paramètres', 'route' => 'settings.index', 'icon' => 'settings', 'match' => self::settingsRoutePatterns()],
                $profile,
            ],
        };
    }

    /**
     * Motifs de routes qui allument une entrée (menu, barre du bas, rail) : 'match' si
     * l'entrée le précise, sinon toutes les pages de sa ressource (users.index → users.*).
     *
     * @return array<int, string>
     */
    public static function activePatterns(array $item): array
    {
        return $item['match'] ?? [Str::beforeLast($item['route'], '.').'.*'];
    }

    /** L'entrée correspond-elle à la page affichée ? Règle unique pour toutes les navigations. */
    public static function isActive(array $item): bool
    {
        return request()->routeIs(...self::activePatterns($item));
    }

    /** Liste des OT côté housekeeping : ceux de l'agent, ou de toute l'équipe pour la gouvernante. */
    public static function housekeepingListLabel(User $user): string
    {
        return $user->isDepartmentHead() ? "Signalements de l'équipe" : 'Mes signalements';
    }

    /** OT réparés qui attendent leur contrôle qualité. */
    private static function toReviewCount(): int
    {
        return WorkOrder::where('status', 'resolu')->count();
    }

    private static function roomBlockCount(string $status): int
    {
        return RoomBlock::where('status', $status)->count();
    }

    private static function lowStockCount(): int
    {
        return Part::whereColumn('quantity_on_hand', '<=', 'reorder_threshold')
            ->where('is_active', true)
            ->count();
    }
}
