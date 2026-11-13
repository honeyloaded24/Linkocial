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

// Schedule background check for abandoned virtual number orders every minute
Schedule::command('vn:check-expired')->everyMinute()->withoutOverlapping();

// Schedule SMSPool balance check every hour
Schedule::command('smspool:check-balance')->hourly()->withoutOverlapping();

// Schedule SMSPool price update every 5 minutes
Schedule::command('smspool:update-prices')->everyFiveMinutes()->withoutOverlapping();

// Schedule Fadded Log Marketplace automatic sync and price/stock updates every 30 minutes
Schedule::command('fadded:sync')->everyThirtyMinutes()->withoutOverlapping();

// Schedule AI Tools product synchronization every 30 minutes
Schedule::command('ai-tools:sync-products')->everyThirtyMinutes()->withoutOverlapping();

// Schedule background queue worker to process delayed jobs (like delayed welcome emails)
Schedule::command('queue:work --stop-when-empty --tries=3')->everyMinute()->withoutOverlapping();


