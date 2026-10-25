<?php

namespace App\Console\Commands;

use App\Models\Service;
use Illuminate\Console\Command;

class ConvertServicesToNgn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:convert-to-ngn 
                            {rate=1450 : The historical conversion rate from USD to NGN} 
                            {--max-threshold=100 : Only convert services whose selling_price is <= this threshold (assumed USD)}
                            {--dry-run : Preview changes without updating the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely convert legacy USD-denominated service selling prices back to NGN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rate = (float) $this->argument('rate');
        $threshold = (float) $this->option('max-threshold');
        $isDryRun = $this->option('dry-run');

        if ($rate <= 0) {
            $this->error("Invalid conversion rate specified: {$rate}");
            return Command::FAILURE;
        }

        $this->info("Scanning services for prices <= {$threshold} (legacy USD denomination)...");
        if ($isDryRun) {
            $this->warn("DRY RUN MODE: No database records will be modified.");
        }

        $services = Service::where('selling_price', '<=', $threshold)->get();
        $totalFound = $services->count();

        if ($totalFound === 0) {
            $this->info("No legacy USD services found below threshold {$threshold}. All services appear to already be denominated in NGN.");
            return Command::SUCCESS;
        }

        $this->info("Found {$totalFound} service(s) eligible for conversion at 1 USD = {$rate} NGN.");

        $bar = $this->output->createProgressBar($totalFound);
        $bar->start();

        $convertedCount = 0;
        foreach ($services as $service) {
            $oldPrice = (float) $service->selling_price;
            $newPrice = round($oldPrice * $rate, 2);

            if (!$isDryRun) {
                $service->update([
                    'selling_price' => $newPrice
                ]);
            }

            $convertedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($isDryRun) {
            $this->info("DRY RUN COMPLETE: Would convert {$convertedCount} service prices to NGN.");
        } else {
            $this->info("SUCCESS: Converted {$convertedCount} service prices to NGN using rate {$rate}.");
        }

        return Command::SUCCESS;
    }
}
