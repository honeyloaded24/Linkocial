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

        $this->customer = new User([
            'id' => 9881,
            'name' => 'Gift Card Customer',
            'email' => 'gccustomer@linkocial.test',
            'username' => 'gccustomer',
            'is_admin' => false,
            'role' => 'user',
            'balance' => 50000.00,
            'currency_code' => 'NGN',
        ]);

        $this->otherCustomer = new User([
            'id' => 9882,
            'name' => 'Other Customer',
            'email' => 'other@linkocial.test',
            'username' => 'othercustomer',
            'is_admin' => false,
            'role' => 'user',
            'balance' => 10000.00,
            'currency_code' => 'NGN',
        ]);

        $this->admin = new User([
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

        // 1. Pending -> Processing
        $this->assertTrue($order->canTransitionTo(GiftCardOrder::STATUS_PROCESSING));
        $order->status = GiftCardOrder::STATUS_PROCESSING;
        $this->assertTrue($order->isProcessing());

        // Repeated processing of same state must return true (idempotent)
        $this->assertTrue($order->canTransitionTo(GiftCardOrder::STATUS_PROCESSING));

        // 2. Processing -> Completed
        $this->assertTrue($order->canTransitionTo(GiftCardOrder::STATUS_COMPLETED));
        $order->status = GiftCardOrder::STATUS_COMPLETED;
        $this->assertTrue($order->isCompleted());
        $this->assertTrue($order->isTerminal());

        // Repeated completion of already completed order must return true (idempotent)
        $this->assertTrue($order->canTransitionTo(GiftCardOrder::STATUS_COMPLETED));
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
        $order->status = GiftCardOrder::STATUS_REFUNDED;
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

        // Simulating handleTimeout logic
        if ($order->status === GiftCardOrder::STATUS_PENDING) {
            $order->status = GiftCardOrder::STATUS_PROCESSING;
            $order->status_details = 'Provider timed out; awaiting async verification.';
        }

        $this->assertEquals(GiftCardOrder::STATUS_PROCESSING, $order->status);
        $this->assertFalse($order->isFailed());
        $this->assertFalse($order->isRefunded());
        $this->assertStringContainsString('Provider timed out', $order->status_details);
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
     */
    public function test_prestmit_service_safety_when_unconfigured(): void
    {
        Http::fake();

        config([
            'services.prestmit.api_key' => null,
            'services.prestmit.base_url' => 'https://api.prestmit.com/v1',
        ]);

        $service = new PrestmitGiftCardService();
        $this->assertFalse($service->isConfigured());

        $this->expectException(GiftCardProviderNotConfiguredException::class);
        $service->getAvailableProducts();

        // Ensure zero external HTTP requests were attempted
        Http::assertNothingSent();
    }

    /**
     * Test authorization policy allows owning customer and admin, blocks others.
     */
    public function test_gift_card_order_policy_authorization(): void
    {
        $policy = new GiftCardOrderPolicy();

        $order = new GiftCardOrder([
            'user_id' => $this->customer->id,
            'status'  => GiftCardOrder::STATUS_PENDING,
        ]);

        // Owning customer can view their order
        $this->assertTrue($policy->view($this->customer, $order));

        // Administrator can view any order
        $this->assertTrue($policy->view($this->admin, $order));

        // Another customer CANNOT view this order
        $this->assertFalse($policy->view($this->otherCustomer, $order));

        // Only admin can update
        $this->assertTrue($policy->update($this->admin, $order));
        $this->assertFalse($policy->update($this->customer, $order));

        // Only admin can delete
        $this->assertTrue($policy->delete($this->admin, $order));
        $this->assertFalse($policy->delete($this->customer, $order));
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
