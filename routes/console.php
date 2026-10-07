<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\CheckWorkOrderSla;
use App\Console\Commands\GeneratePreventiveWorkOrders;
use App\Console\Commands\WatchGuestReturns;
use App\Support\SchedulerHealth;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CheckWorkOrderSla::class)->everyFifteenMinutes();
// Préventifs : une fois par jour à partir de 5 h. Si l'ordinateur était éteint à 5 h, la
// génération est rattrapée au premier passage suivant (tous les quarts d'heure jusqu'à minuit).
Schedule::command(GeneratePreventiveWorkOrders::class)
    ->everyFifteenMinutes()
    ->when(function () {
        $fiveAm = today()->setTimeFromTimeString('05:00');

        return now()->gte($fiveAm) && ! SchedulerHealth::ranSince('preventive', $fiveAm);
    });
Schedule::command(WatchGuestReturns::class)->everyFifteenMinutes();