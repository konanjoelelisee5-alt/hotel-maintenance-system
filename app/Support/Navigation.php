<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Part;
use App\Models\RoomBlock;
use App\Models\User;
use App\Models\WorkOrder;

/**
 * Construit la sidebar / bottom-nav de la nouvelle coquille, par rôle.
 * Utilise nos noms de route existants (aucune route renommée pour la refonte).
 *
 * NB : certains modules du plan (Contrôle qualité en page dédiée, Pièces & stock
 * ouvert aux techniciens, "Mes chambres" par étage pour le housekeeping...) n'ont
 * pas encore d'équivalent chez nous — ils seront ajoutés ici au fil des phases
 * suivantes, pas inventés à l'avance (une entrée de nav sans route réelle serait
 * une 404).
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
        // Admin : sections par nature (le quotidien, le stock, les données de référence,
        // les règles automatiques, puis comptes et sécurité) plutôt qu'une liste à plat.
        if ($user->role === UserRole::Admin) {
            return [
                self::section('Exploitation', [
                    ['label' => 'Supervision', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                    ['label' => 'Ordres de travail', 'route' => 'work-orders.index', 'badge' => WorkOrder::visibleTo($user)->open()->count(), 'icon' => 'clipboard'],
                    ['label' => 'Planning', 'route' => 'planning.index', 'icon' => 'calendar'],
                    ['label' => 'Chambres bloquées', 'route' => 'room-blocks.index', 'badge' => self::roomBlockCount(RoomBlock::REQUESTED), 'icon' => 'building'],
                    ['label' => 'Maintenance préventive', 'route' => 'maintenance-plans.index', 'icon' => 'status'],
                    ['label' => 'Rapports', 'route' => 'reports.index', 'icon' => 'list'],
                ]),
                self::section('Stock & achats', [
                    ['label' => 'Pièces & stock', 'route' => 'parts.index', 'badge' => self::lowStockCount()],
                    ['label' => 'Bons de commande', 'route' => 'purchase-orders.index'],
                    ['label' => 'Fournisseurs', 'route' => 'suppliers.index'],
                ], secondary: true),
                self::section('Référentiels', [
                    ['label' => 'Lieux', 'route' => 'rooms.index'],
                    ['label' => 'Équipements', 'route' => 'equipment.index'],
                    ['label' => "Types d'OT", 'route' => 'work-order-types.index'],
                    ['label' => 'Priorités', 'route' => 'work-order-priorities.index'],
                    ['label' => 'Compétences', 'route' => 'skills.index'],
                ], secondary: true),
                self::section('Alertes & SLA', [
                    ['label' => 'Politiques SLA', 'route' => 'sla-policies.index'],
                    ['label' => "Règles d'escalade", 'route' => 'escalation-rules.index'],
                    ['label' => 'Astreinte', 'route' => 'on-call.edit'],
                ], secondary: true),
                self::section('Administration', [
                    ['label' => 'Utilisateurs', 'route' => 'users.index'],
                    ['label' => "Journal d'activité", 'route' => 'activity-logs.index'],
                ], secondary: true),
            ];
        }

        $nav = self::legacySidebar($user);

        return array_values(array_filter([
            self::section('Opérations', $nav['ops']),
            $nav['admin'] ? self::section($nav['adminTitle'], $nav['admin'], secondary: true) : null,
        ]));
    }

    private static function section(string $title, array $items, bool $secondary = false): array
    {
        return ['title' => $title, 'secondary' => $secondary, 'items' => $items];
    }

    /**
     * Menus des autres rôles : une section d'opérations, plus des ressources pour le manager.
     *
     * @return array{ops: array, adminTitle: string, admin: array}
     */
    private static function legacySidebar(User $user): array
    {
        $openCount = fn () => WorkOrder::visibleTo($user)->open()->count();

        return match ($user->role) {
            UserRole::Manager => [
                'ops' => [
                    ['label' => 'Pilotage', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                    ['label' => 'Ordres de travail', 'route' => 'work-orders.index', 'badge' => $openCount(), 'icon' => 'clipboard'],
                    ['label' => 'Planning', 'route' => 'planning.index', 'icon' => 'calendar'],
                    ['label' => 'Rapports', 'route' => 'reports.index', 'icon' => 'list'],
                    ['label' => 'Chambres bloquées', 'route' => 'room-blocks.index', 'badge' => self::roomBlockCount(RoomBlock::BLOCKED), 'icon' => 'building'],
                ],
                'adminTitle' => 'Ressources',
                'admin' => [
                    ['label' => 'Lieux', 'route' => 'rooms.index'],
                    ['label' => 'Équipements', 'route' => 'equipment.index'],
                    ['label' => 'Pièces & stock', 'route' => 'parts.index'],
                    ['label' => 'Fournisseurs & achats', 'route' => 'purchase-orders.index'],
                    ['label' => 'Maintenance préventive', 'route' => 'maintenance-plans.index'],
                ],
            ],
            UserRole::Technicien => [
                'ops' => [
                    ['label' => 'Ma journée', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                    ['label' => 'Mes ordres', 'route' => 'work-orders.index', 'badge' => $openCount(), 'icon' => 'clipboard'],
                    ['label' => 'Mon planning', 'route' => 'planning.technician', 'params' => ['technician' => $user->id], 'icon' => 'calendar'],
                ],
                'adminTitle' => '',
                'admin' => [],
            ],
            UserRole::Housekeeping => [
                'ops' => [
                    ['label' => 'Signaler un problème', 'route' => 'quick-reports.create', 'icon' => 'mic'],
                    ['label' => $user->isDepartmentHead() ? "Signalements de l'équipe" : 'Mes signalements', 'route' => $user->dashboardRoute(), 'badge' => $openCount(), 'icon' => 'home'],
                    ['label' => 'Historique', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                    // La gouvernante demande les blocages et remet les chambres en vente.
                    ...($user->isDepartmentHead()
                        ? [['label' => 'Chambres bloquées', 'route' => 'room-blocks.index', 'badge' => self::roomBlockCount(RoomBlock::BLOCKED), 'icon' => 'building']]
                        : []),
                ],
                'adminTitle' => '',
                'admin' => [],
            ],
            UserRole::Reception => [
                'ops' => [
                    ['label' => 'Accueil', 'route' => $user->dashboardRoute(), 'icon' => 'search'],
                    ['label' => 'Demandes', 'route' => 'work-orders.index', 'badge' => $openCount(), 'icon' => 'clipboard'],
                    // Badge = demandes de blocage à valider.
                    ['label' => 'Blocages', 'route' => 'room-blocks.index', 'badge' => self::roomBlockCount(RoomBlock::REQUESTED), 'icon' => 'building'],
                ],
                'adminTitle' => '',
                'admin' => [],
            ],
        };
    }

    /**
     * @return array<int, array{label: string, route: string, icon: string, params?: array}>
     */
    public static function forBottomNav(User $user): array
    {
        $profile = ['label' => 'Profil', 'route' => 'profile.edit', 'icon' => 'user'];

        return match ($user->role) {
            UserRole::Technicien => [
                ['label' => 'Accueil', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                ['label' => 'Mes OT', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                ['label' => 'Planning', 'route' => 'planning.technician', 'params' => ['technician' => $user->id], 'icon' => 'calendar'],
                $profile,
            ],
            UserRole::Housekeeping => [
                ['label' => 'Accueil', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                ['label' => 'Signaler', 'route' => 'quick-reports.create', 'icon' => 'mic'],
                ['label' => 'Mes OT', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                $profile,
            ],
            UserRole::Reception => [
                ['label' => 'Recherche', 'route' => $user->dashboardRoute(), 'icon' => 'search'],
                ['label' => 'Demandes', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                $profile,
            ],
            UserRole::Manager => [
                ['label' => 'Pilotage', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                ['label' => 'Ordres', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                ['label' => 'Planning', 'route' => 'planning.index', 'icon' => 'calendar'],
                $profile,
            ],
            UserRole::Admin => [
                ['label' => 'Accueil', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                ['label' => 'Ordres', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                ['label' => 'Utilisateurs', 'route' => 'users.index', 'icon' => 'users'],
                $profile,
            ],
        };
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
