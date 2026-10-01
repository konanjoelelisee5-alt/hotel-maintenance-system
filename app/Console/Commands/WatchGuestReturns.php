<?php

namespace App\Console\Commands;

use App\Enums\RoomOccupancy;
use App\Models\WorkOrder;
use App\Notifications\GuestRoomAtRiskNotification;
use App\Support\ReceptionDesk;
use App\Support\SchedulerHealth;
use Illuminate\Console\Command;

/**
 * Client sorti, retour proche, panne toujours là : la réception est prévenue
 * une heure avant l'échéance pour décider d'un délogement à temps.
 */
class WatchGuestReturns extends Command
{
    protected $signature = 'rooms:watch-guest-returns';

    protected $description = 'Prévient la réception quand un client va retrouver sa chambre encore en panne';

    /** Préavis laissé à la réception avant le retour du client. */
    public const NOTICE_MINUTES = 60;

    public function handle(): int
    {
        $atRisk = WorkOrder::open()
            ->where('room_occupancy', RoomOccupancy::ClientAbsent)
            ->whereNull('reception_alerted_at')
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addMinutes(self::NOTICE_MINUTES))
            ->with('room')
            ->get();

        $atRisk->each(fn (WorkOrder $w) => ReceptionDesk::alertGuestAtRisk($w, GuestRoomAtRiskNotification::DEADLINE));

        $this->info("{$atRisk->count()} chambre(s) signalée(s) à la réception.");

        SchedulerHealth::record('guest_returns');

        return self::SUCCESS;
    }
}
