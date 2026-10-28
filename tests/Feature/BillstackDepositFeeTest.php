<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use App\Models\Payment;
use App\Models\UserVirtualAccount;
use App\Services\BillstackService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class BillstackDepositFeeTest extends TestCase
{
    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            // Ignore in test env
        }

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_fee_test@linkocial.test'],
            [
                'id' => 9111,
                'name' => 'HQ Fee Admin',
                'username' => 'hqfee_admin',
                'password' => bcrypt('secret123'),
                'is_admin' => true,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->user = User::firstOrCreate(
            ['email' => 'billstack_user@linkocial.test'],
            [
                'id' => 9112,
                'name' => 'Billstack Tester',
                'username' => 'billstack_tester',
                'password' => bcrypt('secret123'),
                'balance' => 1000.00,
                'currency_code' => 'NGN',
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Test 1: Configured fee (default ₦45) appears correctly on the Fund Wallet frontend.
     */
    public function test_default_45_fee_notice_appears_on_fund_wallet_page(): void
    {
        Setting::set('billstack_deposit_fee', '45');
        Setting::clearCache();

        $this->actingAs($this->user);

        $response = $this->get(route('wallet.index'));
        $response->assertStatus(200);
        $response->assertSee('Deposit Fee Notice:');
        $response->assertSee('A ₦45 fee will be deducted from every deposit.');
        $response->assertSee('The amount credited to your wallet will be your deposit amount minus the applicable fee.');
    }

    /**
     * Test 2: When an administrator changes the fee in Admin Management, the frontend updates dynamically.
     */
    public function test_admin_can_update_deposit_fee_and_notice_updates_immediately(): void
    {
        $this->actingAs($this->admin);

        // Update fee to ₦75 via admin settings route
        $response = $this->post(route('hq.settings.update'), [
            'billstack_deposit_fee' => '75',
        ]);
        $response->assertRedirect();

        $this->assertEquals('75', Setting::get('billstack_deposit_fee'));

        // Visit wallet page as regular user
        $this->actingAs($this->user);
        $responseWallet = $this->get(route('wallet.index'));
        $responseWallet->assertStatus(200);
        $responseWallet->assertSee('A ₦75 fee will be deducted from every deposit.');
    }

    /**
     * Test 3: Zero-fee setting displays appropriate no-fee notice.
     */
    public function test_zero_fee_displays_appropriate_no_fee_notice(): void
    {
        Setting::set('billstack_deposit_fee', '0');
        Setting::clearCache();

        $this->actingAs($this->user);

        $response = $this->get(route('wallet.index'));
        $response->assertStatus(200);
        $response->assertSee('Deposit Fee Notice:');
        $response->assertSee('No deposit fee is charged on virtual account transfers. The full deposit amount will be credited to your wallet.');
        $response->assertDontSee('will be deducted');
    }

    /**
     * Test 4: Webhook applies configured fee dynamically: Wallet credit = Deposit amount - Fee.
     */
    public function test_webhook_credits_deposit_amount_minus_configured_fee(): void
    {
        Setting::set('billstack_deposit_fee', '45');
        Setting::clearCache();

        $initialBalance = (float) $this->user->balance;

        // Associate virtual account with this user
        UserVirtualAccount::firstOrCreate(
            ['account_number' => '9922334455'],
            [
                'user_id' => $this->user->id,
                'bank_name' => '9PSB',
                'account_name' => 'Billstack Tester',
                'reference' => 'VA_REF_TEST_45',
            ]
        );

        $ref = 'TX_BS_TEST_' . time() . '_45';
        $grossDeposit = 2000.00; // Deposit amount
        $expectedFee = 45.00;
        $expectedCredit = 1955.00; // 2000 - 45

        // Mock Billstack webhook signature verification
        $billstackMock = $this->createMock(BillstackService::class);
        $billstackMock->method('verifyWebhookSignature')->willReturn(true);
        $this->app->instance(BillstackService::class, $billstackMock);

        $payload = [
            'event' => 'PAYMENT_NOTIFICATION',
            'data' => [
                'type' => 'RESERVED_ACCOUNT_TRANSACTION',
                'status' => 'successful',
                'amount' => $grossDeposit,
                'transaction_ref' => $ref,
                'account_number' => '9922334455',
                'bank_name' => '9PSB',
            ]
        ];

        $response = $this->postJson('/api/webhooks/billstack', $payload);
        $response->assertStatus(200);
        $response->assertJson(['status' => true, 'message' => 'successful']);

        $this->user->refresh();
        $this->assertEquals($initialBalance + $expectedCredit, (float) $this->user->balance);

        // Verify Payment record was stored with net amount
        $payment = Payment::where('reference', 'BS_' . $ref)->first();
        $this->assertNotNull($payment);
        $this->assertEquals($expectedCredit, (float) $payment->amount);
    }

    /**
     * Test 5: Inbound deposit smaller than or equal to fee is rejected/absorbed without crediting.
     */
    public function test_deposit_smaller_than_or_equal_to_fee_is_not_credited(): void
    {
        Setting::set('billstack_deposit_fee', '45');
        Setting::clearCache();

        $initialBalance = (float) $this->user->balance;

        UserVirtualAccount::firstOrCreate(
            ['account_number' => '9922334466'],
            [
                'user_id' => $this->user->id,
                'bank_name' => 'PalmPay',
                'account_name' => 'Billstack Tester',
                'reference' => 'VA_REF_TEST_SMALL',
            ]
        );

        $billstackMock = $this->createMock(BillstackService::class);
        $billstackMock->method('verifyWebhookSignature')->willReturn(true);
        $this->app->instance(BillstackService::class, $billstackMock);

        // Scenario A: Deposit exactly equal to fee (₦45)
        $refEqual = 'TX_BS_TEST_EQUAL_' . time();
        $payloadEqual = [
            'event' => 'PAYMENT_NOTIFICATION',
            'data' => [
                'type' => 'RESERVED_ACCOUNT_TRANSACTION',
                'status' => 'successful',
                'amount' => 45.00,
                'transaction_ref' => $refEqual,
                'account_number' => '9922334466',
                'bank_name' => 'PalmPay',
            ]
        ];

        $responseEqual = $this->postJson('/api/webhooks/billstack', $payloadEqual);
        $responseEqual->assertStatus(200);
        $responseEqual->assertJson(['status' => true, 'message' => 'Deposit amount absorbed by fee']);

        $this->user->refresh();
        $this->assertEquals($initialBalance, (float) $this->user->balance);

        // Scenario B: Deposit smaller than fee (₦20)
        $refSmall = 'TX_BS_TEST_LESS_' . time();
        $payloadSmall = [
            'event' => 'PAYMENT_NOTIFICATION',
            'data' => [
                'type' => 'RESERVED_ACCOUNT_TRANSACTION',
                'status' => 'successful',
                'amount' => 20.00,
                'transaction_ref' => $refSmall,
                'account_number' => '9922334466',
                'bank_name' => 'PalmPay',
            ]
        ];

        $responseSmall = $this->postJson('/api/webhooks/billstack', $payloadSmall);
        $responseSmall->assertStatus(200);
        $responseSmall->assertJson(['status' => true, 'message' => 'Deposit amount absorbed by fee']);

        $this->user->refresh();
        $this->assertEquals($initialBalance, (float) $this->user->balance);
    }

    /**
     * Test 6: Zero fee credits 100% of the deposit amount.
     */
    public function test_zero_fee_credits_full_amount(): void
    {
        Setting::set('billstack_deposit_fee', '0');
        Setting::clearCache();

        $initialBalance = (float) $this->user->balance;

        UserVirtualAccount::firstOrCreate(
            ['account_number' => '9922334477'],
            [
                'user_id' => $this->user->id,
                'bank_name' => '9PSB',
                'account_name' => 'Billstack Tester',
                'reference' => 'VA_REF_TEST_ZERO',
            ]
        );

        $ref = 'TX_BS_TEST_ZERO_' . time();
        $grossDeposit = 1500.00;

        $billstackMock = $this->createMock(BillstackService::class);
        $billstackMock->method('verifyWebhookSignature')->willReturn(true);
        $this->app->instance(BillstackService::class, $billstackMock);

        $payload = [
            'event' => 'PAYMENT_NOTIFICATION',
            'data' => [
                'type' => 'RESERVED_ACCOUNT_TRANSACTION',
                'status' => 'successful',
                'amount' => $grossDeposit,
                'transaction_ref' => $ref,
                'account_number' => '9922334477',
                'bank_name' => '9PSB',
            ]
        ];

        $response = $this->postJson('/api/webhooks/billstack', $payload);
        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertEquals($initialBalance + $grossDeposit, (float) $this->user->balance);
    }
}
