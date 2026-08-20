<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\CheckWorkOrderSla;
use App\Console\Commands\GeneratePreventiveWorkOrders;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CheckWorkOrderSla::class)->everyFifteenMinutes();
Schedule::command(GeneratePreventiveWorkOrders::class)->dailyAt('05:00');