<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'description', 'quantity', 'unit_price', 'received_quantity',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->received_quantity >= $this->quantity;
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    protected static function booted(): void
    {
        static::saved(function (PurchaseOrderItem $item) {
            $item->purchaseOrder->recalculateTotal();
        });

        static::deleted(function (PurchaseOrderItem $item) {
            $item->purchaseOrder->recalculateTotal();
        });
    }
}