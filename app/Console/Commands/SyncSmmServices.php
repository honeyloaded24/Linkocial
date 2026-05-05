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
            Log::warning('SMM Sync: No active providers found in database.');
            return;
        }

        foreach ($providers as $provider) {
            /** @var \App\Models\Provider $provider */
            $this->info("Fetching services for Provider: {$provider->name}");
            Log::info("SMM Sync: Starting fetch for {$provider->name}");
            
            $services = $smmService->getServices($provider);

            if (!$services || !is_array($services)) {
                $this->error("Failed to fetch services for {$provider->name} or response not an array.");
                Log::error("SMM Sync: Failed fetch for {$provider->name}. Response: " . json_encode($services));
                continue;
            }

            $count = count($services);
            $this->info("Received {$count} services from {$provider->name}");
            Log::info("SMM Sync: Received {$count} services from {$provider->name}");

            $syncedCount = 0;
            $newCount = 0;
            $errorCount = 0;

            foreach ($services as $providerService) {
                try {
                    // Extract data with fallbacks and sanitization
                    $providerServiceId = (string) ($providerService['service'] ?? ($providerService['id'] ?? ''));
                    $name = trim($providerService['name'] ?? 'Unknown Service');
                    $category = trim($providerService['category'] ?? 'General');
                    $rate = (float) ($providerService['rate'] ?? 0);
                    $min = (int) ($providerService['min'] ?? 0);
                    $max = (int) ($providerService['max'] ?? 0);
                    $type = trim($providerService['type'] ?? 'Default');
                    $desc = $providerService['desc'] ?? null;
                    $averageTime = $providerService['average_time'] ?? null;

                    if (empty($providerServiceId)) {
                        Log::debug("SMM Sync: Skipping service with no ID from {$provider->name}");
                        continue;
                    }

                    // Check if mapping exists for this provider and service ID
                    $mapping = ProviderServiceMapping::where('provider_id', $provider->id)
                                ->where('provider_service_id', $providerServiceId)
                                ->first();

                    if ($mapping) {
                        // Update existing mapping cost
                        $mapping->update(['original_price' => $rate]);
                        
                        if ($mapping->service) {
                            $mapping->service->update([
                                'category' => $category,
                                'description' => $desc,
                                'average_time' => $averageTime,
                                'min' => $min,
                                'max' => $max,
                            ]);
                        }
                        $syncedCount++;
                    } else {
                        // Create a NEW service and mapping
                        DB::transaction(function () use ($provider, $providerServiceId, $name, $rate, $min, $max, $type, $category, $desc, $averageTime) {
                            $internalService = Service::create([
                                'name' => "[Review] {$name}",
                                'category' => $category,
                                'description' => $desc,
                                'average_time' => $averageTime,
                                'selling_price' => $rate * 1.5, // Default markup
                                'min' => $min,
                                'max' => $max,
                                'is_active' => false, // New services must be reviewed
                                'type' => $type
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
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("SMM Sync Error [{$provider->name}] - Service ID: " . ($providerService['service'] ?? 'N/A') . " - Error: " . $e->getMessage());
                }
            }

            $this->info("Completed {$provider->name}: Synced {$syncedCount}, Added {$newCount}, Errors {$errorCount}");
            Log::info("SMM Sync: Completed {$provider->name}. Synced: {$syncedCount}, Added: {$newCount}, Errors: {$errorCount}");
        }

        $this->info('Synchronization complete!');
        Log::info('SMM Sync: Full process complete.');
    }
}
