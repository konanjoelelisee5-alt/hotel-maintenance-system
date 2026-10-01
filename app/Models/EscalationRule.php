<?php

namespace App\Models;

use App\Models\Concerns\LogsConfigurationChanges;
use Illuminate\Database\Eloquent\Model;

class EscalationRule extends Model
{
    use LogsConfigurationChanges;

    const LOG_PREFIX = 'escalation_rule';
    const LOG_LABEL = "la règle d'escalade";

    protected $fillable = [
        'name', 'trigger_type', 'offset_minutes', 'notify_target', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function logs()
    {
        return $this->hasMany(EscalationLog::class);
    }

    public function getTriggerTypeLabelAttribute(): string
    {
        return match ($this->trigger_type) {
            'reponse_proche' => 'Réponse bientôt due',
            'reponse_depassee' => 'Réponse dépassée',
            'resolution_proche' => 'Résolution bientôt due',
            'resolution_depassee' => 'Résolution dépassée',
            default => $this->trigger_type,
        };
    }
}