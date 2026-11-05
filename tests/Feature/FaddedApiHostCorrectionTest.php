<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\FaddedApi;
use App\Models\Setting;
use App\Models\User;
use App\Models\LogProduct;
use App\Models\LogOrder;
use App\Models\Transaction;
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
     * Test API key normalization trims whitespace and prevents duplicate Bearer prefix.
     */
    public function test_api_key_is_normalized_and_avoids_duplicate_bearer(): void
    {
        $this->assertEquals('sk_my_key', FaddedApi::normalizeApiKey("  sk_my_key  \n"));
        $this->assertEquals('sk_my_key', FaddedApi::normalizeApiKey("Bearer sk_my_key"));
        $this->assertEquals('sk_my_key', FaddedApi::normalizeApiKey("  bearer   sk_my_key  "));
        $this->assertNull(FaddedApi::normalizeApiKey(null));
        $this->assertNull(FaddedApi::normalizeApiKey('   '));

        Setting::set('fadded_api_key', "  Bearer  sk_test_fadded_key_12345 \n ");

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
                && !str_contains($request->header('Authorization')[0], 'Bearer Bearer');
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
     * Test order request hits api.fadded.net/api/v1/reseller/order with documented JSON payload.
     */
    public function test_order_request_uses_documented_json_structure(): void
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

        $res = $api->order('prod_144', 1, 'YOUR-UNIQUE-ORDER-REFERENCE');
        $this->assertTrue($res['success']);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return $request->url() === 'https://api.fadded.net/api/v1/reseller/order'
                && $request->header('Authorization')[0] === 'Bearer sk_test_fadded_key_12345'
                && $request->header('Content-Type')[0] === 'application/json'
                && $data['product_key'] === 'prod_144'
                && $data['quantity'] === 1
                && $data['external_order_id'] === 'YOUR-UNIQUE-ORDER-REFERENCE';
        });
    }

    /**
     * Test official error responses correctly distinguish invalid key from expired subscription
     * and produce distinct admin diagnostics without leaking to customers.
     */
    public function test_official_error_responses_and_distinctions(): void
    {
        // 401 INVALID_API_KEY
        $err1 = FaddedApi::parseProviderError(401, 'INVALID_API_KEY', 'Invalid or inactive API key');
        $this->assertStringContainsString('Invalid or inactive Fadded API key', $err1['admin_message']);
        $this->assertStringNotContainsString('expired', strtolower($err1['admin_message']));

        // 403 SUBSCRIPTION_EXPIRED
        $err2 = FaddedApi::parseProviderError(403, 'SUBSCRIPTION_EXPIRED', 'Reseller subscription has expired');
        $this->assertStringContainsString('subscription has expired', $err2['admin_message']);
        $this->assertStringNotContainsString('Invalid API key', $err2['admin_message']);

        // 400 INSUFFICIENT_BALANCE
        $err3 = FaddedApi::parseProviderError(400, 'INSUFFICIENT_BALANCE', 'Low balance');
        $this->assertStringContainsString('wallet balance is insufficient', $err3['admin_message']);

        // 400 INSUFFICIENT_STOCK
        $err4 = FaddedApi::parseProviderError(400, 'INSUFFICIENT_STOCK', 'No stock');
        $this->assertStringContainsStringIgnoringCase('insufficient stock', $err4['admin_message']);
        $this->assertStringContainsStringIgnoringCase('out of stock', $err4['customer_message']);

        // Customer message should never leak provider technical errors
        $this->assertStringNotContainsString('401', $err1['customer_message']);
        $this->assertStringNotContainsString('INVALID_API_KEY', $err1['customer_message']);
        $this->assertStringNotContainsString('SUBSCRIPTION_EXPIRED', $err2['customer_message']);
        $this->assertStringNotContainsString('INSUFFICIENT_BALANCE', $err3['customer_message']);
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

        $product = LogProduct::where('api_product_key', 'telegram_us_fresh')->first();
        $this->assertNotNull($product);
        $this->assertEquals('Fresh Telegram Account (USA)', $product->name);
        $this->assertEquals(1000.0, (float)$product->base_api_price);
        $this->assertEquals(5, $product->in_stock);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fadded.net/api/v1/reseller/products';
        });
    }
}
