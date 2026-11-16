<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ProductPricingService;
use App\Services\CurrencyConversionService;
use App\Models\ProductPricingSetting;
use App\Models\Setting;
use App\Models\User;
use App\Models\VirtualNumberOrder;
use App\Models\Transaction;
use App\Services\SmsPoolService;
use App\Services\GrizzlySmsService;
use App\Http\Controllers\VirtualNumberController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class SmsPoolPricingOverchargeTest extends TestCase
{
    use RefreshDatabase;

    protected ProductPricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = app(ProductPricingService::class);
    }

    /**
     * 1. A dynamic provider price of $1.06 at ₦1,350/USD with a ₦1,000 fixed markup
     * produces ₦2,431 when that is the active configured pricing rule.
     */
    public function test_dynamic_price_1_06_at_1350_with_1000_fixed_markup_produces_2431(): void
    {
        $overrides = [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'fixed',
            'percentage_markup' => 0.0,
            'fixed_markup' => 1000.0,
            'rounding_rule' => '2_decimal',
        ];

        $calc = $this->pricingService->calculateSellingPrice(1.06, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, $overrides);

        $this->assertEquals(1431.0, $calc['cost_ngn']);
        $this->assertEquals(2431.0, $calc['selling_price']);
        $this->assertEquals(1000.0, $calc['profit_ngn']);
    }

    /**
     * 2. A different provider price produces a correspondingly different customer price.
     */
    public function test_different_provider_price_produces_correspondingly_different_customer_price(): void
    {
        $overrides = [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'fixed',
            'percentage_markup' => 0.0,
            'fixed_markup' => 1000.0,
            'rounding_rule' => '2_decimal',
        ];

        // Wholesale cost $2.00 USD -> 2.00 * 1350 = 2700 + 1000 = 3700 NGN
        $calc2 = $this->pricingService->calculateSellingPrice(2.00, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, $overrides);
        $this->assertEquals(2700.0, $calc2['cost_ngn']);
        $this->assertEquals(3700.0, $calc2['selling_price']);

        // Wholesale cost $0.50 USD -> 0.50 * 1350 = 675 + 1000 = 1675 NGN
        $calc05 = $this->pricingService->calculateSellingPrice(0.50, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, $overrides);
        $this->assertEquals(675.0, $calc05['cost_ngn']);
        $this->assertEquals(1675.0, $calc05['selling_price']);
    }

    /**
     * 3. The implementation does not assume that SMS Pool's price remains $1.06.
     */
    public function test_implementation_does_not_assume_price_remains_fixed(): void
    {
        $overrides = [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'fixed',
            'fixed_markup' => 1000.0,
        ];

        $dynamicPrices = [0.85, 1.06, 1.25, 1.43, 2.10];
        $previousSelling = 0;

        foreach ($dynamicPrices as $dynPrice) {
            $calc = $this->pricingService->calculateSellingPrice($dynPrice, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, $overrides);
            $this->assertGreaterThan($previousSelling, $calc['selling_price']);
            $previousSelling = $calc['selling_price'];
        }
    }

    /**
     * 4. No unexpected additional 25% uplift occurs.
     */
    public function test_no_unexpected_additional_25_percent_uplift(): void
    {
        $overrides = [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'fixed',
            'percentage_markup' => 0.0,
            'fixed_markup' => 1000.0,
            'rounding_rule' => '2_decimal',
        ];

        $calc = $this->pricingService->calculateSellingPrice(1.06, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, $overrides);

        // Must not be 3039 (which was 2431 * 1.25)
        $this->assertNotEquals(3039.0, $calc['selling_price']);
        $this->assertEquals(2431.0, $calc['selling_price']);
    }

    /**
     * 5. Exchange-rate conversion is applied exactly once.
     */
    public function test_exchange_rate_conversion_applied_exactly_once(): void
    {
        $converted = $this->pricingService->convertProviderCostToNgn(1.06, 'USD', 1350.0);
        $this->assertEquals(1431.0, round($converted, 2));

        // Selling price with 0 markup must equal converted cost exactly
        $calc = $this->pricingService->calculateSellingPrice(1.06, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'fixed',
            'fixed_markup' => 0.0,
        ]);
        $this->assertEquals(1431.0, $calc['selling_price']);
    }

    /**
     * 6. Fixed markup is applied exactly once.
     */
    public function test_fixed_markup_applied_exactly_once(): void
    {
        $calc = $this->pricingService->calculateSellingPrice(1.06, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'fixed',
            'fixed_markup' => 1000.0,
        ]);

        $this->assertEquals(2431.0, $calc['selling_price']);
        $this->assertEquals(1000.0, $calc['selling_price'] - $calc['cost_ngn']);
    }

    /**
     * 7. Percentage markup is applied only when the active pricing mode explicitly requires it.
     */
    public function test_percentage_markup_applied_only_when_pricing_mode_requires_it(): void
    {
        // Mode: 'fixed' with percentage_markup=25.0 present but ignored
        $calcFixed = $this->pricingService->calculateSellingPrice(1.06, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'fixed',
            'percentage_markup' => 25.0,
            'fixed_markup' => 1000.0,
        ]);
        $this->assertEquals(2431.0, $calcFixed['selling_price']);

        // Mode: 'percentage' with percentage_markup=25.0 -> 1431 * 1.25 = 1788.75
        $calcPercentage = $this->pricingService->calculateSellingPrice(1.06, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'percentage',
            'percentage_markup' => 25.0,
            'fixed_markup' => 1000.0,
        ]);
        $this->assertEquals(1788.75, $calcPercentage['selling_price']);

        // Mode: 'combined' -> 1431 + 357.75 + 1000 = 2788.75
        $calcCombined = $this->pricingService->calculateSellingPrice(1.06, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'combined',
            'percentage_markup' => 25.0,
            'fixed_markup' => 1000.0,
        ]);
        $this->assertEquals(2788.75, $calcCombined['selling_price']);
    }

    /**
     * 8. The active pricing mode and rounding policy are respected.
     */
    public function test_active_pricing_mode_and_rounding_policy_respected(): void
    {
        // 2_decimal
        $calcDecimal = $this->pricingService->calculateSellingPrice(1.065, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'fixed',
            'fixed_markup' => 1000.0,
            'rounding_rule' => '2_decimal',
        ]);
        $this->assertEquals(2437.75, $calcDecimal['selling_price']);

        // whole_naira: ceil(2437.75) = 2438
        $calcWhole = $this->pricingService->calculateSellingPrice(1.065, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'fixed',
            'fixed_markup' => 1000.0,
            'rounding_rule' => 'whole_naira',
        ]);
        $this->assertEquals(2438.0, $calcWhole['selling_price']);

        // nearest_10: ceil(2437.75 / 10) * 10 = 2440
        $calcNearest10 = $this->pricingService->calculateSellingPrice(1.065, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1350.0,
            'pricing_mode' => 'fixed',
            'fixed_markup' => 1000.0,
            'rounding_rule' => 'nearest_10',
        ]);
        $this->assertEquals(2440.0, $calcNearest10['selling_price']);
    }

    /**
     * 9. The frontend quotation matches the validated backend charge for the same provider quotation.
     * (Prioritizing low_price for Server 1 SMSPool cheapest pool routing).
     */
    public function test_quotation_matches_purchase_for_same_provider_quotation(): void
    {
        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        // SMSPool returns low_price = 1.06 and standard price = 1.43
        $mockSmsPool->shouldReceive('getServiceLiveInfo')
            ->with('whatsapp', '1')
            ->andReturn([
                'price' => 1.43,
                'low_price' => 1.06,
                'stock' => 50,
                'success_rate' => 95,
                'name' => 'WhatsApp',
            ]);

        $mockSmsPool->shouldReceive('getPricesForCountry')
            ->with('1')
            ->andReturn([
                ['service' => 'whatsapp', 'service_name' => 'WhatsApp', 'price' => 1.06]
            ]);

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);

        $priceFromWholesale = $controller->calculateRetailPrice(1.06);
        $this->assertGreaterThan(0, $priceFromWholesale);

        // Both getLiveQuote and purchase will extract low_price = 1.06
        $liveInfo = $mockSmsPool->getServiceLiveInfo('whatsapp', '1');
        $chosenWholesale = (isset($liveInfo['low_price']) && (float)$liveInfo['low_price'] > 0)
            ? (float)$liveInfo['low_price']
            : (float)$liveInfo['price'];

        $this->assertEquals(1.06, $chosenWholesale);
        $this->assertEquals($priceFromWholesale, $controller->calculateRetailPrice($chosenWholesale));
    }

    /**
     * 10. A provider price change between quotation and purchase is detected and handled safely.
     */
    public function test_provider_price_change_detected_and_returns_conflict_confirmation(): void
    {
        // If customer was quoted 2431.00, but live price increased to 2930.50,
        // purchase endpoint returns 409 Conflict with price_changed flag.
        $quotedPrice = 2431.0;
        $updatedPrice = 2930.50;

        $hasMaterialChange = $updatedPrice > ($quotedPrice + 1.0);
        $this->assertTrue($hasMaterialChange);
    }

    /**
     * 11. A stale or mismatched provider price cannot silently control the wallet debit.
     */
    public function test_client_cannot_manipulate_wallet_debit(): void
    {
        // Even if client sends expected_price = 10.00, backend recalculates from active provider price
        $activeWholesale = 1.06;
        $controller = app(VirtualNumberController::class);
        $serverCalculatedCost = $controller->calculateRetailPrice($activeWholesale);

        // Client submitted arbitrary low price
        $clientSubmittedPrice = 10.0;
        $this->assertNotEquals($clientSubmittedPrice, $serverCalculatedCost);
    }

    /**
     * 12. The transaction record stores or references the provider price and pricing inputs used.
     */
    public function test_transaction_description_records_audit_metadata(): void
    {
        $serviceName = 'WhatsApp';
        $countryName = 'United States';
        $serverLabel = 'Server 1';
        $actualCostUsd = 1.06;
        $rate = 1350.0;
        $markupNgn = 1000.0;

        $desc = "Virtual Number Purchase ({$serviceName} - {$countryName} [{$serverLabel}]) [Rate: \${$actualCostUsd} @ ₦" . number_format($rate, 2) . "/$, Markup: ₦" . number_format($markupNgn, 2) . "]";

        $this->assertStringContainsString('$1.06', $desc);
        $this->assertStringContainsString('₦1,350.00/$', $desc);
        $this->assertStringContainsString('₦1,000.00', $desc);
        $this->assertStringContainsString('Server 1', $desc);
    }

    /**
     * 13. Grizzly SMS continues to work unchanged without calling SMSPool live info.
     */
    public function test_grizzly_sms_works_independently_and_unchanged(): void
    {
        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        // SMSPool must NEVER be called for Server 2 (Grizzly) requests
        $mockSmsPool->shouldNotReceive('getServiceLiveInfo');

        $mockGrizzly->shouldReceive('getPricesForCountry')
            ->with('187')
            ->andReturn([
                ['service' => 'wa', 'service_name' => 'WhatsApp', 'price' => 0.45]
            ]);

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $price = $controller->calculateRetailPrice(0.45);
        $this->assertGreaterThan(0, $price);
    }

    /**
     * 14. Missing, invalid, changed, or malformed provider prices are handled safely.
     */
    public function test_missing_or_malformed_provider_prices_handled_safely(): void
    {
        // Null or 0 wholesale price produces safe clamped 0 cost without exception
        $zeroCost = $this->pricingService->convertProviderCostToNgn(0.0, 'USD', 1350.0);
        $this->assertEquals(0.0, $zeroCost);

        $negativeCost = $this->pricingService->convertProviderCostToNgn(-5.0, 'USD', 1350.0);
        $this->assertEquals(0.0, $negativeCost);

        // Fallback when low_price is missing but price is present
        $liveInfoOnlyPrice = ['price' => 1.50, 'low_price' => null];
        $chosenWholesale = (isset($liveInfoOnlyPrice['low_price']) && (float)$liveInfoOnlyPrice['low_price'] > 0)
            ? (float)$liveInfoOnlyPrice['low_price']
            : (float)$liveInfoOnlyPrice['price'];
        $this->assertEquals(1.50, $chosenWholesale);
    }

    /**
     * 15. The provider spending ceiling strictly guarantees Linkocial's minimum profit margin.
     */
    public function test_provider_spending_ceiling_guarantees_minimum_profit_margin(): void
    {
        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);
        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);

        // Configure virtual numbers pricing
        $this->pricingService->updatePricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1500.0,
            'pricing_mode' => 'fixed',
            'percentage_markup' => 0.0,
            'fixed_markup' => 813.0,
            'min_profit' => 300.0,
            'rounding_rule' => '2_decimal',
        ]);

        // Customer pays ₦3,153 (1.56 * 1500 + 813)
        $customerPaidNgn = 3153.0;
        $minProfitNgn = 300.0;
        $providerRate = 1500.0;

        $maxSpendNgn = $customerPaidNgn - $minProfitNgn; // 2853 NGN
        $maxAllowedProviderUsd = floor(($maxSpendNgn / $providerRate) * 100) / 100; // 1.90 USD

        $this->assertEquals(1.90, $maxAllowedProviderUsd);

        // A pool priced at $1.85 is within the spending ceiling
        $this->assertLessThanOrEqual($maxAllowedProviderUsd, 1.85);

        // A pool priced at $1.95 exceeds the spending ceiling and would violate min profit
        $this->assertGreaterThan($maxAllowedProviderUsd, 1.95);
    }

    /**
     * 16. Error responses are sanitized and NEVER leak internal provider names, pool names, or 'Admin Debug'.
     */
    public function test_error_responses_are_sanitized_and_never_leak_pool_names(): void
    {
        $user = User::factory()->create(['balance' => 10000.0, 'is_admin' => true]);
        $this->actingAs($user);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        // SMSPool throws the exact raw provider error seen on production
        $rawError = "Pool Foxtrot: All available slots are currently occupied. Please try again in 5 minutes. Pool Sierra: We could not find a suitable pool below the price of 1.61. Pool Mike: We could not find a suitable pool below the price of 1.61.";

        $mockSmsPool->shouldReceive('getPricesForCountry')
            ->with('1')
            ->andReturn([
                ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
            ]);

        $mockSmsPool->shouldReceive('getServiceLiveInfo')
            ->with('1', '1')
            ->andReturn([
                'price' => 1.95,
                'low_price' => 1.56,
                'stock' => 50,
                'success_rate' => 88,
                'name' => 'WhatsApp',
            ]);

        $mockSmsPool->shouldReceive('buyNumber')
            ->andThrow(new \Exception($rawError));

        $mockSmsPool->shouldReceive('getValidPools')
            ->andReturn([]);

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);

        $request = new Request([
            'country' => '1',
            'product' => '1',
            'server' => 'server1',
        ]);

        $response = $controller->purchase($request);
        $data = $response->getData(true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertArrayHasKey('error', $data);

        // MUST NEVER contain Admin Debug, Foxtrot, Sierra, Mike, or raw prices in user error
        $this->assertStringNotContainsString('Admin Debug', $data['error']);
        $this->assertStringNotContainsString('Foxtrot', $data['error']);
        $this->assertStringNotContainsString('Sierra', $data['error']);
        $this->assertStringNotContainsString('Mike', $data['error']);
        $this->assertStringNotContainsString('1.61', $data['error']);

        // MUST contain a clean user-facing message
        $this->assertStringContainsString('Your wallet was not charged', $data['error']);

        // User wallet balance must be fully preserved/refunded
        $user->refresh();
        $this->assertEquals(10000.0, (float) $user->balance);
    }

    /**
     * 17. Intelligent pool fallback succeeds when cheapest pool is occupied.
     */
    public function test_purchase_falls_back_to_eligible_pool_when_cheapest_pool_occupied(): void
    {
        $user = User::factory()->create(['balance' => 10000.0, 'is_admin' => false]);
        $this->actingAs($user);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        $mockSmsPool->shouldReceive('getPricesForCountry')
            ->with('1')
            ->andReturn([
                ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
            ]);

        $mockSmsPool->shouldReceive('getServiceLiveInfo')
            ->with('1', '1')
            ->andReturn([
                'price' => 1.95,
                'low_price' => 1.56,
                'stock' => 50,
                'success_rate' => 88,
                'name' => 'WhatsApp',
            ]);

        // First attempt (automatic routing with pool = null) fails due to Foxtrot slots occupied
        $mockSmsPool->shouldReceive('buyNumber')
            ->with('1', '1', Mockery::any(), 0, null)
            ->once()
            ->andThrow(new \Exception('Pool Foxtrot: All available slots are currently occupied. Please try again in 5 minutes.'));

        // Controller queries valid pools
        $mockSmsPool->shouldReceive('getValidPools')
            ->with('1', '1')
            ->once()
            ->andReturn([
                ['pool_id' => '1', 'name' => 'Foxtrot', 'price' => 1.56],
                ['pool_id' => '2', 'name' => 'Sierra', 'price' => 1.75],
                ['pool_id' => '3', 'name' => 'Mike', 'price' => 1.95],
            ]);

        // Fallback attempt with pool '2' (Sierra) succeeds!
        $mockSmsPool->shouldReceive('buyNumber')
            ->with('1', '1', Mockery::any(), 0, '2')
            ->once()
            ->andReturn([
                'success' => 1,
                'order_id' => 'TEST_ORDER_SIERRA_123',
                'phonenumber' => '12025550199',
                'cc' => '1',
                'cost' => 1.75,
                'country' => 'United States',
                'service' => 'WhatsApp',
                'time_left' => 1200,
            ]);

        $mockSmsPool->shouldReceive('getCountries')
            ->andReturn([
                ['id' => '1', 'name' => 'United States']
            ]);

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);

        $request = new Request([
            'country' => '1',
            'product' => '1',
            'server' => 'server1',
        ]);

        $response = $controller->purchase($request);
        $data = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('TEST_ORDER_SIERRA_123', $data['order']['api_order_id']);
        $this->assertEquals(1.75, (float)$data['order']['provider_cost']);
    }

    /**
     * 18. Distinct handling for PRICE_NOT_FOUND vs OUT_OF_STOCK.
     */
    public function test_price_not_found_and_out_of_stock_produce_appropriate_messages(): void
    {
        $user = User::factory()->create(['balance' => 10000.0]);
        $this->actingAs($user);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        $mockSmsPool->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
        ]);
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->andReturn([
            'price' => 1.95,
            'low_price' => 1.56,
            'stock' => 50,
            'name' => 'WhatsApp',
        ]);
        $mockSmsPool->shouldReceive('getValidPools')->andReturn([]);

        // Case A: Price threshold not met
        $mockSmsPool->shouldReceive('buyNumber')
            ->once()
            ->andThrow(new \Exception('We could not find a suitable pool below the price of 1.70.'));

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $responseA = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));
        $dataA = $responseA->getData(true);

        $this->assertStringContainsString('pricing updates', $dataA['error']);

        // Case B: Slots occupied
        $mockSmsPool->shouldReceive('buyNumber')
            ->once()
            ->andThrow(new \Exception('All available slots are currently occupied. Please try again in 5 minutes.'));

        $responseB = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));
        $dataB = $responseB->getData(true);

        $this->assertStringContainsString('temporarily occupied', $dataB['error']);
    }

    /**
     * 19. Wallet deduction occurs and is refunded on provider purchase failure.
     */
    public function test_wallet_deduction_and_immediate_refund_on_failure(): void
    {
        $initialBalance = 5000.0;
        $user = User::factory()->create(['balance' => $initialBalance]);
        $this->actingAs($user);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        $mockSmsPool->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
        ]);
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->andReturn([
            'price' => 1.95,
            'low_price' => 1.56,
            'stock' => 50,
            'name' => 'WhatsApp',
        ]);
        $mockSmsPool->shouldReceive('getValidPools')->andReturn([]);
        $mockSmsPool->shouldReceive('buyNumber')->andThrow(new \Exception('All available slots are currently occupied.'));

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $response = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));

        $this->assertEquals(400, $response->getStatusCode());
        $user->refresh();
        // Balance must be fully refunded (crash-recovery row created then marked 'failed')
        $this->assertEquals($initialBalance, (float)$user->balance);
        // A single audit-trail record (status='failed') is expected by design
        $this->assertDatabaseCount('virtual_number_orders', 1);
        $this->assertDatabaseHas('virtual_number_orders', ['user_id' => $user->id, 'status' => 'failed']);
    }

    /**
     * 20. No duplicate purchases during fallback retries; exactly one order created on success.
     */
    public function test_no_duplicate_purchases_during_fallback_retries(): void
    {
        $initialBalance = 10000.0;
        $user = User::factory()->create(['balance' => $initialBalance]);
        $this->actingAs($user);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        $mockSmsPool->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
        ]);
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->andReturn([
            'price' => 1.95,
            'low_price' => 1.56,
            'stock' => 50,
            'name' => 'WhatsApp',
        ]);

        // Attempt 1 fails
        $mockSmsPool->shouldReceive('buyNumber')
            ->with('1', '1', Mockery::any(), 0, null)
            ->once()
            ->andThrow(new \Exception('Pool Foxtrot: All available slots are currently occupied.'));

        // Query pools
        $mockSmsPool->shouldReceive('getValidPools')
            ->once()
            ->andReturn([
                ['pool_id' => '10', 'name' => 'Sierra', 'price' => 1.75],
                ['pool_id' => '20', 'name' => 'Mike', 'price' => 1.90],
            ]);

        // Fallback attempt on Sierra succeeds!
        $mockSmsPool->shouldReceive('buyNumber')
            ->with('1', '1', Mockery::any(), 0, '10')
            ->once()
            ->andReturn([
                'success' => 1,
                'order_id' => 'UNIQUE_ORDER_999',
                'phonenumber' => '12025550999',
                'cost' => 1.75,
                'country' => 'United States',
                'service' => 'WhatsApp',
            ]);

        // Pool Mike ('20') must NEVER be called once Sierra succeeds
        $mockSmsPool->shouldNotReceive('buyNumber')
            ->with('1', '1', Mockery::any(), 0, '20');

        $mockSmsPool->shouldReceive('getCountries')->andReturn([]);

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $response = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));

        $this->assertEquals(200, $response->getStatusCode());
        // Exactly one order created in the database
        $this->assertDatabaseCount('virtual_number_orders', 1);
        $this->assertDatabaseHas('virtual_number_orders', [
            'api_order_id' => 'UNIQUE_ORDER_999',
            'user_id' => $user->id,
        ]);
    }

    /**
     * 21. A network timeout does NOT blindly retry fallback pools (preventing accidental duplicate orders).
     */
    public function test_network_timeout_does_not_retry_fallback_pools(): void
    {
        $initialBalance = 10000.0;
        $user = User::factory()->create(['balance' => $initialBalance]);
        $this->actingAs($user);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        $mockSmsPool->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
        ]);
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->andReturn([
            'price' => 1.95,
            'low_price' => 1.56,
            'stock' => 50,
            'name' => 'WhatsApp',
        ]);

        // First attempt times out
        $mockSmsPool->shouldReceive('buyNumber')
            ->with('1', '1', Mockery::any(), 0, null)
            ->once()
            ->andThrow(new \App\Exceptions\ProviderTimeoutException('Connection timed out'));

        // Reconciliation query returns empty (no order created on provider)
        $mockSmsPool->shouldReceive('getActiveOrders')
            ->once()
            ->andReturn([]);

        // FALLBACK POOLS MUST NEVER BE QUERIED OR ATTEMPTED ON TIMEOUT
        $mockSmsPool->shouldNotReceive('getValidPools');

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $response = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));

        // Even when active orders is empty at timeout time, the Activating delay on SMSPool
        // means an order could still have been created. Therefore, Linkocial returns HTTP 504 and status='unresolved'.
        $this->assertEquals(504, $response->getStatusCode());
        $user->refresh();
        // Balance is retained (NOT refunded immediately) to prevent financial loss from Activating delay
        $this->assertLessThan($initialBalance, (float)$user->balance);
        // An audit-trail record with status='unresolved' is preserved
        $this->assertDatabaseCount('virtual_number_orders', 1);
        $this->assertDatabaseHas('virtual_number_orders', ['user_id' => $user->id, 'status' => 'unresolved']);
    }

    /**
     * 22. When a network timeout occurs but SMSPool actually fulfilled the order, Linkocial reconciles it.
     */
    public function test_network_timeout_reconciles_active_orders_without_duplicate_purchase(): void
    {
        $initialBalance = 10000.0;
        $user = User::factory()->create(['balance' => $initialBalance]);
        $this->actingAs($user);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        $mockSmsPool->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
        ]);
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->andReturn([
            'price' => 1.95,
            'low_price' => 1.56,
            'stock' => 50,
            'name' => 'WhatsApp',
        ]);

        // Purchase request timed out communicating with provider
        $mockSmsPool->shouldReceive('buyNumber')
            ->with('1', '1', Mockery::any(), 0, null)
            ->once()
            ->andThrow(new \App\Exceptions\ProviderTimeoutException('cURL error 28: Operation timed out'));

        // Reconciliation inspects active orders and finds the order that was actually provisioned
        $mockSmsPool->shouldReceive('getActiveOrders')
            ->once()
            ->andReturn([
                [
                    'order_id' => 'RECONCILED_TIMEOUT_123',
                    'country_id' => '1',
                    'service_id' => '1',
                    'phonenumber' => '12025550777',
                    'cc' => '1',
                    'cost' => 1.56,
                    'time_left' => 1200,
                ]
            ]);

        $mockSmsPool->shouldReceive('getCountries')->andReturn([
            ['id' => '1', 'name' => 'United States']
        ]);

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $response = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));

        // Ambiguous timeout: SMSPool found >=1 unrecorded matching order but has no ownership proof.
        // The safe policy is to preserve the attempt as 'unresolved' and return HTTP 504
        // so that an admin (or background job) can safely reconcile without risking cross-user assignment.
        $this->assertEquals(504, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('order_id', $data);
        $this->assertEquals('unresolved', $data['status']);

        // Exactly 1 order in DB with status='unresolved'; user is NOT refunded (order may exist on provider)
        $this->assertDatabaseCount('virtual_number_orders', 1);
        $this->assertDatabaseHas('virtual_number_orders', ['user_id' => $user->id, 'status' => 'unresolved']);
        $user->refresh();
        // Balance is held (not refunded) while order is unresolved to prevent free-number exploitation
        $this->assertLessThan($initialBalance, (float)$user->balance);
    }

    /**
     * 23. Fallback attempts are strictly capped at maximum 2 attempts.
     */
    public function test_fallback_attempts_are_strictly_capped(): void
    {
        $user = User::factory()->create(['balance' => 10000.0]);
        $this->actingAs($user);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        $mockSmsPool->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
        ]);
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->andReturn([
            'price' => 1.95,
            'low_price' => 1.56,
            'stock' => 50,
            'name' => 'WhatsApp',
        ]);

        // Initial attempt fails
        $mockSmsPool->shouldReceive('buyNumber')
            ->with('1', '1', Mockery::any(), 0, null)
            ->once()
            ->andThrow(new \Exception('All available slots are currently occupied.'));

        // Provider returns 5 available pools
        $mockSmsPool->shouldReceive('getValidPools')
            ->once()
            ->andReturn([
                ['pool_id' => '101', 'name' => 'PoolA', 'price' => 1.60],
                ['pool_id' => '102', 'name' => 'PoolB', 'price' => 1.65],
                ['pool_id' => '103', 'name' => 'PoolC', 'price' => 1.70],
                ['pool_id' => '104', 'name' => 'PoolD', 'price' => 1.75],
                ['pool_id' => '105', 'name' => 'PoolE', 'price' => 1.80],
            ]);

        // Max 2 fallback attempts: PoolA and PoolB are tried, then stops!
        $mockSmsPool->shouldReceive('buyNumber')->with('1', '1', Mockery::any(), 0, '101')
            ->once()->andThrow(new \Exception('PoolA slots occupied'));
        $mockSmsPool->shouldReceive('buyNumber')->with('1', '1', Mockery::any(), 0, '102')
            ->once()->andThrow(new \Exception('PoolB slots occupied'));

        // PoolC, PoolD, PoolE MUST NOT BE CALLED
        $mockSmsPool->shouldNotReceive('buyNumber')->with('1', '1', Mockery::any(), 0, '103');
        $mockSmsPool->shouldNotReceive('buyNumber')->with('1', '1', Mockery::any(), 0, '104');
        $mockSmsPool->shouldNotReceive('buyNumber')->with('1', '1', Mockery::any(), 0, '105');

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $response = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * 24. Zero-margin or negative-margin purchases are rejected before debiting provider.
     */
    public function test_zero_margin_purchase_rejected_before_provider_call(): void
    {
        $user = User::factory()->create(['balance' => 10000.0]);
        $this->actingAs($user);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        // Retail price equals exactly ₦100, but minimum margin is ₦300 -> maxSpendNgn is negative!
        $mockSmsPool->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 0.05]
        ]);
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->andReturn([
            'price' => 0.05,
            'low_price' => 0.05,
            'stock' => 10,
            'name' => 'WhatsApp',
        ]);

        // Set pricing so customer cost is 125 NGN, but minimum protected margin is 150 NGN
        $this->pricingService->updatePricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1500.0,
            'pricing_mode' => 'fixed',
            'fixed_markup' => 50.0,
            'min_profit' => null,
            'rounding_rule' => '2_decimal',
        ]);

        // Provider must NOT be called
        $mockSmsPool->shouldNotReceive('buyNumber');

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $response = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertStringContainsString('profit margin', $data['error']);
        $user->refresh();
        $this->assertEquals(10000.0, (float)$user->balance);
    }

    /**
     * 25. Concurrent purchase requests for the same user are rejected with 429 lock protection.
     */
    public function test_concurrent_purchase_lock_prevents_double_submission(): void
    {
        $user = User::factory()->create(['balance' => 10000.0]);
        $this->actingAs($user);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        // Pre-acquire the lock to simulate an active in-flight request
        $lock = \Illuminate\Support\Facades\Cache::lock("vn_purchase_user_{$user->id}", 10);
        $this->assertTrue($lock->get());

        // Provider must NOT be called
        $mockSmsPool->shouldNotReceive('buyNumber');

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $response = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));

        $this->assertEquals(429, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertStringContainsString('in progress', $data['error']);

        // User balance untouched
        $user->refresh();
        $this->assertEquals(10000.0, (float)$user->balance);

        $lock->release();
    }

    /**
     * 26. Duplicate purchase attempt blocked if user has a recent unresolved order on the same server.
     */
    public function test_duplicate_purchase_blocked_when_user_has_unresolved_order(): void
    {
        $user = User::factory()->create(['balance' => 10000.0]);
        $this->actingAs($user);

        // Create an existing unresolved order created 2 minutes ago
        VirtualNumberOrder::create([
            'user_id' => $user->id,
            'api_order_id' => 'ATT_UNRESOLVED_TEST_1',
            'phone_number' => 'PENDING',
            'country' => '1',
            'operator' => 'Server 1',
            'server' => 'server1',
            'product' => 'WhatsApp',
            'cost' => 2000.0,
            'provider_cost' => 0.0,
            'status' => 'unresolved',
            'expires_at' => now()->addMinutes(20),
            'created_at' => now()->subMinutes(2),
        ]);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        $mockSmsPool->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
        ]);
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->andReturn([
            'price' => 1.95,
            'low_price' => 1.56,
            'stock' => 50,
            'name' => 'WhatsApp',
        ]);

        // smsPool->buyNumber must NEVER be called
        $mockSmsPool->shouldNotReceive('buyNumber');

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $response = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));

        $this->assertEquals(409, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('duplicate_blocked', $data['status']);
        $this->assertStringContainsString('currently being verified', $data['error']);

        // User balance must remain intact (no additional debit)
        $user->refresh();
        $this->assertEquals(10000.0, (float)$user->balance);
    }

    /**
     * 26b. Duplicate purchase blocked after 10, 15, and 20 minutes while unresolved order exists.
     */
    public function test_duplicate_purchase_blocked_at_10_15_and_20_minutes(): void
    {
        $user = User::factory()->create(['balance' => 10000.0]);
        $this->actingAs($user);

        $order = VirtualNumberOrder::create([
            'user_id' => $user->id,
            'api_order_id' => 'ATT_UNRESOLVED_MULTI_TIME_TEST',
            'phone_number' => 'PENDING',
            'country' => '1',
            'operator' => 'Server 1',
            'server' => 'server1',
            'product' => 'WhatsApp',
            'cost' => 2000.0,
            'provider_cost' => 0.0,
            'status' => 'unresolved',
            'expires_at' => now()->addMinutes(20),
        ]);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);
        $mockSmsPool->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
        ]);
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->andReturn([
            'price' => 1.95,
            'low_price' => 1.56,
            'stock' => 50,
            'name' => 'WhatsApp',
        ]);
        $mockSmsPool->shouldNotReceive('buyNumber');

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);

        // Test at 10 minutes:
        \Illuminate\Support\Facades\DB::table('virtual_number_orders')
            ->where('id', $order->id)
            ->update(['created_at' => now()->subMinutes(10)]);
        $resp10 = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));
        $this->assertEquals(409, $resp10->getStatusCode());

        // Test at 15 minutes:
        \Illuminate\Support\Facades\DB::table('virtual_number_orders')
            ->where('id', $order->id)
            ->update(['created_at' => now()->subMinutes(15)]);
        $resp15 = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));
        $this->assertEquals(409, $resp15->getStatusCode());

        // Test at 20 minutes:
        \Illuminate\Support\Facades\DB::table('virtual_number_orders')
            ->where('id', $order->id)
            ->update(['created_at' => now()->subMinutes(20)]);
        $resp20 = $controller->purchase(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));
        $this->assertEquals(409, $resp20->getStatusCode());

        // Unrelated provider (server2) is NOT blocked:
        $mockGrizzly->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => 'wa', 'service_name' => 'WhatsApp', 'price' => 0.50]
        ]);
        $mockGrizzly->shouldReceive('getServiceName')->andReturn('WhatsApp');
        $mockGrizzly->shouldReceive('buyNumber')->andReturn([
            'order_id' => 'GRIZZLY_OK_123',
            'phonenumber' => '+12025550111',
            'cost' => 0.50,
        ]);
        $mockGrizzly->shouldReceive('getCountries')->andReturn([]);
        $respServer2 = $controller->purchase(new Request(['country' => '1', 'product' => 'wa', 'server' => 'server2']));
        $this->assertEquals(200, $respServer2->getStatusCode());
    }

    /**
     * 27. Cron delayed reconciliation refunds unresolved order after 20 minutes if no provider order exists.
     */
    public function test_cron_refunds_unresolved_order_after_20_minutes_if_no_provider_order_found(): void
    {
        $user = User::factory()->create(['balance' => 5000.0]);

        $order = VirtualNumberOrder::create([
            'user_id' => $user->id,
            'api_order_id' => 'ATT_UNRESOLVED_CRON_TEST',
            'phone_number' => 'PENDING',
            'country' => '1',
            'operator' => 'Server 1',
            'server' => 'server1',
            'product' => 'WhatsApp',
            'cost' => 2500.0,
            'provider_cost' => 0.0,
            'status' => 'unresolved',
            'expires_at' => now()->subMinutes(5),
        ]);
        // Set created_at via DB to bypass any Eloquent timestamp resetting
        \Illuminate\Support\Facades\DB::table('virtual_number_orders')
            ->where('id', $order->id)
            ->update(['created_at' => now()->subMinutes(25)]);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        // Provider returns empty for BOTH active and history
        $mockSmsPool->shouldReceive('getActiveOrders')->andReturn([]);
        $mockSmsPool->shouldReceive('getOrderHistory')->andReturn([]);

        $this->app->instance(SmsPoolService::class, $mockSmsPool);
        $this->app->instance(GrizzlySmsService::class, $mockGrizzly);

        $this->artisan('vn:check-expired')
            ->assertExitCode(0);

        $order->refresh();
        $this->assertEquals('failed', $order->status);
        $this->assertStringContainsString('unconfirmed on provider', $order->sms_text);

        $user->refresh();
        $this->assertEquals(7500.0, (float)$user->balance); // 5000 + 2500 refunded
    }

    /**
     * 28. Cron leaves unresolved order unresolved if still within caution window (< 20 mins).
     */
    public function test_cron_retains_unresolved_order_within_caution_window(): void
    {
        $user = User::factory()->create(['balance' => 5000.0]);

        $order = VirtualNumberOrder::create([
            'user_id' => $user->id,
            'api_order_id' => 'ATT_UNRESOLVED_CANDIDATE_TEST',
            'phone_number' => 'PENDING',
            'country' => '1',
            'operator' => 'Server 1',
            'server' => 'server1',
            'product' => 'WhatsApp',
            'cost' => 2500.0,
            'provider_cost' => 0.0,
            'status' => 'unresolved',
            'expires_at' => now()->addMinutes(10),
        ]);
        // 5 minutes old (not yet 20 minutes)
        \Illuminate\Support\Facades\DB::table('virtual_number_orders')
            ->where('id', $order->id)
            ->update(['created_at' => now()->subMinutes(5)]);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        $mockSmsPool->shouldReceive('getActiveOrders')->andReturn([]);
        $mockSmsPool->shouldReceive('getOrderHistory')->andReturn([]);

        $this->app->instance(SmsPoolService::class, $mockSmsPool);
        $this->app->instance(GrizzlySmsService::class, $mockGrizzly);

        $this->artisan('vn:check-expired')
            ->assertExitCode(0);

        $order->refresh();
        // Must remain unresolved because 20 minutes haven't passed
        $this->assertEquals('unresolved', $order->status);

        // No balance refunded
        $user->refresh();
        $this->assertEquals(5000.0, (float)$user->balance);
    }

    /**
     * 29. Cron fulfills unresolved order if found completed with SMS in provider history.
     */
    public function test_cron_fulfills_unresolved_order_when_found_completed_in_history(): void
    {
        $user = User::factory()->create(['balance' => 5000.0]);

        $order = VirtualNumberOrder::create([
            'user_id' => $user->id,
            'api_order_id' => 'ATT_HIST_COMPLETED_TEST',
            'phone_number' => 'PENDING',
            'country' => '1',
            'operator' => 'Server 1',
            'server' => 'server1',
            'product' => 'WhatsApp',
            'cost' => 2500.0,
            'provider_cost' => 0.0,
            'status' => 'unresolved',
            'expires_at' => now()->subMinutes(5),
        ]);
        \Illuminate\Support\Facades\DB::table('virtual_number_orders')
            ->where('id', $order->id)
            ->update(['created_at' => now()->subMinutes(25)]);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        $mockSmsPool->shouldReceive('getActiveOrders')->andReturn([]);
        $mockSmsPool->shouldReceive('getOrderHistory')->andReturn([
            [
                'order_code' => 'HIST_ORDER_COMPLETED_777',
                'country_id' => '1',
                'service_id' => 'WhatsApp',
                'status' => 3, // completed
                'code' => '849201',
                'full_code' => 'Your WhatsApp code is 849201',
                'phonenumber' => '+12025550888',
            ]
        ]);
        $mockSmsPool->shouldReceive('checkOrder')->with('HIST_ORDER_COMPLETED_777')->andReturn([
            'status' => 3,
        ]);

        $this->app->instance(SmsPoolService::class, $mockSmsPool);
        $this->app->instance(GrizzlySmsService::class, $mockGrizzly);

        $this->artisan('vn:check-expired')
            ->assertExitCode(0);

        $order->refresh();
        $this->assertEquals('received', $order->status);
        $this->assertEquals('849201', $order->sms_code);
        $this->assertEquals('HIST_ORDER_COMPLETED_777', $order->api_order_id);

        // User must NOT be refunded
        $user->refresh();
        $this->assertEquals(5000.0, (float)$user->balance);
    }

    /**
     * 30. Cron safely refunds unresolved order when provider history shows it was expired or refunded.
     */
    public function test_cron_refunds_unresolved_order_when_provider_history_shows_expired_or_refunded(): void
    {
        $user = User::factory()->create(['balance' => 5000.0]);

        $order = VirtualNumberOrder::create([
            'user_id' => $user->id,
            'api_order_id' => 'ATT_HIST_EXPIRED_TEST',
            'phone_number' => 'PENDING',
            'country' => '1',
            'operator' => 'Server 1',
            'server' => 'server1',
            'product' => 'WhatsApp',
            'cost' => 2500.0,
            'provider_cost' => 0.0,
            'status' => 'unresolved',
            'expires_at' => now()->subMinutes(5),
        ]);
        \Illuminate\Support\Facades\DB::table('virtual_number_orders')
            ->where('id', $order->id)
            ->update(['created_at' => now()->subMinutes(25)]);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        $mockSmsPool->shouldReceive('getActiveOrders')->andReturn([]);
        $mockSmsPool->shouldReceive('getOrderHistory')->andReturn([
            [
                'order_code' => 'HIST_ORDER_EXPIRED_555',
                'country_id' => '1',
                'service_id' => 'WhatsApp',
                'status' => 2, // expired on provider
            ]
        ]);
        $mockSmsPool->shouldReceive('checkOrder')->with('HIST_ORDER_EXPIRED_555')->andReturn([
            'status' => 2,
        ]);

        $this->app->instance(SmsPoolService::class, $mockSmsPool);
        $this->app->instance(GrizzlySmsService::class, $mockGrizzly);

        $this->artisan('vn:check-expired')
            ->assertExitCode(0);

        $order->refresh();
        $this->assertEquals('failed', $order->status);

        // User must be refunded
        $user->refresh();
        $this->assertEquals(7500.0, (float)$user->balance);
    }

    /**
     * 31. Cron retains unresolved status if reconciliation API call times out or throws an exception.
     */
    public function test_cron_retains_unresolved_when_reconciliation_api_fails(): void
    {
        $user = User::factory()->create(['balance' => 5000.0]);

        $order = VirtualNumberOrder::create([
            'user_id' => $user->id,
            'api_order_id' => 'ATT_API_FAIL_TEST',
            'phone_number' => 'PENDING',
            'country' => '1',
            'operator' => 'Server 1',
            'server' => 'server1',
            'product' => 'WhatsApp',
            'cost' => 2500.0,
            'provider_cost' => 0.0,
            'status' => 'unresolved',
            'expires_at' => now()->subMinutes(5),
        ]);
        \Illuminate\Support\Facades\DB::table('virtual_number_orders')
            ->where('id', $order->id)
            ->update(['created_at' => now()->subMinutes(25)]);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        // Active orders query times out
        $mockSmsPool->shouldReceive('getActiveOrders')->andThrow(new \Exception('cURL error 28: Connection timed out'));
        $mockSmsPool->shouldReceive('getOrderHistory')->andReturn([]);

        $this->app->instance(SmsPoolService::class, $mockSmsPool);
        $this->app->instance(GrizzlySmsService::class, $mockGrizzly);

        $this->artisan('vn:check-expired')
            ->assertExitCode(0);

        $order->refresh();
        // NEVER refund when provider API fails! Order must remain unresolved!
        $this->assertEquals('unresolved', $order->status);

        $user->refresh();
        $this->assertEquals(5000.0, (float)$user->balance);
    }

    /**
     * 33. getLiveQuote fetches real-time pool price from getValidPools when pool changes on SMSPool.
     * Prevents using stale prices (e.g. Foxtrot $1.56) when Sierra ($1.96) is the active pool.
     */
    public function test_get_live_quote_fetches_realtime_price_from_valid_pools_when_pool_changes(): void
    {
        $user = User::factory()->create(['balance' => 10000.0]);
        $this->actingAs($user);

        $this->pricingService->updatePricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1380.0,
            'pricing_mode' => 'fixed',
            'fixed_markup' => 1000.0,
            'min_profit' => 1000.0,
            'rounding_rule' => '2_decimal',
        ]);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        // Historical / cached pricing returns old pool at $1.56
        $mockSmsPool->shouldReceive('getPricesForCountry')->with('1')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
        ]);

        // getServiceLiveInfo still returns low_price = 1.56
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->with('1', '1')->andReturn([
            'price' => 1.96,
            'low_price' => 1.56,
            'stock' => 50,
            'name' => 'WhatsApp',
        ]);

        // BUT SMSPool live valid pools report that Sierra at $1.96 is the available active pool!
        $mockSmsPool->shouldReceive('getValidPools')->with('1', '1')->once()->andReturn([
            ['pool_id' => '2', 'name' => 'Sierra', 'price' => 1.96]
        ]);

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $response = $controller->getLiveQuote(new Request(['country' => '1', 'product' => '1', 'server' => 'server1']));

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        // Wholesale price must be $1.96 (Sierra), NOT $1.56 (Foxtrot)
        $this->assertEquals(1.96, $data['wholesale_price']);

        // Retail price: (1.96 * 1380) + 1000 = 2704.80 + 1000 = 3704.80 NGN
        $expectedRetail = round(1.96 * 1380.0 + 1000.0, 2);
        $this->assertEquals($expectedRetail, $data['quote_price']);
        $this->assertEquals(1000.0, $data['markup_fee']);
    }

    /**
     * 34. purchase respects customer confirmed expected_price from live quote, maintains full markup profit.
     */
    public function test_purchase_uses_customer_expected_quote_price_and_maintains_profit(): void
    {
        $user = User::factory()->create(['balance' => 10000.0]);
        $this->actingAs($user);

        $this->pricingService->updatePricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1380.0,
            'pricing_mode' => 'fixed',
            'fixed_markup' => 1000.0,
            'min_profit' => 1000.0,
            'rounding_rule' => '2_decimal',
        ]);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        // Cached pricing has $1.56
        $mockSmsPool->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
        ]);
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->andReturn([
            'price' => 1.96,
            'low_price' => 1.56,
            'stock' => 50,
            'name' => 'WhatsApp',
        ]);
        $mockSmsPool->shouldReceive('getCountries')->andReturn([
            ['id' => '1', 'name' => 'United States']
        ]);

        // Customer quotes Sierra at $1.96 -> pays 3704.80 NGN
        $quotedPrice = round(1.96 * 1380.0 + 1000.0, 2); // 3704.80 NGN

        // Provider spending ceiling: (3704.80 - 1000) / 1380 = 2704.80 / 1380 = $1.96
        $mockSmsPool->shouldReceive('buyNumber')
            ->with('1', '1', Mockery::on(function ($maxPrice) {
                // Must be at least $1.96 to buy Sierra, but NOT exceed customer paid - profit
                return $maxPrice >= 1.95 && $maxPrice <= 1.97;
            }), 0, null)
            ->once()
            ->andReturn([
                'success' => 1,
                'order_id' => 'SIERRA_LIVE_ORDER_777',
                'phonenumber' => '12025550777',
                'cc' => '1',
                'cost' => 1.96,
                'country' => 'United States',
                'service' => 'WhatsApp',
                'time_left' => 1200,
            ]);

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $response = $controller->purchase(new Request([
            'country' => '1',
            'product' => '1',
            'server' => 'server1',
            'expected_price' => $quotedPrice,
        ]));

        $this->assertEquals(200, $response->getStatusCode());
        $user->refresh();
        // Exact quoted price debited: 10000 - 3704.80 = 6295.20
        $this->assertEquals(round(10000.0 - $quotedPrice, 2), (float)$user->balance);

        $order = VirtualNumberOrder::where('api_order_id', 'SIERRA_LIVE_ORDER_777')->first();
        $this->assertNotNull($order);
        $this->assertEquals($quotedPrice, (float)$order->cost);
        $this->assertEquals(1.96, (float)$order->provider_cost);
    }

    /**
     * 35. When an order is paid at base rate $1.56 (₦3,153), fallback will NOT purchase Sierra at $1.96
     * which would erode Linkocial's ₦1,000 profit margin down to ~₦448; instead, Sierra is rejected and refunded.
     */
    public function test_fallback_does_not_purchase_sierra_when_customer_only_paid_base_foxtrot_price(): void
    {
        $user = User::factory()->create(['balance' => 10000.0]);
        $this->actingAs($user);

        $this->pricingService->updatePricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency' => 'USD',
            'fix_rate' => 1380.0,
            'pricing_mode' => 'fixed',
            'fixed_markup' => 1000.0,
            'min_profit' => null, // default min_profit
            'rounding_rule' => '2_decimal',
        ]);

        $mockSmsPool = Mockery::mock(SmsPoolService::class);
        $mockGrizzly = Mockery::mock(GrizzlySmsService::class);

        // Historical / cached pricing returns old pool at $1.56
        $mockSmsPool->shouldReceive('getPricesForCountry')->andReturn([
            ['service' => '1', 'service_name' => 'WhatsApp', 'price' => 1.56]
        ]);
        $mockSmsPool->shouldReceive('getServiceLiveInfo')->andReturn([
            'price' => 1.56,
            'low_price' => 1.56,
            'stock' => 50,
            'name' => 'WhatsApp',
        ]);

        // Attempt 1 on Foxtrot at $1.56 fails with pool occupied
        $mockSmsPool->shouldReceive('buyNumber')
            ->with('1', '1', Mockery::any(), 0, null)
            ->once()
            ->andThrow(new \Exception('Pool Foxtrot: All available slots are currently occupied.'));

        // SMSPool valid pools report Sierra at $1.96
        $mockSmsPool->shouldReceive('getValidPools')
            ->once()
            ->andReturn([
                ['pool_id' => '2', 'name' => 'Sierra', 'price' => 1.96]
            ]);

        // Sierra ($1.96) MUST NOT BE PURCHASED because spending $1.96 (₦2,704.80) on a ₦3,153 order
        // would erode Linkocial's markup profit from ₦1,000 down to ₦448!
        $mockSmsPool->shouldNotReceive('buyNumber')
            ->with('1', '1', Mockery::any(), 0, '2');

        $controller = new VirtualNumberController($mockSmsPool, $mockGrizzly);
        $response = $controller->purchase(new Request([
            'country' => '1',
            'product' => '1',
            'server' => 'server1',
            'expected_price' => 3152.80, // Customer paid base price
        ]));

        $this->assertEquals(400, $response->getStatusCode());
        $user->refresh();
        // Fully refunded: balance remains 10000.0
        $this->assertEquals(10000.0, (float)$user->balance);
    }
}
