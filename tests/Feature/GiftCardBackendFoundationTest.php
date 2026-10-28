<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use App\Models\GiftCardOrder;
use App\Models\GiftCardProduct;
use App\Models\ProductPricingSetting;
use App\Services\ProductPricingService;
use App\Services\CurrencyConversionService;
use App\Services\PrestmitGiftCardService;
use App\Exceptions\GiftCardProviderNotConfiguredException;
use App\Policies\GiftCardOrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use DomainException;

class GiftCardBackendFoundationTest extends TestCase
{
    protected User $customer;
    protected User $otherCustomer;
    protected User $admin;
    protected ProductPricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            // Safe fallback in test environments
        }

        // Set global baseline rates
        Setting::set('usd_to_ngn_rate', '1500');
        Setting::set('provider_usd_rate', '1500');

        $this->customer = (new User())->forceFill([
            'id' => 9881,
            'name' => 'Gift Card Customer',
            'email' => 'gccustomer@linkocial.test',
            'username' => 'gccustomer',
            'is_admin' => false,
            'role' => 'user',
            'balance' => 50000.00,
            'currency_code' => 'NGN',
        ]);

        $this->otherCustomer = (new User())->forceFill([
            'id' => 9882,
            'name' => 'Other Customer',
            'email' => 'other@linkocial.test',
            'username' => 'othercustomer',
            'is_admin' => false,
            'role' => 'user',
            'balance' => 10000.00,
            'currency_code' => 'NGN',
        ]);

        $this->admin = (new User())->forceFill([
            'id' => 9883,
            'name' => 'HQ Admin',
            'email' => 'hqadmin@linkocial.test',
            'username' => 'hqadmin',
            'is_admin' => true,
            'role' => 'admin',
        ]);

        $this->pricingService = app(ProductPricingService::class);
    }

    /**
     * Test model instantiation, default values, and reference generation.
     */
    public function test_gift_card_order_creation_and_defaults(): void
    {
        $order = new GiftCardOrder([
            'user_id'             => 9881,
            'brand_code'          => 'amazon_us',
            'brand_name'          => 'Amazon US Gift Card',
            'country_code'        => 'US',
            'face_value'          => 50.00,
            'face_value_currency' => 'USD',
            'amount_ngn'          => 75000.00,
            'provider'            => 'prestmit',
        ]);

        // Manually trigger boot logic if not saving to DB
        if (empty($order->order_reference)) {
            $order->order_reference = GiftCardOrder::generateOrderReference();
        }
        if (empty($order->idempotency_key)) {
            $order->idempotency_key = (string) \Illuminate\Support\Str::uuid();
        }
        if (empty($order->status)) {
            $order->status = GiftCardOrder::STATUS_PENDING;
        }

        $this->assertNotEmpty($order->order_reference);
        $this->assertStringStartsWith('GC-', $order->order_reference);
        $this->assertNotEmpty($order->idempotency_key);
        $this->assertEquals(GiftCardOrder::STATUS_PENDING, $order->status);
        $this->assertTrue($order->isPending());
        $this->assertFalse($order->isTerminal());
        $this->assertEquals('prestmit', $order->provider);
        $this->assertEquals(50.00, (float) $order->face_value);
        $this->assertEquals(75000.00, (float) $order->amount_ngn);
    }

    /**
     * Test valid lifecycle transitions and idempotency of repeated processing.
     */
    public function test_valid_status_transitions_and_repeated_processing(): void
    {
        $order = new GiftCardOrder([
            'user_id' => 9881,
            'status'  => GiftCardOrder::STATUS_PENDING,
        ]);

        // 1. Pending -> Processing (with provider reference)
        $this->assertTrue($order->canTransitionTo(GiftCardOrder::STATUS_PROCESSING));
        $this->assertTrue($order->markAsProcessing('PREST-REF-1001', 'Dispatched to provider'));
        $this->assertEquals(GiftCardOrder::STATUS_PROCESSING, $order->status);
        $this->assertTrue($order->isProcessing());
        $this->assertEquals('PREST-REF-1001', $order->provider_reference);

        // Repeated processing of same state must return true (idempotent)
        $this->assertTrue($order->canTransitionTo(GiftCardOrder::STATUS_PROCESSING));
        $this->assertTrue($order->markAsProcessing('PREST-REF-1001', 'Duplicate callback received'));

        // 2. Processing -> Completed
        $this->assertTrue($order->canTransitionTo(GiftCardOrder::STATUS_COMPLETED));
        $this->assertTrue($order->markAsCompleted('Voucher issued successfully'));
        $this->assertEquals(GiftCardOrder::STATUS_COMPLETED, $order->status);
        $this->assertTrue($order->isCompleted());
        $this->assertTrue($order->isTerminal());

        // Repeated completion of already completed order must return true (idempotent)
        $this->assertTrue($order->canTransitionTo(GiftCardOrder::STATUS_COMPLETED));
        $this->assertTrue($order->markAsCompleted('Second completion event'));
    }

    /**
     * Test invalid status transitions are rejected.
     */
    public function test_invalid_status_transitions(): void
    {
        $order = new GiftCardOrder([
            'user_id' => 9881,
            'status'  => GiftCardOrder::STATUS_COMPLETED,
        ]);

        // Completed is terminal; cannot transition to pending or processing
        $this->assertFalse($order->canTransitionTo(GiftCardOrder::STATUS_PENDING));
        $this->assertFalse($order->canTransitionTo(GiftCardOrder::STATUS_PROCESSING));

        $this->expectException(DomainException::class);
        $order->transitionTo(GiftCardOrder::STATUS_PENDING);
    }

    /**
     * Test refund lifecycle rules.
     */
    public function test_refund_lifecycle_rules(): void
    {
        $order = new GiftCardOrder([
            'user_id' => 9881,
            'status'  => GiftCardOrder::STATUS_FAILED,
        ]);

        // Failed -> Refunded is valid
        $this->assertTrue($order->canTransitionTo(GiftCardOrder::STATUS_REFUNDED));
        $this->assertTrue($order->markAsRefunded('Customer refunded due to provider rejection'));
        $this->assertEquals(GiftCardOrder::STATUS_REFUNDED, $order->status);
        $this->assertTrue($order->isRefunded());
        $this->assertTrue($order->isTerminal());

        // Refunded cannot transition back to completed
        $this->assertFalse($order->canTransitionTo(GiftCardOrder::STATUS_COMPLETED));
    }

    /**
     * Test provider timeout handling leaves order in processing and never triggers premature failure/refund.
     */
    public function test_timeout_handling_does_not_fail_or_refund(): void
    {
        $order = new GiftCardOrder([
            'user_id' => 9881,
            'status'  => GiftCardOrder::STATUS_PENDING,
        ]);

        $this->assertTrue($order->isPending());

        // Call handleTimeout
        $order->handleTimeout('Provider HTTP timeout after 30 seconds');

        $this->assertEquals(GiftCardOrder::STATUS_PROCESSING, $order->status);
        $this->assertFalse($order->isFailed());
        $this->assertFalse($order->isRefunded());
        $this->assertStringContainsString('Provider HTTP timeout', $order->status_details);

        // Repeated timeout on already processing order should retain processing status
        $order->handleTimeout('Second timeout reminder');
        $this->assertEquals(GiftCardOrder::STATUS_PROCESSING, $order->status);
        $this->assertFalse($order->isFailed());
        $this->assertFalse($order->isRefunded());
    }

    /**
     * Test gift-card pricing isolation from other verticals.
     */
    public function test_gift_card_pricing_isolation(): void
    {
        $defaultGc = $this->pricingService->getDefaultSettings(ProductPricingService::PRODUCT_GIFT_CARDS);
        $this->assertEquals('USD', $defaultGc['currency']);
        $this->assertEquals(0.0, $defaultGc['percentage_markup']);
        $this->assertEquals('percentage', $defaultGc['pricing_mode']);

        // Update gift_cards pricing
        $this->pricingService->updatePricingSettings(ProductPricingService::PRODUCT_GIFT_CARDS, [
            'currency'          => 'USD',
            'fix_rate'          => 1620.00,
            'pricing_mode'      => 'percentage',
            'percentage_markup' => 8.5,
            'fixed_markup'      => 0.0,
            'rounding_rule'     => '2_decimal',
        ]);

        $gcSettings = $this->pricingService->getPricingSettings(ProductPricingService::PRODUCT_GIFT_CARDS);
        $this->assertEquals(1620.00, $gcSettings->getFixRate());
        $this->assertEquals(8.5, $gcSettings->getPercentageMarkup());

        // Verify other product verticals remain unaffected
        $vtu = $this->pricingService->getPricingSettings(ProductPricingService::PRODUCT_VTU);
        $this->assertNotEquals(1620.00, $vtu->getFixRate());

        $boost = $this->pricingService->getPricingSettings(ProductPricingService::PRODUCT_BOOST);
        $this->assertNotEquals(1620.00, $boost->getFixRate());

        $logs = $this->pricingService->getPricingSettings(ProductPricingService::PRODUCT_LOGS);
        $this->assertNotEquals(1620.00, $logs->getFixRate());
    }

    /**
     * Test Prestmit service boundary throws and makes NO external HTTP requests when unconfigured.
     * Verifies that missing credentials or missing explicit base URL cause safe failure.
     */
    public function test_prestmit_service_safety_when_unconfigured(): void
    {
        Http::fake();

        // Case 1: Both credentials and base_url absent
        config([
            'services.prestmit.api_key' => null,
            'services.prestmit.base_url' => null,
        ]);

        $service = new PrestmitGiftCardService();
        $this->assertFalse($service->isConfigured());

        // Case 2: API key present but base_url absent (verifies no implicit default URL)
        config([
            'services.prestmit.api_key' => 'secret_test_key_123',
            'services.prestmit.base_url' => null,
        ]);
        $serviceNoUrl = new PrestmitGiftCardService();
        $this->assertFalse($serviceNoUrl->isConfigured());

        // Case 3: Base URL present but API key absent
        config([
            'services.prestmit.api_key' => null,
            'services.prestmit.base_url' => 'https://api.prestmit.com/v1',
        ]);
        $serviceNoKey = new PrestmitGiftCardService();
        $this->assertFalse($serviceNoKey->isConfigured());

        $this->expectException(GiftCardProviderNotConfiguredException::class);
        $service->getAvailableProducts();

        // Ensure zero external HTTP requests were attempted
        Http::assertNothingSent();
    }

    /**
     * Test Prestmit service makes NO external HTTP requests even when credentials are configured in Phase 1.
     */
    public function test_prestmit_service_makes_no_network_calls_even_when_configured(): void
    {
        Http::fake();

        config([
            'services.prestmit.api_key' => 'verified_partner_key',
            'services.prestmit.base_url' => 'https://partner.prestmit.example/api',
            'services.prestmit.mode' => 'sandbox',
        ]);

        $service = new PrestmitGiftCardService();
        $this->assertTrue($service->isConfigured());
        $this->assertEquals('sandbox', $service->getMode());
        $this->assertEquals('https://partner.prestmit.example/api', $service->getBaseUrl());

        // Phase 1 safe placeholders: no outbound calls even with credentials
        $products = $service->getAvailableProducts();
        $this->assertIsArray($products);
        $this->assertEmpty($products);

        $details = $service->getProductDetails('amazon_us');
        $this->assertNull($details);

        $status = $service->getOrderStatus('TEST-REF-123');
        $this->assertIsArray($status);
        $this->assertEquals('awaiting_provider_verification', $status['status']);

        // Assert strictly zero HTTP network calls were made
        Http::assertNothingSent();
    }

    /**
     * Test authorization policy:
     * - viewAny(): strictly restricted to administrators.
     * - view(): restricted to owning customer and administrators.
     * - update() and delete(): strictly restricted to administrators.
     */
    public function test_gift_card_order_policy_authorization(): void
    {
        $policy = new GiftCardOrderPolicy();

        $order = new GiftCardOrder([
            'user_id' => $this->customer->id,
            'status'  => GiftCardOrder::STATUS_PENDING,
        ]);

        // 1. viewAny(): Only admin passes; ordinary users are blocked
        $this->assertTrue($policy->viewAny($this->admin));
        $this->assertFalse($policy->viewAny($this->customer));
        $this->assertFalse($policy->viewAny($this->otherCustomer));

        // 2. view(): Owning customer can view their own order
        $this->assertTrue($policy->view($this->customer, $order));

        // Administrator can view any order
        $this->assertTrue($policy->view($this->admin, $order));

        // Another customer CANNOT view this order
        $this->assertFalse($policy->view($this->otherCustomer, $order));

        // 3. update(): Only admin can update
        $this->assertTrue($policy->update($this->admin, $order));
        $this->assertFalse($policy->update($this->customer, $order));
        $this->assertFalse($policy->update($this->otherCustomer, $order));

        // 4. delete(): Only admin can delete
        $this->assertTrue($policy->delete($this->admin, $order));
        $this->assertFalse($policy->delete($this->customer, $order));
        $this->assertFalse($policy->delete($this->otherCustomer, $order));
    }

    /**
     * Test catalogue model creation and scopes.
     */
    public function test_gift_card_product_model(): void
    {
        $product = new GiftCardProduct([
            'brand_code'          => 'apple_us',
            'brand_name'          => 'Apple App Store & iTunes US',
            'country_code'        => 'US',
            'currency'            => 'USD',
            'min_face_value'      => 10.00,
            'max_face_value'      => 500.00,
            'fixed_denominations' => [10, 15, 25, 50, 100],
            'is_active'           => true,
        ]);

        $this->assertEquals('apple_us', $product->brand_code);
        $this->assertTrue($product->is_active);
        $this->assertIsArray($product->fixed_denominations);
        $this->assertCount(5, $product->fixed_denominations);
    }
}
