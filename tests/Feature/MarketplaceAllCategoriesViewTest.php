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

        // Verify accessibility and WAI-ARIA tab semantics
        $response->assertSee('role="tablist"', false);
        $response->assertSee('role="tab"', false);
        $response->assertSee('aria-selected="true"', false);
        $response->assertSee('aria-controls="all-categories-view"', false);
        $response->assertSee('role="tabpanel"', false);

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
     * Test 7: Category model getIconHtml returns correct platform icons using word boundaries without false positives.
     */
    public function test_log_category_get_icon_html_auto_detection(): void
    {
        // Platforms matching with word boundaries
        $ig = new LogCategory(['name' => 'Instagram Logs']);
        $this->assertStringContainsString('fa-brands fa-instagram', $ig->getIconHtml());

        $igWord = new LogCategory(['name' => 'IG Aged Accounts']);
        $this->assertStringContainsString('fa-brands fa-instagram', $igWord->getIconHtml());

        $tt = new LogCategory(['name' => 'TikTok Accounts']);
        $this->assertStringContainsString('fa-brands fa-tiktok', $tt->getIconHtml());

        $fb = new LogCategory(['name' => 'FB Marketplace Verified']);
        $this->assertStringContainsString('fa-brands fa-facebook-f', $fb->getIconHtml());

        $twitter = new LogCategory(['name' => 'Twitter/X Accounts']);
        $this->assertStringContainsString('fa-brands fa-x-twitter', $twitter->getIconHtml());

        $snap = new LogCategory(['name' => 'Snapchat Scores']);
        $this->assertStringContainsString('fa-brands fa-snapchat', $snap->getIconHtml());

        $wa = new LogCategory(['name' => 'WhatsApp Channels']);
        $this->assertStringContainsString('fa-brands fa-whatsapp', $wa->getIconHtml());

        $tg = new LogCategory(['name' => 'TG Premium Subscribers']);
        $this->assertStringContainsString('fa-brands fa-telegram', $tg->getIconHtml());

        // False positive prevention: ambiguous words should NOT trigger platforms
        $digital = new LogCategory(['name' => 'Digital Marketing Services']);
        $this->assertStringNotContainsString('fa-brands fa-instagram', $digital->getIconHtml());
        $this->assertStringContainsString('fa-solid fa-layer-group', $digital->getIconHtml());

        $chatGpt = new LogCategory(['name' => 'ChatGPT Plus Accounts']);
        $this->assertStringNotContainsString('fa-brands fa-telegram', $chatGpt->getIconHtml());
        $this->assertStringContainsString('fa-solid fa-layer-group', $chatGpt->getIconHtml());

        $wallet = new LogCategory(['name' => 'Wallet Recovery Guides']);
        $this->assertStringNotContainsString('fa-brands fa-whatsapp', $wallet->getIconHtml());
        $this->assertStringContainsString('fa-solid fa-layer-group', $wallet->getIconHtml());

        $software = new LogCategory(['name' => 'Software License Keys']);
        $this->assertStringNotContainsString('fa-brands fa-whatsapp', $software->getIconHtml());
        $this->assertStringContainsString('fa-solid fa-layer-group', $software->getIconHtml());
    }

    /**
     * Test 8: Malicious icon values (XSS, javascript:, invalid classes, directory traversal) are sanitized and neutralized.
     */
    public function test_malicious_icon_values_are_sanitized_and_neutralized(): void
    {
        // 1. Script tag injection in icon attribute
        $xssCategory = new LogCategory(['name' => 'XSS Test']);
        $xssCategory->icon = '<script>alert("xss")</script>';
        $html1 = $xssCategory->getIconHtml();
        $this->assertStringNotContainsString('<script>', $html1);
        $this->assertStringNotContainsString('alert(', $html1);

        // 2. Attribute breakout in icon
        $attrBreakout = new LogCategory(['name' => 'Breakout Test']);
        $attrBreakout->icon = '"><img src=x onerror=alert(1)>';
        $html2 = $attrBreakout->getIconHtml();
        $this->assertStringNotContainsString('onerror', $html2);
        $this->assertStringNotContainsString('<script>', $html2);

        // 3. javascript: URI in icon
        $jsCategory = new LogCategory(['name' => 'JS URI Test']);
        $jsCategory->icon = 'javascript:alert(document.cookie)';
        $html3 = $jsCategory->getIconHtml();
        $this->assertStringNotContainsString('javascript:', $html3);

        // 4. Invalid Font Awesome class containing malicious characters
        $maliciousFa = new LogCategory(['name' => 'Malicious FA']);
        $maliciousFa->icon = 'fa-solid fa-user" onmouseover="alert(1)"';
        $html4 = $maliciousFa->getIconHtml();
        $this->assertStringNotContainsString('onmouseover', $html4);

        // 5. Directory traversal in local icon path
        $traversal = new LogCategory(['name' => 'Traversal Test']);
        $traversal->icon = '/storage/../../etc/passwd';
        $html5 = $traversal->getIconHtml();
        $this->assertStringNotContainsString('etc/passwd', $html5);

        // 6. Valid safe local storage path is allowed and properly escaped
        $validLocal = new LogCategory(['name' => 'Valid Local Icon']);
        $validLocal->icon = 'storage/categories/custom_badge.png';
        $html6 = $validLocal->getIconHtml();
        $this->assertStringContainsString('<img src=', $html6);
        $this->assertStringContainsString('storage/categories/custom_badge.png', $html6);

        // 7. Valid safe Font Awesome class is allowed
        $validFa = new LogCategory(['name' => 'Valid FA Icon']);
        $validFa->icon = 'fa-solid fa-shield-halved';
        $html7 = $validFa->getIconHtml();
        $this->assertStringContainsString('fa-solid fa-shield-halved', $html7);

        // 8. Valid safe HTTPS URL is allowed and escaped
        $validUrl = new LogCategory(['name' => 'Valid Remote Icon']);
        $validUrl->icon = 'https://linkocial.com/assets/images/sample.png';
        $html8 = $validUrl->getIconHtml();
        $this->assertStringContainsString('<img src="https://linkocial.com/assets/images/sample.png"', $html8);
    }

    /**
     * Test 9: Active products count explicitly counts is_active = true and never trusts unfiltered products_count.
     */
    public function test_active_products_count_ignores_unfiltered_products_count(): void
    {
        $cat = LogCategory::create(['name' => 'Reliability Test Category', 'is_active' => true]);

        // 2 active products
        $this->createProduct([
            'name' => 'Active Product 1',
            'log_category_id' => $cat->id,
            'is_active' => true,
        ]);
        $this->createProduct([
            'name' => 'Active Product 2',
            'log_category_id' => $cat->id,
            'is_active' => true,
        ]);

        // 3 inactive products
        for ($j = 1; $j <= 3; $j++) {
            $this->createProduct([
                'name' => 'Inactive Product ' . $j,
                'log_category_id' => $cat->id,
                'is_active' => false,
            ]);
        }

        // Simulate a query that loaded total products_count = 5 (without is_active filter)
        $simulatedUnfilteredCat = clone $cat;
        $simulatedUnfilteredCat->setAttribute('products_count', 5);

        // Even with products_count = 5 in attributes, active_products_count MUST strictly be 2!
        $this->assertEquals(2, $simulatedUnfilteredCat->active_products_count);

        // Test in-memory collection counting when relations are loaded
        $eagerLoadedCat = LogCategory::with('products')->find($cat->id);
        $this->assertCount(5, $eagerLoadedCat->products);
        $this->assertEquals(2, $eagerLoadedCat->active_products_count);

        // Test eager-loaded constrained active_products_count
        $queryLoadedCat = LogCategory::where('id', $cat->id)
            ->withCount(['products as active_products_count' => fn($q) => $q->where('is_active', true)])
            ->first();
        $this->assertEquals(2, $queryLoadedCat->active_products_count);
    }

    /**
     * Test 10: Direct URLs with category, search, or pagination resolve activeTab to products and preserve state.
     */
    public function test_direct_category_urls_and_tab_state_resolution(): void
    {
        $cat = LogCategory::create(['name' => 'Telegram Direct', 'is_active' => true]);
        $prod = $this->createProduct([
            'name' => 'Telegram Direct Product',
            'log_category_id' => $cat->id,
            'is_active' => true,
        ]);

        // Direct request with category parameter alone resolves to products view
        $response = $this->actingAs($this->customer)->get(route('logs.index', ['category' => $cat->id]));
        $response->assertStatus(200);
        $response->assertSee('Telegram Direct Product');
        $response->assertSee('aria-controls="all-products-view"', false);
        $response->assertSee('tab-btn-products', false);

        // Direct request with search parameter alone resolves to products view
        $responseSearch = $this->actingAs($this->customer)->get(route('logs.index', ['search' => 'Direct']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Telegram Direct Product');

        // Request with explicit tab=categories stays in categories view even if category list exists
        $responseCatTab = $this->actingAs($this->customer)->get(route('logs.index', ['tab' => 'categories']));
        $responseCatTab->assertStatus(200);
        $responseCatTab->assertSee('Telegram Direct');
        $responseCatTab->assertSee('aria-controls="all-categories-view"', false);
        $responseCatTab->assertSee('tab-btn-categories', false);
    }
}
