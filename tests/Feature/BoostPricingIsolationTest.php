<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Setting;
use App\Models\ProductPricingSetting;
use App\Services\ProductPricingService;
use App\Services\SmmPricingService;
use App\Services\CurrencyConversionService;
use Illuminate\Support\Facades\Artisan;

class BoostPricingIsolationTest extends TestCase
{
    protected User $admin;
    protected User $customer;
    protected Provider $boostProvider;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            // Ignore in test env
        }

        // Set global baseline rates
        Setting::set('usd_to_ngn_rate', '1500');
        Setting::set('provider_usd_rate', '1500');

        $this->admin = new User([
            'id' => 9991,
            'name' => 'HQ Pricing Admin',
            'email' => 'hqpricingadmin@linkocial.test',
            'username' => 'hqpricingadmin',
            'is_admin' => true,
            'role' => 'admin',
        ]);

        $this->customer = new User([
            'id' => 9992,
            'name' => 'Regular Customer',
            'email' => 'regcustomer@linkocial.test',
            'username' => 'regcustomer',
            'is_admin' => false,
            'role' => 'user',
            'balance' => 50000.00,
            'currency_code' => 'NGN',
        ]);

        $this->boostProvider = Provider::firstOrCreate(
            ['name' => 'Momopanel'],
            [
                'api_url'            => 'https://momopanel.com/api/v2',
                'api_key'            => 'secret_key_boost_xyz',
                'currency'           => 'USD',
                'pricing_mode'       => 'percentage',
                'percentage_markup'  => 30.0,
                'fixed_markup_per_k' => 0.0,
                'min_selling_price'  => null,
                'min_profit'         => null,
                'rounding_rule'      => '2_decimal',
                'is_active'          => true,
            ]
        );
    }

    /**
     * Test 1: BOOST's ACTIVE FIX RATE can be changed and saved independently.
     */
    public function test_boost_active_fix_rate_can_be_saved_independently(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('hq.providers.pricing.update'), [
            'provider_id'        => $this->boostProvider->id,
            'fix_rate'           => 1750.0,
            'currency'           => 'USD',
            'pricing_mode'       => 'percentage',
            'percentage_markup'  => 30.0,
            'fixed_markup_per_k' => 0.0,
            'rounding_rule'      => '2_decimal',
        ]);

        $response->assertRedirect(route('hq.providers.pricing', ['provider_id' => $this->boostProvider->id]));
        $response->assertSessionHas('success');

        // Verify BOOST rate stored in dedicated keys
        $this->assertEquals('1750', Setting::get('pricing_boost_fix_rate'));
        $this->assertEquals('1750', Setting::get('boost_fix_rate'));

        $smmPricing = app(SmmPricingService::class);
        $this->assertEquals(1750.0, $smmPricing->getExchangeRate());
    }

    /**
     * Test 2: The saved BOOST value persists after refreshing the page.
     */
    public function test_saved_boost_fix_rate_persists_on_page_reload(): void
    {
        $this->actingAs($this->admin);

        // Set BOOST-isolated rate to 1820.0
        Setting::set('pricing_boost_fix_rate', '1820');
        Setting::set('boost_fix_rate', '1820');
        Setting::clearCache();

        $response = $this->get(route('hq.providers.pricing'));
        $response->assertStatus(200);

        // Verify the rendered page contains the saved isolated fix rate
        $response->assertSee('value="1820"', false);
        $response->assertSee('1,820.00');
        $response->assertSee('BOOST Isolated');
    }

    /**
     * Test 3: Changing BOOST's value does not change other product categories' fixed rates.
     */
    public function test_changing_boost_rate_does_not_affect_vtu_logs_or_virtual_numbers(): void
    {
        $this->actingAs($this->admin);

        $pricingService = app(ProductPricingService::class);

        // Pre-configure distinct rates for VTU, Logs, and Virtual Numbers
        $pricingService->updatePricingSettings(ProductPricingService::PRODUCT_VTU, [
            'fix_rate'          => 1400.0,
            'currency'          => 'NGN',
            'pricing_mode'      => 'percentage',
            'percentage_markup' => 10.0,
            'fixed_markup'      => 0.0,
            'rounding_rule'     => '2_decimal',
        ]);

        $pricingService->updatePricingSettings(ProductPricingService::PRODUCT_LOGS, [
            'fix_rate'          => 1450.0,
            'currency'          => 'NGN',
            'pricing_mode'      => 'percentage',
            'percentage_markup' => 20.0,
            'fixed_markup'      => 0.0,
            'rounding_rule'     => '2_decimal',
        ]);

        $pricingService->updatePricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS, [
            'fix_rate'          => 1600.0,
            'currency'          => 'USD',
            'pricing_mode'      => 'fixed',
            'percentage_markup' => 0.0,
            'fixed_markup'      => 1000.0,
            'rounding_rule'     => '2_decimal',
        ]);

        // Record initial state
        $initialVtuRate = $pricingService->getPricingSettings(ProductPricingService::PRODUCT_VTU)->getFixRate();
        $initialLogsRate = $pricingService->getPricingSettings(ProductPricingService::PRODUCT_LOGS)->getFixRate();
        $initialVnRate = $pricingService->getPricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS)->getFixRate();
        $initialGlobalRate = app(CurrencyConversionService::class)->getUsdToNgnRate();

        // Update BOOST's fix rate to 1950.0
        $this->post(route('hq.providers.pricing.update'), [
            'provider_id'        => $this->boostProvider->id,
            'fix_rate'           => 1950.0,
            'currency'           => 'USD',
            'pricing_mode'       => 'percentage',
            'percentage_markup'  => 35.0,
            'fixed_markup_per_k' => 0.0,
            'rounding_rule'      => '2_decimal',
        ]);

        // Verify other products remain completely unchanged
        $this->assertEquals($initialVtuRate, $pricingService->getPricingSettings(ProductPricingService::PRODUCT_VTU)->getFixRate());
        $this->assertEquals($initialLogsRate, $pricingService->getPricingSettings(ProductPricingService::PRODUCT_LOGS)->getFixRate());
        $this->assertEquals($initialVnRate, $pricingService->getPricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS)->getFixRate());
        $this->assertEquals($initialGlobalRate, app(CurrencyConversionService::class)->getUsdToNgnRate());

        // Verify BOOST has updated
        $this->assertEquals(1950.0, app(SmmPricingService::class)->getExchangeRate());
    }

    /**
     * Test 4: Changing another product's fixed rate does not change BOOST's value.
     */
    public function test_changing_other_products_does_not_affect_boost(): void
    {
        $this->actingAs($this->admin);

        // Pre-configure BOOST isolated rate to 1650.0
        Setting::set('pricing_boost_fix_rate', '1650');
        Setting::set('boost_fix_rate', '1650');
        Setting::clearCache();

        $pricingService = app(ProductPricingService::class);
        $smmService = app(SmmPricingService::class);

        $this->assertEquals(1650.0, $smmService->getExchangeRate());

        // Change VTU fix rate to 2000.0
        $this->post(route('hq.vtu.pricing.update'), [
            'fix_rate'          => 2000.0,
            'currency'          => 'NGN',
            'pricing_mode'      => 'percentage',
            'percentage_markup' => 15.0,
            'fixed_markup'      => 0.0,
            'rounding_rule'     => '2_decimal',
        ]);

        // BOOST rate must still be 1650.0
        $this->assertEquals(1650.0, $smmService->getExchangeRate());

        // Change Virtual Numbers fix rate to 2200.0
        $this->post(route('hq.virtual-numbers.pricing.update'), [
            'fix_rate'          => 2200.0,
            'currency'          => 'USD',
            'pricing_mode'      => 'fixed',
            'percentage_markup' => 0.0,
            'fixed_markup'      => 1200.0,
            'rounding_rule'     => '2_decimal',
        ]);

        // BOOST rate must still be 1650.0
        $this->assertEquals(1650.0, $smmService->getExchangeRate());

        // Change Logs Marketplace fix rate to 1800.0
        $this->post(route('hq.logs.pricing.update'), [
            'fix_rate'          => 1800.0,
            'currency'          => 'NGN',
            'pricing_mode'      => 'percentage',
            'percentage_markup' => 25.0,
            'fixed_markup'      => 0.0,
            'rounding_rule'     => '2_decimal',
        ]);

        // BOOST rate must still be 1650.0
        $this->assertEquals(1650.0, $smmService->getExchangeRate());
    }

    /**
     * Test 5: BOOST pricing calculations use the BOOST-specific setting.
     */
    public function test_boost_pricing_calculations_use_isolated_boost_setting(): void
    {
        // Configure BOOST rate to 1600.0 (while global rate is 1500.0)
        Setting::set('usd_to_ngn_rate', '1500');
        Setting::set('pricing_boost_fix_rate', '1600');
        Setting::set('boost_fix_rate', '1600');
        Setting::clearCache();

        $smmPricing = app(SmmPricingService::class);

        // At 1600 rate: $1.00 USD cost should be ₦1,600.00 NGN (not ₦1,500.00)
        $costNgn = $smmPricing->convertProviderRateToNgn(1.00, 'USD');
        $this->assertEquals(1600.00, $costNgn);

        // Selling price with 30% markup on $1.00 @ 1600 = 1600 * 1.30 = 2080.00
        $this->boostProvider->percentage_markup = 30.0;
        $this->boostProvider->pricing_mode = 'percentage';
        $this->boostProvider->rounding_rule = '2_decimal';

        $calc = $smmPricing->calculateSellingPrice(1.00, $this->boostProvider);
        $this->assertEquals(1600.00, $calc['cost_ngn']);
        $this->assertEquals(2080.00, $calc['selling_price']);
        $this->assertEquals(480.00, $calc['profit_ngn']);
        $this->assertEquals(1600.0, $calc['exchange_rate']);
    }

    /**
     * Test 6: Fallback safely preserves current rate when isolated rate is not yet set.
     */
    public function test_fallback_safely_preserves_rate_when_unconfigured(): void
    {
        // Unset any isolated rate
        Setting::set('pricing_boost_fix_rate', null);
        Setting::set('boost_fix_rate', null);
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('product_pricing_settings')) {
                ProductPricingSetting::where('product_key', 'boost')->delete();
            }
        } catch (\Throwable $e) {}
        Setting::clearCache();

        Setting::set('usd_to_ngn_rate', '1525');

        $smmPricing = app(SmmPricingService::class);
        $this->assertEquals(1525.0, $smmPricing->getExchangeRate());
    }
}
