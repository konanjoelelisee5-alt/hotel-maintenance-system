<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku', 'name', 'unit', 'quantity_on_hand', 'quantity_reserved',
        'reorder_threshold', 'unit_cost', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'unit_cost' => 'decimal:2',
    ];

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function reservations()
    {
        return $this->hasMany(PartReservation::class);
    }

    public function getQuantityAvailableAttribute(): int
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }

    public function getIsBelowThresholdAttribute(): bool
    {
        return $this->quantity_on_hand <= $this->reorder_threshold;
    }

    /**
     * Enregistre un mouvement de stock et met à jour la quantité en stock
     * de façon sécurisée contre les accès concurrents (verrou de ligne).
     *
     * @param  int  $quantity  Toujours positif ; le signe est déterminé par $type.
     */
    public function recordMovement(string $type, int $quantity, array $attributes = []): StockMovement
    {
        return DB::transaction(function () use ($type, $quantity, $attributes) {
            // Verrouille la ligne "part" le temps de la transaction, pour empêcher
            // un autre processus de lire/modifier la même quantité simultanément.
            $part = static::where('id', $this->id)->lockForUpdate()->first();

            $quantityBefore = $part->quantity_on_hand;

            $delta = match ($type) {
                'entree' => $quantity,
                'sortie' => -$quantity,
                'ajustement' => $quantity, // peut être négatif si $quantity est négatif
                default => 0,
            };

            $part->quantity_on_hand = max(0, $part->quantity_on_hand + $delta);
            $part->save();

            $movement = $part->stockMovements()->create([
                'type' => $type,
                'quantity' => $quantity,
                'created_by' => Auth::id(),
                ...$attributes,
            ]);

            // Alerte uniquement au moment où le stock PASSE sous le seuil
            // (transition), pour ne pas spammer de notification à chaque
            // mouvement supplémentaire tant qu'on reste sous le seuil.
            if ($quantityBefore > $part->reorder_threshold && $part->is_below_threshold) {
                $part->notifyLowStock();
            }

            return $movement;
        });
    }

    /**
     * Notifie les managers/admins qu'une pièce vient de passer sous son seuil d'alerte.
     */
    public function notifyLowStock(): void
    {
        $recipients = User::whereIn('role', ['manager', 'admin'])->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new \App\Notifications\LowStockNotification($this));
        }
    }
}