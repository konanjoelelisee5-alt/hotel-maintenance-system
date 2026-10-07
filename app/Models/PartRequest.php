<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Pièce absente du magasin, demandée par le technicien depuis son OT. Le manager
 * la commande ou la trouve, puis marque la demande « traitée » avec un mot.
 */
class PartRequest extends Model
{
    protected $fillable = [
        'work_order_id', 'requested_by', 'description', 'quantity',
        'status', 'handled_by', 'handled_at', 'handling_note',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'demandee');
    }
}
