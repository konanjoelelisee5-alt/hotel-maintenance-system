<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

/**
 * Journalise toute modification d'un élément de paramétrage (SLA, escalade,
 * priorité, type d'OT, compétence), quel que soit l'écran ou le code qui la fait.
 *
 * Le modèle déclare :
 *  - LOG_PREFIX : préfixe des actions du journal (ex. "sla_policy" → "sla_policy.updated")
 *  - LOG_LABEL  : nom lisible de l'élément (ex. "la politique SLA")
 *
 * Seules les modifications faites par un utilisateur connecté sont tracées : les
 * seeders et migrations ne polluent pas le journal.
 */
trait LogsConfigurationChanges
{
    /** Colonnes techniques dont le changement n'a pas d'intérêt pour l'audit. */
    private static array $ignoredForLog = ['updated_at', 'created_at'];

    public static function bootLogsConfigurationChanges(): void
    {
        static::created(function (self $model) {
            if (Auth::check()) {
                ActivityLog::record(static::LOG_PREFIX.'.created', 'Création de '.static::LOG_LABEL." « {$model->logName()} »", $model);
            }
        });

        static::updated(function (self $model) {
            if (! Auth::check()) {
                return;
            }

            $changes = collect($model->getChanges())
                ->except(self::$ignoredForLog)
                ->map(fn ($new, $field) => ['from' => $model->getOriginal($field), 'to' => $new]);

            if ($changes->isEmpty()) {
                return;
            }

            $action = match (true) {
                $changes->keys()->all() === ['is_active'] => $model->is_active ? 'reactivated' : 'deactivated',
                default => 'updated',
            };

            $verb = ['reactivated' => 'Réactivation', 'deactivated' => 'Désactivation', 'updated' => 'Modification'][$action];
            $detail = $action === 'updated'
                ? ' : '.$changes->map(fn ($c, $field) => "{$field} ".self::formatLogValue($c['from']).' → '.self::formatLogValue($c['to']))->implode(', ')
                : '';

            ActivityLog::record(
                static::LOG_PREFIX.'.'.$action,
                "{$verb} de ".static::LOG_LABEL." « {$model->logName()} »{$detail}",
                $model,
                $changes->all(),
            );
        });
    }

    public function logName(): string
    {
        return (string) ($this->name ?? $this->label ?? "#{$this->id}");
    }

    private static function formatLogValue(mixed $value): string
    {
        return match (true) {
            is_null($value) => '(vide)',
            is_bool($value) => $value ? 'oui' : 'non',
            default => (string) $value,
        };
    }
}
