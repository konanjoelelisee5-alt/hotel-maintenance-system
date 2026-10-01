<?php

namespace App\Notifications;

use App\Models\RoomBlock;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Étapes du blocage d'une chambre, pour la réception (qui décide et tient Opera
 * à jour) et pour la personne qui a demandé le blocage.
 */
class RoomBlockNotification extends Notification
{
    use Queueable;

    public const REQUESTED = 'requested';
    public const APPROVED = 'approved';
    public const REFUSED = 'refused';
    public const RELEASED = 'released';

    public function __construct(
        protected RoomBlock $block,
        protected string $step,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $block = $this->block->loadMissing('room', 'requester', 'decider', 'releaser');
        $place = $block->room->label;

        $message = match ($this->step) {
            self::REQUESTED => "🚫 Blocage demandé : {$place} — {$block->reason} (par {$block->requester->name}). À valider.",
            self::APPROVED => "🚫 {$place} bloquée par {$block->decider?->name} : retirée de la vente jusqu'à la réparation.",
            self::REFUSED => "↩️ Blocage de {$place} refusé par {$block->decider?->name}"
                .($block->decision_note ? " : {$block->decision_note}" : '.'),
            self::RELEASED => "🟢 {$place} remise en vente par {$block->releaser?->name}. Pensez à la remettre en service dans Opera.",
        };

        return [
            'room_block_id' => $block->id,
            'target' => 'room-blocks',
            'step' => $this->step,
            'message' => $message,
        ];
    }
}
