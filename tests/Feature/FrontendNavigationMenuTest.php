<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\VtuOrder;
use App\Models\Service;
use App\Models\Provider;
use Illuminate\Support\Facades\Artisan;

class FrontendNavigationMenuTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            // Ignore in test env
        }

        $this->user = User::firstOrCreate(
            ['email' => 'nav_menu_test@linkocial.test'],
            [
                'id' => 9888,
                'first_name' => 'Nav',
                'last_name' => 'Tester',
                'name' => 'Nav Menu Tester',
                'username' => 'navmenu_tester',
                'password' => bcrypt('secret123'),
                'balance' => 5000.00,
                'currency_code' => 'NGN',
                'email_verified_at' => now(),
            ]
        );

        $this->user->markEmailAsVerified();
    }

    /**
     * Test 1: Main navigation menu contains BOOST and VTU, and does NOT contain standalone Order History or Logs Purchases.
     */
    public function test_main_navigation_menu_structure(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);

        // 1. Confirm NEW ORDER is replaced by BOOST
        $response->assertSee('BOOST');
        $response->assertSee(route('orders.create'));
        $response->assertDontSee('New Order</a>', false);

        // 2. Confirm VTU is present as a top-level menu item
        $response->assertSee('VTU');
        $response->assertSee(route('vtu.index'));

        // 3. Confirm standalone Order History is NOT in the main navigation
        // Main menu navigation should not have the standalone "Order History" link
        $content = $response->getContent();
        $this->assertStringNotContainsString('<i class="fa-solid fa-list-ul"></i> Order History', $content);

        // 4. Confirm standalone Logs Purchases is NOT in the main navigation
        $this->assertStringNotContainsString('<i class="fa-solid fa-clock-rotate-left"></i> Logs Purchases', $content);

        // 5. Confirm no dropdown/submenu for Order History exists in the user menu
        $this->assertStringNotContainsString('id="boostSubmenu"', $content);
        $this->assertStringNotContainsString('id="vtuSubmenu"', $content);
    }

    /**
     * Test 2: BOOST order placement page provides in-page access to Order History.
     */
    public function test_boost_page_provides_order_history_access(): void
    {
        $response = $this->actingAs($this->user)->get(route('orders.create'));

        $response->assertStatus(200);
        $response->assertSee('BOOST');
        $response->assertSee(route('orders.index'));
        $response->assertSee('Order History');
    }

    /**
     * Test 3: BOOST order history page loads properly and links back to BOOST order creation.
     */
    public function test_boost_order_history_page_loads_and_links_to_create(): void
    {
        $response = $this->actingAs($this->user)->get(route('orders.index'));

        $response->assertStatus(200);
        $response->assertSee('BOOST');
        $response->assertSee(route('orders.create'));
    }

    /**
     * Test 4: VTU main page loads and provides in-page access to VTU Order History.
     */
    public function test_vtu_page_provides_order_history_access(): void
    {
        $response = $this->actingAs($this->user)->get(route('vtu.index'));

        $response->assertStatus(200);
        $response->assertSee(route('vtu.history'));
        $response->assertSee('VTU Order History');
    }

    /**
     * Test 5: VTU Order History page displays only VTU orders and remains separate from BOOST orders.
     */
    public function test_vtu_order_history_displays_only_vtu_orders(): void
    {
        // Create a VTU order for user
        $vtuOrder = VtuOrder::create([
            'user_id' => $this->user->id,
            'service_type' => 'airtime',
            'network' => 'MTN',
            'phone_or_meter' => '08012345678',
            'plan_or_amount' => '500',
            'cost' => 500.00,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)->get(route('vtu.history'));

        $response->assertStatus(200);
        $response->assertSee('VTU Order History');
        $response->assertSee('08012345678');
        $response->assertSee('Airtime');
        $response->assertSee('MTN');

        // Confirm VTU page links back to VTU services
        $response->assertSee(route('vtu.airtime'));
        $response->assertSee(route('vtu.data'));
    }

    /**
     * Test 6: BOOST order history does not mix VTU orders, keeping them strictly separated.
     */
    public function test_boost_and_vtu_orders_remain_separate(): void
    {
        $uniqueVtuPhone = '09099887766';
        VtuOrder::create([
            'user_id' => $this->user->id,
            'service_type' => 'data',
            'network' => 'GLO',
            'phone_or_meter' => $uniqueVtuPhone,
            'plan_or_amount' => '1GB',
            'cost' => 300.00,
            'status' => 'completed',
        ]);

        // BOOST history should NOT show VTU phone number
        $boostResponse = $this->actingAs($this->user)->get(route('orders.index'));
        $boostResponse->assertStatus(200);
        $boostResponse->assertDontSee($uniqueVtuPhone);

        // VTU history should show VTU phone number
        $vtuResponse = $this->actingAs($this->user)->get(route('vtu.history'));
        $vtuResponse->assertStatus(200);
        $vtuResponse->assertSee($uniqueVtuPhone);
    }
}
