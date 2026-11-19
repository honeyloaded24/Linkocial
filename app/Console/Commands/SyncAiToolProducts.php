<?php

namespace App\Console\Commands;

use App\Models\AiToolCategory;
use App\Models\AiToolProduct;
use App\Services\AlaricApiService;
use App\Services\PixelSubApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SyncAiToolProducts extends Command
{
    protected $signature = 'ai-tools:sync-products {--force : Force sync even if recently synced}';
    protected $description = 'Synchronize AI Tools products from configured APIs';

    public function handle(): int
    {
        if (!$this->option('force') && Cache::has('ai_tools_products_last_sync')) {
            $this->info('Skipping — recently synchronized. Use --force to override.');
            return self::SUCCESS;
        }

        if (!Schema::hasTable('ai_tool_products') || !Schema::hasTable('ai_tool_categories')) {
            Artisan::call('migrate', ['--force' => true]);
        }
        if (!Schema::hasTable('ai_tool_products')) {
            $this->error('ai_tool_products table does not exist.');
            return self::FAILURE;
        }

        $alaric = app(AlaricApiService::class)->getProducts();
        $pixelSubService = app(PixelSubApiService::class);
        $pixelSub = $pixelSubService->getProducts();
        $stockResponse = $pixelSubService->getStock();
        $stockMap = $stockResponse['data'] ?? [];

        if (!($alaric['success'] ?? false) && !($pixelSub['success'] ?? false)) {
            $this->error('Both product APIs failed. Existing catalogue was left unchanged.');
            Log::error('AI Tools product sync failed for both APIs', ['alaric' => $alaric, 'pixelsub' => $pixelSub]);
            return self::FAILURE;
        }

        $created = $updated = $errors = 0;
        $synced = ['alaric' => [], 'pixelsub' => []];

        foreach ([
            ['provider' => 'alaric', 'result' => $alaric, 'prefix' => '', 'currency' => 'USDT'],
            ['provider' => 'pixelsub', 'result' => $pixelSub, 'prefix' => 'pixelsub:', 'currency' => 'USD'],
        ] as $source) {
            if (!($source['result']['success'] ?? false)) {
                Log::warning('AI Tools provider sync skipped', ['provider' => $source['provider'], 'error' => $source['result']['error_message'] ?? null]);
                continue;
            }

            $raw = $source['result']['data'] ?? [];
            if (isset($raw['products']) && is_array($raw['products'])) $products = $raw['products'];
            elseif (isset($raw['data']) && is_array($raw['data'])) $products = $raw['data'];
            elseif (isset($raw['items']) && is_array($raw['items'])) $products = $raw['items'];
            elseif (is_array($raw) && array_is_list($raw)) $products = $raw;
            else $products = [];

            foreach ($products as $p) {
                try {
                    if (!is_array($p)) continue;
                    $rawId = (string) ($p['id'] ?? $p['product_id'] ?? $p['_id'] ?? '');
                    if ($rawId === '') continue;
                    $providerId = $source['prefix'] . $rawId;
                    $name = (string) ($p['name'] ?? $p['title'] ?? $p['product_name'] ?? 'Unnamed Product');
                    $price = (float) ($p['price'] ?? $p['rate'] ?? $p['price_usdt'] ?? $p['cost'] ?? 0);
                    $stockValue = $source['provider'] === 'pixelsub'
                        ? ($stockMap[$rawId] ?? $p['stock'] ?? null)
                        : ($p['stock'] ?? null);
                    $inStock = $source['provider'] === 'pixelsub'
                        ? (array_key_exists('inStock', $p) ? (bool) $p['inStock'] : ($stockValue === null || (int) $stockValue > 0))
                        : (isset($p['in_stock']) ? (bool) $p['in_stock'] : ($stockValue === null || (int) $stockValue > 0));
                    $stock = $stockValue === null ? 0 : (int) $stockValue;
                    $maxQuantity = max(1, (int) ($p['max_quantity'] ?? $p['max'] ?? 20));
                    $categoryName = is_string($p['category'] ?? null) ? trim($p['category']) : trim((string) ($p['category_name'] ?? ''));
                    $categoryId = null;
                    if ($categoryName !== '' && Schema::hasTable('ai_tool_categories')) {
                        $category = AiToolCategory::firstOrCreate(
                            ['name' => $categoryName],
                            ['slug' => Str::slug($categoryName) . '-' . Str::random(4), 'is_active' => true]
                        );
                        $categoryId = $category->id;
                    }

                    $data = [
                        'provider' => $source['provider'],
                        'name' => $name,
                        'price_usdt' => $price,
                        'currency' => $source['currency'],
                        'in_stock' => $inStock,
                        'stock' => $stock,
                        'max_quantity' => $maxQuantity,
                        'fulfillment' => 'on_demand',
                        'last_synced_at' => now(),
                    ];
                    if (!empty($p['description'])) $data['description'] = (string) $p['description'];
                    if ($categoryId) $data['category_id'] = $categoryId;

                    $existing = AiToolProduct::where('provider_product_id', $providerId)->first();
                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                    } else {
                        $data += [
                            'provider_product_id' => $providerId,
                            'category_id' => $categoryId,
                            'is_active' => true,
                            'is_featured' => false,
                            'slug' => Str::slug($name) . '-' . Str::random(5),
                        ];
                        AiToolProduct::create($data);
                        $created++;
                    }
                    $synced[$source['provider']][] = $providerId;
                } catch (\Throwable $e) {
                    $errors++;
                    Log::error('AI Tools product sync processing failed', ['provider' => $source['provider'], 'product_id' => $p['id'] ?? null, 'error' => $e->getMessage()]);
                }
            }
        }

        foreach ($synced as $provider => $ids) {
            if (!empty($ids)) {
                AiToolProduct::where('provider', $provider)->whereNotIn('provider_product_id', $ids)->update(['in_stock' => false]);
            }
        }

        Cache::put('ai_tools_products_last_sync', now(), 10);
        $this->info("AI Tools sync complete — created: {$created}, updated: {$updated}, errors: {$errors}.");
        Log::info('AI Tools product sync completed', compact('created', 'updated', 'errors', 'synced'));
        return self::SUCCESS;
    }
}
