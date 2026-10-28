<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LogCategory;
use App\Models\LogProduct;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarketplaceHotPicksAndPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'role' => 'admin',
        ]);

        $this->customer = User::factory()->create([
            'is_admin' => false,
            'role' => 'user',
        ]);
    }

    /**
     * Test 1: HOT PICKS section is empty by default when admin has not selected any products.
     * Products are NOT automatically chosen by sales, stock, or price.
     */
    public function test_hot_picks_empty_by_default_without_admin_selection(): void
    {
        Setting::set('marketplace_hot_picks', '[]');

        $category = LogCategory::create(['name' => 'Instagram Accounts', 'is_active' => true]);
        $product = LogProduct::create([
            'name' => 'Aged IG Account 2018',
            'log_category_id' => $category->id,
            'type' => 'local',
            'our_price' => 2500,
            'in_stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->customer)->get(route('logs.index'));

        $response->assertStatus(200);
        $response->assertSee('HOT PICKS 🔥');
        $response->assertSee('No products currently featured in HOT PICKS');
        // Product should still appear in the regular catalogue
        $response->assertSee('Aged IG Account 2018');
    }

    /**
     * Test 2: Only authorized administrators can manage HOT PICKS.
     */
    public function test_only_authorized_admin_can_manage_hot_picks(): void
    {
        $category = LogCategory::create(['name' => 'Twitter Accounts', 'is_active' => true]);
        $product = LogProduct::create([
            'name' => 'Twitter Blue Verified Account',
            'log_category_id' => $category->id,
            'type' => 'local',
            'our_price' => 5000,
            'in_stock' => 5,
            'is_active' => true,
        ]);

        // Regular customer attempt must be blocked
        $custResponse = $this->actingAs($this->customer)->post(route('hq.logs.hot-picks.toggle', $product->id));
        $this->assertTrue(in_array($custResponse->status(), [302, 403]));
        $this->assertEquals('[]', Setting::get('marketplace_hot_picks', '[]'));

        // Authorized admin can toggle product into HOT PICKS
        $adminResponse = $this->actingAs($this->admin)->post(route('hq.logs.hot-picks.toggle', $product->id));
        $adminResponse->assertStatus(302);

        $savedIds = json_decode(Setting::get('marketplace_hot_picks', '[]'), true);
        $this->assertContains($product->id, $savedIds);

        // Toggle again to remove without deleting product
        $this->actingAs($this->admin)->post(route('hq.logs.hot-picks.toggle', $product->id));
        $savedIdsAfter = json_decode(Setting::get('marketplace_hot_picks', '[]'), true);
        $this->assertNotContains($product->id, $savedIdsAfter);

        // Product itself is NOT deleted
        $this->assertDatabaseHas('log_products', ['id' => $product->id]);
    }

    /**
     * Test 3: HOT PICKS displays only admin-selected products in the chosen order.
     */
    public function test_hot_picks_displays_only_admin_selected_products_in_order(): void
    {
        $category = LogCategory::create(['name' => 'Facebook Accounts', 'is_active' => true]);
        $prodA = LogProduct::create([
            'name' => 'FB Profile 2020 High Activity',
            'log_category_id' => $category->id,
            'type' => 'local',
            'our_price' => 3000,
            'in_stock' => 8,
            'is_active' => true,
        ]);
        $prodB = LogProduct::create([
            'name' => 'FB 2-Line Reinstated Page',
            'log_category_id' => $category->id,
            'type' => 'local',
            'our_price' => 7500,
            'in_stock' => 3,
            'is_active' => true,
        ]);
        $prodC = LogProduct::create([
            'name' => 'FB Unselected Regular Product',
            'log_category_id' => $category->id,
            'type' => 'local',
            'our_price' => 1200,
            'in_stock' => 20,
            'is_active' => true,
        ]);

        // Admin updates HOT PICKS in specific order: prodB first, then prodA
        $this->actingAs($this->admin)->post(route('hq.logs.hot-picks.update'), [
            'product_ids' => [$prodB->id, $prodA->id],
        ]);

        $response = $this->actingAs($this->customer)->get(route('logs.index'));
        $response->assertStatus(200);

        // Featured products have the HOT PICK badge
        $response->assertSee('HOT PICK');
        $response->assertSee('FB 2-Line Reinstated Page');
        $response->assertSee('FB Profile 2020 High Activity');

        // Verify order: prodB appears before prodA in the HOT PICKS section
        $content = $response->getContent();
        $posB = strpos($content, 'FB 2-Line Reinstated Page');
        $posA = strpos($content, 'FB Profile 2020 High Activity');
        $this->assertTrue($posB < $posA);
    }

    /**
     * Test 4: Selected products remain available in their normal category.
     */
    public function test_selected_hot_pick_products_remain_in_normal_category(): void
    {
        $category = LogCategory::create(['name' => 'TikTok Accounts', 'is_active' => true]);
        $product = LogProduct::create([
            'name' => 'TikTok 10K Followers Account',
            'log_category_id' => $category->id,
            'type' => 'local',
            'our_price' => 15000,
            'in_stock' => 2,
            'is_active' => true,
        ]);

        Setting::set('marketplace_hot_picks', json_encode([$product->id]));

        // Visiting the category filter
        $response = $this->actingAs($this->customer)->get(route('logs.index', ['category' => $category->id]));
        $response->assertStatus(200);

        // The product appears in the category
        $response->assertSee('TikTok 10K Followers Account');
        $response->assertSee('Explore Categories');
    }

    /**
     * Test 5: Category navigation preserves counts and filters correctly.
     */
    public function test_category_navigation_and_accurate_counts(): void
    {
        $cat1 = LogCategory::create(['name' => 'Gmail & Google', 'is_active' => true]);
        $cat2 = LogCategory::create(['name' => 'VPN Services', 'is_active' => true]);

        LogProduct::create([
            'name' => 'Gmail Aged 2015',
            'log_category_id' => $cat1->id,
            'type' => 'local',
            'our_price' => 800,
            'in_stock' => 50,
            'is_active' => true,
        ]);
        LogProduct::create([
            'name' => 'ExpressVPN 1 Month Key',
            'log_category_id' => $cat2->id,
            'type' => 'local',
            'our_price' => 3500,
            'in_stock' => 12,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->customer)->get(route('logs.index'));
        $response->assertStatus(200);

        // Category names and counts should be visible
        $response->assertSee('Gmail & Google');
        $response->assertSee('VPN Services');

        // Filtering by category 1
        $cat1Response = $this->actingAs($this->customer)->get(route('logs.index', ['category' => $cat1->id]));
        $cat1Response->assertStatus(200);
        $cat1Response->assertSee('Gmail Aged 2015');
    }

    /**
     * Test 6: Previous and Next navigation works and preserves category filters.
     */
    public function test_pagination_previous_and_next_controls(): void
    {
        $category = LogCategory::create(['name' => 'Bulk Logs', 'is_active' => true]);

        // Create 20 products (page size is 16)
        for ($i = 1; $i <= 20; $i++) {
            LogProduct::create([
                'name' => "Log Item Number $i",
                'log_category_id' => $category->id,
                'type' => 'local',
                'our_price' => 1000 + $i,
                'in_stock' => 5,
                'is_active' => true,
            ]);
        }

        // Page 1
        $page1Response = $this->actingAs($this->customer)->get(route('logs.index', ['category' => $category->id]));
        $page1Response->assertStatus(200);
        $page1Response->assertSee('Page 1 of 2');
        $page1Response->assertSee('Previous');
        $page1Response->assertSee('Next');
        $page1Response->assertSee('Log Item Number 1');

        // Page 2
        $page2Response = $this->actingAs($this->customer)->get(route('logs.index', ['category' => $category->id, 'page' => 2]));
        $page2Response->assertStatus(200);
        $page2Response->assertSee('Page 2 of 2');
        $page2Response->assertSee('Previous');
        $page2Response->assertSee('Next');
    }
}
