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
use Illuminate\Support\Facades\Schedule;
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
            'is_active' => 1, // Active so products show on the site immediately upon sync
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

    public function test_admin_can_manually_fulfill_ai_tool_order()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $user = User::factory()->create(['balance' => 5000]);

        $order = AiToolOrder::create([
            'user_id'             => $user->id,
            'provider_product_id' => 'tool-manual-fulfill',
            'product_name'        => 'ChatGPT Plus Account',
            'idempotency_key'     => 'idemp-manual-1',
            'status'              => AiToolOrder::STATUS_PROCESSING,
            'quantity'            => 2,
            'unit_price_usdt'     => 10.00,
            'total_price_usdt'    => 20.00,
            'exchange_rate'       => 1500.00,
            'unit_price_ngn'      => 18000.00,
            'total_paid_ngn'      => 36000.00,
            'provider_cost_ngn'   => 30000.00,
            'profit_ngn'          => 6000.00,
        ]);

        $credentials = "user1@openai.com:Secret123\nuser2@openai.com:Secret456";

        $response = $this->actingAs($admin)->post("/hq/ai-tools/orders/{$order->id}/fulfill", [
            'credentials'  => $credentials,
            'instructions' => 'Log in directly at chat.openai.com',
        ]);

        $response->assertRedirect();
        $order->refresh();

        $this->assertEquals(AiToolOrder::STATUS_COMPLETED, $order->status);
        $this->assertNotNull($order->completed_at);
        $this->assertEquals(2, $order->items()->count());

        $firstItem = $order->items()->where('item_index', 0)->first();
        $this->assertEquals('user1@openai.com:Secret123', $firstItem->code);

        $secondItem = $order->items()->where('item_index', 1)->first();
        $this->assertEquals('user2@openai.com:Secret456', $secondItem->code);
    }

    public function test_admin_can_refund_ai_tool_order()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $user = User::factory()->create(['balance' => 1000]);

        $order = AiToolOrder::create([
            'user_id'             => $user->id,
            'provider_product_id' => 'tool-manual-refund',
            'product_name'        => 'Claude Pro 1 Month',
            'idempotency_key'     => 'idemp-manual-2',
            'status'              => AiToolOrder::STATUS_UNDER_REVIEW,
            'quantity'            => 1,
            'unit_price_usdt'     => 15.00,
            'total_price_usdt'    => 15.00,
            'exchange_rate'       => 1500.00,
            'unit_price_ngn'      => 25000.00,
            'total_paid_ngn'      => 25000.00,
            'provider_cost_ngn'   => 22500.00,
            'profit_ngn'          => 2500.00,
        ]);

        $response = $this->actingAs($admin)->post("/hq/ai-tools/orders/{$order->id}/refund", [
            'reason' => 'Out of stock at supplier',
        ]);

        $response->assertRedirect();
        $order->refresh();

        $this->assertEquals(AiToolOrder::STATUS_REFUNDED, $order->status);
        $this->assertNotNull($order->refunded_at);
        $this->assertEquals(26000.00, (float) $user->fresh()->balance);
    }

    public function test_get_on_fulfill_refund_sync_redirects_cleanly_without_500()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create(['balance' => 1000]);

        $order = AiToolOrder::create([
            'user_id'             => $user->id,
            'provider_product_id' => 'tool-test-redirects',
            'product_name'        => 'ChatGPT Plus',
            'idempotency_key'     => 'idemp-redirect-1',
            'status'              => AiToolOrder::STATUS_PROCESSING,
            'quantity'            => 1,
            'unit_price_usdt'     => 20.00,
            'total_price_usdt'    => 20.00,
            'exchange_rate'       => 1500.00,
            'unit_price_ngn'      => 33000.00,
            'total_paid_ngn'      => 33000.00,
            'provider_cost_ngn'   => 30000.00,
            'profit_ngn'          => 3000.00,
        ]);

        // GET /fulfill redirects to order details without 500
        $responseFulfill = $this->actingAs($admin)->get("/hq/ai-tools/orders/{$order->id}/fulfill");
        $responseFulfill->assertRedirect(route('hq.ai-tools.orders.show', $order->id));

        // GET /refund redirects to order details without 500
        $responseRefund = $this->actingAs($admin)->get("/hq/ai-tools/orders/{$order->id}/refund");
        $responseRefund->assertRedirect(route('hq.ai-tools.orders.show', $order->id));

        // GET /sync redirects to order details without 500
        $responseSync = $this->actingAs($admin)->get("/hq/ai-tools/orders/{$order->id}/sync");
        $responseSync->assertRedirect(route('hq.ai-tools.orders.show', $order->id));
    }

    public function test_ai_tool_order_item_handles_invalid_mac_or_raw_strings_gracefully()
    {
        $user = User::factory()->create(['balance' => 1000]);

        $order = AiToolOrder::create([
            'user_id'             => $user->id,
            'provider_product_id' => 'tool-corrupt-mac',
            'product_name'        => 'Claude Pro',
            'idempotency_key'     => 'idemp-corrupt-1',
            'status'              => AiToolOrder::STATUS_COMPLETED,
            'quantity'            => 1,
            'unit_price_usdt'     => 15.00,
            'total_price_usdt'    => 15.00,
            'exchange_rate'       => 1500.00,
            'unit_price_ngn'      => 25000.00,
            'total_paid_ngn'      => 25000.00,
            'provider_cost_ngn'   => 22500.00,
            'profit_ngn'          => 2500.00,
        ]);

        // Directly insert raw / invalid MAC ciphertext into the database table
        \Illuminate\Support\Facades\DB::table('ai_tool_order_items')->insert([
            'ai_tool_order_id' => $order->id,
            'item_index'       => 0,
            'code'             => 'invalid-base64-mac-payload-string',
            'details'          => 'invalid-json-or-mac',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $item = \App\Models\AiToolOrderItem::where('ai_tool_order_id', $order->id)->first();
        $this->assertNotNull($item);

        // Accessing code & details must not throw DecryptException or 500
        $this->assertEquals('invalid-base64-mac-payload-string', $item->code);
        $this->assertEquals('invalid-json-or-mac', $item->details);

        // Rendering order detail view with this item must not throw DecryptException
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get("/hq/ai-tools/orders/{$order->id}");
        $response->assertOk();
    }


    public function test_ambiguous_provider_submission_is_flagged_for_reconciliation_without_false_processing(): void
    {
        Queue::fake();
        $user = User::factory()->create(['balance' => 20000, 'status' => 'active']);
        Setting::set('alaric_api_key', 'test_key_123');
        Setting::set('usdt_to_ngn_rate', 1500.0);
        Setting::set('markup_ai_tools', 20.0);

        $product = AiToolProduct::create([
            'provider_product_id' => 'ambiguous-submission-test',
            'name' => 'Ambiguous Submission Test',
            'slug' => 'ambiguous-submission-test',
            'price_usdt' => 3.00,
            'currency' => 'USDT',
            'in_stock' => true,
            'is_active' => true,
            'fulfillment' => 'on_demand',
            'max_quantity' => 1,
        ]);

        Http::fake(function ($request) {
            throw new \\Illuminate\\Http\\Client\\ConnectionException('Connection reset after submission');
        });

        $response = $this->actingAs($user)->post('/ai-tools/purchase', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $response->assertRedirect();

        $order = AiToolOrder::where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertSame(AiToolOrder::STATUS_UNDER_REVIEW, $order->status);
        $this->assertNull($order->provider_order_id);
        $this->assertStringContainsString('ambiguous', strtolower($order->failure_reason));
        $this->assertLessThan(20000, (float) $user->fresh()->balance);
        Queue::assertNotPushed(PollAiToolOrderStatus::class);
    }

    public function test_stale_order_recovery_command_automatically_delivers_completed_order()
    {
        $user = User::factory()->create(['balance' => 1000]);
        $order = AiToolOrder::create([
            'user_id' => $user->id,
            'provider_product_id' => 'tool-recovery-test',
            'product_name' => 'Recovery Test Tool',
            'idempotency_key' => 'idemp-recovery-test',
            'provider_order_id' => 'alaric-recovery-123',
            'status' => AiToolOrder::STATUS_PROCESSING,
            'quantity' => 1,
            'unit_price_usdt' => 5,
            'total_price_usdt' => 5,
            'exchange_rate' => 1500,
            'unit_price_ngn' => 9000,
            'total_paid_ngn' => 9000,
            'provider_cost_ngn' => 7500,
            'profit_ngn' => 1500,
            'last_polled_at' => null,
        ]);

        Setting::set('alaric_api_key', 'test_key_123');
        Http::fake([
            'https://api.alaricstore.com/api/v1/orders/alaric-recovery-123' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'alaric-recovery-123',
                    'status' => 'COMPLETED',
                    'items' => [['code' => 'recovered-secret-credential']],
                ],
            ], 200),
        ]);

        $exitCode = Artisan::call('ai-tools:poll-stale-orders');
        $this->assertSame(0, $exitCode);
        $order->refresh();

        $this->assertSame(AiToolOrder::STATUS_COMPLETED, $order->status);
        $this->assertNotNull($order->completed_at);
        $this->assertNotNull($order->last_polled_at);
        $this->assertSame('recovered-secret-credential', $order->items()->first()->code);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'alaric-recovery-123'));

        // Verify the recovery command is registered and scheduled every minute.
        $this->assertArrayHasKey('ai-tools:poll-stale-orders', Artisan::all());
        $this->assertTrue(collect(Schedule::events())->contains(function ($event) {
            return str_contains($event->command, 'ai-tools:poll-stale-orders')
                && $event->expression === '* * * * *';
        }));
    }

}
