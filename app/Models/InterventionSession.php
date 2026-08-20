<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterventionSession extends Model
{
    protected $fillable = [
        'work_order_id', 'technician_id',
        'started_at', 'ended_at', 'duration_minutes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * La session est-elle actuellement en cours (pas encore arrêtée) ?
     */
    public function getIsActiveAttribute(): bool
    {
        return is_null($this->ended_at);
    }

    /**
     * Arrête la session et calcule automatiquement la durée.
     */
    public function stop(): void
    {
        $endedAt = now();

        $this->update([
            'ended_at' => $endedAt,
            'duration_minutes' => $this->started_at->diffInMinutes($endedAt),
        ]);
    }
}