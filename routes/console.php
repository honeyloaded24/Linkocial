<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the SMM Sync Command to run daily at midnight
Schedule::command('smm:sync-services')->dailyAt('00:00')->withoutOverlapping();

// Schedule the Order Status Check to run every 5 minutes
Schedule::command('smm:check-orders')->everyFiveMinutes()->withoutOverlapping();
