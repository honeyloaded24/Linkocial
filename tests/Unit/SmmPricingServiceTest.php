<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\CurrencyConversionService;
use App\Services\SmmProviderService;
use App\Services\SmmPricingService;
use InvalidArgumentException;

class SmmPricingServiceTest extends TestCase
{
    protected SmmPricingService $pricingService;
    protected CurrencyConversionService $currencyService;
    protected Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currencyService = new CurrencyConversionService();
        $smmProviderService = $this->createMock(SmmProviderService::class);
        $this->pricingService = new SmmPricingService($this->currencyService, $smmProviderService);

        // Standard test exchange rate: 1 USD = ₦1,550 NGN
        Setting::set('usd_to_ngn_rate', '1550');

        $this->provider = new Provider([
            'id'                 => 999,
            'name'               => 'Momopanel',
            'api_url'            => 'https://momopanel.com/api/v2',
            'api_key'            => 'secret_key_12345',
            'currency'           => 'USD',
            'pricing_mode'       => 'percentage',
            'percentage_markup'  => 30.0,
            'fixed_markup_per_k' => 0.0,
            'min_selling_price'  => null,
            'min_profit'         => null,
            'rounding_rule'      => '2_decimal',
        ]);
    }

    /**
     * Requirement 1: USD provider rates are converted using the saved admin exchange rate.
     */
    public function test_usd_provider_rates_converted_using_admin_exchange_rate(): void
    {
        // At ₦1,550 per USD:
        // $1.00 USD -> ₦1,550.00
        $this->assertEquals(1550.00, $this->pricingService->convertProviderRateToNgn(1.00, 'USD'));

        // $2.50 USD -> ₦3,875.00
        $this->assertEquals(3875.00, $this->pricingService->convertProviderRateToNgn(2.50, 'USD'));

        // $0.00 -> ₦0.00
        $this->assertEquals(0.00, $this->pricingService->convertProviderRateToNgn(0.00, 'USD'));

        // If provider is already NGN, no FX conversion
        $this->assertEquals(2500.00, $this->pricingService->convertProviderRateToNgn(2500.00, 'NGN'));
    }

    /**
     * Requirement 2: Percentage markup is applied correctly.
     * Example: $1.00 USD @ ₦1,550 + 30% = ₦2,015.00
     * Example: $2.50 USD @ ₦1,550 + 30% = ₦5,037.50
     */
    public function test_percentage_markup_applied_correctly(): void
    {
        $this->provider->percentage_markup = 30.0;
        $this->provider->pricing_mode = 'percentage';
        $this->provider->rounding_rule = '2_decimal';

        // 1. $1.00 rate
        $calc1 = $this->pricingService->calculateSellingPrice(1.00, $this->provider);
        $this->assertEquals(1550.00, $calc1['cost_ngn']);
        $this->assertEquals(2015.00, $calc1['selling_price']);
        $this->assertEquals(465.00, $calc1['profit_ngn']);
        $this->assertEquals(30.0, $calc1['profit_margin_percent']);

        // 2. $2.50 rate
        $calc2 = $this->pricingService->calculateSellingPrice(2.50, $this->provider);
        $this->assertEquals(3875.00, $calc2['cost_ngn']);
        $this->assertEquals(5037.50, $calc2['selling_price']);
        $this->assertEquals(1162.50, $calc2['profit_ngn']);
        $this->assertEquals(30.0, $calc2['profit_margin_percent']);
    }

    /**
     * Requirement 3: Fixed markup per 1,000 units is applied correctly when enabled.
     * Example: $1.00 USD @ ₦1,550 + ₦100 fixed = ₦1,650.00
     */
    public function test_fixed_markup_applied_correctly(): void
    {
        $this->provider->pricing_mode = 'fixed_per_k';
        $this->provider->fixed_markup_per_k = 100.0;

        $calc = $this->pricingService->calculateSellingPrice(1.00, $this->provider);
        $this->assertEquals(1550.00, $calc['cost_ngn']);
        $this->assertEquals(1650.00, $calc['selling_price']);
        $this->assertEquals(100.00, $calc['profit_ngn']);
    }

    /**
     * Combined markup mode: Converted Cost × (1 + %/100) + Fixed.
     */
    public function test_combined_markup_mode(): void
    {
        $this->provider->pricing_mode = 'combined';
        $this->provider->percentage_markup = 20.0; // ₦1,550 * 1.20 = ₦1,860
        $this->provider->fixed_markup_per_k = 140.0; // + ₦140 = ₦2,000

        $calc = $this->pricingService->calculateSellingPrice(1.00, $this->provider);
        $this->assertEquals(1550.00, $calc['cost_ngn']);
        $this->assertEquals(2000.00, $calc['selling_price']);
        $this->assertEquals(450.00, $calc['profit_ngn']);
    }

    /**
     * Minimum profit floor and never selling below cost guarantees.
     */
    public function test_minimum_profit_and_no_selling_at_loss(): void
    {
        // Configure 0% markup but minimum profit ₦50.00
        $this->provider->pricing_mode = 'percentage';
        $this->provider->percentage_markup = 0.0;
        $this->provider->min_profit = 50.0;

        $calc = $this->pricingService->calculateSellingPrice(1.00, $this->provider);
        $this->assertEquals(1550.00, $calc['cost_ngn']);
        $this->assertEquals(1600.00, $calc['selling_price']); // ₦1550 + ₦50 min profit
        $this->assertEquals(50.00, $calc['profit_ngn']);

        // Even with negative or 0 markup and no profit floor, price NEVER falls below cost
        $this->provider->min_profit = null;
        $calcNoLoss = $this->pricingService->calculateSellingPrice(1.00, $this->provider);
        $this->assertGreaterThanOrEqual($calcNoLoss['cost_ngn'], $calcNoLoss['selling_price']);
    }

    /**
     * Requirement 4: Provider pricing units (per-1000 units vs package).
     */
    public function test_provider_pricing_units_respected(): void
    {
        // Standard Service (priced per 1,000 units)
        $standardService = new Service([
            'id' => 101,
            'name' => 'Instagram Followers [HQ]',
            'type' => 'Default',
            'selling_price' => 2015.00, // ₦2,015 per 1,000
        ]);

        // Customer orders 500 units -> Charge is (2,015 / 1000) * 500 = ₦1,007.50
        $charge500 = $this->pricingService->calculateCustomerCharge($standardService, 500);
        $this->assertFalse($charge500['is_package']);
        $this->assertEquals(1007.50, $charge500['final_charge']);

        // Customer orders 1,000 units -> Charge is ₦2,015.00
        $charge1000 = $this->pricingService->calculateCustomerCharge($standardService, 1000);
        $this->assertEquals(2015.00, $charge1000['final_charge']);

        // Customer orders 2,500 units -> Charge is (2,015 / 1000) * 2500 = ₦5,037.50
        $charge2500 = $this->pricingService->calculateCustomerCharge($standardService, 2500);
        $this->assertEquals(5037.50, $charge2500['final_charge']);

        // Package Service (flat fixed rate, not divided by 1,000)
        $packageService = new Service([
            'id' => 102,
            'name' => 'Telegram Account Verification Package',
            'type' => 'Package',
            'selling_price' => 15000.00,
        ]);

        $chargePackage = $this->pricingService->calculateCustomerCharge($packageService, 1);
        $this->assertTrue($chargePackage['is_package']);
        $this->assertEquals(15000.00, $chargePackage['final_charge']);
    }

    /**
     * Requirement 6: Changing the saved exchange rate changes the next calculated price.
     */
    public function test_changing_saved_exchange_rate_changes_calculated_price(): void
    {
        $this->provider->percentage_markup = 30.0;

        // At rate 1550: $1.00 -> ₦2,015.00
        Setting::set('usd_to_ngn_rate', '1550');
        $calc1 = $this->pricingService->calculateSellingPrice(1.00, $this->provider);
        $this->assertEquals(2015.00, $calc1['selling_price']);

        // Update admin rate to 1600: $1.00 -> $1.00 * 1600 * 1.30 = ₦2,080.00
        Setting::set('usd_to_ngn_rate', '1600');
        $calc2 = $this->pricingService->calculateSellingPrice(1.00, $this->provider);
        $this->assertEquals(2080.00, $calc2['selling_price']);
    }

    /**
     * Requirement 7: Changing the markup changes the selling price without modifying provider's raw rate.
     */
    public function test_changing_markup_preserves_provider_raw_rate(): void
    {
        $rawRate = 2.50;

        $this->provider->percentage_markup = 20.0;
        $calc20 = $this->pricingService->calculateSellingPrice($rawRate, $this->provider);
        $this->assertEquals(2.50, $calc20['provider_rate_raw']);
        // $2.50 * 1550 = ₦3,875.00 * 1.20 = ₦4,650.00
        $this->assertEquals(4650.00, $calc20['selling_price']);

        $this->provider->percentage_markup = 40.0;
        $calc40 = $this->pricingService->calculateSellingPrice($rawRate, $this->provider);
        $this->assertEquals(2.50, $calc40['provider_rate_raw']); // Provider raw rate remains strictly $2.50
        // $2.50 * 1550 = ₦3,875.00 * 1.40 = ₦5,425.00
        $this->assertEquals(5425.00, $calc40['selling_price']);
    }

    /**
     * Requirement 8: Invalid exchange rates throw an exception.
     */
    public function test_invalid_exchange_rate_throws_exception(): void
    {
        Setting::set('usd_to_ngn_rate', '0');
        $this->expectException(InvalidArgumentException::class);
        $this->pricingService->convertProviderRateToNgn(1.00, 'USD');
    }

    /**
     * Negative provider rate throws exception.
     */
    public function test_negative_provider_rate_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pricingService->convertProviderRateToNgn(-5.00, 'USD');
    }

    /**
     * Rounding policies: 2_decimal, whole_naira, nearest_10.
     */
    public function test_rounding_policies(): void
    {
        $this->provider->percentage_markup = 33.33; // raw: 1550 * 1.3333 = 2066.615
        
        // 2 Decimals
        $this->provider->rounding_rule = '2_decimal';
        $res1 = $this->pricingService->calculateSellingPrice(1.00, $this->provider);
        $this->assertEquals(2066.62, $res1['selling_price']);

        // Whole Naira
        $this->provider->rounding_rule = 'whole_naira';
        $res2 = $this->pricingService->calculateSellingPrice(1.00, $this->provider);
        $this->assertEquals(2067.00, $res2['selling_price']);

        // Nearest 10
        $this->provider->rounding_rule = 'nearest_10';
        $res3 = $this->pricingService->calculateSellingPrice(1.00, $this->provider);
        $this->assertEquals(2070.00, $res3['selling_price']);
    }
}
