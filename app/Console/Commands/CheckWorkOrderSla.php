<?php

namespace App\Console\Commands;

use App\Models\EscalationLog;
use App\Models\EscalationRule;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\SlaEscalationNotification;
use Illuminate\Console\Command;

class CheckWorkOrderSla extends Command
{
    /**
     * Le nom et la signature de la commande, tel qu'on l'appellera en ligne de commande.
     */
    protected $signature = 'work-orders:check-sla';

    /**
     * Description affichée dans "php artisan list".
     */
    protected $description = 'Vérifie les délais SLA des OT actifs et déclenche les escalades nécessaires.';

    public function handle(): void
    {
        $this->info('Vérification des SLA en cours...');

        $rules = EscalationRule::where('is_active', true)->get();

        // On ne s'intéresse qu'aux OT encore actifs (pas déjà résolus/fermés)
        $activeWorkOrders = WorkOrder::whereNotIn('status', ['resolu', 'ferme'])
            ->whereNotNull('sla_resolution_due_at')
            ->get();

        $triggeredCount = 0;

        foreach ($activeWorkOrders as $workOrder) {
            foreach ($rules as $rule) {
                if ($this->shouldTrigger($workOrder, $rule) && ! $this->alreadyTriggered($workOrder, $rule)) {
                    $this->trigger($workOrder, $rule);
                    $triggeredCount++;
                }
            }
        }

        $this->info("Vérification terminée. {$triggeredCount} escalade(s) déclenchée(s).");
    }

    /**
     * Détermine si une règle d'escalade doit se déclencher MAINTENANT pour cet OT,
     * en comparant l'heure actuelle à l'échéance SLA + le décalage (offset) de la règle.
     */
    private function shouldTrigger(WorkOrder $workOrder, EscalationRule $rule): bool
    {
        $referenceDate = str_contains($rule->trigger_type, 'reponse')
            ? $workOrder->sla_response_due_at
            : $workOrder->sla_resolution_due_at;

        if (! $referenceDate) {
            return false;
        }

        // Le moment exact où cette règle doit se déclencher = échéance + décalage
        $triggerMoment = $referenceDate->copy()->addMinutes($rule->offset_minutes);

        // On considère que la règle "doit se déclencher" si ce moment est déjà passé
        return now()->greaterThanOrEqualTo($triggerMoment);
    }

    /**
     * Vérifie si cette combinaison OT + règle a déjà été enregistrée dans les logs,
     * pour éviter d'envoyer la même alerte plusieurs fois.
     */
    private function alreadyTriggered(WorkOrder $workOrder, EscalationRule $rule): bool
    {
        return EscalationLog::where('work_order_id', $workOrder->id)
            ->where('escalation_rule_id', $rule->id)
            ->exists();
    }

    /**
     * Déclenche réellement l'escalade : détermine qui notifier, envoie la notification,
     * et enregistre le log pour ne pas répéter cette alerte.
     */
    private function trigger(WorkOrder $workOrder, EscalationRule $rule): void
    {
        $recipients = $this->resolveRecipients($workOrder, $rule);

        foreach ($recipients as $recipient) {
            $recipient->notify(new SlaEscalationNotification($workOrder, $rule));
        }

        EscalationLog::create([
            'work_order_id' => $workOrder->id,
            'escalation_rule_id' => $rule->id,
            'notified_user_id' => $recipients->first()?->id,
            'triggered_at' => now(),
            'message' => "Règle « {$rule->name} » déclenchée pour l'OT #{$workOrder->id}.",
        ]);

        if (! $workOrder->sla_breached && str_contains($rule->trigger_type, 'depassee')) {
            $workOrder->update(['sla_breached' => true]);
        }
    }

    /**
     * Détermine qui doit recevoir la notification selon la cible configurée sur la règle.
     */
    private function resolveRecipients(WorkOrder $workOrder, EscalationRule $rule)
    {
        return match ($rule->notify_target) {
            'technicien_assigne' => $workOrder->assignee ? collect([$workOrder->assignee]) : collect(),
            'manager' => User::where('role', 'manager')->get(),
            'admin' => User::where('role', 'admin')->get(),
            default => collect(),
        };
    }
}