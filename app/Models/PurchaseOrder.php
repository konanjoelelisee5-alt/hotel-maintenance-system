<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'supplier_id', 'work_order_id', 'created_by',
        'status', 'order_date', 'expected_delivery_date', 'total_amount', 'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function recalculateTotal(): void
    {
        $total = $this->items->sum(fn (PurchaseOrderItem $item) => $item->quantity * $item->unit_price);

        $this->updateQuietly(['total_amount' => $total]);
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->items->every(fn (PurchaseOrderItem $item) => $item->received_quantity >= $item->quantity);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'brouillon' => 'Brouillon',
            'envoyee' => 'Envoyée',
            'confirmee' => 'Confirmée',
            'reception_partielle' => 'Réception partielle',
            'receptionnee' => 'Réceptionnée',
            'facturee' => 'Facturée',
            'annulee' => 'Annulée',
            default => $this->status,
        };
    }

    /**
     * Génère un numéro de commande unique et lisible (ex: BC-2026-0001),
     * en utilisant un compteur atomique pour être fiable même en cas
     * de créations simultanées par plusieurs utilisateurs.
     */
    public static function generateNumber(): string
    {
        $year = now()->year;
        $key = "purchase_order_{$year}";

        // INSERT ... ON DUPLICATE KEY UPDATE est une opération ATOMIQUE en MySQL :
        // aucune autre requête ne peut s'intercaler entre la lecture et l'écriture
        // de cette valeur, contrairement à un simple "SELECT puis UPDATE" séparés.
        DB::statement(
            'INSERT INTO number_sequences (sequence_key, value, created_at, updated_at)
             VALUES (?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE value = value + 1, updated_at = NOW()',
            [$key]
        );

        $nextNumber = DB::table('number_sequences')->where('sequence_key', $key)->value('value');

        return sprintf('BC-%d-%04d', $year, $nextNumber);
    }
}