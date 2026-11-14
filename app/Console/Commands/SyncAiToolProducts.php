<?php

namespace App\Console\Commands;

use App\Models\AiToolCategory;
use App\Models\AiToolProduct;
use App\Services\AlaricApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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

        // Auto-run migrations if tables do not exist
        if (!Schema::hasTable('ai_tool_products') || !Schema::hasTable('ai_tool_categories')) {
            $this->info('AI Tool tables not found. Running migrations...');
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::error('SyncAiToolProducts: auto-migrate failed', ['error' => $e->getMessage()]);
            }
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

        // Robust payload extraction: supports {data: [...]}, {products: [...]}, {data: {products: [...]}}, or raw list
        $raw = $result['data'] ?? [];
        if (isset($raw['products']) && is_array($raw['products'])) {
            $products = $raw['products'];
        } elseif (isset($raw['data']) && is_array($raw['data'])) {
            $products = $raw['data'];
        } elseif (is_array($raw) && array_is_list($raw)) {
            $products = $raw;
        } elseif (isset($raw['items']) && is_array($raw['items'])) {
            $products = $raw['items'];
        } else {
            $products = is_array($raw) ? $raw : [];
        }

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
                if (!is_array($p)) {
                    continue;
                }

                $providerProductId = (string) ($p['id'] ?? $p['product_id'] ?? $p['_id'] ?? '');
                if (empty($providerProductId)) {
                    continue;
                }

                $name = (string) ($p['name'] ?? $p['title'] ?? $p['product_name'] ?? 'Unnamed Product');
                $price = (float) ($p['price'] ?? $p['rate'] ?? $p['price_usdt'] ?? $p['cost'] ?? 0);
                $currency = (string) ($p['currency'] ?? 'USDT');
                $inStock = isset($p['in_stock']) ? (bool) $p['in_stock'] : (isset($p['stock']) ? ((int) $p['stock'] > 0) : true);
                $stock = isset($p['stock']) ? (int) $p['stock'] : 0;
                $maxQuantity = isset($p['max_quantity']) ? (int) $p['max_quantity'] : (isset($p['max']) ? (int) $p['max'] : 20);

                $priceTiers = (!empty($p['price_tiers']) && is_array($p['price_tiers']))
                    ? $p['price_tiers']
                    : null;

                $fulfillment = in_array($p['fulfillment'] ?? '', ['stock', 'on_demand'])
                    ? $p['fulfillment']
                    : 'on_demand';

                // Handle categories if present
                $categoryName = null;
                if (!empty($p['category']) && is_string($p['category'])) {
                    $categoryName = trim($p['category']);
                } elseif (!empty($p['category']['name']) && is_string($p['category']['name'])) {
                    $categoryName = trim($p['category']['name']);
                } elseif (!empty($p['category_name']) && is_string($p['category_name'])) {
                    $categoryName = trim($p['category_name']);
                }

                $categoryId = null;
                if ($categoryName && Schema::hasTable('ai_tool_categories')) {
                    $cat = AiToolCategory::firstOrCreate(
                        ['name' => $categoryName],
                        ['slug' => Str::slug($categoryName) . '-' . Str::random(4), 'is_active' => true]
                    );
                    $categoryId = $cat->id;
                }

                $description = (string) ($p['description'] ?? $p['desc'] ?? '');

                $data = [
                    'name'           => $name,
                    'price_usdt'     => $price,
                    'currency'       => $currency,
                    'in_stock'       => $inStock,
                    'stock'          => $stock,
                    'max_quantity'   => $maxQuantity,
                    'fulfillment'    => $fulfillment,
                    'price_tiers'    => $priceTiers,
                    'last_synced_at' => now(),
                ];

                if (!empty($description)) {
                    $data['description'] = $description;
                }

                $existing = AiToolProduct::where('provider_product_id', $providerProductId)->first();

                if ($existing) {
                    if ($categoryId && !$existing->category_id) {
                        $data['category_id'] = $categoryId;
                    }
                    $existing->update($data);
                    $updated++;
                } else {
                    // New products are ACTIVE by default so they appear on the site immediately
                    $data['provider_product_id'] = $providerProductId;
                    $data['category_id']         = $categoryId;
                    $data['is_active']           = true;
                    $data['is_featured']         = false;
                    $data['slug']                = Str::slug($name) . '-' . Str::random(5);
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
