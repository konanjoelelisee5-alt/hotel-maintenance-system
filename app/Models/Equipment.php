<?php

namespace App\Models;

use App\Models\Concerns\LogsConfigurationChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory, LogsConfigurationChanges;

    const LOG_PREFIX = 'equipment';
    const LOG_LABEL = "l'équipement";

    public const STATUS_LABELS = [
        'operationnel' => 'Opérationnel',
        'maintenance' => 'En maintenance',
        'hors_service' => 'Hors service',
    ];

    protected $table = 'equipment';

    protected $fillable = ['name', 'type', 'room_id', 'status'];

    protected $attributes = [
        'status' => 'operationnel',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function maintenancePlans()
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /** « Climatiseur — Chambre 312 » : un nom seul est ambigu (il y a 40 climatiseurs). */
    public function getLabelAttribute(): string
    {
        return $this->room ? "{$this->name} — {$this->room->label}" : $this->name;
    }

    /** Équipements qu'on peut encore viser par un OT ou un plan préventif. */
    public function scopeInService(Builder $query): Builder
    {
        return $query->where('status', '!=', 'hors_service');
    }

    /**
     * Équipements proposés dans un formulaire : hors service masqués, sauf
     * celui déjà sélectionné.
     */
    public static function forSelect(?int $keepId = null)
    {
        return static::with('room')
            ->where(fn (Builder $q) => $q->inService()->when($keepId, fn ($q) => $q->orWhere('id', $keepId)))
            ->orderBy('name')
            ->get();
    }
}
