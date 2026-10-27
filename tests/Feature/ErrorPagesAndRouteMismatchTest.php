<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

class ErrorPagesAndRouteMismatchTest extends TestCase
{
    /**
     * Test that all error pages render successfully without runtime or syntax errors.
     */
    public function test_500_error_page_renders_successfully(): void
    {
        $view = view('errors.500', [
            'exception' => new \Exception('Test server exception')
        ])->render();

        $this->assertStringContainsString('500', $view);
        $this->assertStringContainsString('Server Encountered an Error', $view);
        $this->assertStringNotContainsString('</button></a>', $view);
    }

    public function test_404_error_page_renders_successfully(): void
    {
        $view = view('errors.404')->render();

        $this->assertStringContainsString('404', $view);
        $this->assertStringContainsString('Page Not Found', $view);
    }

    public function test_403_error_page_renders_successfully(): void
    {
        $view = view('errors.403')->render();

        $this->assertStringContainsString('403', $view);
        $this->assertStringContainsString('Access Restricted', $view);
    }

    public function test_419_error_page_renders_successfully(): void
    {
        $view = view('errors.419')->render();

        $this->assertStringContainsString('419', $view);
        $this->assertStringContainsString('Session or Security Token Expired', $view);
    }

    public function test_503_error_page_renders_successfully(): void
    {
        $view = view('errors.503')->render();

        $this->assertStringContainsString('503', $view);
        $this->assertStringContainsString('Maintenance', $view);
    }

    /**
     * Test all previously mismatched route names resolve without RouteNotFoundException.
     */
    public function test_route_mismatches_are_resolved(): void
    {
        // hq.logs and hq.activity-logs
        $this->assertNotEmpty(route('hq.logs'));
        $this->assertNotEmpty(route('hq.activity-logs'));

        // virtual-numbers.index and services.virtual-numbers
        $this->assertNotEmpty(route('virtual-numbers.index'));
        $this->assertNotEmpty(route('services.virtual-numbers'));

        // support redirect route
        $this->assertNotEmpty(route('support'));
    }

    /**
     * Test Setting::get gracefully returns default without throwing on DB errors.
     */
    public function test_setting_get_handles_exceptions_gracefully(): void
    {
        Setting::clearCache();

        $value = Setting::get('non_existent_random_key_xyz', 'fallback_value');
        $this->assertEquals('fallback_value', $value);
    }

    /**
     * Test ProductPricingService handles non-positive fix_rate and negative cost gracefully without throwing 500 exceptions.
     */
    public function test_product_pricing_service_resilient_to_invalid_inputs(): void
    {
        $service = app(\App\Services\ProductPricingService::class);

        // When fix_rate is 0 or negative, it must fall back to platform rate and not throw InvalidArgumentException
        $converted = $service->convertProviderCostToNgn(1.50, 'USD', 0.0);
        $this->assertGreaterThan(0, $converted);

        // Negative rawCost should clamp to 0 without throwing
        $convertedNegative = $service->convertProviderCostToNgn(-10.0, 'USD', 1500.0);
        $this->assertEquals(0.0, $convertedNegative);
    }

    /**
     * Test ProductPricingSetting model getFixRate returns default when stored fix_rate is 0 or null.
     */
    public function test_product_pricing_setting_model_fix_rate_fallback(): void
    {
        $settingZero = new \App\Models\ProductPricingSetting(['fix_rate' => 0]);
        $this->assertEquals(1500.0, $settingZero->getFixRate());

        $settingNull = new \App\Models\ProductPricingSetting(['fix_rate' => null]);
        $this->assertEquals(1500.0, $settingNull->getFixRate());
    }

    /**
     * Test HQ user transactions page view renders with empty collections without errors.
     */
    public function test_hq_user_transactions_view_renders_cleanly(): void
    {
        $mockUser = (object)[
            'id' => 999,
            'username' => 'testuser',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'balance' => 5000.00
        ];

        $view = view('hq.users.transactions', [
            'user' => $mockUser,
            'deposits' => collect([]),
            'transactions' => collect([]),
            'orders' => collect([])
        ])->render();

        $this->assertStringContainsString('Activity History: testuser', $view);
        $this->assertStringContainsString('No deposits found for this user', $view);
        $this->assertStringContainsString('No ledger transactions recorded yet', $view);
        $this->assertStringContainsString('No orders found for this user', $view);
    }
}
