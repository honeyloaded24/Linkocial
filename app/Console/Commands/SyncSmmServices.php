<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Provider;
use App\Models\Service;
use App\Models\ProviderServiceMapping;
use App\Services\SmmProviderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSmmServices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'smm:sync-services';

    /**
     * The console command description.
     */
    protected $description = 'Fetch and synchronize services from all active SMM providers';

    /**
     * Execute the console command.
     */
    public function handle(SmmProviderService $smmService)
    {
        $this->info('Starting SMM Service Synchronization...');
        
        $providers = Provider::where('is_active', true)->get();

        if ($providers->isEmpty()) {
            $this->warn('No active providers found.');
            return;
        }

        /** @var \App\Models\Provider $provider */
        foreach ($providers as $provider) {
            $this->info("Fetching services for Provider: {$provider->name}");
            
            $services = $smmService->getServices($provider);

            if (!$services) {
                $this->error("Failed to fetch services for {$provider->name}");
                continue;
            }

            $syncedCount = 0;
            $newCount = 0;

            foreach ($services as $providerService) {
                // SMM v2 APIs typically return: service, name, type, rate, min, max, desc, average_time
                $providerServiceId = $providerService['service'] ?? null;
                $name = $providerService['name'] ?? 'Unknown Service';
                $rate = $providerService['rate'] ?? 0;
                $min = $providerService['min'] ?? 0;
                $max = $providerService['max'] ?? 0;
                $type = $providerService['type'] ?? 'Default';
                $category = $providerService['category'] ?? 'Default';
                $desc = $providerService['desc'] ?? null;
                $averageTime = $providerService['average_time'] ?? null;

                if (!$providerServiceId) continue;

                // Check if mapping exists
                $mapping = ProviderServiceMapping::where('provider_id', $provider->id)
                            ->where('provider_service_id', $providerServiceId)
                            ->first();

                if ($mapping) {
                    // Update existing mapping cost and service details
                    $mapping->update([
                        'original_price' => $rate
                    ]);
                    
                    $mapping->service->update([
                        'category' => $category,
                        'description' => $desc,
                        'average_time' => $averageTime,
                        'min' => $min,
                        'max' => $max,
                    ]);
                    $syncedCount++;
                } else {
                    // This is a completely new service from the provider.
                    // We automatically create an inactive internal service for admin review.
                    DB::transaction(function () use ($provider, $providerServiceId, $name, $rate, $min, $max, $type, $category, $desc, $averageTime) {
                        
                        /** @var \App\Models\Service $internalService */
                        $internalService = Service::create([
                            'name' => "[Review] {$name}",
                            'category' => $category,
                            'description' => $desc,
                            'average_time' => $averageTime,
                            'selling_price' => $rate * 1.5, // Auto-apply a default 50% markup for review
                            'type' => $type,
                            'min' => $min,
                            'max' => $max,
                            'is_active' => false, // Requires manual admin approval
                        ]);

                        ProviderServiceMapping::create([
                            'service_id' => $internalService->id,
                            'provider_id' => $provider->id,
                            'provider_service_id' => $providerServiceId,
                            'original_price' => $rate,
                            'priority' => 1,
                            'is_active' => true,
                        ]);
                    });

                    $newCount++;
                }
            }

            $this->info("Completed {$provider->name}: Synced {$syncedCount} existing, Added {$newCount} new services for review.");
        }

        $this->info('Synchronization complete!');
    }
}
