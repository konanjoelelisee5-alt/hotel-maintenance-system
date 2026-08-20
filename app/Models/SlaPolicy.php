<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    protected $fillable = [
        'name', 'priority', 'work_order_type',
        'response_time_minutes', 'resolution_time_minutes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * Trouve la politique SLA la plus spécifique pour un OT donné
     * (priorité + type), en repliant progressivement vers des règles
     * plus générales si aucune correspondance exacte n'existe.
     */
    public static function findBestMatch(string $priority, string $workOrderType): ?self
    {
        return static::where('is_active', true)
            ->where(function ($query) use ($priority, $workOrderType) {
                $query->where(function ($q) use ($priority, $workOrderType) {
                    // Cas 1 : correspondance exacte (priorité ET type)
                    $q->where('priority', $priority)->where('work_order_type', $workOrderType);
                })
                ->orWhere(function ($q) use ($priority) {
                    // Cas 2 : correspondance sur la priorité seulement
                    $q->where('priority', $priority)->whereNull('work_order_type');
                })
                ->orWhere(function ($q) use ($workOrderType) {
                    // Cas 3 : correspondance sur le type seulement
                    $q->whereNull('priority')->where('work_order_type', $workOrderType);
                })
                ->orWhere(function ($q) {
                    // Cas 4 : politique générale (filet de sécurité)
                    $q->whereNull('priority')->whereNull('work_order_type');
                });
            })
            // On trie pour que le cas le plus spécifique arrive en premier :
            // une politique avec priority ET type définis (non null) est plus spécifique
            // qu'une politique avec un seul des deux, elle-même plus spécifique qu'une politique générale.
            ->orderByRaw('(priority IS NOT NULL) + (work_order_type IS NOT NULL) DESC')
            ->first();
    }
}