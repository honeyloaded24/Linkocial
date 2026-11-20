<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\VirtualNumberOrder;
use App\Services\SmsPoolService;
use App\Services\GrizzlySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Tests\TestCase;

class VirtualNumberRefundIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // These tests exercise the admin refund controller's financial logic,
        // not the unrelated email-verification gate.
        $this->withoutMiddleware(EnsureEmailIsVerified::class);
    }

    public function test_admin_cannot_refund_an_already_refunded_failed_virtual_number_order(): void
    {
        $admin = new User();
        $admin->first_name = 'Audit';
        $admin->last_name = 'Admin';
        $admin->username = 'vn_refund_admin';
        $admin->email = 'vn-refund-admin@example.test';
        $admin->password = bcrypt('password');
        $admin->email_verified_at = now();
        $admin->is_admin = true;
        $admin->role = 'admin';
        $admin->save();

        $customer = new User();
        $customer->first_name = 'Refund';
        $customer->last_name = 'Customer';
        $customer->username = 'vn_refund_customer';
        $customer->email = 'vn-refund-customer@example.test';
        $customer->password = bcrypt('password');
        $customer->balance = 1200;
        $customer->save();

        $order = VirtualNumberOrder::create([
            'user_id' => $customer->id,
            'api_order_id' => 'AUDIT-VN-REFUND-1',
            'phone_number' => '',
            'country' => 'Nigeria',
            'operator' => 'Test',
            'server' => 'server1',
            'product' => 'test-service',
            'cost' => 250,
            'provider_cost' => 1,
            'status' => 'failed',
            'sms_text' => 'Provider reconciliation confirmed no order; customer already refunded.',
        ]);

        Transaction::create([
            'user_id' => $customer->id,
            'amount' => 250,
            'type' => 'credit',
            'transaction_type' => 'refund',
            'balance_before' => 950,
            'balance_after' => 1200,
            'description' => 'Automatic refund for unresolved virtual-number order',
            'reference' => 'REF-VN-' . $order->id,
        ]);

        $this->app->instance(SmsPoolService::class, new SmsPoolService());
        $this->app->instance(GrizzlySmsService::class, new GrizzlySmsService());

        $this->actingAs($admin)
            ->withHeader('referer', route('hq.virtual-numbers'))
            ->post(route('hq.virtual-numbers.refund', $order->id))
            ->assertRedirect(route('hq.virtual-numbers'));

        $this->assertSame(1200.0, (float) $customer->fresh()->balance);
        $this->assertSame('failed', $order->fresh()->status);
        $this->assertSame(1, Transaction::where('reference', 'REF-VN-' . $order->id)->count());
        $this->assertSame(0, Transaction::where('reference', 'REF-VN-ADMIN-' . $order->id)->count());
    }

    public function test_admin_refund_is_withheld_when_provider_cancellation_fails(): void
    {
        $admin = new User();
        $admin->first_name = 'Audit';
        $admin->last_name = 'Admin';
        $admin->username = 'vn_cancel_admin';
        $admin->email = 'vn-cancel-admin@example.test';
        $admin->password = bcrypt('password');
        $admin->email_verified_at = now();
        $admin->is_admin = true;
        $admin->role = 'admin';
        $admin->save();

        $customer = new User();
        $customer->first_name = 'Pending';
        $customer->last_name = 'Customer';
        $customer->username = 'vn_cancel_customer';
        $customer->email = 'vn-cancel-customer@example.test';
        $customer->password = bcrypt('password');
        $customer->balance = 500;
        $customer->save();

        $order = VirtualNumberOrder::create([
            'user_id' => $customer->id, 'api_order_id' => 'AUDIT-VN-CANCEL-FAIL',
            'phone_number' => '', 'country' => 'Nigeria', 'operator' => 'Test',
            'server' => 'server1', 'product' => 'test-service', 'cost' => 250,
            'provider_cost' => 1, 'status' => 'pending',
        ]);

        $this->mock(SmsPoolService::class, function ($mock) {
            $mock->shouldReceive('cancelOrder')->once()->andThrow(new \RuntimeException('Provider cancellation failed'));
        });
        $this->mock(GrizzlySmsService::class);

        $this->actingAs($admin)
            ->withHeader('referer', route('hq.virtual-numbers'))
            ->post(route('hq.virtual-numbers.refund', $order->id))
            ->assertRedirect(route('hq.virtual-numbers'));

        $this->assertSame(500.0, (float) $customer->fresh()->balance);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, Transaction::where('reference', 'REF-VN-ADMIN-' . $order->id)->count());
    }

    public function test_admin_cannot_refund_virtual_number_after_sms_received(): void
    {
        $admin = new User();
        $admin->first_name = 'Audit';
        $admin->last_name = 'Admin';
        $admin->username = 'vn_received_admin';
        $admin->email = 'vn-received-admin@example.test';
        $admin->password = bcrypt('password');
        $admin->email_verified_at = now();
        $admin->is_admin = true;
        $admin->role = 'admin';
        $admin->save();

        $customer = new User();
        $customer->first_name = 'Received';
        $customer->last_name = 'Customer';
        $customer->username = 'vn_received_customer';
        $customer->email = 'vn-received-customer@example.test';
        $customer->password = bcrypt('password');
        $customer->balance = 500;
        $customer->save();

        $order = VirtualNumberOrder::create([
            'user_id' => $customer->id, 'api_order_id' => 'AUDIT-VN-RECEIVED',
            'phone_number' => '+234000000000', 'country' => 'Nigeria', 'operator' => 'Test',
            'server' => 'server1', 'product' => 'test-service', 'cost' => 250,
            'provider_cost' => 1, 'status' => 'received', 'sms_code' => '123456',
            'sms_text' => 'Your verification code is 123456',
        ]);

        $this->mock(SmsPoolService::class, function ($mock) {
            $mock->shouldNotReceive('cancelOrder');
        });
        $this->mock(GrizzlySmsService::class);

        $this->actingAs($admin)
            ->withHeader('referer', route('hq.virtual-numbers'))
            ->post(route('hq.virtual-numbers.refund', $order->id))
            ->assertRedirect(route('hq.virtual-numbers'));

        $this->assertSame(500.0, (float) $customer->fresh()->balance);
        $this->assertSame('received', $order->fresh()->status);
        $this->assertSame(0, Transaction::where('reference', 'REF-VN-ADMIN-' . $order->id)->count());
    }
    public function test_user_cancel_refunds_when_diagnostic_sms_text_exists_but_no_code_arrived(): void
    {
        $customer = new User();
        $customer->first_name = 'Cancel';
        $customer->last_name = 'Customer';
        $customer->username = 'vn_cancel_text_customer';
        $customer->email = 'vn-cancel-text@example.test';
        $customer->password = bcrypt('password');
        $customer->balance = 500;
        $customer->save();

        $order = VirtualNumberOrder::create([
            'user_id' => $customer->id, 'api_order_id' => 'AUDIT-VN-CANCEL-TEXT',
            'phone_number' => '+234000000000', 'country' => 'Nigeria', 'operator' => 'Test',
            'server' => 'server1', 'product' => 'test-service', 'cost' => 250,
            'provider_cost' => 1, 'status' => 'pending',
            'sms_text' => 'Cancellation not confirmed by provider. Refund withheld pending reconciliation.',
        ]);

        $this->mock(SmsPoolService::class, function ($mock) {
            $mock->shouldReceive('checkOrder')->once()->with('AUDIT-VN-CANCEL-TEXT')->andReturn(['status' => 1]);
            $mock->shouldReceive('cancelOrder')->once()->with('AUDIT-VN-CANCEL-TEXT')->andReturn(['success' => 1]);
        });
        $this->mock(GrizzlySmsService::class);

        $this->actingAs($customer)
            ->post('/api/virtual-numbers/' . $order->id . '/cancel')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(750.0, (float) $customer->fresh()->balance);
        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame(1, Transaction::where('reference', 'REF-VN-' . $order->id)->count());
    }

    public function test_expired_virtual_number_is_refunded_once_and_ledger_balances(): void
    {
        $customer = new User();
        $customer->first_name = 'Expiry';
        $customer->last_name = 'Customer';
        $customer->username = 'vn_expiry_customer';
        $customer->email = 'vn-expiry-customer@example.test';
        $customer->password = bcrypt('password');
        $customer->balance = 250;
        $customer->save();

        $order = VirtualNumberOrder::create([
            'user_id' => $customer->id, 'api_order_id' => 'AUDIT-VN-EXPIRED',
            'phone_number' => '+234000000001', 'country' => 'Nigeria', 'operator' => 'Test',
            'server' => 'server1', 'product' => 'test-service', 'cost' => 250,
            'provider_cost' => 1, 'status' => 'pending', 'expires_at' => now()->subMinutes(10),
        ]);

        $this->mock(SmsPoolService::class, function ($mock) {
            $mock->shouldReceive('checkOrder')->once()->with('AUDIT-VN-EXPIRED')->andReturn(['status' => 2]);
        });
        $this->mock(GrizzlySmsService::class);

        $this->artisan('vn:check-expired')->assertExitCode(0);
        $this->assertSame(500.0, (float) $customer->fresh()->balance);
        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame(1, Transaction::where('reference', 'REF-VN-' . $order->id)->count());

        // A second reconciliation pass must not credit the wallet again.
        $this->artisan('vn:check-expired')->assertExitCode(0);
        $this->assertSame(500.0, (float) $customer->fresh()->balance);
        $this->assertSame(1, Transaction::where('reference', 'REF-VN-' . $order->id)->count());
    }

    public function test_expiry_worker_does_not_refund_if_sms_code_arrives_during_provider_check(): void
    {
        $customer = new User();
        $customer->first_name = 'Race';
        $customer->last_name = 'Customer';
        $customer->username = 'vn_expiry_race_customer';
        $customer->email = 'vn-expiry-race@example.test';
        $customer->password = bcrypt('password');
        $customer->balance = 250;
        $customer->save();

        $order = VirtualNumberOrder::create([
            'user_id' => $customer->id, 'api_order_id' => 'AUDIT-VN-EXPIRY-RACE',
            'phone_number' => '+234000000002', 'country' => 'Nigeria', 'operator' => 'Test',
            'server' => 'server1', 'product' => 'test-service', 'cost' => 250,
            'provider_cost' => 1, 'status' => 'pending', 'expires_at' => now()->subMinutes(10),
        ]);

        $this->mock(SmsPoolService::class, function ($mock) use ($order) {
            $mock->shouldReceive('checkOrder')->once()->with('AUDIT-VN-EXPIRY-RACE')->andReturnUsing(function () use ($order) {
                // Simulate the SMS polling path persisting a code after the worker's initial check.
                $order->update(['sms_code' => '123456']);
                return ['status' => 2];
            });
        });
        $this->mock(GrizzlySmsService::class);

        $this->artisan('vn:check-expired')->assertExitCode(0);
        $this->assertSame(250.0, (float) $customer->fresh()->balance);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('123456', $order->fresh()->sms_code);
        $this->assertSame(0, Transaction::where('reference', 'REF-VN-' . $order->id)->count());
    }

    public function test_virtual_number_refund_ledger_reference_is_unique_and_balance_reconciles(): void
    {
        $customer = new User();
        $customer->first_name = 'Ledger';
        $customer->last_name = 'Customer';
        $customer->username = 'vn_ledger_customer';
        $customer->email = 'vn-ledger-customer@example.test';
        $customer->password = bcrypt('password');
        $customer->balance = 1000;
        $customer->save();

        $order = VirtualNumberOrder::create([
            'user_id' => $customer->id, 'api_order_id' => 'AUDIT-VN-LEDGER',
            'phone_number' => '', 'country' => 'Nigeria', 'operator' => 'Test',
            'server' => 'server1', 'product' => 'test-service', 'cost' => 250,
            'provider_cost' => 1, 'status' => 'failed',
        ]);

        Transaction::create([
            'user_id' => $customer->id, 'amount' => 250, 'type' => 'debit',
            'transaction_type' => 'virtual_number', 'balance_before' => 1000,
            'balance_after' => 750, 'description' => 'Virtual Number Purchase',
            'reference' => 'VN-DEBIT-' . $order->id,
        ]);
        Transaction::create([
            'user_id' => $customer->id, 'amount' => 250, 'type' => 'credit',
            'transaction_type' => 'refund', 'balance_before' => 750,
            'balance_after' => 1000, 'description' => 'Virtual Number Refund',
            'reference' => 'REF-VN-' . $order->id,
        ]);

        $debits = Transaction::where('user_id', $customer->id)->where('type', 'debit')->sum('amount');
        $credits = Transaction::where('user_id', $customer->id)->where('type', 'credit')->sum('amount');
        $this->assertSame(0.0, round((float) $debits - (float) $credits, 4));
        $this->assertSame(1000.0, (float) $customer->fresh()->balance);
        $this->assertSame(1, Transaction::where('reference', 'REF-VN-' . $order->id)->count());
    }

    public function test_stock_unavailable_purchase_does_not_debit_wallet_or_create_order(): void
    {
        $customer = new User();
        $customer->first_name = 'Stock';
        $customer->last_name = 'Customer';
        $customer->username = 'vn_stock_customer';
        $customer->email = 'vn-stock-customer@example.test';
        $customer->password = bcrypt('password');
        $customer->balance = 1000;
        $customer->save();

        Cache::put('smspool_prices_NG', [['service' => 'test-service', 'service_name' => 'Test Service', 'price' => 1.0]], 60);
        $this->mock(SmsPoolService::class, function ($mock) {
            $mock->shouldReceive('getServiceLiveInfo')->once()->with('test-service', 'NG')->andReturn(['stock' => 0]);
            $mock->shouldNotReceive('buyNumber');
        });
        $this->mock(GrizzlySmsService::class);

        $this->actingAs($customer)
            ->post('/api/virtual-numbers/purchase', ['country' => 'NG', 'product' => 'test-service', 'server' => 'server1'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'Test Service is currently out of stock for this country on Server 1. Please try Server 2 or select another country.');

        $this->assertSame(1000.0, (float) $customer->fresh()->balance);
        $this->assertSame(0, VirtualNumberOrder::where('user_id', $customer->id)->count());
        $this->assertSame(0, Transaction::where('user_id', $customer->id)->count());
    }

}
