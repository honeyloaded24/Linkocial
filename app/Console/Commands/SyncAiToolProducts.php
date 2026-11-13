<?php

namespace App\Console\Commands;

use App\Models\AiToolProduct;
use App\Services\AlaricApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SyncAiToolProducts extends Command
{
    protected $signature   = 'ai-tools:sync-products {--force : Force sync even if recently synced}';
    protected $description = 'Synchronize AI Tools products from the Alaric Store provider API';

    public function handle(): int
    {
        $cacheKey = 'alaric_products_last_sync';

        // Throttle: skip if synced within the last 5 minutes (unless --force)
        if (!$this->option('force') && Cache::has($cacheKey)) {
            $this->info('Skipping — last sync was less than 5 minutes ago. Use --force to override.');
            return 0;
        }

        if (!Schema::hasTable('ai_tool_products')) {
            $this->error('ai_tool_products table does not exist. Run migrations first.');
            return 1;
        }

        $this->info('Fetching products from provider...');

        /** @var AlaricApiService $service */
        $service = app(AlaricApiService::class);
        $result  = $service->getProducts();

        if (!($result['success'] ?? false)) {
            $this->error('Failed to fetch products: ' . ($result['error_message'] ?? 'Unknown error'));
            Log::error('SyncAiToolProducts: API call failed', $result);
            return 1;
        }

        $products = $result['data'] ?? [];

        if (empty($products)) {
            $this->warn('No products returned from provider.');
            Cache::put($cacheKey, now(), 300);
            return 0;
        }

        $this->info('Processing ' . count($products) . ' products...');

        $syncedIds = [];
        $created   = 0;
        $updated   = 0;
        $errors    = 0;

        foreach ($products as $p) {
            try {
                $providerProductId = (string) ($p['id'] ?? '');
                if (empty($providerProductId)) {
                    continue;
                }

                $priceTiers = (!empty($p['price_tiers']) && is_array($p['price_tiers']))
                    ? $p['price_tiers']
                    : null;

                $fulfillment = in_array($p['fulfillment'] ?? '', ['stock', 'on_demand'])
                    ? $p['fulfillment']
                    : 'on_demand';

                $data = [
                    'name'           => (string) ($p['name'] ?? 'Unnamed Product'),
                    'price_usdt'     => (float)  ($p['price'] ?? 0),
                    'currency'       => (string) ($p['currency'] ?? 'USDT'),
                    'in_stock'       => (bool)   ($p['in_stock'] ?? false),
                    'stock'          => isset($p['stock']) ? (int) $p['stock'] : 0,
                    'max_quantity'   => isset($p['max_quantity']) ? (int) $p['max_quantity'] : 1,
                    'fulfillment'    => $fulfillment,
                    'price_tiers'    => $priceTiers,
                    'last_synced_at' => now(),
                ];

                $existing = AiToolProduct::where('provider_product_id', $providerProductId)->first();

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    // New products remain INACTIVE until admin approves
                    $data['provider_product_id'] = $providerProductId;
                    $data['is_active']           = false;
                    $data['is_featured']         = false;
                    $data['slug']                = Str::slug($data['name']) . '-' . Str::random(5);
                    AiToolProduct::create($data);
                    $created++;
                }

                $syncedIds[] = $providerProductId;
            } catch (\Throwable $e) {
                $errors++;
                Log::error('SyncAiToolProducts: Failed to process product', [
                    'product_id' => $p['id'] ?? 'unknown',
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // Mark products removed from provider as out-of-stock (preserve purchase history)
        if (!empty($syncedIds)) {
            $deactivated = AiToolProduct::whereNotIn('provider_product_id', $syncedIds)
                ->where('in_stock', true)
                ->update(['in_stock' => false]);

            if ($deactivated > 0) {
                $this->warn("Marked {$deactivated} product(s) out-of-stock (no longer in provider catalogue).");
            }
        }

        // Cache sync timestamp
        Cache::put($cacheKey, now(), 300);

        $this->info("Sync complete — created: {$created}, updated: {$updated}, errors: {$errors}.");
        Log::info('SyncAiToolProducts: Completed', compact('created', 'updated', 'errors'));

        return 0;
    }
}
