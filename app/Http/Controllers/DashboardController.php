<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard overview.
     */
    public function index()
    {
        $user = Auth::user();

        // 1. Calculate stats
        $smmSpent = Order::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'partial'])
            ->where('is_refunded', false)
            ->sum('charge');

        $vnSpent = \App\Models\VirtualNumberOrder::where('user_id', $user->id)
            ->whereIn('status', ['received', 'finished'])
            ->sum('cost');

        $vtuSpent = \App\Models\VtuOrder::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->sum('cost');

        $logSpent = (float) \App\Models\LogOrder::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'completed'])
            ->sum('total_cost');

        $totalSpent = $smmSpent + $vnSpent + $vtuSpent + $logSpent;

        $totalDeposits = (float) \App\Models\Payment::where('user_id', $user->id)
            ->where('status', 'success')
            ->sum('amount');

        $activeOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing', 'in_progress'])
            ->count() 
            + \App\Models\VirtualNumberOrder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count()
            + \App\Models\VtuOrder::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->count()
            + \App\Models\LogOrder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $completedOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'partial'])
            ->count() 
            + \App\Models\VirtualNumberOrder::where('user_id', $user->id)
            ->whereIn('status', ['received', 'finished'])
            ->count()
            + \App\Models\VtuOrder::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count()
            + \App\Models\LogOrder::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // 2. Fetch recent activities
        $recentActivities = \App\Models\Transaction::where('user_id', $user->id)
            ->ordered()
            ->take(5)
            ->get();

        // 3. Calculate growth (simplified for demo)
        $thisWeekSmmSpent = Order::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'partial'])
            ->where('is_refunded', false)
            ->where('created_at', '>=', now()->startOfWeek())
            ->sum('charge');

        $thisWeekVnSpent = \App\Models\VirtualNumberOrder::where('user_id', $user->id)
            ->whereIn('status', ['received', 'finished'])
            ->where('created_at', '>=', now()->startOfWeek())
            ->sum('cost');

        $thisWeekVtuSpent = \App\Models\VtuOrder::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->where('created_at', '>=', now()->startOfWeek())
            ->sum('cost');

        $thisWeekLogSpent = (float) \App\Models\LogOrder::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'completed'])
            ->where('created_at', '>=', now()->startOfWeek())
            ->sum('total_cost');

        $thisWeekSpent = $thisWeekSmmSpent + $thisWeekVnSpent + $thisWeekVtuSpent + $thisWeekLogSpent;
        
        $lastWeekSmmSpent = Order::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'partial'])
            ->where('is_refunded', false)
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->sum('charge');

        $lastWeekVnSpent = \App\Models\VirtualNumberOrder::where('user_id', $user->id)
            ->whereIn('status', ['received', 'finished'])
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->sum('cost');

        $lastWeekVtuSpent = \App\Models\VtuOrder::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->sum('cost');

        $lastWeekLogSpent = (float) \App\Models\LogOrder::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'completed'])
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->sum('total_cost');

        $lastWeekSpent = $lastWeekSmmSpent + $lastWeekVnSpent + $lastWeekVtuSpent + $lastWeekLogSpent;

        $growth = 0;
        if ($lastWeekSpent > 0) {
            $growth = (($thisWeekSpent - $lastWeekSpent) / $lastWeekSpent) * 100;
        }

        $channels = \App\Models\Service::getChannels();
        
        $sliders = collect();
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('dashboard_sliders')) {
                $sliders = \App\Models\DashboardSlider::where('is_active', true)->orderBy('sort_order')->latest()->get();
            }
        } catch (\Throwable $e) {
            $sliders = collect();
        }

        $conversionService = app(\App\Services\CurrencyConversionService::class);
        $exchangeRate = $conversionService->getUsdToNgnRate();
        $usdBalance = $exchangeRate > 0 ? round(((float)$user->balance) / $exchangeRate, 2) : 0.0;

        return view('dashboard.index', compact(
            'totalSpent', 
            'totalDeposits',
            'activeOrders', 
            'completedOrders', 
            'recentActivities',
            'growth',
            'channels',
            'sliders',
            'exchangeRate',
            'usdBalance'
        ));
    }
    public function services()
    {
        return view('dashboard.services');
    }
}
