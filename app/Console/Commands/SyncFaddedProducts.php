<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FaddedApi;
use App\Models\LogProduct;
use App\Models\LogCategory;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class SyncFaddedProducts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fadded:sync';

    /**
     * The console command description.
     */
    protected $description = 'Synchronize products from Fadded Logs Marketplace';

    /**
     * Execute the console command.
     */
    public function handle(FaddedApi $api)
    {
        $this->info('Starting Fadded API products synchronization...');

        $rawKey = Setting::get('fadded_api_key') ?: config('services.fadded.key');
        $apiKey = $rawKey !== null ? trim((string)$rawKey) : '';
        if (empty($apiKey) && !$api->isConfigured()) {
            $this->warn('Fadded API key is not configured. Please set your API key in HQ Settings or .env.');
            return 1;
        }

        $result = $api->getProducts();
        if (!$result['success']) {
            $msg = $result['message'] ?? 'Failed to connect to Fadded API.';
            $this->error($msg);
            Log::warning('Fadded Sync Warning: ' . $msg);
            return 1;
        }

        $products = $result['data'] ?? [];
        if (empty($products)) {
            $this->info('No products returned from Fadded API.');
            return 0;
        }

        $pricingService = app(\App\Services\ProductPricingService::class);
        $markupRate = (float) Setting::get('markup_logs_market', 20.0);
        $markupMultiplier = 1 + ($markupRate / 100);

        $synced = 0;
        foreach ($products as $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = (string)($item['product_key'] ?? $item['key'] ?? $item['id'] ?? '');
            if (empty($key)) {
                continue;
            }

            $name = $item['name'] ?? $item['title'] ?? ('Product #' . $key);
            
            // Extract raw price from any common field name in provider payload
            $rawPrice = $item['price'] 
                ?? $item['amount'] 
                ?? $item['unit_price'] 
                ?? $item['cost'] 
                ?? $item['rate'] 
                ?? $item['price_ngn'] 
                ?? $item['price_usd'] 
                ?? $item['selling_price'] 
                ?? $item['cost_price'] 
                ?? $item['price_per_item'] 
                ?? $item['price_per_account'] 
                ?? $item['price_per_unit']
                ?? ($item['pricing']['price'] ?? null)
                ?? ($item['pricing']['amount'] ?? null)
                ?? null;

            $price = 0.0;
            if ($rawPrice !== null) {
                if (is_numeric($rawPrice)) {
                    $price = (float) $rawPrice;
                } elseif (is_string($rawPrice)) {
                    // Strip commas, spaces, currency symbols (₦, $, €, £)
                    $cleaned = str_replace(',', '', trim($rawPrice));
                    if (preg_match('/[0-9]+(?:\.[0-9]+)?/', $cleaned, $matches)) {
                        $price = (float) $matches[0];
                    }
                }
            }

            $existing = LogProduct::where('api_product_key', $key)->first();
            // If current sync returned 0 but we already have a recorded positive price, preserve it
            if ($price <= 0 && $existing && (float)$existing->unit_price > 0) {
                $price = (float)$existing->unit_price;
            }

            if ($price <= 0) {
                Log::warning("Fadded sync: Product '{$name}' (Key: {$key}) has zero or missing price. Raw item data: " . json_encode($item));
            }

            $stock = (int)($item['stock'] ?? $item['in_stock'] ?? $item['quantity'] ?? $item['available'] ?? 0);
            $desc = LogProduct::sanitizeDescription($item['description'] ?? '');

            // Find or create category if provided
            $categoryId = null;
            if (!empty($item['category'])) {
                $catName = is_array($item['category']) ? ($item['category']['name'] ?? 'General') : (string)$item['category'];
                if (!empty(trim($catName))) {
                    $cat = LogCategory::firstOrCreate(
                        ['name' => trim($catName)],
                        ['is_active' => true]
                    );
                    $categoryId = $cat->id;
                }
            }

            if ($existing) {
                // For existing products:
                // Sync provider wholesale price changes, stock availability, and update our_price with margin.
                $updateData = [
                    'in_stock' => $stock,
                ];

                if (!empty($item['id'])) {
                    $updateData['api_product_id'] = $item['id'];
                }

                // If Fadded's original price changed, update base_api_price, unit_price, and recalculate our_price with margin
                if ($price > 0) {
                    try {
                        $calc = $pricingService->calculateSellingPrice($price, \App\Services\ProductPricingService::PRODUCT_LOGS);
                        $sellingPrice = (float) $calc['selling_price'];
                    } catch (\Throwable $e) {
                        $sellingPrice = round($price * $markupMultiplier, 2);
                    }
                    $updateData['base_api_price'] = $price;
                    $updateData['unit_price'] = $price;
                    $updateData['our_price'] = $sellingPrice;
                }

                // Only fill category/description if currently blank
                if (empty($existing->log_category_id) && $categoryId) {
                    $updateData['log_category_id'] = $categoryId;
                }
                if (empty($existing->description) && !empty($desc)) {
                    $updateData['description'] = $desc;
                }

                $existing->update($updateData);
            } else {
                // New product: add with provider wholesale price and margin-calculated selling price
                try {
                    $calc = $pricingService->calculateSellingPrice($price, \App\Services\ProductPricingService::PRODUCT_LOGS);
                    $calculatedSellingPrice = (float) $calc['selling_price'];
                } catch (\Throwable $e) {
                    $calculatedSellingPrice = round($price * $markupMultiplier, 2);
                }
                LogProduct::create([
                    'type' => 'api',
                    'api_product_key' => $key,
                    'api_product_id' => $item['id'] ?? null,
                    'log_category_id' => $categoryId,
                    'name' => $name,
                    'description' => $desc,
                    'base_api_price' => $price,
                    'unit_price' => $price,
                    'our_price' => $calculatedSellingPrice,
                    'in_stock' => $stock,
                    'is_active' => true,
                ]);
            }

            $synced++;
        }

        $this->info("Successfully synchronized {$synced} products from Fadded.");
        return 0;
    }
}
