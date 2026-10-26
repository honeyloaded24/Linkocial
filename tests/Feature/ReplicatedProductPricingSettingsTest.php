<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Provider;
use App\Models\Setting;
use App\Services\ProductPricingService;
use Illuminate\Support\Facades\Artisan;

class ReplicatedProductPricingSettingsTest extends TestCase
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
            // Ignore if in test env
        }

        Setting::set('usd_to_ngn_rate', '1500');

        $this->admin = new User([
            'id' => 9881,
            'name' => 'HQ Master Admin',
            'email' => 'hqmaster@linkocial.test',
            'username' => 'hqmaster',
            'is_admin' => true,
            'role' => 'admin',
        ]);

        $this->customer = new User([
            'id' => 9882,
            'name' => 'John Regular User',
            'email' => 'johnregular@linkocial.test',
            'username' => 'johnreg',
            'is_admin' => false,
            'role' => 'user',
            'balance' => 50000.00,
            'currency_code' => 'NGN',
        ]);

        $this->boostProvider = Provider::firstOrCreate(
            ['name' => 'Momopanel'],
            [
                'api_url'            => 'https://momopanel.com/api/v2',
                'api_key'            => 'test_key_boost',
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
     * Requirement: All five settings appear in the Pricing & Markup section for VTU.
     */
    public function test_vtu_pricing_page_displays_all_five_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('hq.vtu.pricing'));
        $response->assertStatus(200);
        $response->assertSee('VTU Pricing & Markup Settings');
        $response->assertSee('Active Fix Rate');
        $response->assertSee('Provider Currency');
        $response->assertSee('Pricing Mode');
        $response->assertSee('Percentage Markup');
        $response->assertSee('Fixed Markup');
        $response->assertSee('Rounding Policy');
    }

    /**
     * Requirement: All five settings appear in the Pricing & Markup section for Logs Marketplace.
     */
    public function test_logs_pricing_page_displays_all_five_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('hq.logs.pricing'));
        $response->assertStatus(200);
        $response->assertSee('Logs Marketplace Pricing & Markup Settings');
        $response->assertSee('Active Fix Rate');
        $response->assertSee('Provider Currency');
        $response->assertSee('Pricing Mode');
        $response->assertSee('Percentage Markup');
        $response->assertSee('Fixed Markup');
        $response->assertSee('Rounding Policy');
    }

    /**
     * Requirement: All five settings appear in the Pricing & Markup section for Virtual Numbers.
     */
    public function test_virtual_numbers_pricing_page_displays_all_five_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('hq.virtual-numbers.pricing'));
        $response->assertStatus(200);
        $response->assertSee('Virtual Numbers Pricing & Markup Settings');
        $response->assertSee('Active Fix Rate');
        $response->assertSee('Provider Currency');
        $response->assertSee('Pricing Mode');
        $response->assertSee('Percentage Markup');
        $response->assertSee('Fixed Markup');
        $response->assertSee('Rounding Policy');
    }

    /**
     * Requirement: Non-admin users cannot access any product pricing settings.
     */
    public function test_non_admin_cannot_access_any_product_pricing_settings(): void
    {
        $this->actingAs($this->customer);

        $responseVtu = $this->get(route('hq.vtu.pricing'));
        $this->assertTrue(in_array($responseVtu->status(), [403, 302]));

        $responseLogs = $this->get(route('hq.logs.pricing'));
        $this->assertTrue(in_array($responseLogs->status(), [403, 302]));

        $responseVn = $this->get(route('hq.virtual-numbers.pricing'));
        $this->assertTrue(in_array($responseVn->status(), [403, 302]));
    }

    /**
     * Requirement: Admin can save VTU pricing settings.
     */
    public function test_admin_can_update_vtu_pricing_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('hq.vtu.pricing.update'), [
            'fix_rate'           => 1650.0,
            'currency'           => 'NGN',
            'pricing_mode'       => 'percentage',
            'percentage_markup'  => 8.5,
            'fixed_markup'       => 20.0,
            'min_selling_price'  => 100.0,
            'min_profit'         => 15.0,
            'rounding_rule'      => 'whole_naira',
        ]);

        $response->assertRedirect(route('hq.vtu.pricing'));
        $response->assertSessionHas('success');

        $pricingService = app(ProductPricingService::class);
        $settings = $pricingService->getPricingSettings(ProductPricingService::PRODUCT_VTU);

        $this->assertEquals(1650.0, $settings->getFixRate());
        $this->assertEquals('NGN', $settings->getCurrency());
        $this->assertEquals('percentage', $settings->getPricingMode());
        $this->assertEquals(8.5, $settings->getPercentageMarkup());
        $this->assertEquals(20.0, $settings->getFixedMarkup());
        $this->assertEquals('whole_naira', $settings->getRoundingRule());
    }

    /**
     * Requirement: Admin can save Logs Marketplace pricing settings.
     */
    public function test_admin_can_update_logs_pricing_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('hq.logs.pricing.update'), [
            'fix_rate'           => 1580.0,
            'currency'           => 'USD',
            'pricing_mode'       => 'combined',
            'percentage_markup'  => 25.0,
            'fixed_markup'       => 100.0,
            'min_selling_price'  => 500.0,
            'min_profit'         => 50.0,
            'rounding_rule'      => 'nearest_10',
        ]);

        $response->assertRedirect(route('hq.logs.pricing'));
        $response->assertSessionHas('success');

        $pricingService = app(ProductPricingService::class);
        $settings = $pricingService->getPricingSettings(ProductPricingService::PRODUCT_LOGS);

        $this->assertEquals(1580.0, $settings->getFixRate());
        $this->assertEquals('USD', $settings->getCurrency());
        $this->assertEquals('combined', $settings->getPricingMode());
        $this->assertEquals(25.0, $settings->getPercentageMarkup());
        $this->assertEquals(100.0, $settings->getFixedMarkup());
        $this->assertEquals('nearest_10', $settings->getRoundingRule());
    }

    /**
     * Requirement: Admin can save Virtual Numbers pricing settings.
     */
    public function test_admin_can_update_virtual_numbers_pricing_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('hq.virtual-numbers.pricing.update'), [
            'fix_rate'           => 1700.0,
            'currency'           => 'USD',
            'pricing_mode'       => 'fixed',
            'percentage_markup'  => 0.0,
            'fixed_markup'       => 1600.0,
            'min_selling_price'  => 800.0,
            'min_profit'         => 300.0,
            'rounding_rule'      => 'whole_naira',
        ]);

        $response->assertRedirect(route('hq.virtual-numbers.pricing'));
        $response->assertSessionHas('success');

        $pricingService = app(ProductPricingService::class);
        $settings = $pricingService->getPricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS);

        $this->assertEquals(1700.0, $settings->getFixRate());
        $this->assertEquals('USD', $settings->getCurrency());
        $this->assertEquals('fixed', $settings->getPricingMode());
        $this->assertEquals(1600.0, $settings->getFixedMarkup());
        $this->assertEquals('whole_naira', $settings->getRoundingRule());
    }

    /**
     * Requirement: Independence: Changing VTU does not change Logs, Virtual Numbers, or BOOST.
     */
    public function test_changing_vtu_does_not_affect_other_products(): void
    {
        $this->actingAs($this->admin);

        $pricingService = app(ProductPricingService::class);

        // Record initial states
        $initialLogs = $pricingService->getPricingSettings(ProductPricingService::PRODUCT_LOGS);
        $initialVn = $pricingService->getPricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS);
        $initialBoostMarkup = $this->boostProvider->percentage_markup;

        // Change VTU
        $this->post(route('hq.vtu.pricing.update'), [
            'fix_rate'           => 1850.0,
            'currency'           => 'USD',
            'pricing_mode'       => 'combined',
            'percentage_markup'  => 44.0,
            'fixed_markup'       => 75.0,
            'rounding_rule'      => 'nearest_10',
        ]);

        // Verify other products unchanged
        $afterLogs = $pricingService->getPricingSettings(ProductPricingService::PRODUCT_LOGS);
        $afterVn = $pricingService->getPricingSettings(ProductPricingService::PRODUCT_VIRTUAL_NUMBERS);
        $this->boostProvider->refresh();

        $this->assertEquals($initialLogs->getPercentageMarkup(), $afterLogs->getPercentageMarkup());
        $this->assertEquals($initialLogs->getFixRate(), $afterLogs->getFixRate());
        $this->assertEquals($initialVn->getFixedMarkup(), $afterVn->getFixedMarkup());
        $this->assertEquals($initialBoostMarkup, $this->boostProvider->percentage_markup);
    }

    /**
     * Requirement: Negative percentage markup is rejected.
     */
    public function test_negative_markup_is_rejected_across_all_products(): void
    {
        $this->actingAs($this->admin);

        $responseVtu = $this->post(route('hq.vtu.pricing.update'), [
            'fix_rate'           => 1500.0,
            'currency'           => 'NGN',
            'pricing_mode'       => 'percentage',
            'percentage_markup'  => -10.0,
            'fixed_markup'       => 0.0,
            'rounding_rule'      => '2_decimal',
        ]);
        $responseVtu->assertSessionHasErrors('percentage_markup');

        $responseLogs = $this->post(route('hq.logs.pricing.update'), [
            'fix_rate'           => 1500.0,
            'currency'           => 'NGN',
            'pricing_mode'       => 'percentage',
            'percentage_markup'  => -5.0,
            'fixed_markup'       => 0.0,
            'rounding_rule'      => '2_decimal',
        ]);
        $responseLogs->assertSessionHasErrors('percentage_markup');

        $responseVn = $this->post(route('hq.virtual-numbers.pricing.update'), [
            'fix_rate'           => 1500.0,
            'currency'           => 'USD',
            'pricing_mode'       => 'fixed',
            'percentage_markup'  => -20.0,
            'fixed_markup'       => 0.0,
            'rounding_rule'      => '2_decimal',
        ]);
        $responseVn->assertSessionHasErrors('percentage_markup');
    }
}
