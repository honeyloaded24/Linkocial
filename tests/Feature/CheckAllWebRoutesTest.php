<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Order;
use App\Models\VtuOrder;
use App\Models\VirtualNumberOrder;
use App\Models\Ticket;
use App\Models\LogProduct;
use App\Models\LogOrder;
use Illuminate\Support\Facades\Route;

class CheckAllWebRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Config::set('database.connections.sqlite.database', database_path('database.sqlite'));
        \Illuminate\Support\Facades\DB::purge('sqlite');
    }

    public function test_scan_all_get_routes_for_500_errors()
    {
        // Find or create admin and regular user from the real sqlite database
        $admin = User::where('role', 'admin')->orWhere('is_admin', true)->first();
        if (!$admin) {
            $admin = User::firstOrCreate(
                ['email' => 'admin@linkocial.test'],
                [
                    'first_name' => 'Admin',
                    'last_name' => 'Linkocial',
                    'username' => 'admin',
                    'password' => bcrypt('password'),
                    'role' => 'admin',
                    'is_admin' => true,
                    'status' => 'active',
                ]
            );
        }

        $user = User::where('role', 'user')->where('is_admin', false)->first();
        if (!$user) {
            $user = User::firstOrCreate(
                ['email' => 'user@linkocial.test'],
                [
                    'first_name' => 'Regular',
                    'last_name' => 'User',
                    'username' => 'reguser',
                    'password' => bcrypt('password'),
                    'role' => 'user',
                    'is_admin' => false,
                    'status' => 'active',
                ]
            );
        }

        $routes = Route::getRoutes()->getRoutesByMethod()['GET'] ?? [];
        $failedRoutes = [];
        $testedCount = 0;

        foreach ($routes as $uri => $route) {
            // Determine parameter substitutes
            $path = $uri;
            if (str_contains($path, '{')) {
                // Try substituting standard parameters
                $userParam = $user ? $user->id : 1;
                $orderParam = Order::first()?->id ?? 1;
                $serviceParam = Service::first()?->id ?? 1;
                $ticketParam = Ticket::first()?->id ?? 1;
                $logProductParam = LogProduct::first()?->id ?? 1;

                $path = str_replace(
                    ['{user}', '{id}', '{order}', '{service}', '{ticket}', '{provider}', '{slug}', '{product}'],
                    [$userParam, 1, $orderParam, $serviceParam, $ticketParam, 1, 'general', $logProductParam],
                    $path
                );

                if (str_contains($path, '{')) {
                    // Skip any unhandled complex params
                    continue;
                }
            }

            $testedCount++;
            $actor = str_starts_with($uri, 'hq') ? $admin : $user;

            try {
                $response = $this->actingAs($actor)->get('/' . ltrim($path, '/'));
                $status = $response->getStatusCode();
                if ($status >= 500) {
                    $failedRoutes[] = [
                        'route' => $uri,
                        'path' => $path,
                        'status' => $status,
                        'error' => 'Returned HTTP ' . $status,
                    ];
                    dump("FAILED [{$status}]: /{$path} (Route: {$uri})");
                }
            } catch (\Throwable $e) {
                $failedRoutes[] = [
                    'route' => $uri,
                    'path' => $path,
                    'status' => 500,
                    'error' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                ];
                dump("EXCEPTION: /{$path} -> " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")");
            }
        }

        // Also test public guest access on public routes
        $publicRoutes = [
            '/',
            '/login',
            '/register',
            '/forgot-password',
            '/services',
            '/contact',
            '/terms',
            '/privacy',
            '/api/v1',
            '/api',
            '/api/v1/public-stats',
            '/about',
            '/blog',
            '/faq',
        ];

        foreach ($publicRoutes as $pubUri) {
            try {
                $response = $this->get($pubUri);
                $status = $response->getStatusCode();
                if ($status >= 500) {
                    $failedRoutes[] = [
                        'route' => $pubUri,
                        'path' => $pubUri,
                        'status' => $status,
                        'error' => 'Public route returned HTTP ' . $status,
                    ];
                }
            } catch (\Throwable $e) {
                $failedRoutes[] = [
                    'route' => $pubUri,
                    'path' => $pubUri,
                    'status' => 500,
                    'error' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                ];
            }
        }

        dump("Scanned {$testedCount} GET routes. 500 errors found: " . count($failedRoutes));
        $this->assertEmpty($failedRoutes, "Found " . count($failedRoutes) . " routes returning 500: " . json_encode($failedRoutes));
    }
}
