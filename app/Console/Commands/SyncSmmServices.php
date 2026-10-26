<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Provider;
use App\Models\Service;
use App\Models\ProviderServiceMapping;
use App\Services\SmmPricingService;
use Illuminate\Support\Facades\Log;

class SyncSmmServices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'smm:sync-services {--provider= : Specific provider ID to sync}';

    /**
     * The console command description.
     */
    protected $description = 'Fetch and synchronize services from SMM providers applying active FX rate and markup rules';

    /**
     * Execute the console command.
     */
    public function handle(SmmPricingService $pricingService)
    {
        $this->info('Starting SMM Service Synchronization...');
        $providerId = $this->option('provider');
        
        $query = Provider::where('is_active', true);
        if ($providerId) {
            $query->where('id', $providerId);
        }
        $providers = $query->get();

        if ($providers->isEmpty()) {
            $this->warn('No active providers found to synchronize.');
            Log::warning('SMM Sync: No active providers found in database.');
            return 0;
        }

        $totalSynced = 0;
        $totalNew = 0;

        foreach ($providers as $provider) {
            /** @var \App\Models\Provider $provider */
            $this->info("Synchronizing Provider: {$provider->name} (Markup: {$provider->getPercentageMarkup()}%, Mode: {$provider->getPricingMode()})");

            $result = $pricingService->executeProviderSync($provider);

            if ($result['success']) {
                $this->info(" ✓ {$provider->name}: {$result['message']}");
                $totalSynced += $result['synced_count'];
                $totalNew += $result['new_count'];
            } else {
                $this->error(" ✗ {$provider->name} Failed: " . ($result['error'] ?? 'Unknown error'));
            }
        }

        $this->newLine();
        $this->info("Synchronization complete! Total updated: {$totalSynced}, Total new imported: {$totalNew}.");
        return 0;
    }
}
