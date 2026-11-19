<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');




/**
 * Automated Daily Refresh Pipeline (Beep Requirement: "refreshes every day on its own")
 *
 * Stage 1: Scrape all registered spiders daily at midnight (00:00).
 * Stage 2: 5 minutes later (00:05), run Gemini AI classification on all newly scraped jobs.
 */
Schedule::command('jobs:scrape-all')
    ->dailyAt('00:00')
    ->withoutOverlapping();

Schedule::command('app:classify-jobs')
    ->dailyAt('00:05')
    ->withoutOverlapping();

