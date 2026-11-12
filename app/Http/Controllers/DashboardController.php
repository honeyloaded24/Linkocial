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

        // 1. Calculate stats using authoritative FinancialMetricsService
        $financialService = app(\App\Services\FinancialMetricsService::class);
        $totalSpent = $financialService->getUserTotalSpent($user->id);
        $totalDeposits = $financialService->getUserTotalDeposits($user->id);

        $aiActiveOrders = 0;
        $aiCompletedOrders = 0;
        try {
            if (class_exists(\App\Models\AiOrder::class) && \Illuminate\Support\Facades\Schema::hasTable('ai_orders')) {
                $aiActiveOrders = \App\Models\AiOrder::where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'processing', 'awaiting_manual'])
                    ->count();
                $aiCompletedOrders = \App\Models\AiOrder::where('user_id', $user->id)
                    ->where('status', 'fulfilled')
                    ->count();
            }
        } catch (\Throwable $e) {
            $aiActiveOrders = 0;
            $aiCompletedOrders = 0;
        }

        $pendingLogOrders = 0;
        $completedLogOrders = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('log_orders')) {
            $pendingLogOrders = \App\Models\LogOrder::where('user_id', $user->id)
                ->where('status', 'pending')
                ->count();
            $completedLogOrders = \App\Models\LogOrder::where('user_id', $user->id)
                ->where('status', 'completed')
                ->count();
        }

        $activeOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing', 'in_progress'])
            ->count() 
            + \App\Models\VirtualNumberOrder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count()
            + \App\Models\VtuOrder::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->count()
            + $pendingLogOrders
            + $aiActiveOrders;

        $completedOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'partial'])
            ->count() 
            + \App\Models\VirtualNumberOrder::where('user_id', $user->id)
            ->whereIn('status', ['received', 'finished'])
            ->count()
            + \App\Models\VtuOrder::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count()
            + $completedLogOrders
            + $aiCompletedOrders;

        // 2. Fetch recent activities
        $recentActivities = \App\Models\Transaction::where('user_id', $user->id)
            ->ordered()
            ->take(5)
            ->get();

        // 3. Calculate growth using consistent financial calculation
        $thisWeekSpent = $financialService->getUserSpentPeriod($user->id, now()->startOfWeek());
        $lastWeekSpent = $financialService->getUserSpentPeriod($user->id, now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek());

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
