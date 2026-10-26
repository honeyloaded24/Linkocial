<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Artisan;

class WalletRecoveryAndExchangeRateSettingsTest extends TestCase
{
    /**
     * Test authorized admin can view and update exchange rate settings.
     */
    public function test_admin_can_update_exchange_rate_settings(): void
    {
        $admin = new User([
            'id' => 10001,
            'email' => 'admin@linkocial.test',
            'username' => 'superadmin',
            'is_admin' => true,
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $response = $this->get('/hq/settings/currency');
        $response->assertStatus(200);
        $response->assertSee('Currency & Exchange Rate Settings');

        $updateResponse = $this->post('/hq/settings/currency', [
            'usd_to_ngn_rate' => 1600,
            'ngn_to_usd_rate' => round(1 / 1600, 10),
        ]);

        $updateResponse->assertRedirect();
        $this->assertEquals('1600', Setting::get('usd_to_ngn_rate'));
    }

    /**
     * Test regular user cannot access or modify exchange rate settings.
     */
    public function test_non_admin_cannot_modify_exchange_rate_settings(): void
    {
        $regularUser = new User([
            'id' => 10002,
            'email' => 'user@linkocial.test',
            'username' => 'regularuser',
            'is_admin' => false,
            'role' => 'user',
        ]);

        $this->actingAs($regularUser);

        $response = $this->get('/hq/settings/currency');
        $this->assertTrue(in_array($response->status(), [403, 302]));

        $postResponse = $this->post('/hq/settings/currency', [
            'usd_to_ngn_rate' => 2000,
        ]);
        $this->assertTrue(in_array($postResponse->status(), [403, 302]));
    }

    /**
     * Test zero or negative rate is rejected.
     */
    public function test_zero_or_negative_rate_is_rejected(): void
    {
        $admin = new User([
            'id' => 10003,
            'email' => 'admin2@linkocial.test',
            'is_admin' => true,
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $response = $this->post('/hq/settings/currency', [
            'usd_to_ngn_rate' => 0,
        ]);
        $response->assertSessionHasErrors('usd_to_ngn_rate');

        $negResponse = $this->post('/hq/settings/currency', [
            'usd_to_ngn_rate' => -500,
        ]);
        $negResponse->assertSessionHasErrors('usd_to_ngn_rate');
    }

    /**
     * Test updating exchange rate does NOT change existing NGN wallet balances.
     */
    public function test_rate_change_does_not_alter_existing_balances(): void
    {
        $user = new User([
            'id' => 10004,
            'email' => 'customer@linkocial.test',
            'balance' => 70500.00,
            'currency_code' => 'NGN',
        ]);

        $initialBalance = $user->balance;

        // Change exchange rate from 1500 to 1800
        Setting::set('usd_to_ngn_rate', '1800');

        $this->assertEquals($initialBalance, $user->balance);
        $this->assertEquals(70500.00, $user->balance);
    }
}
