<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterventionSession extends Model
{
    protected $fillable = [
        'work_order_id', 'technician_id',
        'started_at', 'ended_at', 'duration_minutes',
        'is_manual', 'corrected_at',
    ];

    /** Une session saisie ou corrigée à la main ne peut dépasser cette durée. */
    public const MAX_MANUAL_MINUTES = 12 * 60;

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_manual' => 'boolean',
        'corrected_at' => 'datetime',
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
            // Minutes entières (diffInMinutes() renvoie un décimal depuis Carbon 3).
            'duration_minutes' => (int) floor($this->started_at->diffInMinutes($endedAt)),
        ]);
    }

    /** Minutes entières entre deux instants (diffInMinutes() renvoie un décimal depuis Carbon 3). */
    public static function minutesBetween($start, $end): int
    {
        return (int) floor($start->diffInMinutes($end));
    }
}
