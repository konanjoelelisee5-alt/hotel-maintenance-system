<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Part;
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
     * @return array{ops: array, adminTitle: string, admin: array}
     */
    public static function forSidebar(User $user): array
    {
        $openCount = fn () => WorkOrder::visibleTo($user)->open()->count();

        return match ($user->role) {
            UserRole::Admin => [
                'ops' => [
                    ['label' => 'Supervision', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                    ['label' => 'Ordres de travail', 'route' => 'work-orders.index', 'badge' => $openCount(), 'icon' => 'clipboard'],
                    ['label' => 'Planning', 'route' => 'planning.index', 'icon' => 'calendar'],
                    ['label' => 'Rapports', 'route' => 'reports.index', 'icon' => 'list'],
                    ['label' => 'Pièces & stock', 'route' => 'parts.index', 'badge' => self::lowStockCount(), 'icon' => 'part'],
                ],
                'adminTitle' => 'Administration',
                'admin' => [
                    ['label' => 'Utilisateurs', 'route' => 'users.index'],
                    ['label' => 'Compétences', 'route' => 'skills.index'],
                    ['label' => 'Types OT', 'route' => 'work-order-types.index'],
                    ['label' => 'Priorités', 'route' => 'work-order-priorities.index'],
                    ['label' => 'Politiques SLA', 'route' => 'sla-policies.index'],
                    ['label' => 'Règles escalade', 'route' => 'escalation-rules.index'],
                    ['label' => 'Maintenance préventive', 'route' => 'maintenance-plans.index'],
                    ['label' => 'Fournisseurs & achats', 'route' => 'purchase-orders.index'],
                    ['label' => "Journal d'activité", 'route' => 'activity-logs.index'],
                ],
            ],
            UserRole::Manager => [
                'ops' => [
                    ['label' => 'Pilotage', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
                    ['label' => 'Ordres de travail', 'route' => 'work-orders.index', 'badge' => $openCount(), 'icon' => 'clipboard'],
                    ['label' => 'Planning', 'route' => 'planning.index', 'icon' => 'calendar'],
                    ['label' => 'Rapports', 'route' => 'reports.index', 'icon' => 'list'],
                ],
                'adminTitle' => 'Ressources',
                'admin' => [
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
                    ['label' => 'Mes signalements', 'route' => $user->dashboardRoute(), 'badge' => $openCount(), 'icon' => 'home'],
                    ['label' => 'Historique', 'route' => 'work-orders.index', 'icon' => 'clipboard'],
                ],
                'adminTitle' => '',
                'admin' => [],
            ],
            UserRole::Reception => [
                'ops' => [
                    ['label' => 'Accueil', 'route' => $user->dashboardRoute(), 'icon' => 'search'],
                    ['label' => 'Demandes', 'route' => 'work-orders.index', 'badge' => $openCount(), 'icon' => 'clipboard'],
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
                ['label' => 'Signalements', 'route' => $user->dashboardRoute(), 'icon' => 'home'],
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
                ['label' => 'Comptes', 'route' => 'users.index', 'icon' => 'users'],
                $profile,
            ],
        };
    }

    private static function lowStockCount(): int
    {
        return Part::whereColumn('quantity_on_hand', '<=', 'reorder_threshold')
            ->where('is_active', true)
            ->count();
    }
}
