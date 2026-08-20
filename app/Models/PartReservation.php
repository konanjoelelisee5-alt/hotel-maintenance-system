<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PartReservation extends Model
{
    protected $fillable = [
        'part_id', 'work_order_id', 'quantity', 'status', 'reserved_by',
    ];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function reservedBy()
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'reservee' => 'Réservée',
            'sortie' => 'Sortie',
            'annulee' => 'Annulée',
            default => $this->status,
        };
    }

    /**
     * Confirme la sortie physique du stock pour cette réservation :
     * libère la quantité réservée et enregistre le mouvement de sortie réel.
     */
    public function markAsWithdrawn(): void
    {
        DB::transaction(function () {
            $this->part->decrement('quantity_reserved', $this->quantity);

            $this->part->recordMovement('sortie', $this->quantity, [
                'work_order_id' => $this->work_order_id,
                'note' => 'Sortie suite à réservation #' . $this->id,
            ]);

            $this->update(['status' => 'sortie']);
        });
    }

    /**
     * Annule la réservation sans jamais avoir sorti physiquement le stock.
     */
    public function cancel(): void
    {
        DB::transaction(function () {
            $this->part->decrement('quantity_reserved', $this->quantity);
            $this->update(['status' => 'annulee']);
        });
    }

    protected static function booted(): void
    {
        static::created(function (PartReservation $reservation) {
            $reservation->part->increment('quantity_reserved', $reservation->quantity);
        });
    }
}