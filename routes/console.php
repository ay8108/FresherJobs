<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');




// Strict 45-minute schedule (00:00, 00:45, 01:30, 02:15, 03:00, 03:45, etc.)
foreach ([
    '0,45 0,3,6,9,12,15,18,21 * * *',
    '30 1,4,7,10,13,16,19,22 * * *',
    '15 2,5,8,11,14,17,20,23 * * *',
] as $cronExpr) {
    Schedule::command('jobs:scrape-all')
        ->cron($cronExpr)
        ->withoutOverlapping();
}

