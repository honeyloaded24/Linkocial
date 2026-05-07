<?php

namespace App\Console\Commands;

use App\Models\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update-rates {--buffer=4 : Percentage buffer to add for margin protection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch live exchange rates and update the database with a safety buffer';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automated currency update...');
        
        $apiKey = config('services.exchangerate.key') ?: 'PASTE_YOUR_API_KEY_HERE';
        $buffer = (float) $this->option('buffer');
        
        // Use a reliable free API endpoint (Free tier allows 1,500 requests/month)
        $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/USD");

        if ($response->failed()) {
            $this->error('Failed to fetch rates from the global provider.');
            return 1;
        }

        $data = $response->json();
        $rates = $data['conversion_rates'] ?? [];

        if (empty($rates)) {
            $this->error('No rates found in the API response.');
            return 1;
        }

        $activeCurrencies = Currency::where('code', '!=', 'USD')->get();

        foreach ($activeCurrencies as $currency) {
            /** @var \App\Models\Currency $currency */
            if (isset($rates[$currency->code])) {
                $rawRate = (float) $rates[$currency->code];
                
                // Apply Profit Shield (Safety Buffer)
                // New Rate = Raw Rate * (1 + Buffer%)
                $protectedRate = $rawRate * (1 + ($buffer / 100));
                
                $currency->update([
                    'exchange_rate' => $protectedRate
                ]);

                // Seal the fix by clearing the cache
                Cache::forget("currency_data_{$currency->code}");

                $this->info("Updated {$currency->code}: Market was {$rawRate}, Sealed at {$protectedRate} (Inc. {$buffer}% Buffer)");
            } else {
                $this->warn("Rate for {$currency->code} not found in global feed.");
            }
        }

        $this->info('Global Currency Pulse: SYNCHRONIZED.');
        return 0;
    }
}
