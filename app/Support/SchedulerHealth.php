<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * "Battement de cœur" des tâches automatiques : chaque tâche note l'heure de sa
 * dernière exécution réussie, et le tableau de bord admin signale celles qui ne
 * tournent plus. Sans cela, un planificateur arrêté est une panne silencieuse :
 * plus d'escalade, plus d'OT préventif, et personne ne s'en aperçoit.
 */
class SchedulerHealth
{
    /** Tâches suivies et leur fréquence attendue (en minutes), cf. routes/console.php. */
    public const TASKS = [
        'sla' => ['label' => 'Vérification des délais SLA', 'every' => 15],
        'preventive' => ['label' => 'Génération des OT préventifs', 'every' => 24 * 60, 'at' => '05:00'],
        'guest_returns' => ['label' => 'Alerte réception avant le retour des clients', 'every' => 15],
    ];

    public static function record(string $task): void
    {
        Setting::put(self::key($task), now()->toIso8601String());
    }

    /** La tâche a-t-elle tourné depuis ce moment ? (rattrapage des préventifs, routes/console.php) */
    public static function ranSince(string $task, Carbon $moment): bool
    {
        return (bool) self::lastRun($task)?->gte($moment);
    }

    public static function lastRun(string $task): ?Carbon
    {
        $value = Setting::get(self::key($task));

        return $value ? Carbon::parse($value) : null;
    }

    /**
     * @return Collection<int, array{label: string, state: string, text: string}>
     *         state : "ok" | "late" | "waiting" | "never"
     */
    public static function report(): Collection
    {
        // Le planificateur tourne si une tâche est passée dans son délai normal : une tâche
        // quotidienne jamais exécutée attend alors simplement son heure (ex. 5 h).
        $alive = collect(self::TASKS)->contains(fn (array $task, string $key) => self::lastRun($key)?->gte(now()->subMinutes($task['every'] * 2 + 5)));

        return collect(self::TASKS)->map(function (array $task, string $key) use ($alive) {
            $last = self::lastRun($key);

            // Marge : deux cycles manqués (plus 5 min) avant de crier au retard.
            $state = match (true) {
                $last === null => $alive ? 'waiting' : 'never',
                $last->lt(now()->subMinutes($task['every'] * 2 + 5)) => 'late',
                default => 'ok',
            };

            return [
                'label' => $task['label'],
                'state' => $state,
                'text' => match ($state) {
                    'waiting' => 'En attente : premier passage prévu '.self::nextRun($task),
                    'never' => "Jamais exécutée : le planificateur ne tourne pas",
                    'late' => 'En retard — dernier passage '.$last->locale('fr')->diffForHumans(),
                    'ok' => 'Dernier passage '.$last->locale('fr')->diffForHumans(),
                },
            ];
        })->values();
    }

    /** Prochain passage d'une tâche qui n'a encore jamais tourné (cf. routes/console.php). */
    private static function nextRun(array $task): string
    {
        if (! isset($task['at'])) {
            return "dans moins de {$task['every']} minutes";
        }

        // Passé l'heure prévue, la tâche est rattrapée au prochain quart d'heure (routes/console.php).
        return now()->setTimeFromTimeString($task['at'])->isFuture()
            ? "aujourd'hui à {$task['at']}"
            : 'au prochain quart d\'heure (rattrapage du jour)';
    }

    private static function key(string $task): string
    {
        return "scheduler.last_run.{$task}";
    }
}
