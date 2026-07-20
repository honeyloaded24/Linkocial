<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmsPoolService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateSmsPoolPrices extends Command
{
    protected $signature = 'smspool:update-prices';
    protected $description = 'Query SMSPool to fetch and cache virtual number prices for all countries';

    public function handle(SmsPoolService $smspool)
    {
        // Increase limits for fetching the complete pricing database
        ini_set('memory_limit', '1024M');
        set_time_limit(180);

        $this->info('Starting update of SMSPool prices...');
        Log::info('Artisan command smspool:update-prices started.');

        try {
            // Fetch all prices in one call
            $prices = $smspool->getPricesForCountry();

            if (empty($prices)) {
                $this->error('No pricing data received from SMSPool.');
                return 1;
            }

            $grouped = [];
            foreach ($prices as $item) {
                $countryId = $item['country'] ?? null;
                if ($countryId !== null) {
                    $grouped[$countryId][] = $item;
                }
            }

            $count = 0;
            foreach ($grouped as $countryId => $countryPrices) {
                $cacheKey = "smspool_prices_{$countryId}";
                // Cache each country's pricing list for 10 minutes
                Cache::put($cacheKey, $countryPrices, now()->addMinutes(10));
                $count++;
            }

            $this->info("Successfully cached prices for {$count} countries.");
            Log::info("Successfully cached SMSPool prices for {$count} countries.");

            return 0;
        } catch (\Exception $e) {
            Log::error('UpdateSmsPoolPrices failed', ['error' => $e->getMessage()]);
            $this->error('Failed to update SMSPool prices: ' . $e->getMessage());
            return 1;
        }
    }
}
