<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Service;
use App\Models\Provider;
use App\Models\VirtualNumberOrder;
use App\Models\VtuOrder;
use App\Models\LogOrder;
use App\Models\LogProduct;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\CurrencyConversionService;
use App\Services\FinancialMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FinancialMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialMetricsService $service;
    protected CurrencyConversionService $currencyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currencyService = new CurrencyConversionService();
        $this->service = new FinancialMetricsService($this->currencyService);

        // Configure standard test exchange rate: 1 USD = ₦1,500 NGN
        Setting::set('usd_to_ngn_rate', '1500');

        Provider::firstOrCreate(
            ['id' => 1],
            [
                'name'      => 'Momopanel',
                'api_url'   => 'https://momopanel.com/api/v2',
                'api_key'   => 'test_api_key',
                'is_active' => true,
            ]
        );

        Service::firstOrCreate(
            ['id' => 1],
            [
                'name'          => 'Instagram Followers',
                'category'      => 'Instagram',
                'selling_price' => 5000.00,
                'is_active'     => true,
            ]
        );

        LogProduct::firstOrCreate(
            ['id' => 1],
            [
                'name'           => 'Facebook Aged 2018',
                'type'           => 'api',
                'base_api_price' => 300.00,
                'unit_price'     => 1600.00,
                'our_price'      => 1600.00,
                'is_active'      => true,
                'in_stock'       => 10,
            ]
        );
    }

    /**
     * Test completed purchases are included and pending/failed/canceled orders are excluded from customer Total Spent.
     */
    public function test_customer_total_spent_includes_only_completed_purchases(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // 1. SMM: Completed order (₦2,500) -> INCLUDED
        Order::create([
            'user_id'       => $userA->id,
            'service_id'    => 1,
            'link'          => 'https://instagram.com/test',
            'quantity'      => 1000,
            'charge'        => 2500.00,
            'provider_cost' => 1.00,
            'status'        => 'completed',
            'is_refunded'   => false,
        ]);

        // 2. SMM: Pending order (₦1,000) -> EXCLUDED
        Order::create([
            'user_id'       => $userA->id,
            'service_id'    => 1,
            'link'          => 'https://instagram.com/test',
            'quantity'      => 500,
            'charge'        => 1000.00,
            'provider_cost' => 0.40,
            'status'        => 'pending',
            'is_refunded'   => false,
        ]);

        // 3. SMM: Canceled order (₦800) -> EXCLUDED
        Order::create([
            'user_id'       => $userA->id,
            'service_id'    => 1,
            'link'          => 'https://instagram.com/test',
            'quantity'      => 400,
            'charge'        => 800.00,
            'provider_cost' => 0.30,
            'status'        => 'canceled',
            'is_refunded'   => true,
        ]);

        // 4. Virtual Number: Completed / received (₦1,800) -> INCLUDED
        VirtualNumberOrder::create([
            'user_id'       => $userA->id,
            'fivesim_id'    => 'vn_101',
            'phone_number'  => '+2348011112222',
            'country'       => 'Nigeria',
            'operator'      => 'SMSPool',
            'product'       => 'WhatsApp',
            'cost'          => 1800.00,
            'provider_cost' => 0.80,
            'status'        => 'received',
        ]);

        // 5. Virtual Number: Pending waiting for SMS (₦1,500) -> EXCLUDED
        VirtualNumberOrder::create([
            'user_id'       => $userA->id,
            'fivesim_id'    => 'vn_102',
            'phone_number'  => '+2348033334444',
            'country'       => 'Nigeria',
            'operator'      => 'SMSPool',
            'product'       => 'Telegram',
            'cost'          => 1500.00,
            'provider_cost' => 0.60,
            'status'        => 'pending',
        ]);

        // 6. VTU: Completed airtime (₦500) -> INCLUDED
        VtuOrder::create([
            'user_id'        => $userA->id,
            'service_type'   => 'airtime',
            'network'        => 'MTN',
            'phone_or_meter' => '08012345678',
            'cost'           => 500.00,
            'status'         => 'completed',
        ]);

        // 7. VTU: Refunded/failed airtime (₦1,000) -> EXCLUDED
        VtuOrder::create([
            'user_id'        => $userA->id,
            'service_type'   => 'airtime',
            'network'        => 'GLO',
            'phone_or_meter' => '08098765432',
            'cost'           => 1000.00,
            'status'         => 'refunded',
        ]);

        // 8. Logs: Completed purchase (₦3,200) -> INCLUDED
        LogOrder::create([
            'user_id'           => $userA->id,
            'log_product_id'    => 1,
            'api_product_key'   => 'fb_aged',
            'product_name'      => 'Facebook Aged 2018',
            'quantity'          => 2,
            'unit_cost'         => 1600.00,
            'total_cost'        => 3200.00,
            'external_order_id' => 'LOG-1001',
            'status'            => 'completed',
        ]);

        // 9. Other user's order (₦9,999) -> ISOLATED & EXCLUDED
        Order::create([
            'user_id'       => $userB->id,
            'service_id'    => 1,
            'link'          => 'https://instagram.com/userB',
            'quantity'      => 5000,
            'charge'        => 9999.00,
            'provider_cost' => 4.00,
            'status'        => 'completed',
            'is_refunded'   => false,
        ]);

        // Expected User A Spent = 2500 (SMM) + 1800 (VN) + 500 (VTU) + 3200 (Logs) = 8000.00
        $userASpent = $this->service->getUserTotalSpent($userA->id);
        $this->assertEquals(8000.00, $userASpent);

        // Expected User B Spent = 9999.00 (Isolated)
        $userBSpent = $this->service->getUserTotalSpent($userB->id);
        $this->assertEquals(9999.00, $userBSpent);
    }

    /**
     * Test SMM partial orders calculate delivered portion and correctly ignore refunded undelivered portion.
     */
    public function test_smm_partial_order_calculates_actual_fulfilled_spending(): void
    {
        $user = User::factory()->create();

        // Placed 1,000 units for ₦10,000. Delivered 750 units, 250 remained.
        // Refund of ₦2,500 was issued, is_refunded was marked true.
        // Real customer spending = ₦7,500.
        Order::create([
            'user_id'       => $user->id,
            'service_id'    => 1,
            'link'          => 'https://instagram.com/test',
            'quantity'      => 1000,
            'remains'       => 250,
            'charge'        => 10000.00,
            'provider_cost' => 4.00,
            'status'        => 'partial',
            'is_refunded'   => true,
        ]);

        $spent = $this->service->getUserTotalSpent($user->id);
        $this->assertEquals(7500.00, $spent);
    }

    /**
     * Test customer Total Deposits counts only qualifying successful payments.
     */
    public function test_customer_total_deposits_counts_only_success_payments(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Successful Paystack payment (₦5,000) -> INCLUDED
        Payment::create([
            'user_id'   => $userA->id,
            'amount'    => 5000.00,
            'currency'  => 'NGN',
            'reference' => 'PAY_001',
            'status'    => 'success',
        ]);

        // Successful Admin manual deposit (₦2,500) -> INCLUDED
        Payment::create([
            'user_id'   => $userA->id,
            'amount'    => 2500.00,
            'currency'  => 'NGN',
            'reference' => 'DEP_MANUAL_123',
            'status'    => 'success',
        ]);

        // Pending deposit attempt (₦10,000) -> EXCLUDED
        Payment::create([
            'user_id'   => $userA->id,
            'amount'    => 10000.00,
            'currency'  => 'NGN',
            'reference' => 'PAY_PENDING_001',
            'status'    => 'pending',
        ]);

        // Failed payment (₦3,000) -> EXCLUDED
        Payment::create([
            'user_id'   => $userA->id,
            'amount'    => 3000.00,
            'currency'  => 'NGN',
            'reference' => 'PAY_FAILED_001',
            'status'    => 'failed',
        ]);

        // User B payment (₦50,000) -> EXCLUDED from User A
        Payment::create([
            'user_id'   => $userB->id,
            'amount'    => 50000.00,
            'currency'  => 'NGN',
            'reference' => 'PAY_USER_B',
            'status'    => 'success',
        ]);

        $this->assertEquals(7500.00, $this->service->getUserTotalDeposits($userA->id));
        $this->assertEquals(50000.00, $this->service->getUserTotalDeposits($userB->id));
    }

    /**
     * Test Admin Financial Performance and Total Profit calculations.
     * Verifies that foreign provider wholesale costs in USD are converted to NGN at authoritative exchange rate.
     */
    public function test_admin_financial_performance_and_profit_with_currency_conversion(): void
    {
        // Set FX rate: 1 USD = ₦1,500 NGN
        Setting::set('usd_to_ngn_rate', '1500');

        $user = User::factory()->create();

        // 1. Deposits: ₦20,000
        Payment::create([
            'user_id'   => $user->id,
            'amount'    => 20000.00,
            'currency'  => 'NGN',
            'reference' => 'PAY_ADMIN_01',
            'status'    => 'success',
        ]);

        // 2. SMM Order: Customer charged ₦5,000. Momopanel provider cost = $2.00 USD.
        // Converted provider cost = $2.00 * 1,500 = ₦3,000. SMM profit = ₦2,000.
        Order::create([
            'user_id'       => $user->id,
            'service_id'    => 1,
            'link'          => 'https://tiktok.com/@test',
            'quantity'      => 1000,
            'charge'        => 5000.00,
            'provider_cost' => 2.00,
            'status'        => 'completed',
            'is_refunded'   => false,
        ]);

        // 3. Virtual Number Order: Customer charged ₦2,200. Wholesale cost = $0.80 USD.
        // Converted provider cost = $0.80 * 1,500 = ₦1,200. VN profit = ₦1,000.
        VirtualNumberOrder::create([
            'user_id'       => $user->id,
            'fivesim_id'    => 'vn_perf_01',
            'phone_number'  => '+12025550199',
            'country'       => 'USA',
            'operator'      => 'SMSPool',
            'product'       => 'OpenAI',
            'cost'          => 2200.00,
            'provider_cost' => 0.80,
            'status'        => 'received',
        ]);

        // 4. Pending Virtual Number Order: should NOT be counted in revenue or profit!
        VirtualNumberOrder::create([
            'user_id'       => $user->id,
            'fivesim_id'    => 'vn_perf_pending',
            'phone_number'  => '+12025550188',
            'country'       => 'USA',
            'operator'      => 'SMSPool',
            'product'       => 'OpenAI',
            'cost'          => 2200.00,
            'provider_cost' => 0.80,
            'status'        => 'pending',
        ]);

        $metrics = $this->service->getAdminFinancialPerformance();

        // Assertions
        $this->assertEquals(20000.00, $metrics['totalDeposited']);
        $this->assertEquals(7200.00, $metrics['totalSpent']); // 5000 SMM + 2200 VN (pending VN excluded)
        $this->assertEquals(4200.00, $metrics['totalCost']);  // 3000 SMM cost (USD converted) + 1200 VN cost (USD converted)
        $this->assertEquals(3000.00, $metrics['totalProfit']); // 7200 spent - 4200 cost = 3000 gross profit
        $this->assertEquals(1500.0, $metrics['exchangeRate']);
    }

    /**
     * Test SMM Product Summary returns accurate counts, USD-to-NGN converted costs, and profits.
     */
    public function test_smm_summary_metrics_and_status_counts(): void
    {
        Setting::set('usd_to_ngn_rate', '1500');
        $user = User::factory()->create();

        // Completed SMM order: charge 4,000 NGN, cost $1.00 USD -> ₦1,500 NGN cost
        Order::create([
            'user_id'       => $user->id,
            'service_id'    => 1,
            'link'          => 'https://instagram.com/boost',
            'quantity'      => 1000,
            'charge'        => 4000.00,
            'provider_cost' => 1.00,
            'status'        => 'completed',
            'is_refunded'   => false,
        ]);

        // Pending SMM order: charge 1,500 NGN
        Order::create([
            'user_id'       => $user->id,
            'service_id'    => 1,
            'link'          => 'https://instagram.com/boost',
            'quantity'      => 500,
            'charge'        => 1500.00,
            'provider_cost' => 0.40,
            'status'        => 'pending',
            'is_refunded'   => false,
        ]);

        // Partial SMM order: 50% fulfilled (remains 500 out of 1000)
        // charge 2,000 NGN -> ₦1,000 eligible revenue; provider cost $0.60 USD -> $0.30 eligible * 1,500 = ₦450 cost
        Order::create([
            'user_id'       => $user->id,
            'service_id'    => 1,
            'link'          => 'https://instagram.com/boost',
            'quantity'      => 1000,
            'remains'       => 500,
            'charge'        => 2000.00,
            'provider_cost' => 0.60,
            'status'        => 'partial',
            'is_refunded'   => false,
        ]);

        // Canceled/Failed SMM order
        Order::create([
            'user_id'       => $user->id,
            'service_id'    => 1,
            'link'          => 'https://instagram.com/boost',
            'quantity'      => 200,
            'charge'        => 800.00,
            'provider_cost' => 0.20,
            'status'        => 'canceled',
            'is_refunded'   => true,
        ]);

        $summary = $this->service->getSmmSummary();

        $this->assertEquals(4, $summary['total']);
        $this->assertEquals(1, $summary['completed']);
        $this->assertEquals(1, $summary['pending']);
        $this->assertEquals(1, $summary['partial']);
        $this->assertEquals(1, $summary['failed']);
        $this->assertEquals(5000.00, $summary['revenue']); // 4000 + 1000 prorated
        $this->assertEquals(1950.00, $summary['provider_cost']); // 1500 + 450 prorated
        $this->assertEquals(3050.00, $summary['profit']); // 5000 - 1950
    }

    /**
     * Test Virtual Number Product Summary calculations with USD provider cost conversion.
     */
    public function test_virtual_number_summary_metrics_and_status_counts(): void
    {
        Setting::set('usd_to_ngn_rate', '1500');
        $user = User::factory()->create();

        // 1. Received order: cost 1500 NGN, provider_cost $0.50 USD -> ₦750 NGN
        VirtualNumberOrder::create([
            'user_id'       => $user->id,
            'fivesim_id'    => 'vn_sum_01',
            'phone_number'  => '+2348000000001',
            'country'       => 'Nigeria',
            'operator'      => 'SMSPool',
            'product'       => 'WhatsApp',
            'cost'          => 1500.00,
            'provider_cost' => 0.50,
            'status'        => 'received',
        ]);

        // 2. Finished order: cost 1200 NGN, provider_cost $0.40 USD -> ₦600 NGN
        VirtualNumberOrder::create([
            'user_id'       => $user->id,
            'fivesim_id'    => 'vn_sum_02',
            'phone_number'  => '+2348000000002',
            'country'       => 'Nigeria',
            'operator'      => 'SMSPool',
            'product'       => 'Telegram',
            'cost'          => 1200.00,
            'provider_cost' => 0.40,
            'status'        => 'finished',
        ]);

        // 3. Pending order: cost 1000 NGN
        VirtualNumberOrder::create([
            'user_id'       => $user->id,
            'fivesim_id'    => 'vn_sum_03',
            'phone_number'  => '+2348000000003',
            'country'       => 'Nigeria',
            'operator'      => 'SMSPool',
            'product'       => 'Instagram',
            'cost'          => 1000.00,
            'provider_cost' => 0.30,
            'status'        => 'pending',
        ]);

        // 4. Canceled order: cost 800 NGN
        VirtualNumberOrder::create([
            'user_id'       => $user->id,
            'fivesim_id'    => 'vn_sum_04',
            'phone_number'  => '+2348000000004',
            'country'       => 'Nigeria',
            'operator'      => 'SMSPool',
            'product'       => 'Twitter',
            'cost'          => 800.00,
            'provider_cost' => 0.20,
            'status'        => 'canceled',
        ]);

        $summary = $this->service->getVirtualNumberSummary();

        $this->assertEquals(4, $summary['total']);
        $this->assertEquals(2, $summary['completed']);
        $this->assertEquals(1, $summary['pending']);
        $this->assertEquals(1, $summary['failed']);
        $this->assertEquals(2700.00, $summary['revenue']); // 1500 + 1200
        $this->assertEquals(1350.00, $summary['provider_cost']); // 750 + 600
        $this->assertEquals(1350.00, $summary['profit']); // 2700 - 1350
    }

    /**
     * Test VTU Product Summary calculations and unrecorded wholesale cost handling.
     */
    public function test_vtu_summary_metrics_and_status_counts(): void
    {
        $user = User::factory()->create();

        VtuOrder::create([
            'user_id'         => $user->id,
            'phone_or_meter'  => '08012345678',
            'service_type'    => 'airtime',
            'network'         => 'MTN',
            'cost'            => 1000.00,
            'status'          => 'completed',
            'api_reference'   => 'VTU_TST_01',
        ]);

        VtuOrder::create([
            'user_id'         => $user->id,
            'phone_or_meter'  => '08012345679',
            'service_type'    => 'data',
            'network'         => 'AIRTEL',
            'cost'            => 2500.00,
            'status'          => 'completed',
            'api_reference'   => 'VTU_TST_02',
        ]);

        VtuOrder::create([
            'user_id'         => $user->id,
            'phone_or_meter'  => '08012345680',
            'service_type'    => 'electricity',
            'network'         => 'IKEDC',
            'cost'            => 5000.00,
            'status'          => 'pending',
            'api_reference'   => 'VTU_TST_03',
        ]);

        VtuOrder::create([
            'user_id'         => $user->id,
            'phone_or_meter'  => '08012345681',
            'service_type'    => 'data',
            'network'         => 'GLO',
            'cost'            => 1200.00,
            'status'          => 'refunded',
            'api_reference'   => 'VTU_TST_04',
        ]);

        $summary = $this->service->getVtuSummary();

        $this->assertEquals(4, $summary['total']);
        $this->assertEquals(2, $summary['completed']);
        $this->assertEquals(1, $summary['pending']);
        $this->assertEquals(1, $summary['failed']);
        $this->assertEquals(3500.00, $summary['revenue']); // 1000 + 2500
        $this->assertNull($summary['provider_cost']); // Unrecorded wholesale cost
        $this->assertFalse($summary['cost_is_recorded']);
        $this->assertEquals(3500.00, $summary['profit']);
    }

    /**
     * Test Logs Marketplace Product Summary calculations with joined product cost.
     */
    public function test_log_summary_metrics_and_status_counts(): void
    {
        $user = User::factory()->create();

        $product = LogProduct::create([
            'name'           => 'Facebook Aged 2018',
            'type'           => 'api',
            'base_api_price' => 300.00,
            'unit_price'     => 500.00,
            'our_price'      => 500.00,
            'is_active'      => true,
            'in_stock'       => 10,
        ]);

        // Completed order: 2 items @ ₦500 = ₦1,000 revenue. Cost = 2 * 300 = ₦600.
        LogOrder::create([
            'user_id'           => $user->id,
            'log_product_id'    => $product->id,
            'product_name'      => $product->name,
            'quantity'          => 2,
            'unit_cost'         => 500.00,
            'total_cost'        => 1000.00,
            'status'            => 'completed',
            'external_order_id' => 'LOG_TST_01',
        ]);

        // Pending order: 1 item
        LogOrder::create([
            'user_id'           => $user->id,
            'log_product_id'    => $product->id,
            'product_name'      => $product->name,
            'quantity'          => 1,
            'unit_cost'         => 500.00,
            'total_cost'        => 500.00,
            'status'            => 'pending',
            'external_order_id' => 'LOG_TST_02',
        ]);

        // Failed order: 1 item
        LogOrder::create([
            'user_id'           => $user->id,
            'log_product_id'    => $product->id,
            'product_name'      => $product->name,
            'quantity'          => 1,
            'unit_cost'         => 500.00,
            'total_cost'        => 500.00,
            'status'            => 'failed',
            'external_order_id' => 'LOG_TST_03',
        ]);

        $summary = $this->service->getLogSummary();

        $this->assertEquals(3, $summary['total']);
        $this->assertEquals(1, $summary['completed']);
        $this->assertEquals(1, $summary['pending']);
        $this->assertEquals(1, $summary['failed']);
        $this->assertEquals(1000.00, $summary['revenue']);
        $this->assertEquals(600.00, $summary['provider_cost']);
        $this->assertEquals(400.00, $summary['profit']);
    }

    /**
     * Test legacy USD-denominated orders do not cause negative profit margins.
     * Orders placed before NGN conversion (e.g. $2.50 charge, $1.80 provider cost)
     * must both be converted to NGN, ensuring positive profit rather than massive minus.
     */
    public function test_legacy_usd_orders_do_not_produce_negative_margin(): void
    {
        Setting::set('usd_to_ngn_rate', '1500');
        $user = User::factory()->create();

        // Legacy USD SMM order: charge $2.50 USD, provider cost $1.80 USD
        // At 1,500 rate: Spent = ₦3,750, Cost = ₦2,700, Profit = +₦1,050
        Order::create([
            'user_id'       => $user->id,
            'service_id'    => 1,
            'link'          => 'https://instagram.com/legacy',
            'quantity'      => 1000,
            'charge'        => 2.50,
            'provider_cost' => 1.80,
            'status'        => 'completed',
            'is_refunded'   => false,
        ]);

        // Legacy USD Virtual Number order: cost $1.50 USD, provider cost $0.80 USD
        // At 1,500 rate: Spent = ₦2,250, Cost = ₦1,200, Profit = +₦1,050
        VirtualNumberOrder::create([
            'user_id'       => $user->id,
            'api_order_id'  => 'vn_legacy_01',
            'phone_number'  => '+12025550999',
            'country'       => 'USA',
            'operator'      => 'SMSPool',
            'product'       => 'OpenAI',
            'cost'          => 1.50,
            'provider_cost' => 0.80,
            'status'        => 'received',
        ]);

        $metrics = $this->service->getAdminFinancialPerformance();

        // Spent = 3750 + 2250 = 6000
        $this->assertEquals(6000.00, $metrics['totalSpent']);
        // Cost = 2700 + 1200 = 3900
        $this->assertEquals(3900.00, $metrics['totalCost']);
        // Profit = 6000 - 3900 = +2100 (POSITIVE, never negative!)
        $this->assertEquals(2100.00, $metrics['totalProfit']);
        $this->assertGreaterThan(0, $metrics['totalProfit']);
    }

    /**
     * Test orders with provider costs already stored in NGN do not get multiplied by 1,500.
     * Prevents multi-million Naira inflation on provider expenses.
     */
    public function test_orders_with_ngn_denominated_provider_cost_do_not_overinflate_costs(): void
    {
        Setting::set('usd_to_ngn_rate', '1500');
        $user = User::factory()->create();

        // Customer charged ₦5,000 NGN. Provider cost already recorded in NGN as ₦3,500.
        // If falsely multiplied by 1,500, cost would be ₦5,250,000 and profit -₦5,245,000!
        // With intelligent detection: Cost = ₦3,500, Profit = +₦1,500.
        Order::create([
            'user_id'       => $user->id,
            'service_id'    => 1,
            'link'          => 'https://instagram.com/ngn_test',
            'quantity'      => 1000,
            'charge'        => 5000.00,
            'provider_cost' => 3500.00,
            'status'        => 'completed',
            'is_refunded'   => false,
        ]);

        $metrics = $this->service->getAdminFinancialPerformance();

        $this->assertEquals(5000.00, $metrics['totalSpent']);
        $this->assertEquals(3500.00, $metrics['totalCost']);
        $this->assertEquals(1500.00, $metrics['totalProfit']);
        $this->assertGreaterThan(0, $metrics['totalProfit']);
    }
}
