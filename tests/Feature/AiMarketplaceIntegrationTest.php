<?php

namespace Tests\Feature;

use App\Models\AiOrder;
use App\Models\AiProduct;
use App\Models\Setting;
use App\Models\User;
use App\Services\AiPlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiMarketplaceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('aiplug_api_key', 'test_aiplug_key_123');
        Setting::set('aiplug_webhook_secret', 'whsec_test_secret_456789abcdef');
        Setting::set('ai_marketplace_markup', '20');
    }

    public function test_catalog_browsing_displays_active_ai_products(): void
    {
        $user = User::factory()->create(['balance' => 10000.00]);

        AiProduct::create([
            'provider_product_id'  => 'ext:67',
            'name'                 => 'Google Gemini Advanced Pro',
            'category'             => 'Google AI',
            'description'          => 'Full 1-month access to Gemini 1.5 Pro with 2M token window',
            'wholesale_price_kobo' => 350000,
            'wholesale_price'      => 3500.00,
            'retail_price'         => 4200.00,
            'in_stock'             => true,
            'is_active'            => true,
        ]);

        $response = $this->actingAs($user)->get('/ai-marketplace');

        $response->assertStatus(200);
        $response->assertSee('Google Gemini Advanced Pro');
        $response->assertSee('4,200.00');
    }

    public function test_user_can_purchase_ai_service_with_sufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 10000.00]);

        $product = AiProduct::create([
            'provider_product_id'  => 'ext:67',
            'name'                 => 'Google Gemini Pro',
            'category'             => 'Google AI',
            'wholesale_price_kobo' => 300000,
            'wholesale_price'      => 3000.00,
            'retail_price'         => 3600.00,
            'in_stock'             => true,
            'is_active'            => true,
        ]);

        // Mock AIPlug POST /orders API
        Http::fake([
            'https://resellers.aiplug.store/api/reseller/v1/orders' => Http::response([
                'order' => [
                    'id'        => 'AIP-R-TEST-001',
                    'status'    => 'processing',
                    'totalKobo' => 300000,
                    'currency'  => 'NGN',
                ],
                'delivery' => 'Portal credentials generated on fulfillment',
            ], 200),
        ]);

        $response = $this->actingAs($user)->post("/ai-marketplace/product/{$product->id}/buy", [
            'quantity' => 1,
        ]);

        $response->assertRedirect('/ai-marketplace/history');

        // Balance deducted: 10000 - 3600 = 6400
        $user->refresh();
        $this->assertEquals(6400.00, (float) $user->balance);

        // Order created
        $this->assertDatabaseHas('ai_orders', [
            'user_id'           => $user->id,
            'ai_product_id'     => $product->id,
            'provider_order_id' => 'AIP-R-TEST-001',
            'total_price'       => 3600.00,
            'provider_cost'     => 3000.00,
        ]);

        // Ledger transaction recorded
        $this->assertDatabaseHas('transactions', [
            'user_id'          => $user->id,
            'amount'           => 3600.00,
            'transaction_type' => 'ai_purchase',
            'type'             => 'debit',
        ]);
    }

    public function test_purchase_rejected_when_balance_is_insufficient(): void
    {
        $user = User::factory()->create(['balance' => 500.00]);

        $product = AiProduct::create([
            'provider_product_id'  => 'ext:99',
            'name'                 => 'Google Vertex AI Credits',
            'category'             => 'Google AI',
            'wholesale_price_kobo' => 400000,
            'wholesale_price'      => 4000.00,
            'retail_price'         => 4800.00,
            'in_stock'             => true,
            'is_active'            => true,
        ]);

        $response = $this->actingAs($user)->post("/ai-marketplace/product/{$product->id}/buy", [
            'quantity' => 1,
        ]);

        $response->assertSessionHas('error');
        $user->refresh();
        $this->assertEquals(500.00, (float) $user->balance);
        $this->assertDatabaseCount('ai_orders', 0);
    }

    public function test_signed_webhook_fulfills_order_and_records_credentials(): void
    {
        $user = User::factory()->create();

        $order = AiOrder::create([
            'user_id'           => $user->id,
            'idempotency_key'   => 'aip_test_key_001',
            'provider_order_id' => 'AIP-R-998877',
            'product_name'      => 'Google Cloud Vertex Credits',
            'quantity'          => 1,
            'unit_price'        => 5000.00,
            'total_price'       => 5000.00,
            'provider_cost'     => 4000.00,
            'status'            => 'processing',
        ]);

        // Mock GET /orders/:id for credentials retrieval
        Http::fake([
            'https://resellers.aiplug.store/api/reseller/v1/orders/AIP-R-998877' => Http::response([
                'order' => [
                    'id'     => 'AIP-R-998877',
                    'status' => 'fulfilled',
                ],
                'delivery' => [
                    'email'        => 'google.ai.user@linkocial.com',
                    'tempPassword' => 'SecretPass123!',
                    'consoleUrl'   => 'https://console.cloud.google.com',
                ],
            ], 200),
        ]);

        $secret = 'whsec_test_secret_456789abcdef';
        $timestamp = time();

        $payload = [
            'id'        => 'evt_test_fulfillment_1001',
            'type'      => 'order.fulfilled',
            'createdAt' => now()->toISOString(),
            'data'      => [
                'order' => [
                    'id'        => 'AIP-R-998877',
                    'status'    => 'fulfilled',
                    'totalKobo' => 400000,
                    'currency'  => 'NGN',
                ],
                'orderUrl' => 'https://resellers.aiplug.store/api/reseller/v1/orders/AIP-R-998877',
            ],
        ];

        $rawBody = json_encode($payload);
        $signatureHash = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        $signatureHeader = "t={$timestamp},v1={$signatureHash}";

        $response = $this->call('POST', '/webhooks/aiplug', [], [], [], [
            'HTTP_X_AIPLUG_SIGNATURE' => $signatureHeader,
            'CONTENT_TYPE'            => 'application/json',
        ], $rawBody);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('fulfilled', $order->status);
        $this->assertStringContainsString('google.ai.user@linkocial.com', $order->delivery_payload);
        $this->assertStringContainsString('SecretPass123!', $order->delivery_payload);

        // Deduplication event recorded
        $this->assertDatabaseHas('ai_webhook_events', [
            'event_id'   => 'evt_test_fulfillment_1001',
            'event_type' => 'order.fulfilled',
        ]);
    }

    public function test_signed_webhook_cancels_order_and_refunds_wallet(): void
    {
        $user = User::factory()->create(['balance' => 1000.00]);

        $order = AiOrder::create([
            'user_id'           => $user->id,
            'idempotency_key'   => 'aip_test_cancel_001',
            'provider_order_id' => 'AIP-R-CANCEL-123',
            'product_name'      => 'Google Cloud API',
            'quantity'          => 1,
            'unit_price'        => 3500.00,
            'total_price'       => 3500.00,
            'provider_cost'     => 3000.00,
            'status'            => 'processing',
        ]);

        $secret = 'whsec_test_secret_456789abcdef';
        $timestamp = time();

        $payload = [
            'id'        => 'evt_test_cancelled_2002',
            'type'      => 'order.cancelled',
            'createdAt' => now()->toISOString(),
            'data'      => [
                'order' => [
                    'id'        => 'AIP-R-CANCEL-123',
                    'status'    => 'cancelled',
                    'totalKobo' => 300000,
                    'currency'  => 'NGN',
                ],
            ],
        ];

        $rawBody = json_encode($payload);
        $signatureHash = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        $signatureHeader = "t={$timestamp},v1={$signatureHash}";

        $response = $this->call('POST', '/webhooks/aiplug', [], [], [], [
            'HTTP_X_AIPLUG_SIGNATURE' => $signatureHeader,
            'CONTENT_TYPE'            => 'application/json',
        ], $rawBody);

        $response->assertStatus(200);

        // User refunded: 1000 + 3500 = 4500
        $user->refresh();
        $this->assertEquals(4500.00, (float) $user->balance);

        $order->refresh();
        $this->assertEquals('refunded', $order->status);

        // Refund transaction logged
        $this->assertDatabaseHas('transactions', [
            'user_id'          => $user->id,
            'amount'           => 3500.00,
            'transaction_type' => 'refund',
            'type'             => 'credit',
        ]);
    }

    public function test_webhook_rejects_tampered_or_invalid_signature(): void
    {
        $timestamp = time();
        $rawBody = json_encode(['id' => 'evt_fake', 'type' => 'order.fulfilled']);
        $badSignatureHeader = "t={$timestamp},v1=" . str_repeat('a', 64);

        $response = $this->call('POST', '/webhooks/aiplug', [], [], [], [
            'HTTP_X_AIPLUG_SIGNATURE' => $badSignatureHeader,
            'CONTENT_TYPE'            => 'application/json',
        ], $rawBody);

        $response->assertStatus(401);
    }
}
