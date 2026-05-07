<?php

namespace App\Console\Commands;

use App\Models\Service;
use Illuminate\Console\Command;

class ConvertServicesToUsd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:convert-to-usd {rate=1450 : The conversion rate from NGN to USD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-time migration to convert selling_price from NGN to USD base';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rate = (float) $this->argument('rate');
        
        if ($rate <= 0) {
            $this->error("Invalid rate!");
            return;
        }

        $this->info("Starting conversion using rate: 1 USD = {$rate} NGN");

        $services = Service::all();
        $bar = $this->output->createProgressBar(count($services));

        $bar->start();

        // Get the current NGN exchange rate to convert to USD Master
        $ngnCurrency = \App\Models\Currency::where('code', 'NGN')->first() ?? (object)['exchange_rate' => 1450.0];
        $ngnRate = $ngnCurrency->exchange_rate;

        foreach ($services as $service) {
            $oldPrice = $service->selling_price;
            $newPrice = $oldPrice / $rate;
            
            $service->update([
                'selling_price' => $newPrice
            ]);
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully converted " . count($services) . " services to USD base.");
    }
}
