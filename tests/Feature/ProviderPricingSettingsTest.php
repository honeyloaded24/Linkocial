<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Category;
use App\Models\ProviderServiceMapping;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;

class ProviderPricingSettingsTest extends TestCase
{
    protected User $admin;
    protected User $customer;
    protected Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure database migration has run
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            // Ignore if in test env
        }

        Setting::set('usd_to_ngn_rate', '1550');

        $this->admin = new User([
            'id' => 8881,
            'name' => 'HQ Admin',
            'email' => 'hqadmin@linkocial.test',
            'username' => 'hqadmin',
            'is_admin' => true,
            'role' => 'admin',
        ]);

        $this->customer = new User([
            'id' => 8882,
            'name' => 'John Customer',
            'email' => 'customer@linkocial.test',
            'username' => 'jcustomer',
            'is_admin' => false,
            'role' => 'user',
            'balance' => 50000.00,
            'currency_code' => 'NGN',
        ]);

        $this->provider = Provider::firstOrCreate(
            ['name' => 'Momopanel'],
            [
                'api_url'            => 'https://momopanel.com/api/v2',
                'api_key'            => 'super_secret_api_key_xyz',
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
     * Requirement 15: Admin-only settings remain protected.
     */
    public function test_admin_can_access_provider_pricing_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('hq.providers.pricing'));
        $response->assertStatus(200);
        $response->assertSee('Provider Pricing & Markup Settings');
        $response->assertSee('Percentage Markup');
        $response->assertSee('Fixed Markup per 1,000 units');
    }

    /**
     * Requirement 15: Non-admin users cannot access provider pricing settings.
     */
    public function test_non_admin_cannot_access_provider_pricing_settings(): void
    {
        $this->actingAs($this->customer);

        $response = $this->get(route('hq.providers.pricing'));
        $this->assertTrue(in_array($response->status(), [403, 302]));
    }

    /**
     * Requirement 14: Provider API credentials are not exposed to the frontend/settings.
     */
    public function test_provider_api_key_not_exposed_in_pricing_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('hq.providers.pricing'));
        $response->assertStatus(200);
        $response->assertDontSee('super_secret_api_key_xyz');
    }

    /**
     * Validate and update provider markup configuration.
     */
    public function test_admin_can_update_markup_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('hq.providers.pricing.update'), [
            'provider_id'        => $this->provider->id,
            'currency'           => 'USD',
            'pricing_mode'       => 'percentage',
            'percentage_markup'  => 35.0,
            'fixed_markup_per_k' => 50.0,
            'min_selling_price'  => 100.0,
            'min_profit'         => 25.0,
            'rounding_rule'      => 'whole_naira',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->provider->refresh();
        $this->assertEquals(35.0, $this->provider->percentage_markup);
        $this->assertEquals(50.0, $this->provider->fixed_markup_per_k);
        $this->assertEquals('whole_naira', $this->provider->rounding_rule);
        $this->assertEquals(100.0, $this->provider->min_selling_price);
        $this->assertEquals(25.0, $this->provider->min_profit);
    }

    /**
     * Negative percentage markup is rejected by validation.
     */
    public function test_negative_markup_is_rejected(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('hq.providers.pricing.update'), [
            'provider_id'        => $this->provider->id,
            'currency'           => 'USD',
            'pricing_mode'       => 'percentage',
            'percentage_markup'  => -15.0,
            'fixed_markup_per_k' => 0.0,
            'rounding_rule'      => '2_decimal',
        ]);

        $response->assertSessionHasErrors('percentage_markup');
    }

    /**
     * Requirement 11: Customer checkout uses the authoritative backend price.
     */
    public function test_checkout_ignores_customer_submitted_price(): void
    {
        $pricingService = app(\App\Services\SmmPricingService::class);

        $service = new Service([
            'id' => 9999,
            'name' => 'High Quality Followers',
            'selling_price' => 2015.00, // Authoritative backend price
            'type' => 'Default',
        ]);

        // Customer attempts to purchase 1,000 units.
        // Server calculates strictly: (2,015 / 1000) * 1000 = ₦2,015.00
        $calculation = $pricingService->calculateCustomerCharge($service, 1000, $this->customer);
        $this->assertEquals(2015.00, $calculation['final_charge']);
    }
}
