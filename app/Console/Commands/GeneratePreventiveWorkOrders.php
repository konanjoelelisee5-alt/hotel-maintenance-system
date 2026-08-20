<?php

namespace App\Console\Commands;

use App\Models\MaintenancePlan;
use Illuminate\Console\Command;

class GeneratePreventiveWorkOrders extends Command
{
    /**
     * Le nom et la signature de la commande, tel qu'on l'appellera en ligne de commande.
     */
    protected $signature = 'maintenance:generate-preventive-work-orders';

    /**
     * Description affichée dans "php artisan list".
     */
    protected $description = "Génère automatiquement les ordres de travail issus des plans de maintenance préventive arrivés à échéance.";

    public function handle(): int
    {
        $this->info('Vérification des plans de maintenance préventive...');

        $today = today();
        $plans = MaintenancePlan::candidateForGeneration($today)->get();

        $generated = 0;

        foreach ($plans as $plan) {
            if (! $plan->isDueForGeneration($today)) {
                continue;
            }

            $workOrder = $plan->generateWorkOrder();
            $generated++;

            $this->info("OT #{$workOrder->id} généré pour le plan « {$plan->name} » (échéance {$workOrder->due_date->format('d/m/Y')}).");
        }

        $this->info("Terminé : {$generated} ordre(s) de travail généré(s) automatiquement.");

        return self::SUCCESS;
    }
}
