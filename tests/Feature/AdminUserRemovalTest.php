<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AdminUserRemovalTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_admin' => true,
            'is_active' => true,
            'status' => 'active',
            'email' => 'admin_test_' . uniqid() . '@example.com',
        ]);

        $this->targetUser = User::factory()->create([
            'role' => 'user',
            'is_admin' => false,
            'is_active' => true,
            'status' => 'active',
            'balance' => 1500,
            'email' => 'target_user_' . uniqid() . '@example.com',
        ]);
    }

    /**
     * 1. Test that the Remove button appears for eligible registered users,
     * but does NOT appear for the currently logged-in administrator's own row.
     */
    public function test_remove_button_visibility_in_registered_users_table()
    {
        $response = $this->actingAs($this->admin)->get(route('hq.users'));

        $response->assertStatus(200);

        // Target user's remove form should be present
        $response->assertSee(route('hq.users.destroy', $this->targetUser->id));
        $response->assertSee('title="Remove User"', false);

        // Logged-in admin's remove form must NOT be present
        $response->assertDontSee(route('hq.users.destroy', $this->admin->id));
    }

    /**
     * 2. Test that an authorized administrator can remove a user without financial history.
     */
    public function test_admin_can_remove_user_without_financial_records()
    {
        $response = $this->actingAs($this->admin)->delete(route('hq.users.destroy', $this->targetUser->id));

        $response->assertSessionHas('success');
        $this->assertNull(User::find($this->targetUser->id));
    }

    /**
     * 3. Test that removing a user WITH financial transactions preserves all financial records
     * and ledger history, deactivates the account, and removes them from the registered users list.
     */
    public function test_admin_removal_preserves_financial_and_order_records()
    {
        // Add a financial transaction and order for the target user
        $tx = Transaction::add(
            $this->targetUser->id,
            1000,
            'debit',
            'vtu_data',
            'Data plan purchase'
        );

        $order = Order::create([
            'user_id' => $this->targetUser->id,
            'service_id' => 1,
            'link' => 'https://example.com/test',
            'quantity' => 100,
            'charge' => 1000,
            'start_counter' => 0,
            'remains' => 0,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('hq.users.destroy', $this->targetUser->id));

        $response->assertSessionHas('success');

        // Verify the user is marked removed and deactivated
        $removedUser = User::find($this->targetUser->id);
        $this->assertNotNull($removedUser);
        $this->assertFalse((bool)$removedUser->is_active);
        $this->assertEquals('removed', $removedUser->status);

        // Verify financial transactions and orders were NOT cascaded or deleted
        $this->assertDatabaseHas('transactions', [
            'id' => $tx->id,
            'user_id' => $this->targetUser->id,
            'amount' => 1000,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $this->targetUser->id,
        ]);

        // Verify removed user no longer appears in the Registered Users list
        $listResponse = $this->actingAs($this->admin)->get(route('hq.users'));
        $listResponse->assertDontSee($this->targetUser->email);
    }

    /**
     * 4. Test that an administrator CANNOT remove their own account.
     */
    public function test_admin_cannot_remove_own_account()
    {
        $response = $this->actingAs($this->admin)->delete(route('hq.users.destroy', $this->admin->id));

        $response->assertSessionHas('error', 'You cannot remove your own administrator account.');
        $this->assertNotNull(User::find($this->admin->id));
    }

    /**
     * 5. Test that primary administrator account (ID: 1) cannot be removed.
     */
    public function test_primary_admin_account_cannot_be_removed()
    {
        $primaryAdmin = User::find(1);
        if ($primaryAdmin) {
            $response = $this->actingAs($this->admin)->delete(route('hq.users.destroy', 1));
            $response->assertSessionHas('error', 'The primary administrator account is protected and cannot be removed.');
            $this->assertNotNull(User::find(1));
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * 6. Test that a non-admin user CANNOT remove users.
     */
    public function test_non_admin_cannot_remove_users()
    {
        $nonAdmin = User::factory()->create([
            'role' => 'user',
            'is_admin' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($nonAdmin)->delete(route('hq.users.destroy', $this->targetUser->id));

        // AdminMiddleware redirects non-admins
        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull(User::find($this->targetUser->id));
    }

    /**
     * 7. Test that unauthenticated guests cannot call the removal endpoint.
     */
    public function test_guest_cannot_remove_users()
    {
        $response = $this->delete(route('hq.users.destroy', $this->targetUser->id));

        $response->assertRedirect(route('login'));
        $this->assertNotNull(User::find($this->targetUser->id));
    }

    /**
     * 8. Test that invalid user IDs are safely rejected.
     */
    public function test_invalid_user_id_is_rejected()
    {
        $response = $this->actingAs($this->admin)->delete(route('hq.users.destroy', 'invalid_id'));
        $response->assertSessionHas('error');
    }
}
