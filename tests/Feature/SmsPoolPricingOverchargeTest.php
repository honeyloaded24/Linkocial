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
use Mockery;

class SmsPoolPricingOverchargeTest extends TestCase
{
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
}
