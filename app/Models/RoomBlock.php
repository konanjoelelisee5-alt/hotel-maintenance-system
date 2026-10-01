<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Chambre retirée de la vente pour panne. Cycle : demandee → bloquee (accord de la
 * réception) → levee (gouvernante, après vérification) ; ou demandee → refusee.
 * Pendant le blocage, la chambre est en statut « maintenance » (jamais « hors_service »,
 * réservé aux lieux retirés définitivement).
 */
class RoomBlock extends Model
{
    public const REQUESTED = 'demandee';
    public const BLOCKED = 'bloquee';
    public const REFUSED = 'refusee';
    public const RELEASED = 'levee';

    public const STATUS_LABELS = [
        self::REQUESTED => 'En attente de la réception',
        self::BLOCKED => 'Bloquée (hors vente)',
        self::REFUSED => 'Refusée',
        self::RELEASED => 'Remise en vente',
    ];

    protected $fillable = [
        'room_id', 'work_order_id', 'status', 'reason', 'requested_by',
        'decided_by', 'decided_at', 'decision_note', 'released_by', 'released_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function releaser()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /** Demande en cours ou blocage effectif : au plus un par chambre. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::REQUESTED, self::BLOCKED]);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
