<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'part_id', 'type', 'quantity', 'work_order_id',
        'purchase_order_id', 'created_by', 'note',
    ];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'entree' => 'Entrée',
            'sortie' => 'Sortie',
            'ajustement' => 'Ajustement',
            default => $this->type,
        };
    }
}