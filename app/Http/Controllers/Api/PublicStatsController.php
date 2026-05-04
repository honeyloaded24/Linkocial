<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PublicStatsController extends Controller
{
    /**
     * Return public platform statistics and branding assets.
     * This endpoint is used by the separate marketing site to display live data.
     */
    public function index()
    {
        // We cache this for 5 minutes to ensure high performance on the landing page
        return \Illuminate\Support\Facades\Cache::remember('public_stats', 300, function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'site_name' => \App\Models\Setting::get('site_name', 'Linkocial'),
                    'site_logo_dark' => \App\Models\Setting::get('site_logo_dark'),
                    'site_logo_light' => \App\Models\Setting::get('site_logo_light'),
                    'site_favicon' => \App\Models\Setting::get('site_favicon'),
                    'stats' => [
                        'total_orders' => \App\Models\Order::count() + 1000, // Adding a base offset for marketing
                        'active_users' => \App\Models\User::count() + 250,
                        'total_services' => \App\Models\Service::where('is_active', true)->count(),
                    ],
                    'links' => [
                        'login' => route('login'),
                        'register' => route('register'),
                    ]
                ]
            ]);
        });
    }
}
