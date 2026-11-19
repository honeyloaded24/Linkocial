<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\VirtualNumberOrder;
use App\Services\SmsPoolService;
use App\Services\GrizzlySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->post(route('hq.virtual-numbers.refund', $order->id))
            ->assertRedirect(route('hq.virtual-numbers'));

        $this->assertSame(500.0, (float) $customer->fresh()->balance);
        $this->assertSame('received', $order->fresh()->status);
        $this->assertSame(0, Transaction::where('reference', 'REF-VN-ADMIN-' . $order->id)->count());
    }
}
