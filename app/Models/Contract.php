<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'supplier_id', 'reference', 'title', 'description',
        'start_date', 'end_date', 'amount', 'file_path', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Le contrat est-il arrivé à expiration (date dépassée) ?
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'actif' => 'Actif',
            'expire' => 'Expiré',
            'resilie' => 'Résilié',
            default => $this->status,
        };
    }
}