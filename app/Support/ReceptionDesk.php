<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\GuestRoomAtRiskNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * La réception : elle vend les chambres (dans Opera), valide leur blocage et gère
 * les clients. L'application la prévient, elle agit comme aujourd'hui.
 */
class ReceptionDesk
{
    /**
     * @return Collection<int, User>
     */
    public static function staff(): Collection
    {
        return User::where('role', UserRole::Reception)->where('is_active', true)->get();
    }

    /** Prévient la réception une seule fois par OT qu'un client est concerné. */
    public static function alertGuestAtRisk(WorkOrder $workOrder, string $reason): void
    {
        if ($workOrder->reception_alerted_at !== null) {
            return;
        }

        Notification::send(self::staff(), new GuestRoomAtRiskNotification($workOrder->loadMissing('room'), $reason));
        $workOrder->forceFill(['reception_alerted_at' => now()])->saveQuietly();
    }
}
