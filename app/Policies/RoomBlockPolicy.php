<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RoomBlock;
use App\Models\User;
use App\Models\WorkOrder;

/**
 * Bloquer une chambre = la retirer de la vente : la gouvernante (ou la maintenance)
 * le demande, la réception — qui vend les chambres — décide, la gouvernante remet
 * en vente après avoir vérifié la réparation.
 */
class RoomBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isHousekeepingHead($user)
            || in_array($user->role, [UserRole::Reception, UserRole::Manager, UserRole::Admin], true);
    }

    /** Peut demander des blocages en général (le droit sur un OT précis passe par request()). */
    public function requestAny(User $user): bool
    {
        return $this->isHousekeepingHead($user)
            || in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function request(User $user, WorkOrder $workOrder): bool
    {
        return match (true) {
            in_array($user->role, [UserRole::Admin, UserRole::Manager], true) => true,
            $this->isHousekeepingHead($user) => $user->can('view', $workOrder),
            default => false,
        };
    }

    public function decide(User $user, RoomBlock $block): bool
    {
        return $block->status === RoomBlock::REQUESTED
            && in_array($user->role, [UserRole::Reception, UserRole::Admin], true);
    }

    public function release(User $user, RoomBlock $block): bool
    {
        return $block->status === RoomBlock::BLOCKED
            && ($this->isHousekeepingHead($user) || $user->role === UserRole::Admin);
    }

    private function isHousekeepingHead(User $user): bool
    {
        return $user->role === UserRole::Housekeeping && $user->isDepartmentHead();
    }
}
