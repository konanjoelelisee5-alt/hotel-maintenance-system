<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * File de travail des dashboards : onglets proposés selon le rôle et règle de filtrage,
 * partagée par la file affichée, les compteurs des onglets et les indicateurs.
 */
class WorkQueue
{
    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function tabs(User $user): array
    {
        if ($user->role->seesAllWorkOrders()) {
            return [
                ['key' => 'urgent', 'label' => 'Urgents'],
                ['key' => 'unassigned', 'label' => 'Non affectés'],
                ['key' => 'late', 'label' => 'En retard SLA'],
                ['key' => 'to_review', 'label' => 'À contrôler'],
                ['key' => 'waiting', 'label' => 'En attente'],
                ['key' => 'all', 'label' => 'Tous'],
            ];
        }

        return [
            ['key' => 'mine', 'label' => $user->role === UserRole::Technicien ? 'Mes ordres' : 'Mes signalements'],
            ['key' => 'urgent', 'label' => 'Urgents'],
            ['key' => 'all', 'label' => $user->isDepartmentHead() ? "Toute l'équipe" : 'Tous'],
        ];
    }

    public static function top(User $user, string $filter): Collection
    {
        // Housekeeping suit ses signalements : le plus récent en tête (un nouvel OT apparaît en premier).
        if ($user->role === UserRole::Housekeeping) {
            return self::filtered($user, $filter)
                ->with(['room', 'assignee', 'priority', 'reporter'])
                ->latest()->latest('id')
                ->limit(8)
                ->get();
        }

        return self::filtered($user, $filter)
            ->with(['room', 'equipment', 'assignee', 'priority'])
            ->orderByRaw('CASE WHEN sla_breached THEN 0 ELSE 1 END')
            ->orderBy('sla_resolution_due_at')
            ->limit(8)
            ->get();
    }

    /**
     * Ordres visibles par l'utilisateur, restreints au filtre d'onglet demandé
     * (partagé entre la file affichée et les compteurs des onglets).
     */
    public static function filtered(User $user, string $filter): Builder
    {
        $query = WorkOrder::visibleTo($user);

        // Files de travail (tous les rôles) : un OT déjà réparé, fermé ou annulé n'y a plus
        // sa place, même s'il était urgent ou a dépassé son SLA à l'époque. Même règle que
        // les indicateurs (« Urgents » compte les OT ouverts).
        if (in_array($filter, ['urgent', 'unassigned', 'late'], true)) {
            $query->open();
        }

        match ($filter) {
            // Pour un responsable, "Mes signalements" se distingue de "Toute l'équipe".
            'mine' => $user->isDepartmentHead() ? $query->where('reported_by', $user->id) : null,
            'urgent' => $query->whereHas('priority', fn ($p) => $p->where('code', 'urgente')),
            'unassigned' => $query->whereNull('assigned_to'),
            'late' => $query->late(),
            'to_review' => $query->where('status', 'resolu'),
            'waiting' => $query->where('status', 'en_attente'),
            default => null,
        };

        return $query;
    }
}
