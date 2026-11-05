<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\FaddedApi;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

class FaddedApiHostCorrectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {}

        Setting::clearCache();
    }

    /**
     * Test URL normalization correctly rewrites legacy fadded.net to api.fadded.net
     */
    public function test_normalize_api_url_rewrites_legacy_domains_and_ensures_reseller_path(): void
    {
        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            FaddedApi::normalizeApiUrl(null)
        );

        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            FaddedApi::normalizeApiUrl('')
        );

        // Legacy domain variations
        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            FaddedApi::normalizeApiUrl('https://fadded.net/api/v1/reseller')
        );

        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            FaddedApi::normalizeApiUrl('https://fadded.net/api')
        );

        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            FaddedApi::normalizeApiUrl('https://fadded.net')
        );

        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            FaddedApi::normalizeApiUrl('http://fadded.net/api/v1/reseller')
        );

        // Incomplete paths on new api.fadded.net domain
        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            FaddedApi::normalizeApiUrl('https://api.fadded.net')
        );

        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            FaddedApi::normalizeApiUrl('https://api.fadded.net/api')
        );

        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            FaddedApi::normalizeApiUrl('https://api.fadded.net/api/v1')
        );

        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            FaddedApi::normalizeApiUrl('https://api.fadded.net/api/v1/reseller')
        );

        // Trailing endpoint strip
        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            FaddedApi::normalizeApiUrl('https://api.fadded.net/api/v1/reseller/products')
        );
    }

    /**
     * Test endpoint URLs are cleanly built for products, balance, and order.
     */
    public function test_endpoint_urls_are_constructed_consistently(): void
    {
        Setting::set('fadded_api_url', 'https://api.fadded.net/api/v1/reseller');
        $api = new FaddedApi();

        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller/products',
            $api->getEndpointUrl('products')
        );

        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller/balance',
            $api->getEndpointUrl('balance')
        );

        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller/order',
            $api->getEndpointUrl('order')
        );
    }

    /**
     * Test database setting override with legacy hostname is auto-migrated.
     */
    public function test_legacy_database_setting_is_migrated_to_corrected_host(): void
    {
        Setting::set('fadded_api_url', 'https://fadded.net/api/v1/reseller');

        $api = new FaddedApi();

        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller',
            Setting::get('fadded_api_url')
        );
        $this->assertEquals(
            'https://api.fadded.net/api/v1/reseller/products',
            $api->getEndpointUrl('products')
        );
    }

    /**
     * Test API key is trimmed of leading and trailing whitespace.
     */
    public function test_api_key_is_normalized_and_trimmed(): void
    {
        Setting::set('fadded_api_key', "  sk_test_fadded_key_12345 \n ");

        $api = new FaddedApi();
        $this->assertTrue($api->isConfigured());

        Http::fake([
            'https://api.fadded.net/api/v1/reseller/products' => Http::response([
                'success' => true,
                'data' => [
                    ['id' => '1', 'name' => 'Test Account', 'price' => 500, 'stock' => 10]
                ]
            ], 200)
        ]);

        $res = $api->getProducts();
        $this->assertTrue($res['success']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fadded.net/api/v1/reseller/products'
                && $request->header('Authorization')[0] === 'Bearer sk_test_fadded_key_12345'
                && $request->header('X-Api-Key')[0] === 'sk_test_fadded_key_12345';
        });
    }

    /**
     * Test balance retrieval hits api.fadded.net/api/v1/reseller/balance.
     */
    public function test_balance_retrieval_hits_corrected_host(): void
    {
        Setting::set('fadded_api_key', 'sk_test_fadded_key_12345');
        $api = new FaddedApi();

        Http::fake([
            'https://api.fadded.net/api/v1/reseller/balance' => Http::response([
                'success' => true,
                'balance' => 25000.0,
                'currency' => 'NGN'
            ], 200)
        ]);

        $res = $api->getBalance();
        $this->assertTrue($res['success']);
        $this->assertEquals(25000.0, $res['data']['balance']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fadded.net/api/v1/reseller/balance'
                && $request->header('Authorization')[0] === 'Bearer sk_test_fadded_key_12345';
        });
    }

    /**
     * Test order request hits api.fadded.net/api/v1/reseller/order with correct headers.
     */
    public function test_order_request_hits_corrected_host(): void
    {
        Setting::set('fadded_api_key', 'sk_test_fadded_key_12345');
        $api = new FaddedApi();

        Http::fake([
            'https://api.fadded.net/api/v1/reseller/order' => Http::response([
                'success' => true,
                'data' => [
                    'order_id' => 'EXT-1234',
                    'items' => ['user:pass:token']
                ]
            ], 200)
        ]);

        $res = $api->order('fb_aged_1', 1, 'CUSTOM-ORDER-1');
        $this->assertTrue($res['success']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fadded.net/api/v1/reseller/order'
                && $request->header('Authorization')[0] === 'Bearer sk_test_fadded_key_12345'
                && $request['product_key'] === 'fb_aged_1'
                && $request['quantity'] === 1;
        });
    }

    /**
     * Test fadded:sync artisan command synchronizes products from api.fadded.net host.
     */
    public function test_sync_command_synchronizes_products_from_corrected_host(): void
    {
        Setting::set('fadded_api_key', 'sk_test_sync_key');
        Setting::set('markup_logs_market', 20.0);

        Http::fake([
            'https://api.fadded.net/api/v1/reseller/products' => Http::response([
                'success' => true,
                'data' => [
                    [
                        'id' => 101,
                        'product_key' => 'telegram_us_fresh',
                        'name' => 'Fresh Telegram Account (USA)',
                        'price' => 1000.0,
                        'stock' => 5,
                        'category' => 'Telegram Accounts',
                        'description' => 'Clean fresh account'
                    ]
                ]
            ], 200)
        ]);

        $exitCode = Artisan::call('fadded:sync');
        $this->assertEquals(0, $exitCode);

        $product = \App\Models\LogProduct::where('api_product_key', 'telegram_us_fresh')->first();
        $this->assertNotNull($product);
        $this->assertEquals('Fresh Telegram Account (USA)', $product->name);
        $this->assertEquals(1000.0, (float)$product->base_api_price);
        $this->assertEquals(5, $product->in_stock);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fadded.net/api/v1/reseller/products';
        });
    }
}
