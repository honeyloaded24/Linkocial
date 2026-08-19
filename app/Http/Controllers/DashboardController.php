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

        $totalSpent = $smmSpent + $vnSpent;

        $totalDeposits = \App\Models\Payment::where('user_id', $user->id)
            ->where('status', 'success')
            ->sum('amount_usd');

        $activeOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing', 'in_progress'])
            ->count();

        $completedOrders = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // 2. Fetch recent orders
        $recentOrders = Order::where('user_id', $user->id)
            ->with('service')
            ->latest()
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

        $thisWeekSpent = $thisWeekSmmSpent + $thisWeekVnSpent;
        
        $lastWeekSmmSpent = Order::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'partial'])
            ->where('is_refunded', false)
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->sum('charge');

        $lastWeekVnSpent = \App\Models\VirtualNumberOrder::where('user_id', $user->id)
            ->whereIn('status', ['received', 'finished'])
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->sum('cost');

        $lastWeekSpent = $lastWeekSmmSpent + $lastWeekVnSpent;

        $growth = 0;
        if ($lastWeekSpent > 0) {
            $growth = (($thisWeekSpent - $lastWeekSpent) / $lastWeekSpent) * 100;
        }

        $channels = \App\Models\Service::getChannels();

        return view('dashboard.index', compact(
            'totalSpent', 
            'totalDeposits',
            'activeOrders', 
            'completedOrders', 
            'recentOrders',
            'growth',
            'channels'
        ));
    }
}
