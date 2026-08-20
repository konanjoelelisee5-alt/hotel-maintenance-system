<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    /**
     * Qui peut voir la liste de TOUS les OT (pas juste les siens).
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Qui peut voir le détail d'un OT précis.
     */
    public function view(User $user, WorkOrder $workOrder): bool
    {
        return match ($user->role) {
            'admin', 'manager' => true,
            'technicien' => $workOrder->assigned_to === $user->id,
            'housekeeping', 'reception' => $workOrder->reported_by === $user->id,
            default => false,
        };
    }

    /**
     * Qui peut créer un OT (tout le monde, rappel de la matrice validée).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Qui peut modifier un OT (titre, description, type...).
     */
    public function update(User $user, WorkOrder $workOrder): bool
    {
        return match ($user->role) {
            'admin', 'manager' => true,
            'technicien' => $workOrder->assigned_to === $user->id,
            default => false,
        };
    }

    /**
     * Qui peut supprimer un OT.
     */
    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Qui peut agir sur l'intervention (démarrer/arrêter le chrono, rapport).
     */
    public function intervene(User $user, WorkOrder $workOrder): bool
    {
        return match ($user->role) {
            'admin', 'manager' => true,
            'technicien' => $workOrder->assigned_to === $user->id,
            default => false,
        };
    }
}