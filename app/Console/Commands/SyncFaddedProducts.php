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

        $apiKey = Setting::get('fadded_api_key') ?: config('services.fadded.key');
        if (empty($apiKey)) {
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

        $synced = 0;
        foreach ($products as $item) {
            if (!isset($item['id']) && !isset($item['product_key'])) {
                continue;
            }

            $key = $item['product_key'] ?? (string)$item['id'];
            $name = $item['name'] ?? 'Product #' . $key;
            $price = (float)($item['price'] ?? 0);
            $stock = (int)($item['stock'] ?? $item['in_stock'] ?? 0);
            $desc = LogProduct::sanitizeDescription($item['description'] ?? '');

            // Find or create category if provided
            $categoryId = null;
            if (!empty($item['category'])) {
                $cat = LogCategory::firstOrCreate(
                    ['name' => trim($item['category'])],
                    ['is_active' => true]
                );
                $categoryId = $cat->id;
            }

            LogProduct::updateOrCreate(
                ['api_product_key' => $key],
                [
                    'type' => 'api',
                    'api_product_id' => $item['id'] ?? null,
                    'log_category_id' => $categoryId,
                    'name' => $name,
                    'description' => $desc,
                    'base_api_price' => $price,
                    'unit_price' => $price,
                    'in_stock' => $stock,
                    'is_active' => true,
                ]
            );
            $synced++;
        }

        $this->info("Successfully synchronized {$synced} products from Fadded.");
        return 0;
    }
}
