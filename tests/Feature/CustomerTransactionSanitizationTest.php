<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTransactionSanitizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }
    /**
     * Test virtual number purchase description strips provider names, wholesale USD costs,
     * exchange rates, and internal markup metadata.
     */
    public function test_virtual_number_purchase_description_sanitizes_internal_provider_and_pricing_details(): void
    {
        $raw = "Virtual Number Purchase (WhatsApp - United States [Server 1]) [Rate: $1.13 @ ₦1,350.00/$, Markup: ₦1,000.00]";
        $tx = new Transaction(['description' => $raw]);

        $this->assertEquals(
            'Virtual Number Purchase (WhatsApp - United States)',
            $tx->customer_description
        );

        $this->assertStringNotContainsString('Server 1', $tx->customer_description);
        $this->assertStringNotContainsString('$1.13', $tx->customer_description);
        $this->assertStringNotContainsString('1,350.00', $tx->customer_description);
        $this->assertStringNotContainsString('Markup', $tx->customer_description);
        $this->assertStringNotContainsString('Rate', $tx->customer_description);
    }

    /**
     * Test virtual number description with Server 2 (Grizzly) is sanitized correctly.
     */
    public function test_virtual_number_server_2_grizzly_sanitizes_provider_details(): void
    {
        $raw = "Virtual Number Purchase (Telegram - Netherlands [Server 2 (Grizzly)]) [Rate: $0.45 @ ₦1,350.00/$, Markup: ₦1,000.00]";
        $tx = new Transaction(['description' => $raw]);

        $this->assertEquals(
            'Virtual Number Purchase (Telegram - Netherlands)',
            $tx->customer_description
        );

        $this->assertStringNotContainsString('Server 2', $tx->customer_description);
        $this->assertStringNotContainsString('Grizzly', $tx->customer_description);
        $this->assertStringNotContainsString('$0.45', $tx->customer_description);
        $this->assertStringNotContainsString('1,350.00', $tx->customer_description);
        $this->assertStringNotContainsString('Markup', $tx->customer_description);
    }

    /**
     * Test unclosed bracket variant from user prompt example is safely handled.
     */
    public function test_unclosed_rate_bracket_formatting_sanitizes_safely(): void
    {
        $raw = "Virtual Number Purchase (WhatsApp - United States [Server 1] [Rate: $1.13 @ ₦1,350.00/$, Markup: ₦1,000.00]";
        $tx = new Transaction(['description' => $raw]);

        $this->assertEquals(
            'Virtual Number Purchase (WhatsApp - United States)',
            $tx->customer_description
        );

        $this->assertStringNotContainsString('Server 1', $tx->customer_description);
        $this->assertStringNotContainsString('$1.13', $tx->customer_description);
        $this->assertStringNotContainsString('Rate', $tx->customer_description);
    }

    /**
     * Test refund descriptions remain clear and customer-friendly.
     */
    public function test_refund_descriptions_remain_clear_and_customer_friendly(): void
    {
        // Standard clean refund
        $cleanRefund = new Transaction([
            'description' => 'Refund for Virtual Number Purchase (WhatsApp - United States)'
        ]);
        $this->assertEquals(
            'Refund for Virtual Number Purchase (WhatsApp - United States)',
            $cleanRefund->customer_description
        );

        // Historical refund that contained leaked provider / rate info
        $leakedRefund = new Transaction([
            'description' => 'Refund for Virtual Number Purchase (WhatsApp - United States [Server 1] [Rate: $1.13 @ ₦1,350.00/$, Markup: ₦1,000.00])'
        ]);
        $this->assertEquals(
            'Refund for Virtual Number Purchase (WhatsApp - United States)',
            $leakedRefund->customer_description
        );

        // Admin manual refund description
        $adminRefund = new Transaction([
            'description' => 'Admin manual refund for Virtual Number #123 (WhatsApp)'
        ]);
        $this->assertEquals(
            'Admin manual refund for Virtual Number #123 (WhatsApp)',
            $adminRefund->customer_description
        );
    }

    /**
     * Test other transaction types across Linkocial (Boost, VTU, Logs, Payments) remain untouched.
     */
    public function test_other_transaction_descriptions_remain_untouched(): void
    {
        $descriptions = [
            'Order placement for Instagram Followers (10% Tier Discount applied)',
            'Deposit via Paystack (₦5,000.00 / Ref: PAY-12345)',
            '₦1,000.00 Airtime top-up for 08012345678',
            'Refund for failed ₦1,000.00 Airtime top-up',
            'Purchased 2x Netflix Account (₦3,000.00)',
            'Refund for failed log purchase: Netflix Account (₦3,000.00)',
            'Admin adjustment (Added)',
            'Promo Bonus (+10%) for deposit',
        ];

        foreach ($descriptions as $desc) {
            $tx = new Transaction(['description' => $desc]);
            $this->assertEquals($desc, $tx->customer_description);
        }
    }

    /**
     * Test that raw internal database record is preserved for admin auditing,
     * while customer_description sanitizes for customer presentation.
     */
    public function test_internal_admin_visibility_preserves_raw_description_on_database_model(): void
    {
        $raw = "Virtual Number Purchase (WhatsApp - United States [Server 1]) [Rate: $1.13 @ ₦1,350.00/$, Markup: ₦1,000.00]";
        $tx = new Transaction(['description' => $raw]);

        // Admin visibility preserved on raw description
        $this->assertEquals($raw, $tx->description);
        $this->assertStringContainsString('Server 1', $tx->description);
        $this->assertStringContainsString('$1.13', $tx->description);
        $this->assertStringContainsString('₦1,350.00/$', $tx->description);
        $this->assertStringContainsString('Markup: ₦1,000.00', $tx->description);

        // Customer visibility sanitized
        $this->assertEquals('Virtual Number Purchase (WhatsApp - United States)', $tx->customer_description);
    }

    /**
     * Test null and empty descriptions are handled gracefully.
     */
    public function test_null_and_empty_descriptions_handled_safely(): void
    {
        $txNull = new Transaction(['description' => null]);
        $this->assertNull($txNull->customer_description);

        $txEmpty = new Transaction(['description' => '']);
        $this->assertSame('', $txEmpty->customer_description);
    }

    /**
     * Test customer wallet history view outputs customer_description and does not expose provider details.
     */
    public function test_wallet_history_view_renders_sanitized_customer_description(): void
    {
        $user = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'balance' => 5000.0,
            'currency_code' => 'NGN',
        ]);
        $user->id = 1;

        $tx = new Transaction([
            'user_id' => 1,
            'amount' => 2431.0,
            'type' => 'debit',
            'transaction_type' => 'virtual_number',
            'balance_before' => 5000.0,
            'balance_after' => 2569.0,
            'description' => "Virtual Number Purchase (WhatsApp - United States [Server 1]) [Rate: $1.13 @ ₦1,350.00/$, Markup: ₦1,000.00]",
            'reference' => 'VN-TEST-123',
            'created_at' => now(),
        ]);
        $tx->id = 1;
        $tx->created_at = now();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator([$tx], 1, 15, 1);

        $view = $this->actingAs($user)->view('wallet.history', ['transactions' => $paginator]);

        $view->assertSee('Virtual Number Purchase (WhatsApp - United States)');
        $view->assertSee('VN-TEST-123');
        $view->assertDontSee('Server 1');
        $view->assertDontSee('$1.13');
        $view->assertDontSee('1,350.00');
        $view->assertDontSee('Markup:');
    }

    /**
     * Test customer dashboard recent activities table outputs customer_description and does not expose provider details.
     */
    public function test_dashboard_activity_renders_sanitized_customer_description(): void
    {
        $user = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'balance' => 5000.0,
            'currency_code' => 'NGN',
        ]);
        $user->id = 1;

        $tx = new Transaction([
            'user_id' => 1,
            'amount' => 2431.0,
            'type' => 'debit',
            'transaction_type' => 'virtual_number',
            'balance_before' => 5000.0,
            'balance_after' => 2569.0,
            'description' => "Virtual Number Purchase (WhatsApp - United States [Server 1]) [Rate: $1.13 @ ₦1,350.00/$, Markup: ₦1,000.00]",
            'reference' => 'VN-DASH-123',
            'created_at' => now(),
        ]);
        $tx->id = 1;
        $tx->created_at = now();

        $view = $this->actingAs($user)->view('dashboard.index', [
            'recentActivities' => collect([$tx]),
            'totalSpent' => 2431.0,
            'totalDeposits' => 5000.0,
            'activeOrders' => 0,
            'completedOrders' => 0,
            'growth' => 0,
            'channels' => [],
            'sliders' => collect([]),
            'exchangeRate' => 1500.0,
            'usdBalance' => 3.33,
        ]);

        $view->assertSee('Virtual Number Purchase (WhatsApp - United States)');
        $view->assertSee('VN-DASH-123');
        $view->assertDontSee('Server 1');
        $view->assertDontSee('$1.13');
        $view->assertDontSee('1,350.00');
        $view->assertDontSee('Markup:');
    }
}
