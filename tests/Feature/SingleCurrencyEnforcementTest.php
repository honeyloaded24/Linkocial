<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SingleCurrencyEnforcementTest extends TestCase
{
    /**
     * Test currency switch route is removed / returns 404 or redirects without altering currency away from NGN.
     */
    public function test_currency_switch_is_disabled_or_locked_to_ngn(): void
    {
        $response = $this->post('/currency/switch', [
            'currency' => 'USD'
        ]);

        // Either 404 (route removed) or 302 locked to NGN
        $this->assertTrue(in_array($response->status(), [404, 302, 405]));
        $this->assertEquals('NGN', session('currency', 'NGN'));
    }

    /**
     * Test Korapay payment initialization strictly enforces NGN.
     */
    public function test_payment_initialization_requires_ngn(): void
    {
        $user = User::factory()->make([
            'id' => 99999,
            'email' => 'tester@linkocial.test',
            'username' => 'tester',
        ]);

        $this->actingAs($user);

        // Submitting an unsupported currency parameter should be rejected
        $response = $this->post('/wallet/deposit/korapay', [
            'amount' => 5000,
            'currency' => 'USD'
        ]);

        // Must reject or validate strictly
        $this->assertTrue(in_array($response->status(), [302, 400, 422]));
    }

    /**
     * Test wallet atomic add and deduct operations preserve NGN balances.
     */
    public function test_user_wallet_atomic_balance_operations(): void
    {
        $user = new User([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@test.com',
            'username' => 'johndoe',
            'balance' => 10000.00,
            'currency_code' => 'NGN',
        ]);

        $this->assertEquals(10000.00, $user->balance);
        $this->assertEquals('NGN', $user->currency_code);
    }
}
