<?php

namespace Tests\Feature;

use App\Jobs\PollAiToolOrderStatus;
use App\Models\AiToolCategory;
use App\Models\AiToolOrder;
use App\Models\AiToolOrderItem;
use App\Models\AiToolProduct;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AlaricApiService;
use App\Services\ProductPricingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiToolsIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Config::set('database.connections.sqlite.database', database_path('database.sqlite'));
        \Illuminate\Support\Facades\DB::purge('sqlite');

        // Clean up test records
        if (\Illuminate\Support\Facades\Schema::hasTable('ai_tool_order_items')) {
            AiToolOrderItem::truncate();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('ai_tool_orders')) {
            AiToolOrder::truncate();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('ai_tool_products')) {
            AiToolProduct::truncate();
        }
    }

    public function test_product_sync_command()
    {
        // Mock Alaric API /products response
        Http::fake([
            'https://api.alaricstore.com/api/v1/products*' => Http::response([
                'success' => true,
                'data' => [
                    [
                        'id' => 'test-tool-1',
                        'name' => 'ChatGPT Plus Shared',
                        'price' => 2.50,
                        'currency' => 'USDT',
                        'in_stock' => true,
                        'stock' => 10,
                        'max_quantity' => 5,
                        'fulfillment' => 'stock',
                        'price_tiers' => [
                            ['min_quantity' => 1, 'price' => 2.50],
                            ['min_quantity' => 3, 'price' => 2.20],
                        ],
                    ],
                    [
                        'id' => 'test-tool-2',
                        'name' => 'Midjourney Fast Hours',
                        'price' => 5.00,
                        'currency' => 'USDT',
                        'in_stock' => true,
                        'stock' => null,
                        'max_quantity' => 1,
                        'fulfillment' => 'on_demand',
                        'price_tiers' => null,
                    ]
                ]
            ], 200),
        ]);

        Setting::set('alaric_api_key', 'test_key_123');

        $exitCode = Artisan::call('ai-tools:sync-products', ['--force' => true]);
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('ai_tool_products', [
            'provider_product_id' => 'test-tool-1',
            'name' => 'ChatGPT Plus Shared',
            'in_stock' => 1,
            'is_active' => 0, // Should be inactive until admin approves
        ]);

        $this->assertDatabaseHas('ai_tool_products', [
            'provider_product_id' => 'test-tool-2',
            'name' => 'Midjourney Fast Hours',
            'fulfillment' => 'on_demand',
        ]);
    }

    public function test_customer_purchase_flow_with_idempotency_and_wallet_deduction()
    {
        Queue::fake();

        $user = User::factory()->create([
            'balance' => 20000.00,
            'status' => 'active',
        ]);

        Setting::set('alaric_api_key', 'test_key_123');
        Setting::set('usdt_to_ngn_rate', 1500.0);
        Setting::set('markup_ai_tools', 20.0); // 20% markup

        $product = AiToolProduct::create([
            'provider_product_id' => 'claude-pro-1',
            'name' => 'Claude Pro Shared Account',
            'slug' => 'claude-pro-shared',
            'price_usdt' => 3.00,
            'currency' => 'USDT',
            'in_stock' => true,
            'is_active' => true,
            'fulfillment' => 'on_demand',
            'max_quantity' => 2,
        ]);

        // Mock purchase response from Alaric API: PROCESSING
        Http::fake([
            'https://api.alaricstore.com/api/v1/orders' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'alaric-ord-999',
                    'status' => 'PROCESSING',
                ]
            ], 200),
        ]);

        $response = $this->actingAs($user)->post('/ai-tools/purchase', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect();

        // 3.00 USDT * 1500 = 4,500 NGN; + 20% markup = 5,400 NGN
        $expectedPriceNgn = 5400.00;

        $user->refresh();
        $this->assertEquals(20000.00 - $expectedPriceNgn, (float) $user->balance);

        $order = AiToolOrder::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertNotEmpty($order->idempotency_key);
        $this->assertEquals('alaric-ord-999', $order->provider_order_id);
        $this->assertEquals(AiToolOrder::STATUS_PROCESSING, $order->status);

        // Assert transaction was recorded
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => $expectedPriceNgn,
            'type' => 'debit',
            'transaction_type' => 'ai_tools',
        ]);

        // Assert polling job was dispatched
        Queue::assertPushed(PollAiToolOrderStatus::class);
    }

    public function test_insufficient_balance_prevents_purchase()
    {
        $user = User::factory()->create([
            'balance' => 100.00,
            'status' => 'active',
        ]);

        $product = AiToolProduct::create([
            'provider_product_id' => 'chatgpt-test-cheap',
            'name' => 'ChatGPT Team',
            'slug' => 'chatgpt-team',
            'price_usdt' => 10.00,
            'currency' => 'USDT',
            'in_stock' => true,
            'is_active' => true,
            'fulfillment' => 'on_demand',
            'max_quantity' => 1,
        ]);

        $response = $this->actingAs($user)->post('/ai-tools/purchase', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(100.00, (float) $user->fresh()->balance);
        $this->assertDatabaseMissing('ai_tool_orders', [
            'user_id' => $user->id,
            'ai_tool_product_id' => $product->id,
        ]);
    }

    public function test_polling_job_completes_and_encrypts_delivered_credentials()
    {
        $user = User::factory()->create(['balance' => 1000]);

        $product = AiToolProduct::create([
            'provider_product_id' => 'tool-poll-test',
            'name' => 'RunwayML 1 Month',
            'slug' => 'runwayml-1-month',
            'price_usdt' => 5.00,
            'currency' => 'USDT',
            'in_stock' => true,
            'is_active' => true,
            'fulfillment' => 'stock',
            'stock' => 5,
        ]);

        $order = AiToolOrder::create([
            'user_id' => $user->id,
            'ai_tool_product_id' => $product->id,
            'provider_product_id' => $product->provider_product_id,
            'product_name' => $product->name,
            'idempotency_key' => 'idemp-uuid-12345',
            'provider_order_id' => 'alaric-poll-555',
            'status' => AiToolOrder::STATUS_PROCESSING,
            'quantity' => 1,
            'unit_price_usdt' => 5.00,
            'total_price_usdt' => 5.00,
            'exchange_rate' => 1500.00,
            'unit_price_ngn' => 9000.00,
            'total_paid_ngn' => 9000.00,
            'provider_cost_ngn' => 7500.00,
            'profit_ngn' => 1500.00,
        ]);

        Http::fake([
            'https://api.alaricstore.com/api/v1/orders/alaric-poll-555' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'alaric-poll-555',
                    'status' => 'COMPLETED',
                    'items' => [
                        [
                            'code' => 'runway-secret-key-xyz-123',
                            'details' => 'login: test@linkocial.com | pass: SecretPass!123',
                        ]
                    ]
                ]
            ], 200),
        ]);

        $service = app(AlaricApiService::class);
        $job = new PollAiToolOrderStatus($order);
        $job->handle($service);

        $order->refresh();
        $this->assertEquals(AiToolOrder::STATUS_COMPLETED, $order->status);
        $this->assertNotNull($order->completed_at);

        $item = AiToolOrderItem::where('ai_tool_order_id', $order->id)->first();
        $this->assertNotNull($item);
        // Ensure decrypted property matches original secret
        $this->assertEquals('runway-secret-key-xyz-123', $item->code);

        // Ensure raw database column is encrypted, not plaintext
        $rawItem = DB::table('ai_tool_order_items')->where('id', $item->id)->first();
        $this->assertNotEquals('runway-secret-key-xyz-123', $rawItem->code);
    }

    public function test_admin_settings_update()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post('/hq/ai-tools/settings', [
            'alaric_api_key' => 'new_secret_key_999',
            'usdt_to_ngn_rate' => 1550.00,
            'markup_ai_tools' => 25.00,
        ]);

        $response->assertRedirect();
        $this->assertEquals('new_secret_key_999', Setting::get('alaric_api_key'));
        $this->assertEquals(1550.00, (float) Setting::get('usdt_to_ngn_rate'));
        $this->assertEquals(25.00, (float) Setting::get('markup_ai_tools'));
    }
}
