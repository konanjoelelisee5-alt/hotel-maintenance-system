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
        return in_array($user->role?->value, ['admin', 'manager']);
    }

    /**
     * Qui peut voir le détail d'un OT précis.
     */
    public function view(User $user, WorkOrder $workOrder): bool
    {
        return match ($user->role?->value) {
            'admin', 'manager' => true,
            'technicien' => $workOrder->assigned_to === $user->id,
            'housekeeping', 'reception' => $workOrder->reported_by === $user->id
                || ($user->isDepartmentHead() && $workOrder->reporter?->role === $user->role),
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
        return match ($user->role?->value) {
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
        return in_array($user->role?->value, ['admin', 'manager']);
    }

    /**
     * Qui peut agir sur l'intervention (démarrer/arrêter le chrono, rapport).
     */
    public function intervene(User $user, WorkOrder $workOrder): bool
    {
        return match ($user->role?->value) {
            'admin', 'manager' => true,
            'technicien' => $workOrder->assigned_to === $user->id,
            default => false,
        };
    }

    /**
     * Qui peut (ré)affecter un technicien / planifier l'OT.
     * Alias de update() — noms distincts uniquement pour lisibilité des vues.
     */
    public function assign(User $user, WorkOrder $workOrder): bool
    {
        return $this->update($user, $workOrder);
    }

    /**
     * Qui peut agir au quotidien sur l'OT (statut, commentaire, pièce, rapport...).
     * Alias de intervene().
     */
    public function work(User $user, WorkOrder $workOrder): bool
    {
        return $this->intervene($user, $workOrder);
    }

    /**
     * Qui EXÉCUTE la réparation : chrono, rapport d'intervention, signature,
     * sortie de pièces du stock. Uniquement la personne assignée à l'OT, quel que
     * soit son rôle : un admin ou un manager supervise, il ne travaille pas « à la
     * place » du technicien (sinon temps, rapport et signature portent un faux nom).
     * S'il répare lui-même, il s'assigne d'abord l'OT (cf. takeOver).
     */
    public function perform(User $user, WorkOrder $workOrder): bool
    {
        // "rejete" reste exécutable : c'est là que le technicien fait la correction demandée.
        return $workOrder->assigned_to === $user->id
            && $workOrder->status !== 'ferme';
    }

    /**
     * Un admin ou un manager (ex. le chef de maintenance, un soir sans technicien)
     * prend l'OT pour le réparer lui-même : son nom apparaîtra honnêtement partout.
     */
    public function takeOver(User $user, WorkOrder $workOrder): bool
    {
        return in_array($user->role?->value, ['admin', 'manager'], true)
            && $workOrder->assigned_to !== $user->id
            && in_array($workOrder->status, ['ouvert', 'en_cours', 'en_attente'], true);
    }

    /**
     * Qui peut valider/rejeter un contrôle qualité : admin ou manager, mais jamais
     * sur un OT qu'il a lui-même exécuté (séparation des tâches : celui qui fait
     * ne contrôle pas). Sans OT : la question « a-t-il le rôle ? » seulement.
     */
    public function reviewQuality(User $user, ?WorkOrder $workOrder = null): bool
    {
        return in_array($user->role?->value, ['admin', 'manager'], true)
            && ! $workOrder?->wasExecutedBy($user);
    }
}