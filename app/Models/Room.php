<?php

namespace App\Models;

use App\Models\Concerns\LogsConfigurationChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Un LIEU de l'hôtel : une chambre (numéro + étage) ou un espace commun
 * (code court + nom : chaufferie, piscine...). La table garde son nom historique.
 */
class Room extends Model
{
    use HasFactory, LogsConfigurationChanges;

    const LOG_PREFIX = 'room';
    const LOG_LABEL = 'le lieu';

    public const TYPE_ROOM = 'chambre';
    public const TYPE_COMMON_AREA = 'espace_commun';

    /** États possibles par type : "occupée" n'a pas de sens pour une chaufferie. */
    public const STATUSES = [
        self::TYPE_ROOM => ['disponible', 'occupee', 'maintenance', 'hors_service'],
        self::TYPE_COMMON_AREA => ['disponible', 'maintenance', 'hors_service'],
    ];

    public const STATUS_LABELS = [
        'disponible' => 'Disponible',
        'occupee' => 'Occupée',
        'maintenance' => 'En maintenance',
        'hors_service' => 'Hors service',
    ];

    protected $fillable = ['type', 'number', 'name', 'floor', 'status'];

    protected $attributes = [
        'type' => self::TYPE_ROOM,
        'status' => 'disponible',
    ];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    public function maintenancePlans()
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function isCommonArea(): bool
    {
        return $this->type === self::TYPE_COMMON_AREA;
    }

    /**
     * Libellé à afficher partout : « Chambre 312 » ou « Piscine ».
     */
    public function getLabelAttribute(): string
    {
        return $this->isCommonArea()
            ? ($this->name ?: $this->number)
            : 'Chambre '.$this->number;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function scopeRooms(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ROOM);
    }

    public function scopeCommonAreas(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_COMMON_AREA);
    }

    /** Lieux où l'on peut encore signaler une panne ou affecter un équipement. */
    public function scopeInService(Builder $query): Builder
    {
        return $query->where('status', '!=', 'hors_service');
    }

    /**
     * Ordre d'affichage des listes : chambres par numéro, puis espaces communs par nom.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw("CASE WHEN type = 'chambre' THEN 0 ELSE 1 END")
            ->orderBy('number');
    }

    /**
     * Lieux proposés dans un formulaire, groupés pour un <optgroup> :
     * les lieux hors service sont masqués, sauf celui déjà sélectionné.
     *
     * @return array<string, \Illuminate\Support\Collection<int, Room>>
     */
    public static function groupedForSelect(?int $keepId = null): array
    {
        $rooms = static::query()
            ->where(fn (Builder $q) => $q->inService()->when($keepId, fn ($q) => $q->orWhere('id', $keepId)))
            ->ordered()
            ->get();

        return [
            'Chambres' => $rooms->where('type', self::TYPE_ROOM)->values(),
            'Espaces communs' => $rooms->where('type', self::TYPE_COMMON_AREA)->sortBy('label')->values(),
        ];
    }
}
