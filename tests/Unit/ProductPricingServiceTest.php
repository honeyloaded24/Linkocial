<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Setting;
use App\Services\CurrencyConversionService;
use App\Services\ProductPricingService;
use InvalidArgumentException;

class ProductPricingServiceTest extends TestCase
{
    protected ProductPricingService $pricingService;
    protected CurrencyConversionService $currencyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currencyService = new CurrencyConversionService();
        $this->pricingService = new ProductPricingService($this->currencyService);

        // Standard baseline platform exchange rate
        Setting::set('usd_to_ngn_rate', '1500');
    }

    /**
     * Requirement: Provider rates in USD are converted using the product's active fix rate.
     */
    public function test_usd_cost_converted_using_active_fix_rate(): void
    {
        // $1.00 USD @ ₦1,500 = ₦1,500.00
        $this->assertEquals(1500.00, $this->pricingService->convertProviderCostToNgn(1.00, 'USD', 1500.0));

        // $2.50 USD @ ₦1,600 = ₦4,000.00
        $this->assertEquals(4000.00, $this->pricingService->convertProviderCostToNgn(2.50, 'USD', 1600.0));

        // $0.00 cost -> ₦0.00
        $this->assertEquals(0.00, $this->pricingService->convertProviderCostToNgn(0.00, 'USD', 1500.0));

        // If provider is already NGN, no FX conversion
        $this->assertEquals(2500.00, $this->pricingService->convertProviderCostToNgn(2500.00, 'NGN', 1500.0));
    }

    /**
     * Requirement: Percentage markup applied correctly using BOOST's exact formula.
     * Example: $1.00 USD @ ₦1,550 + 30% = ₦2,015.00
     */
    public function test_percentage_markup_applied_correctly(): void
    {
        $overrides = [
            'currency'          => 'USD',
            'fix_rate'          => 1550.0,
            'pricing_mode'      => 'percentage',
            'percentage_markup' => 30.0,
            'fixed_markup'      => 0.0,
            'rounding_rule'     => '2_decimal',
        ];

        // 1. $1.00 rate
        $calc1 = $this->pricingService->calculateSellingPrice(1.00, ProductPricingService::PRODUCT_VTU, $overrides);
        $this->assertEquals(1550.00, $calc1['cost_ngn']);
        $this->assertEquals(2015.00, $calc1['selling_price']);
        $this->assertEquals(465.00, $calc1['profit_ngn']);
        $this->assertEquals(30.0, $calc1['profit_margin_percent']);

        // 2. $2.50 rate: $2.50 * 1550 = 3875 * 1.30 = 5037.50
        $calc2 = $this->pricingService->calculateSellingPrice(2.50, ProductPricingService::PRODUCT_VTU, $overrides);
        $this->assertEquals(3875.00, $calc2['cost_ngn']);
        $this->assertEquals(5037.50, $calc2['selling_price']);
        $this->assertEquals(1162.50, $calc2['profit_ngn']);
        $this->assertEquals(30.0, $calc2['profit_margin_percent']);
    }

    /**
     * Requirement: Fixed flat markup applied correctly.
     * Example: $1.00 USD @ ₦1,500 + ₦500 fixed = ₦2,000.00
     */
    public function test_fixed_flat_markup_applied_correctly(): void
    {
        $overrides = [
            'currency'          => 'USD',
            'fix_rate'          => 1500.0,
            'pricing_mode'      => 'fixed',
            'percentage_markup' => 0.0,
            'fixed_markup'      => 500.0,
            'rounding_rule'     => '2_decimal',
        ];

        $calc = $this->pricingService->calculateSellingPrice(1.00, ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, $overrides);
        $this->assertEquals(1500.00, $calc['cost_ngn']);
        $this->assertEquals(2000.00, $calc['selling_price']);
        $this->assertEquals(500.00, $calc['profit_ngn']);
    }

    /**
     * Requirement: Combined markup mode: Cost * (1 + %/100) + Fixed.
     */
    public function test_combined_markup_mode(): void
    {
        $overrides = [
            'currency'          => 'USD',
            'fix_rate'          => 1500.0,
            'pricing_mode'      => 'combined',
            'percentage_markup' => 20.0, // ₦1,500 * 1.20 = ₦1,800
            'fixed_markup'      => 200.0, // + ₦200 = ₦2,000
            'rounding_rule'     => '2_decimal',
        ];

        $calc = $this->pricingService->calculateSellingPrice(1.00, ProductPricingService::PRODUCT_LOGS, $overrides);
        $this->assertEquals(1500.00, $calc['cost_ngn']);
        $this->assertEquals(2000.00, $calc['selling_price']);
        $this->assertEquals(500.00, $calc['profit_ngn']);
    }

    /**
     * Requirement: Minimum profit floor and loss prevention guarantees.
     */
    public function test_minimum_profit_and_no_selling_at_loss(): void
    {
        $overrides = [
            'currency'          => 'USD',
            'fix_rate'          => 1500.0,
            'pricing_mode'      => 'percentage',
            'percentage_markup' => 0.0,
            'min_profit'        => 75.0,
            'rounding_rule'     => '2_decimal',
        ];

        $calc = $this->pricingService->calculateSellingPrice(1.00, ProductPricingService::PRODUCT_VTU, $overrides);
        $this->assertEquals(1500.00, $calc['cost_ngn']);
        $this->assertEquals(1575.00, $calc['selling_price']); // Cost + ₦75 min profit
        $this->assertEquals(75.00, $calc['profit_ngn']);

        // Never sell at a loss
        $noLossOverrides = [
            'currency'          => 'USD',
            'fix_rate'          => 1500.0,
            'pricing_mode'      => 'percentage',
            'percentage_markup' => 0.0,
            'min_profit'        => null,
            'rounding_rule'     => '2_decimal',
        ];
        $calcNoLoss = $this->pricingService->calculateSellingPrice(1.00, ProductPricingService::PRODUCT_VTU, $noLossOverrides);
        $this->assertGreaterThanOrEqual($calcNoLoss['cost_ngn'], $calcNoLoss['selling_price']);
    }

    /**
     * Requirement: Rounding policies replicate BOOST behavior exactly.
     * (2_decimal, whole_naira, nearest_10)
     */
    public function test_rounding_policies_match_boost(): void
    {
        // Raw calculation: ₦1,500 * (1 + 33.33/100) = ₦1,999.95
        $base = [
            'currency'          => 'USD',
            'fix_rate'          => 1500.0,
            'pricing_mode'      => 'percentage',
            'percentage_markup' => 33.33,
        ];

        // 1. 2_decimal
        $calc1 = $this->pricingService->calculateSellingPrice(1.00, ProductPricingService::PRODUCT_VTU, array_merge($base, ['rounding_rule' => '2_decimal']));
        $this->assertEquals(1999.95, $calc1['selling_price']);

        // 2. whole_naira
        $calc2 = $this->pricingService->calculateSellingPrice(1.00, ProductPricingService::PRODUCT_VTU, array_merge($base, ['rounding_rule' => 'whole_naira']));
        $this->assertEquals(2000.00, $calc2['selling_price']);

        // 3. nearest_10
        $calc3 = $this->pricingService->calculateSellingPrice(1.00, ProductPricingService::PRODUCT_VTU, array_merge($base, ['rounding_rule' => 'nearest_10']));
        $this->assertEquals(2000.00, $calc3['selling_price']);
    }

    /**
     * Requirement: Negative provider cost throws InvalidArgumentException.
     */
    public function test_negative_cost_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pricingService->convertProviderCostToNgn(-10.0, 'USD', 1500.0);
    }

    /**
     * Requirement: Invalid exchange rate (<= 0) throws InvalidArgumentException.
     */
    public function test_invalid_fix_rate_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pricingService->convertProviderCostToNgn(1.00, 'USD', 0.0);
    }

    /**
     * Requirement: Changing settings for one product does not change another product.
     */
    public function test_product_configurations_are_independent(): void
    {
        // Update VTU settings
        $this->pricingService->updatePricingSettings(ProductPricingService::PRODUCT_VTU, [
            'currency'          => 'NGN',
            'fix_rate'          => 1600.0,
            'pricing_mode'      => 'percentage',
            'percentage_markup' => 12.5,
            'fixed_markup'      => 0.0,
            'rounding_rule'     => 'whole_naira',
        ]);

        // Update Virtual Numbers settings
        $this->pricingService->updatePricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'currency'          => 'USD',
            'fix_rate'          => 1750.0,
            'pricing_mode'      => 'fixed',
            'percentage_markup' => 0.0,
            'fixed_markup'      => 1200.0,
            'rounding_rule'     => 'nearest_10',
        ]);

        // Retrieve both
        $vtu = $this->pricingService->getPricingSettings(ProductPricingService::PRODUCT_VTU);
        $vn = $this->pricingService->getPricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS);
        $logs = $this->pricingService->getPricingSettings(ProductPricingService::PRODUCT_LOGS);

        // Verify VTU
        $this->assertEquals('NGN', $vtu->getCurrency());
        $this->assertEquals(1600.0, $vtu->getFixRate());
        $this->assertEquals('percentage', $vtu->getPricingMode());
        $this->assertEquals(12.5, $vtu->getPercentageMarkup());
        $this->assertEquals('whole_naira', $vtu->getRoundingRule());

        // Verify Virtual Numbers is independent
        $this->assertEquals('USD', $vn->getCurrency());
        $this->assertEquals(1750.0, $vn->getFixRate());
        $this->assertEquals('fixed', $vn->getPricingMode());
        $this->assertEquals(1200.0, $vn->getFixedMarkup());
        $this->assertEquals('nearest_10', $vn->getRoundingRule());

        // Verify Logs remains default / untouched
        $this->assertEquals('NGN', $logs->getCurrency());
        $this->assertNotEquals(12.5, $logs->getPercentageMarkup());
    }
}
