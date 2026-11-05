<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LogCategory;
use App\Models\LogProduct;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class MarketplaceAllCategoriesViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:6Cu/hK4AZGsLNd5d0v9676W8/Y3aR5A5nL3x02H8/Yo=']);

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {}

        $this->customer = (new User())->forceFill([
            'first_name' => 'Marketplace',
            'last_name' => 'Tester',
            'username' => 'mktester',
            'email' => 'mktester@linkocial.test',
            'email_verified_at' => now(),
            'status' => 'active',
            'password' => bcrypt('password'),
            'role' => 'user',
            'is_admin' => false,
        ]);
        $this->customer->save();
    }

    protected function createProduct(array $attributes = []): LogProduct
    {
        static $seq = 1000;
        $seq++;
        return LogProduct::create(array_merge([
            'api_product_id' => $seq,
            'api_product_key' => 'prod_' . $seq,
            'name' => 'Sample Product ' . $seq,
            'type' => 'local',
            'base_api_price' => 0,
            'unit_price' => 2000,
            'our_price' => 2500,
            'in_stock' => 5,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Test 1: Visiting marketplace defaults to All Categories tab with active styling and all categories displayed.
     */
    public function test_marketplace_displays_all_categories_view_by_default(): void
    {
        $catInstagram = LogCategory::create(['name' => 'Instagram', 'is_active' => true]);
        $catTikTok = LogCategory::create(['name' => 'TikTok', 'is_active' => true]);
        $catFacebook = LogCategory::create(['name' => 'Facebook', 'is_active' => true]);

        $this->createProduct([
            'name' => 'Instagram Aged 2018 Account',
            'log_category_id' => $catInstagram->id,
            'in_stock' => 5,
        ]);

        $response = $this->actingAs($this->customer)->get(route('logs.index'));

        $response->assertStatus(200);
        // Verify mode navigation tabs exist
        $response->assertSee('All Categories');
        $response->assertSee('All Products');
        $response->assertSee('tab-btn-categories');
        $response->assertSee('tab-btn-products');

        // Verify categories are listed in the grid view
        $response->assertSee('all-categories-view');
        $response->assertSee('Instagram');
        $response->assertSee('TikTok');
        $response->assertSee('Facebook');

        // Verify category card icon is rendered
        $response->assertSee('fa-brands fa-instagram');
        $response->assertSee('fa-brands fa-tiktok');
        $response->assertSee('fa-brands fa-facebook-f');
    }

    /**
     * Test 2: Category cards display exact dynamic product count matching database relationships.
     */
    public function test_category_cards_display_accurate_product_counts(): void
    {
        $catInstagram = LogCategory::create(['name' => 'Instagram', 'is_active' => true]);
        $catTwitter = LogCategory::create(['name' => 'Twitter/X', 'is_active' => true]);

        // 3 active Instagram products
        for ($i = 1; $i <= 3; $i++) {
            $this->createProduct([
                'name' => "Instagram Account $i",
                'log_category_id' => $catInstagram->id,
                'in_stock' => 10,
                'is_active' => true,
            ]);
        }

        // 1 inactive Instagram product (must NOT count towards active product count)
        $this->createProduct([
            'name' => 'Inactive IG Account',
            'log_category_id' => $catInstagram->id,
            'in_stock' => 10,
            'is_active' => false,
        ]);

        // 1 active Twitter product
        $this->createProduct([
            'name' => 'Twitter Verified Account',
            'log_category_id' => $catTwitter->id,
            'in_stock' => 2,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->customer)->get(route('logs.index', ['tab' => 'categories']));

        $response->assertStatus(200);
        // Instagram should display 3 products (plural)
        $response->assertSee('3 products');
        // Twitter should display 1 product (singular)
        $response->assertSee('1 product');

        $this->assertEquals(3, $catInstagram->active_products_count);
        $this->assertEquals(1, $catTwitter->active_products_count);
    }

    /**
     * Test 3: Clicking a category card links to All Products filtered by that category.
     */
    public function test_category_card_links_to_filtered_products(): void
    {
        $catFacebook = LogCategory::create(['name' => 'Facebook', 'is_active' => true]);
        $prodFB = $this->createProduct([
            'name' => 'Facebook Aged Profile',
            'log_category_id' => $catFacebook->id,
            'in_stock' => 8,
            'is_active' => true,
        ]);

        $catInstagram = LogCategory::create(['name' => 'Instagram', 'is_active' => true]);
        $prodIG = $this->createProduct([
            'name' => 'Instagram Blue Check',
            'log_category_id' => $catInstagram->id,
            'in_stock' => 2,
            'is_active' => true,
        ]);

        // Request with category filter
        $response = $this->actingAs($this->customer)->get(route('logs.index', [
            'category' => $catFacebook->id,
            'tab' => 'products'
        ]));

        $response->assertStatus(200);
        // Should show the products view active
        $response->assertSee('all-products-view');
        // Filtered product should appear
        $response->assertSee('Facebook Aged Profile');
        // Non-matching product should NOT appear
        $response->assertDontSee('Instagram Blue Check');
    }

    /**
     * Test 4: All Products tab displays all products and preserves existing pagination and hot picks.
     */
    public function test_all_products_tab_displays_all_catalogue_products(): void
    {
        $cat = LogCategory::create(['name' => 'Streaming', 'is_active' => true]);
        
        $prod1 = $this->createProduct([
            'name' => 'Netflix Premium 4K Account',
            'log_category_id' => $cat->id,
            'in_stock' => 20,
            'is_active' => true,
        ]);

        $prod2 = $this->createProduct([
            'name' => 'Spotify Lifetime Family Slot',
            'log_category_id' => $cat->id,
            'in_stock' => 15,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->customer)->get(route('logs.index', ['tab' => 'products']));

        $response->assertStatus(200);
        $response->assertSee('Netflix Premium 4K Account');
        $response->assertSee('Spotify Lifetime Family Slot');
        $response->assertSee('HOT PICKS 🔥');
        $response->assertSee('marketplace-category-tabs');
    }

    /**
     * Test 5: Category card responsiveness uses two columns on mobile and responsive layout on larger screens.
     */
    public function test_category_grid_structure_and_responsive_classes(): void
    {
        LogCategory::create(['name' => 'Telegram', 'is_active' => true]);
        LogCategory::create(['name' => 'WhatsApp', 'is_active' => true]);

        $response = $this->actingAs($this->customer)->get(route('logs.index', ['tab' => 'categories']));

        $response->assertStatus(200);
        // Grid specifies row-cols-2 for mobile devices
        $response->assertSee('row-cols-2');
        $response->assertSee('category-card');
        $response->assertSee('category-card-icon');
        $response->assertSee('category-card-name');
        $response->assertSee('category-card-count');
    }

    /**
     * Test 6: Switching between categories via AJAX returns JSON payload with catalogue HTML.
     */
    public function test_ajax_category_switch_continues_to_function_seamlessly(): void
    {
        $cat = LogCategory::create(['name' => 'Discord Accounts', 'is_active' => true]);
        $prod = $this->createProduct([
            'name' => 'Discord Early Supporter Badge',
            'log_category_id' => $cat->id,
            'in_stock' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->customer)->get(route('logs.index', ['category' => $cat->id]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'category_id' => $cat->id,
            'category_name' => 'Discord Accounts',
            'total' => 1,
        ]);
        $this->assertStringContainsString('Discord Early Supporter Badge', $response->json('html'));
    }

    /**
     * Test 7: Category model getIconHtml returns correct platform icons and styling.
     */
    public function test_log_category_get_icon_html_auto_detection(): void
    {
        $ig = new LogCategory(['name' => 'Instagram Logs']);
        $this->assertStringContainsString('fa-brands fa-instagram', $ig->getIconHtml());

        $tt = new LogCategory(['name' => 'TikTok Accounts']);
        $this->assertStringContainsString('fa-brands fa-tiktok', $tt->getIconHtml());

        $snap = new LogCategory(['name' => 'Snapchat Scores']);
        $this->assertStringContainsString('fa-brands fa-snapchat', $snap->getIconHtml());

        $wa = new LogCategory(['name' => 'WhatsApp Channels']);
        $this->assertStringContainsString('fa-brands fa-whatsapp', $wa->getIconHtml());

        $gen = new LogCategory(['name' => 'Custom Specialty Category']);
        $this->assertStringContainsString('fa-solid fa-layer-group', $gen->getIconHtml());
    }
}
