<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAttachment;
use App\Support\Housekeeping;

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
     * Personne : un OT ne se supprime pas (son historique, ses temps et ses pièces
     * comptent dans les rapports). Un OT inutile s'annule avec un motif (cf. cancel).
     */
    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return false;
    }

    /**
     * Qui PILOTE l'OT (panneau « Pilotage ») : affecter, planifier, requalifier,
     * suspendre, relancer, annuler. Les superviseurs, pas l'intervenant.
     */
    public function pilot(User $user, WorkOrder $workOrder): bool
    {
        return (bool) $user->role?->dispatchesWork();
    }

    /** Mettre en attente un OT actif, avec un motif (pièce, accès chambre...). */
    public function suspend(User $user, WorkOrder $workOrder): bool
    {
        return $this->pilot($user, $workOrder) && in_array($workOrder->status, ['ouvert', 'en_cours'], true);
    }

    /** Relancer un OT mis en attente. */
    public function resume(User $user, WorkOrder $workOrder): bool
    {
        return $this->pilot($user, $workOrder) && $workOrder->status === 'en_attente';
    }

    /** Annuler un OT pas encore réparé (doublon, fausse alerte), avec un motif. */
    public function cancel(User $user, WorkOrder $workOrder): bool
    {
        return $this->pilot($user, $workOrder) && in_array($workOrder->status, ['ouvert', 'en_cours', 'en_attente'], true);
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
     * Retirer une pièce jointe : son auteur, ou un superviseur. Un technicien ne
     * retire pas la photo prise par la gouvernante (c'est la preuve du signalement).
     */
    public function deleteAttachment(User $user, WorkOrder $workOrder, WorkOrderAttachment $attachment): bool
    {
        return $attachment->work_order_id === $workOrder->id
            && $this->intervene($user, $workOrder)
            && ($attachment->uploaded_by === $user->id || $user->role?->dispatchesWork());
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

    /**
     * Le service qui a signalé (l'agent, ou le responsable de son service) confirme
     * que la panne est réglée, ou rouvre l'OT, pendant les jours qui suivent la réparation.
     */
    public function confirmResolution(User $user, WorkOrder $workOrder): bool
    {
        return in_array($user->role?->value, ['housekeeping', 'reception'], true)
            && $this->view($user, $workOrder)
            && in_array($workOrder->status, ['resolu', 'ferme'], true)
            && $workOrder->requester_confirmed_at === null
            && $workOrder->completed_at?->greaterThanOrEqualTo(now()->subDays(WorkOrder::CONFIRMATION_WINDOW_DAYS));
    }

    /**
     * Housekeeping : l'agent retire son propre signalement fait par erreur (mauvaise
     * chambre, doublon), tant que personne ne s'en occupe et peu après l'envoi.
     * L'OT n'est pas supprimé : il passe « annulé », avec le motif dans l'historique.
     */
    public function withdraw(User $user, WorkOrder $workOrder): bool
    {
        return $user->role?->value === 'housekeeping'
            && $workOrder->reported_by === $user->id
            && $workOrder->status === 'ouvert'
            && $workOrder->assigned_to === null
            && $workOrder->created_at?->greaterThanOrEqualTo(now()->subMinutes(Housekeeping::WITHDRAW_WINDOW_MINUTES));
    }

    /**
     * Housekeeping : ajouter une précision (texte, message vocal, photo) à un signalement
     * pas encore réparé. On ajoute à la suite, on ne modifie rien de ce qui existe.
     */
    public function complement(User $user, WorkOrder $workOrder): bool
    {
        return $user->role?->value === 'housekeeping'
            && $this->view($user, $workOrder)
            && in_array($workOrder->status, ['ouvert', 'en_cours', 'en_attente', 'rejete'], true);
    }
}