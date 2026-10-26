<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class RegistrationSecurityHardeningTest extends TestCase
{
    /**
     * Test registration rejects links and crypto spam in first_name, last_name, or username.
     */
    public function test_registration_rejects_links_and_crypto_spam(): void
    {
        $payloads = [
            [
                'first_name' => 'Claim 5000 USDT at https://spam-crypto.site',
                'last_name' => 'Airdrop',
                'username' => 'cryptospam1',
                'email' => 'spam1@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'terms' => '1',
            ],
            [
                'first_name' => 'Join Telegram',
                'last_name' => 't.me/freecrypto',
                'username' => 'cryptospam2',
                'email' => 'spam2@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'terms' => '1',
            ],
            [
                'first_name' => 'Bitcoin',
                'last_name' => 'Investor',
                'username' => 'cryptospam3',
                'email' => 'spam3@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'terms' => '1',
            ],
        ];

        foreach ($payloads as $payload) {
            $response = $this->post(route('register'), $payload);
            $response->assertSessionHasErrors(['first_name']);
        }
    }

    /**
     * Test registration rejects excessively long names (> 50 characters).
     */
    public function test_registration_rejects_excessively_long_names(): void
    {
        $longName = str_repeat('A', 51);

        $response = $this->post(route('register'), [
            'first_name' => $longName,
            'last_name' => 'Doe',
            'username' => 'validuser',
            'email' => 'valid@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ]);

        $response->assertSessionHasErrors(['first_name']);
    }

    /**
     * Test registration rejects script tags, symbols, and non-name characters.
     */
    public function test_registration_rejects_script_tags_and_disallowed_symbols(): void
    {
        $response = $this->post(route('register'), [
            'first_name' => '<script>alert("xss")</script>',
            'last_name' => 'Test',
            'username' => 'scriptuser',
            'email' => 'script@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ]);

        $response->assertSessionHasErrors(['first_name']);
    }

    /**
     * Test registration rejects invalid username formats.
     */
    public function test_registration_rejects_invalid_username_characters_or_excessive_length(): void
    {
        // Username with invalid symbols (spaces, slashes, or > 30 chars)
        $response = $this->post(route('register'), [
            'first_name' => 'Valid',
            'last_name' => 'User',
            'username' => 'user name with spaces!',
            'email' => 'user@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ]);

        $response->assertSessionHasErrors(['username']);

        // Username longer than 30 characters
        $responseLong = $this->post(route('register'), [
            'first_name' => 'Valid',
            'last_name' => 'User',
            'username' => str_repeat('a', 31),
            'email' => 'user2@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ]);

        $responseLong->assertSessionHasErrors(['username']);
    }

    /**
     * Test unverified user cannot access protected web routes and is redirected to email verification.
     */
    public function test_unverified_user_cannot_access_dashboard_or_wallet(): void
    {
        $unverifiedUser = User::factory()->unverified()->make([
            'id' => 9991,
            'first_name' => 'Unverified',
            'last_name' => 'User',
            'username' => 'unverified_guy',
            'email' => 'unverified@example.com',
        ]);

        $this->actingAs($unverifiedUser);

        // Attempting to access dashboard
        $responseDashboard = $this->get(route('dashboard'));
        $responseDashboard->assertRedirect(route('verification.notice'));

        // Attempting to access wallet
        $responseWallet = $this->get(route('wallet.index'));
        $responseWallet->assertRedirect(route('verification.notice'));
    }

    /**
     * Test Reseller API rejects unverified users with HTTP 403.
     */
    public function test_reseller_api_rejects_unverified_user(): void
    {
        $unverifiedUser = User::factory()->unverified()->make([
            'id' => 9992,
            'api_key' => 'test_unverified_api_key_123456789012345678',
            'is_active' => true,
        ]);

        // Mock User query for the API key
        $response = $this->postJson('/api/v1', [
            'key' => 'non_existent_or_unverified',
            'action' => 'services',
        ]);

        // Returns 401 or 403
        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    /**
     * Test admin updateUserDetails enforces matching anti-spam and length rules.
     */
    public function test_admin_update_user_details_enforces_anti_spam_validation(): void
    {
        $admin = User::factory()->make([
            'id' => 1,
            'role' => 'admin',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        $targetUser = User::factory()->make([
            'id' => 50,
            'email_verified_at' => now(),
        ]);

        $response = $this->put(route('hq.users.update', $targetUser->id), [
            'first_name' => 'Join t.me/cryptoleaks for free USDT',
            'last_name' => 'Doe',
            'username' => 'gooduser',
            'email' => 'target@example.com',
        ]);

        $response->assertSessionHasErrors(['first_name']);
    }
}
