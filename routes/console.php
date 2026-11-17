<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');




Schedule::call(function () {
    $greenhouseStats = app(\App\Services\GreenhouseService::class)->syncJobs();

    $ashbyStats = app(\App\Services\AshbyService::class)->syncJobs();

    Artisan::call('app:classify-jobs');

    logger()->info('Daily job sync completed', [
        'greenhouse' => $greenhouseStats,
        'ashby' => $ashbyStats,
        'classification_output' => Artisan::output(),
    ]);
})
    ->name('daily-job-sync')
    ->dailyAt('02:00')
    ->withoutOverlapping();
