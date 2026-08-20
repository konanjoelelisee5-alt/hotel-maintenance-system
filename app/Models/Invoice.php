<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model
{
    protected $fillable = [
        'purchase_order_id', 'invoice_number', 'invoice_date',
        'amount', 'file_path', 'is_paid', 'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'paid_at' => 'date',
        'is_paid' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }
}