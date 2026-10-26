<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LogCategory;
use App\Models\LogProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogCategoryDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'role' => 'admin',
        ]);

        $this->regularUser = User::factory()->create([
            'is_admin' => false,
            'role' => 'user',
        ]);
    }

    /**
     * Test that an authorized administrator can delete an empty category.
     */
    public function test_authorized_admin_can_delete_empty_category(): void
    {
        $category = LogCategory::create([
            'name' => 'Empty Social Accounts',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('hq.logs.category.delete', $category->id));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('log_categories', [
            'id' => $category->id,
        ]);
    }

    /**
     * Test that a category containing products cannot be deleted without reassignment,
     * protecting products from accidental data loss.
     */
    public function test_category_containing_products_blocks_deletion_by_default(): void
    {
        $category = LogCategory::create([
            'name' => 'Protected Category',
            'is_active' => true,
        ]);

        $product1 = LogProduct::create([
            'type' => 'local',
            'log_category_id' => $category->id,
            'name' => 'Aged Facebook Profile',
            'unit_price' => 1500.00,
            'our_price' => 1500.00,
            'is_active' => true,
        ]);

        $product2 = LogProduct::create([
            'type' => 'local',
            'log_category_id' => $category->id,
            'name' => 'Verified Twitter Account',
            'unit_price' => 2000.00,
            'our_price' => 2000.00,
            'is_active' => true,
        ]);

        // Attempt deletion without reassign_to
        $response = $this->actingAs($this->admin)
            ->delete(route('hq.logs.category.delete', $category->id));

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        // Both the category and the products MUST still exist
        $this->assertDatabaseHas('log_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('log_products', ['id' => $product1->id, 'log_category_id' => $category->id]);
        $this->assertDatabaseHas('log_products', ['id' => $product2->id, 'log_category_id' => $category->id]);
    }

    /**
     * Test that a category with products CAN be safely deleted when a target category is specified for reassignment,
     * transferring products seamlessly without deleting them.
     */
    public function test_category_containing_products_can_be_safely_deleted_when_reassigned(): void
    {
        $oldCategory = LogCategory::create([
            'name' => 'Old Legacy Category',
            'is_active' => true,
        ]);

        $newCategory = LogCategory::create([
            'name' => 'Consolidated Social Category',
            'is_active' => true,
        ]);

        $product = LogProduct::create([
            'type' => 'local',
            'log_category_id' => $oldCategory->id,
            'name' => 'Instagram 10k Account',
            'unit_price' => 5000.00,
            'our_price' => 5000.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('hq.logs.category.delete', $oldCategory->id), [
                'reassign_to' => $newCategory->id,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Old category is deleted
        $this->assertDatabaseMissing('log_categories', ['id' => $oldCategory->id]);

        // New category exists
        $this->assertDatabaseHas('log_categories', ['id' => $newCategory->id]);

        // Product is preserved and reassigned to newCategory
        $this->assertDatabaseHas('log_products', [
            'id' => $product->id,
            'log_category_id' => $newCategory->id,
        ]);
    }

    /**
     * Test that attempting to delete a nonexistent category produces a friendly error.
     */
    public function test_nonexistent_category_returns_friendly_error(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('hq.logs.category.delete', 999999));

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * Test that unauthorized users (guests and non-admins) cannot delete categories.
     */
    public function test_unauthorized_user_cannot_delete_category(): void
    {
        $category = LogCategory::create([
            'name' => 'Admin Only Category',
            'is_active' => true,
        ]);

        // 1. Guest request -> redirected to login
        $guestResponse = $this->delete(route('hq.logs.category.delete', $category->id));
        $guestResponse->assertRedirect(route('login'));

        // 2. Regular customer request -> forbidden or redirected
        $userResponse = $this->actingAs($this->regularUser)
            ->delete(route('hq.logs.category.delete', $category->id));

        $this->assertTrue(in_array($userResponse->status(), [302, 403]));

        // Category remains in database
        $this->assertDatabaseHas('log_categories', ['id' => $category->id]);
    }
}
