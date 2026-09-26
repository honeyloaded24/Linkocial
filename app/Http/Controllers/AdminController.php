<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Service;
use App\Models\Order;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;
use App\Services\SmmProviderService;

class AdminController extends Controller
{
    /**
     * Admin HQ Dashboard Overview
     */
    public function dashboard()
    {
        // User Metrics
        $totalUsers = User::count();
        $totalReferrals = User::whereNotNull('referred_by')->count();
        $totalUserBalance = User::sum('balance'); // Unused deposits

        // Order Metrics (Aggregated SMM + Virtual Numbers + VTU + Logs)
        $totalOrders = Order::count() 
            + \App\Models\VirtualNumberOrder::count()
            + \App\Models\VtuOrder::count()
            + \App\Models\LogOrder::count();

        $pendingOrders = Order::where('status', 'pending')->count() 
            + \App\Models\VirtualNumberOrder::where('status', 'pending')->count()
            + \App\Models\VtuOrder::whereIn('status', ['pending', 'processing'])->count()
            + \App\Models\LogOrder::where('status', 'pending')->count();

        $completedOrders = Order::where('status', 'completed')->count() 
            + \App\Models\VirtualNumberOrder::whereIn('status', ['received', 'finished'])->count()
            + \App\Models\VtuOrder::where('status', 'completed')->count()
            + \App\Models\LogOrder::where('status', 'completed')->count();

        // Financial Metrics (All reporting in USD Master)
        $totalDeposited = \App\Models\Payment::where('status', 'success')->sum('amount_usd');
        
        // SMM Spent (including all active non-refunded orders)
        $smmSpent = Order::whereIn('status', ['pending', 'processing', 'in_progress', 'completed', 'partial'])
            ->where('is_refunded', false)
            ->sum('charge');
            
        $smmCost = Order::whereIn('status', ['pending', 'processing', 'in_progress', 'completed', 'partial'])
            ->where('is_refunded', false)
            ->sum('provider_cost');
        
        // Virtual Numbers Spent (including active non-refunded)
        $vnSpent = \App\Models\VirtualNumberOrder::whereIn('status', ['pending', 'received', 'finished'])->sum('cost');
        $vnCost = \App\Models\VirtualNumberOrder::whereIn('status', ['pending', 'received', 'finished'])->sum('provider_cost');

        // VTU Spent
        $vtuSpent = \App\Models\VtuOrder::whereIn('status', ['pending', 'processing', 'completed'])->sum('cost');
        $vtuCost = 0; // Local / direct VTU provider
        
        $ngnRate = (float) (\App\Models\Currency::where('code', 'NGN')->value('exchange_rate') ?? 1420.7656);

        // Logs Spent & Cost (converted from NGN to USD base)
        $logSpent = (float) (\App\Models\LogOrder::whereIn('status', ['pending', 'completed'])
            ->selectRaw("SUM(CASE WHEN amount_usd > 0 THEN amount_usd ELSE (total_cost / ?) END) as total", [$ngnRate])
            ->value('total') ?? 0);

        $logCost = (float) (\App\Models\LogOrder::whereIn('status', ['pending', 'completed'])
            ->join('log_products', 'log_orders.log_product_id', '=', 'log_products.id')
            ->selectRaw("SUM((log_orders.quantity * log_products.base_api_price) / ?) as total", [$ngnRate])
            ->value('total') ?? 0);

        $totalSpent = $smmSpent + $vnSpent + $vtuSpent + $logSpent;
        $totalCost = $smmCost + $vnCost + $vtuCost + $logCost;
        $totalProfit = $totalSpent - $totalCost;

        // Affiliate Metrics
        $totalCommissionEarned = User::sum('total_earned_commission');
        $totalCommissionWithdrawn = User::sum('total_withdrawn_commission');
        $pendingCommission = User::sum('commission_balance');

        $pendingServices = Service::where('is_active', false)->count();

        // --- CHART DATA (Last 30 Days - Aggregated & Cached) ---
        $chartData = \Illuminate\Support\Facades\Cache::remember('admin_dashboard_chart_v2', 300, function () {
            $startDate = now()->subDays(29)->startOfDay();

            $smmStats = Order::where('created_at', '>=', $startDate)
                ->where('is_refunded', false)
                ->selectRaw("DATE(created_at) as date, count(*) as count, sum(charge - coalesce(provider_cost, 0)) as profit")
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            $vnStats = \App\Models\VirtualNumberOrder::where('created_at', '>=', $startDate)
                ->whereIn('status', ['received', 'finished', 'pending'])
                ->selectRaw("DATE(created_at) as date, count(*) as count, sum(cost - coalesce(provider_cost, 0)) as profit")
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            $vtuCounts = \App\Models\VtuOrder::where('created_at', '>=', $startDate)
                ->selectRaw("DATE(created_at) as date, count(*) as count")
                ->groupBy('date')
                ->pluck('count', 'date');

            $logCounts = \App\Models\LogOrder::where('created_at', '>=', $startDate)
                ->selectRaw("DATE(created_at) as date, count(*) as count")
                ->groupBy('date')
                ->pluck('count', 'date');

            $userCounts = User::where('created_at', '>=', $startDate)
                ->selectRaw("DATE(created_at) as date, count(*) as count")
                ->groupBy('date')
                ->pluck('count', 'date');

            $days = [];
            $orderData = [];
            $profitData = [];
            $userData = [];

            for ($i = 29; $i >= 0; $i--) {
                $targetDate = now()->subDays($i);
                $dateKey = $targetDate->format('Y-m-d');
                $days[] = $targetDate->format('M d');

                $smmCount = (int) ($smmStats[$dateKey]->count ?? 0);
                $smmProfit = (float) ($smmStats[$dateKey]->profit ?? 0);
                $vnCount = (int) ($vnStats[$dateKey]->count ?? 0);
                $vnProfit = (float) ($vnStats[$dateKey]->profit ?? 0);
                $vtuCount = (int) ($vtuCounts[$dateKey] ?? 0);
                $logCount = (int) ($logCounts[$dateKey] ?? 0);

                $orderData[] = $smmCount + $vnCount + $vtuCount + $logCount;
                $profitData[] = round($smmProfit + $vnProfit, 2);
                $userData[] = (int) ($userCounts[$dateKey] ?? 0);
            }

            return [
                'labels' => $days,
                'orders' => $orderData,
                'profits' => $profitData,
                'users' => $userData,
            ];
        });

        // --- RECENT PLATFORM ORDERS (Unified across all products & users) ---
        $recentSmm = Order::with(['user', 'service'])->latest()->take(10)->get()->map(function ($order) {
            return (object) [
                'id' => $order->id,
                'display_id' => '#' . $order->id,
                'type' => 'Social Boost',
                'type_key' => 'smm',
                'user' => $order->user,
                'service_name' => $order->service->name ?? 'Social Boost Service',
                'detail' => $order->quantity ? number_format($order->quantity) . ' qty' : null,
                'charge' => (float) $order->charge,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'url' => route('hq.orders.show', $order->id),
            ];
        });

        $recentVn = \App\Models\VirtualNumberOrder::with('user')->latest()->take(10)->get()->map(function ($order) {
            $serviceName = ($order->country ? $order->country . ' - ' : '') . ($order->product ?? 'SMS Verification');
            return (object) [
                'id' => $order->id,
                'display_id' => '#VN-' . $order->id,
                'type' => 'Virtual Number',
                'type_key' => 'vn',
                'user' => $order->user,
                'service_name' => $serviceName,
                'detail' => $order->phone_number ?: null,
                'charge' => (float) $order->cost,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'url' => route('hq.virtual-numbers'),
            ];
        });

        $recentVtu = \App\Models\VtuOrder::with('user')->latest()->take(10)->get()->map(function ($order) {
            $serviceType = ucfirst($order->service_type ?? 'Utility');
            return (object) [
                'id' => $order->id,
                'display_id' => '#VTU-' . $order->id,
                'type' => "VTU {$serviceType}",
                'type_key' => 'vtu',
                'user' => $order->user,
                'service_name' => "VTU {$serviceType}",
                'detail' => $order->phone_or_meter ?: null,
                'charge' => (float) $order->cost,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'url' => route('hq.vtu'),
            ];
        });

        $recentLogs = \App\Models\LogOrder::with(['user', 'product'])->latest()->take(10)->get()->map(function ($order) use ($ngnRate) {
            $amountUsd = (float) ($order->amount_usd > 0 ? $order->amount_usd : ($order->total_cost / ($ngnRate ?: 1420.0)));
            return (object) [
                'id' => $order->id,
                'display_id' => '#LOG-' . $order->id,
                'type' => 'Logs & Accounts',
                'type_key' => 'logs',
                'user' => $order->user,
                'service_name' => $order->product_name ?? ($order->product->name ?? 'Account Log'),
                'detail' => $order->quantity ? $order->quantity . ' item' . ($order->quantity > 1 ? 's' : '') : null,
                'charge' => $amountUsd,
                'charge_ngn' => (float) $order->total_cost,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'url' => route('hq.logs.orders'),
            ];
        });

        $recentOrders = $recentSmm
            ->concat($recentVn)
            ->concat($recentVtu)
            ->concat($recentLogs)
            ->sortByDesc('created_at')
            ->values()
            ->take(10);

        return view('hq.dashboard', compact(
            'totalUsers',
            'totalReferrals',
            'totalUserBalance',
            'totalOrders',
            'pendingOrders',
            'completedOrders',
            'totalDeposited',
            'totalSpent',
            'totalCost',
            'totalProfit',
            'totalCommissionEarned',
            'totalCommissionWithdrawn',
            'pendingCommission',
            'pendingServices',
            'chartData',
            'recentOrders'
        ));
    }

    /**
     * List all internal services for review/mapping
     */
    public function services(Request $request)
    {
        $query = Service::with('mappings.provider')
            ->withCount(['orders as recent_orders_count' => function($q) {
                $q->where('created_at', '>=', now()->subHours(24));
            }]);

        // Filter by Provider
        if ($request->filled('provider_id')) {
            $query->whereHas('mappings', function ($q) use ($request) {
                $q->where('provider_id', $request->provider_id);
            });
        }

        // Filter by Type/Channel
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search by Name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by Status (Active/Inactive)
        if ($request->filled('status')) {
            $query->where('is_active', $request->status == 'active');
        }

        // Filter by Badges
        if ($request->filled('is_hot')) {
            $query->where('is_hot', true);
        }
        if ($request->filled('is_trending')) {
            $query->where('is_trending', true);
        }

        // Filter by Price Range
        if ($request->filled('price_min')) {
            $query->where('selling_price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('selling_price', '<=', $request->price_max);
        }
        
        // Filter by Recently Sold (Orders in last 24h)
        if ($request->filled('recently_sold') && $request->recently_sold == '1') {
            $query->whereHas('orders', function($q) {
                $q->where('created_at', '>=', now()->subHours(24));
            });
        }

        $services = $query->paginate(50)->withQueryString();
        $providers = Provider::where('is_active', true)->get();
        $types = Service::distinct()->pluck('type');

        return view('hq.services.index', compact('services', 'providers', 'types'));
    }

    /**
     * Update a service (e.g., setting the selling price and activating it)
     */
    public function bulkUpdateServices(Request $request)
    {
        $servicesData = $request->input('services', []);

        if (empty($servicesData)) {
            return back()->with('error', 'No services selected for update.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($servicesData) {
            foreach ($servicesData as $id => $data) {
                $service = Service::find($id);
                if ($service) {
                    $isActive = isset($data['is_active']);
                    $isHot = isset($data['is_hot']);
                    $isTrending = isset($data['is_trending']);
                    
                    $price = $data['selling_price'] ?? $service->selling_price;

                    $service->update([
                        'selling_price' => $price,
                        'is_active' => $isActive,
                        'is_hot' => $isHot,
                        'is_trending' => $isTrending,
                        'name' => str_replace('[Review] ', '', $service->name)
                    ]);
                }
            }
        });

        \App\Models\ActivityLog::log("Bulk updated services catalog", auth()->user(), ['count' => count($servicesData)]);

        return back()->with('success', count($servicesData) . ' services updated successfully.');
    }

    /**
     * List users and balances
     */
    public function users(Request $request)
    {
        $query = User::latest();
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }
        
        $users = $query->paginate(20)->withQueryString();
        return view('hq.users.index', compact('users'));
    }

    /**
     * Export users to CSV (First Name, Last Name, Email, Username, Role, Balance, Joined Date)
     */
    public function exportUsersCsv(Request $request)
    {
        $fileName = 'users_export_' . date('Y-m-d_His') . '.csv';

        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($query) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Columns with First Name, Last Name, Email first as requested
            fputcsv($file, [
                'ID',
                'First Name',
                'Last Name',
                'Email',
                'Username',
                'Role',
                'Balance (USD)',
                'Joined Date'
            ]);

            $query->orderBy('id', 'asc')->chunk(200, function($users) use ($file) {
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->id,
                        $user->first_name ?? '',
                        $user->last_name ?? '',
                        $user->email,
                        $user->username ?? '',
                        ucfirst($user->role ?? 'user'),
                        number_format((float)$user->balance, 2, '.', ''),
                        $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : ''
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Update user role (Promote/Demote)
     */
    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string|in:user,reseller,vip,admin'
        ]);

        $user = User::findOrFail($id);

        // Security Guard: An admin cannot demote themselves
        if (Auth::id() == $user->id && $request->role !== 'admin') {
            return back()->with('error', 'You cannot demote yourself from the administrator role.');
        }

        // Security Guard: Cannot demote the primary administrator account (ID: 1)
        if ($user->id == 1 && $request->role !== 'admin') {
            return back()->with('error', 'The primary administrator account cannot be demoted.');
        }

        $oldRole = $user->role;

        $user->forceFill([
            'role' => $request->role,
            'is_admin' => ($request->role === 'admin')
        ])->save();

        \App\Models\ActivityLog::log("Changed user role", $user, [
            'old_role' => $oldRole,
            'new_role' => $request->role
        ]);

        return back()->with('success', "User #{$user->id} ({$user->username}) has been updated to " . strtoupper($request->role));
    }

    /**
     * List all providers
     */
    public function providers()
    {
        $providers = Provider::all();
        return view('hq.providers.index', compact('providers'));
    }

    /**
     * Store or update a provider
     */
    public function storeProvider(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'api_url' => 'required|url',
            'api_key' => 'required|string',
        ]);

        Provider::updateOrCreate(
            ['name' => $request->name],
            [
                'api_url' => $request->api_url,
                'api_key' => $request->api_key,
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Provider configured successfully.');
    }

    /**
     * Get Provider Balance via AJAX
     */
    public function providerBalance($id, SmmProviderService $smmService)
    {
        $provider = Provider::findOrFail($id);
        $balance = $smmService->getBalance($provider);

        return response()->json([
            'balance' => $balance !== null ? number_format($balance, 2) : 'Error'
        ]);
    }

    /**
     * Get Provider Services Count via AJAX (High-Authority Diagnostic)
     */
    public function providerServicesCount($id, SmmProviderService $smmService)
    {
        $provider = Provider::findOrFail($id);
        $services = $smmService->getServices($provider);

        // Standard SMM v2 APIs return a numerically indexed array of services.
        // If they return an error, it's usually an associative array like ['error' => '...']
        if (is_array($services)) {
            if (isset($services['error'])) {
                return response()->json(['count' => 'Err']);
            }
            
            // Check if it's a list (numerically indexed)
            if (count($services) > 0 && !isset($services[0])) {
                // It's an object but not a list, likely a single error or different format
                return response()->json(['count' => 'Err']);
            }

            return response()->json(['count' => count($services)]);
        }

        return response()->json(['count' => 'Err']);
    }

    /**
     * Debug and View Raw Provider Services (High-Authority Diagnostic)
     */
    public function debugProviderSync($id, SmmProviderService $smmService)
    {
        $provider = Provider::findOrFail($id);
        $rawServices = $smmService->getServices($provider);

        return response()->json([
            'provider' => $provider->name,
            'api_url' => $provider->api_url,
            'count' => is_array($rawServices) ? count($rawServices) : 0,
            'structure_type' => gettype($rawServices),
            'sample_service' => is_array($rawServices) && !empty($rawServices) ? array_slice($rawServices, 0, 1) : 'No data received',
            'raw_payload' => $rawServices
        ]);
    }

    /**
     * Toggle provider active status
     */
    public function toggleProviderStatus($id)
    {
        $provider = Provider::findOrFail($id);
        $provider->is_active = !$provider->is_active;
        $provider->save();

        return back()->with('success', 'Provider status updated.');
    }

    /**
     * Delete a provider
     */
    public function destroyProvider($id)
    {
        $provider = Provider::findOrFail($id);
        $provider->delete();

        return back()->with('success', 'Provider deleted successfully.');
    }


    /**
     * Update user balance manually
     */
    public function updateUserBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'type' => 'required|in:add,subtract'
        ]);

        $user = User::findOrFail($id);

        if ($request->type === 'add') {
            $user->addBalance($request->amount);

            // Record as a Payment so it accumulates in total deposits and financial metrics
            $ref = 'DEP_MANUAL_' . strtoupper(\Illuminate\Support\Str::random(10));
            \App\Models\Payment::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'currency' => $user->currency_code ?? 'USD',
                'amount_usd' => $request->amount,
                'reference' => $ref,
                'status' => 'success',
            ]);
        } else {
            $deducted = $user->deductBalance($request->amount);
            if (!$deducted) {
                return back()->with('error', 'Cannot deduct: user balance (' . money($user->balance, true, $user->currency_code) . ') is insufficient for this deduction.');
            }
        }

        \App\Models\Transaction::add(
            $user->id,
            $request->amount,
            $request->type === 'add' ? 'credit' : 'debit',
            'manual',
            "Admin adjustment (" . ($request->type === 'add' ? 'Added' : 'Deducted') . ")",
            $ref ?? null
        );

        \App\Models\ActivityLog::log("Manually updated user balance", $user, [
            'type' => $request->type,
            'amount' => $request->amount,
            'new_balance' => $user->balance
        ]);

        return back()->with('success', "User balance updated successfully. New balance: " . money($user->balance, true, $user->currency_code));
    }

    /**
     * List all platform orders
     */
    public function orders(Request $request)
    {
        $query = Order::with(['user', 'service'])->latest();

        // Filter by Status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search by Order ID, Username, or Email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->paginate(50)->withQueryString();

        // Calculate counts for tabs
        $counts = [
            'all' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::whereIn('status', ['processing', 'in_progress'])->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'failed' => Order::whereIn('status', ['failed', 'canceled'])->count(),
        ];

        return view('hq.orders.index', compact('orders', 'counts'));
    }

    /**
     * Show details for a specific order
     */
    public function showOrder($id)
    {
        $order = Order::with(['user', 'service', 'provider'])->findOrFail($id);
        return view('hq.orders.show', compact('order'));
    }

    /**
     * Manually mark an order as completed
     */
    public function forceCompleteOrder($id)
    {
        $order = Order::findOrFail($id);

        // If the order was already refunded (failed/canceled), we must RE-DEBIT the user 
        // because we are now saying the work was actually done.
        if ($order->is_refunded) {
            $user = $order->user;

            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($order, $user) {
                    if (!$user->deductBalance((float) $order->charge)) {
                        throw new \Exception("Cannot force complete. User has already been refunded and now has insufficient balance (₦" . number_format((float) $user->balance, 2) . ") to be re-charged.");
                    }

                    \App\Models\Transaction::add(
                        $user->id,
                        $order->charge,
                        'debit',
                        'order',
                        "Re-debit for Force-Completed order #{$order->id}"
                    );

                    $order->update([
                        'status' => 'completed',
                        'is_refunded' => false
                    ]);
                });
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        } else {
            $order->update(['status' => 'completed']);
        }

        \App\Models\ActivityLog::log("Force completed order", $order);

        return back()->with('success', 'Order marked as completed.');
    }

    /**
     * Cancel an order and issue a full refund
     */
    public function cancelAndRefundOrder($id)
    {
        $order = Order::with('user')->findOrFail($id);

        if ($order->status === 'canceled') {
            return back()->with('error', 'This order is already canceled.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $lockedOrder = Order::where('id', $id)->lockForUpdate()->firstOrFail();
            if ($lockedOrder->status === 'canceled' && $lockedOrder->is_refunded) {
                return;
            }

            $lockedUser = \App\Models\User::where('id', $lockedOrder->user_id)->lockForUpdate()->first();

            // Only refund if not already marked as refunded
            if (!$lockedOrder->is_refunded && $lockedUser) {
                $lockedUser->addBalance((float) $lockedOrder->charge);

                \App\Models\Transaction::add(
                    $lockedUser->id,
                    $lockedOrder->charge,
                    'credit',
                    'refund',
                    "Manual refund for order #{$lockedOrder->id} by Admin"
                );

                $lockedOrder->is_refunded = true;
            }

            $lockedOrder->status = 'canceled';
            $lockedOrder->save();

            \App\Models\ActivityLog::log("Manually canceled order", $lockedOrder, [
                'refund_amount' => $lockedOrder->charge,
                'new_balance' => $lockedUser ? $lockedUser->balance : null
            ]);
        });

        // Send Notification to user
        \App\Models\Notification::send(
            $order->user_id,
            "Order Refunded",
            "Order #{$order->id} has been canceled. ₦" . number_format((float) $order->charge, 2) . " has been refunded to your wallet.",
            'warning',
            route('orders.index')
        );

        return back()->with('success', "Order #{$id} has been CANCELED and ₦" . number_format((float) $order->charge, 2) . " has been refunded to the user.");
    }

    /**
     * Resubmit an order to the provider queue
     */
    public function resubmitOrder($id)
    {
        $order = Order::with(['service', 'user'])->findOrFail($id);

        // If the order was already refunded (status canceled or failed), we must re-debit the user
        if (in_array($order->status, ['canceled', 'failed'])) {
            $user = $order->user;

            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($order, $user) {
                    if (!$user->deductBalance((float) $order->charge)) {
                        throw new \Exception("Insufficient user balance to resubmit this order. (Needed: ₦" . number_format((float) $order->charge, 2) . ")");
                    }

                    \App\Models\Transaction::add(
                        $user->id,
                        $order->charge,
                        'debit',
                        'order',
                        "Re-debit for resubmitted order #{$order->id}"
                    );

                    $order->update([
                        'status' => 'processing',
                        'is_refunded' => false,
                        'error_log' => null
                    ]);
                    \App\Jobs\ProcessSmmOrder::dispatch($order, $order->service);
                });
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }

            return back()->with('success', "Order #{$id} has been re-debited and re-queued for processing.");
        }

        // Otherwise just re-queue (e.g. if it was just stuck)
        $order->update(['status' => 'processing', 'error_log' => null]);
        \App\Jobs\ProcessSmmOrder::dispatch($order, $order->service);

        \App\Models\ActivityLog::log("Resubmitted order to provider", $order);

        return back()->with('success', "Order #{$id} has been re-queued for processing.");
    }

    /**
     * Request an order refill from the provider
     */
    public function refillOrder($id, SmmProviderService $smmService)
    {
        $order = Order::with('provider')->findOrFail($id);

        if (!$order->provider || !$order->provider_order_id) {
            return back()->with('error', 'This order is not associated with an active provider.');
        }

        $response = $smmService->refillOrder($order->provider, $order->provider_order_id);

        if ($response && (isset($response['refill']) || !isset($response['error']))) {
            $msg = $response['error'] ?? 'Refill request sent successfully.';
            if (isset($response['error'])) {
                return back()->with('error', "Provider Error: " . $response['error']);
            }
            
            $order->logStatusChange($order->status, "Manual Refill Requested: " . json_encode($response));
            \App\Models\ActivityLog::log("Requested order refill", $order, ['response' => $response]);
            
            return back()->with('success', $msg);
        }

        return back()->with('error', 'Provider failed to accept the refill request or doesn\'t support refill for this service.');
    }


    /**
     * Show detailed user profile in HQ
     */
    public function showUser($id)
    {
        $user = User::findOrFail($id);

        $ngnRate = (float) (\App\Models\Currency::where('code', 'NGN')->value('exchange_rate') ?? 1420.7656);

        // Stats across all product categories
        $smmSpent = Order::where('user_id', $id)->whereIn('status', ['completed', 'partial'])->where('is_refunded', false)->sum('charge');
        $vnSpent = \App\Models\VirtualNumberOrder::where('user_id', $id)->whereIn('status', ['received', 'finished'])->sum('cost');
        $vtuSpent = \App\Models\VtuOrder::where('user_id', $id)->whereIn('status', ['pending', 'processing', 'completed'])->sum('cost');
        $logSpent = (float) (\App\Models\LogOrder::where('user_id', $id)->whereIn('status', ['pending', 'completed'])
            ->selectRaw("SUM(CASE WHEN amount_usd > 0 THEN amount_usd ELSE (total_cost / ?) END) as total", [$ngnRate])
            ->value('total') ?? 0);

        $totalSpent = $smmSpent + $vnSpent + $vtuSpent + $logSpent;
        $totalDeposits = \App\Models\Payment::where('user_id', $id)->where('status', 'success')->sum('amount_usd');

        $totalOrders = Order::where('user_id', $id)->count()
            + \App\Models\VirtualNumberOrder::where('user_id', $id)->count()
            + \App\Models\VtuOrder::where('user_id', $id)->count()
            + \App\Models\LogOrder::where('user_id', $id)->count();

        $ledgerCount = \App\Models\Transaction::where('user_id', $id)->count();
        $totalTransactions = $ledgerCount > 0 ? $ledgerCount : ($totalOrders + \App\Models\Payment::where('user_id', $id)->count());

        $orders = Order::where('user_id', $id)->with('service')->latest()->take(10)->get();

        return view('hq.users.show', compact('user', 'orders', 'totalSpent', 'totalDeposits', 'totalTransactions'));
    }

    /**
     * Toggle user active/banned status
     */
    public function toggleUserStatus($id)
    {
        if (Auth::id() == $id) {
            return back()->with('error', 'You cannot ban yourself!');
        }

        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'suspended';

        \App\Models\ActivityLog::log($user->is_active ? "Unbanned user" : "Banned user", $user);

        return back()->with('success', "User account has been {$status}.");
    }

    /**
     * Login as user (Impersonation)
     */
    public function impersonate($id)
    {
        if (Auth::id() == $id) {
            return back()->with('error', 'You are already logged in as yourself!');
        }

        $user = User::findOrFail($id);

        if ($user->isAdmin()) {
            return back()->with('error', 'Cannot impersonate another administrator.');
        }

        if (!$user->is_active) {
            return back()->with('error', 'Cannot impersonate an inactive or suspended user.');
        }

        $adminId = Auth::id();
        \App\Models\ActivityLog::log("Impersonated user #{$user->id} ({$user->username})", $user);

        // Store original admin ID and regenerate session to protect against session fixation
        session()->regenerate();
        session(['impersonator_id' => $adminId]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', "You are now logged in as {$user->username}");
    }

    /**
     * Return to Admin from impersonation
     */
    public function leaveImpersonation()
    {
        if (!session()->has('impersonator_id')) {
            return redirect()->route('dashboard');
        }

        $adminId = session()->pull('impersonator_id');
        $admin = User::find($adminId);

        if (!$admin || !$admin->isAdmin() || !$admin->is_active) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Administrator session expired or invalid.');
        }

        session()->regenerate();
        Auth::login($admin);

        \App\Models\ActivityLog::log("Exited impersonation and returned to admin session", $admin);

        return redirect()->route('hq.dashboard')->with('success', 'Returned to Admin HQ');
    }

    /**
     * Show user login history
     */
    public function userLogins($id)
    {
        $user = User::findOrFail($id);
        $logs = \App\Models\LoginLog::where('user_id', $id)->latest()->paginate(20);

        return view('hq.users.logins', compact('user', 'logs'));
    }

    /**
     * Show user transactions (Payments + Orders)
     */
    public function userTransactions($id)
    {
        $user = User::findOrFail($id);

        $deposits = \App\Models\Payment::where('user_id', $id)->latest()->get();
        $transactions = \App\Models\Transaction::where('user_id', $id)->latest()->get();

        $ngnRate = (float) (\App\Models\Currency::where('code', 'NGN')->value('exchange_rate') ?? 1420.7656);

        $recentSmm = Order::where('user_id', $id)->with('service')->latest()->take(25)->get()->map(function ($order) {
            return (object) [
                'id' => $order->id,
                'display_id' => '#' . $order->id,
                'service_name' => $order->service->name ?? 'Social Boost Service',
                'quantity' => $order->quantity ? number_format($order->quantity) : '1',
                'charge' => (float) $order->charge,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        });

        $recentVn = \App\Models\VirtualNumberOrder::where('user_id', $id)->latest()->take(25)->get()->map(function ($order) {
            return (object) [
                'id' => $order->id,
                'display_id' => '#VN-' . $order->id,
                'service_name' => ($order->country ? $order->country . ' - ' : '') . ($order->product ?? 'Virtual Number'),
                'quantity' => '1',
                'charge' => (float) $order->cost,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        });

        $recentVtu = \App\Models\VtuOrder::where('user_id', $id)->latest()->take(25)->get()->map(function ($order) {
            $serviceType = ucfirst($order->service_type ?? 'Utility');
            return (object) [
                'id' => $order->id,
                'display_id' => '#VTU-' . $order->id,
                'service_name' => "VTU {$serviceType} ({$order->phone_or_meter})",
                'quantity' => '1',
                'charge' => (float) $order->cost,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        });

        $recentLogs = \App\Models\LogOrder::where('user_id', $id)->with('product')->latest()->take(25)->get()->map(function ($order) use ($ngnRate) {
            $amountUsd = (float) ($order->amount_usd > 0 ? $order->amount_usd : ($order->total_cost / ($ngnRate ?: 1420.0)));
            return (object) [
                'id' => $order->id,
                'display_id' => '#LOG-' . $order->id,
                'service_name' => $order->product_name ?? ($order->product->name ?? 'Account Log'),
                'quantity' => $order->quantity ? (string) $order->quantity : '1',
                'charge' => $amountUsd,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        });

        $orders = $recentSmm
            ->concat($recentVn)
            ->concat($recentVtu)
            ->concat($recentLogs)
            ->sortByDesc('created_at')
            ->values()
            ->take(50);

        return view('hq.users.transactions', compact('user', 'deposits', 'orders', 'transactions'));
    }

    /**
     * Update user basic details
     */
    public function updateUserDetails(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'username' => 'required|string|unique:users,username,' . $id,
        ]);

        $user = User::findOrFail($id);
        $user->update($request->only(['first_name', 'last_name', 'email', 'username']));

        return back()->with('success', 'User information updated successfully.');
    }

    /**
     * Show all system activity logs
     */
    public function activityLogs(Request $request)
    {
        $query = \App\Models\ActivityLog::with('user')->latest();

        // Search by Username, Email or IP
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by Action
        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        $logs = $query->paginate(20)->withQueryString();
        return view('hq.logs', compact('logs'));
    }

    /**
     * Show all financial transactions across the platform
     */
    public function allTransactions(Request $request)
    {
        $query = \App\Models\Transaction::with('user')->latest();

        // Filter by Type (Credit/Debit)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by Transaction Category
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Search by Username, Email or Reference
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->paginate(50)->withQueryString();
        return view('hq.transactions', compact('transactions'));
    }

    /**
     * List all support tickets
     */
    public function hqTickets(Request $request)
    {
        $query = \App\Models\Ticket::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->paginate(20)->withQueryString();
        return view('hq.tickets.index', compact('tickets'));
    }

    /**
     * Show a specific ticket
     */
    public function hqShowTicket($id)
    {
        $ticket = \App\Models\Ticket::with(['user', 'messages.user'])->findOrFail($id);
        return view('hq.tickets.show', compact('ticket'));
    }

    /**
     * Reply to a ticket from HQ
     */
    public function hqReplyTicket(Request $request, $id)
    {
        $ticket = \App\Models\Ticket::findOrFail($id);

        $request->validate([
            'message' => 'required|string|min:2',
        ]);

        $ticket->messages()->create([
            'user_id' => Auth::id(),
            'message' => $request->message,
            'is_admin' => true,
        ]);

        // Send Notification to the ticket owner
        \App\Models\Notification::send(
            $ticket->user_id,
            "New Support Reply",
            "A support agent has replied to your ticket: #{$ticket->id}",
            'info',
            route('tickets.show', $ticket->id)
        );

        // Status 'pending' in our system means "Admin has replied, waiting for user"
        $ticket->update(['status' => 'pending']);

        \App\Models\ActivityLog::log("Replied to support ticket", $ticket, ['ticket_id' => $id]);

        return back()->with('success', 'Your reply has been sent and ticket status updated to PENDING.');
    }

    /**
     * Update ticket status (Close/Open)
     */
    public function hqUpdateTicketStatus(Request $request, $id)
    {
        $ticket = \App\Models\Ticket::findOrFail($id);
        $ticket->update(['status' => $request->status]);

        return back()->with('success', "Ticket status updated to {$request->status}.");
    }

    /**
     * List all withdrawal requests
     */
    public function hqWithdrawals(Request $request)
    {
        $query = \App\Models\WithdrawalRequest::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->paginate(20)->withQueryString();
        return view('hq.withdrawals.index', compact('withdrawals'));
    }

    /**
     * Approve a withdrawal request
     */
    public function hqApproveWithdrawal(Request $request, $id)
    {
        $withdrawal = \App\Models\WithdrawalRequest::with('user')->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'This request is already processed.');
        }

        $user = $withdrawal->user;
        if ($user->commission_balance < $withdrawal->amount) {
            return back()->with('error', 'User has insufficient commission balance to fulfill this request.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($withdrawal, $user, $request) {
            // Deduct from commission balance
            $user->decrement('commission_balance', $withdrawal->amount);

            // Log to Affiliate Ledger
            \App\Models\AffiliateTransaction::log(
                $user->id,
                $withdrawal->amount,
                'debit',
                "Withdrawal #{$withdrawal->id} Approved"
            );

            $withdrawal->update([
                'status' => 'approved',
                'admin_note' => $request->admin_note ?? 'Paid successfully.'
            ]);

            \App\Models\ActivityLog::log("Approved withdrawal request", $user, [
                'amount' => $withdrawal->amount,
                'withdrawal_id' => $withdrawal->id
            ]);
        });

        return back()->with('success', 'Withdrawal request approved and balance updated.');
    }

    /**
     * Decline a withdrawal request
     */
    public function hqDeclineWithdrawal(Request $request, $id)
    {
        $withdrawal = \App\Models\WithdrawalRequest::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'This request is already processed.');
        }

        $withdrawal->update([
            'status' => 'declined',
            'admin_note' => $request->admin_note ?? 'Declined by administrator.'
        ]);

        return back()->with('success', 'Withdrawal request declined.');
    }

    /**
     * List all broadcasts
     */
    public function hqBroadcasts()
    {
        $broadcasts = \App\Models\Broadcast::latest()->get();
        return view('hq.broadcasts.index', compact('broadcasts'));
    }

    /**
     * Store a new broadcast
     */
    public function hqStoreBroadcast(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'type' => 'required|in:info,warning,danger,success',
        ]);

        \App\Models\Broadcast::create($request->all());

        return back()->with('success', 'Broadcast published successfully.');
    }

    /**
     * Delete a broadcast
     */
    public function hqDestroyBroadcast($id)
    {
        \App\Models\Broadcast::findOrFail($id)->delete();
        return back()->with('success', 'Broadcast removed.');
    }

    /**
     * Update Service News Tickers & Page Notes
     */
    public function hqUpdateServiceNotices(Request $request)
    {
        foreach (['global', 'numbers', 'logs', 'smm', 'vtu'] as $service) {
            \App\Models\Setting::set("notice_{$service}_enabled", $request->has("notice_{$service}_enabled") ? '1' : '0');
            \App\Models\Setting::set("notice_{$service}_text", $request->input("notice_{$service}_text", ''));
        }
        \App\Models\ActivityLog::log("Updated Service News Tickers & Notes", auth()->user());
        return back()->with('success', 'Service News Tickers & Notes updated successfully.');
    }

    /**
     * Show site settings page
     */
    public function hqSettings()
    {
        $channels = Service::getChannels();
        $sliders = collect();
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('dashboard_sliders')) {
                $sliders = \App\Models\DashboardSlider::orderBy('sort_order')->latest()->get();
            }
        } catch (\Throwable $e) {
            $sliders = collect();
        }
        return view('hq.settings', compact('channels', 'sliders'));
    }

    /**
     * Store a new dashboard banner slider
     */
    public function hqStoreSlider(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'target_url' => 'nullable|string|max:500',
        ]);

        $file = $request->file('image');
        $path = $file->store('sliders', 'public');
        $imagePath = '/storage/' . $path;

        \App\Models\DashboardSlider::create([
            'image_path' => $imagePath,
            'target_url' => $request->target_url ? trim($request->target_url) : null,
            'is_active' => true,
            'sort_order' => (\App\Models\DashboardSlider::max('sort_order') ?? 0) + 1,
        ]);

        \App\Models\ActivityLog::log("Added a new dashboard slider image", auth()->user(), [
            'image_path' => $imagePath,
            'target_url' => $request->target_url
        ]);

        return redirect()->route('hq.settings', ['tab' => 'slider'])->with('success', 'Dashboard slide uploaded and activated successfully.');
    }

    /**
     * Delete a dashboard slider
     */
    public function hqDestroySlider($id)
    {
        $slider = \App\Models\DashboardSlider::findOrFail($id);

        $relPath = str_replace('/storage/', '', $slider->image_path);
        \Illuminate\Support\Facades\Storage::disk('public')->delete($relPath);

        $slider->delete();

        \App\Models\ActivityLog::log("Deleted dashboard slider #{$id}", auth()->user());

        return redirect()->route('hq.settings', ['tab' => 'slider'])->with('success', 'Slide deleted successfully.');
    }

    /**
     * Update site settings
     */
    public function hqUpdateSettings(Request $request)
    {
        // Handle Platform Instructions specifically
        if ($request->has('platforms') && $request->has('descriptions')) {
            $platforms = $request->input('platforms');
            $descriptions = $request->input('descriptions');
            $combined = [];

            foreach ($platforms as $index => $name) {
                $description = $descriptions[$index] ?? '';
                if (!empty($name) && !empty(trim($description))) {
                    $combined[$name] = $description;
                }
            }

            \App\Models\Setting::set('platform_instructions_json', json_encode($combined));
        }

        // Handle File Uploads for Branding
        $brandFiles = ['site_logo_light', 'site_logo_dark', 'site_favicon', 'site_og_image'];
        foreach ($brandFiles as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store('branding', 'public');
                \App\Models\Setting::set($field, '/storage/' . $path);
            }
        }

        // Handle all other settings
        $settings = $request->except(['_token', 'platforms', 'descriptions', 'site_logo_light', 'site_logo_dark', 'site_favicon', 'site_og_image']);

        foreach ($settings as $key => $value) {
            \App\Models\Setting::set($key, $value);
        }

        // When Log Marketplace markup percentage is updated, sync our_price ONLY for external API products
        if ($request->filled('markup_logs_market')) {
            $newMarkup = (float) $request->input('markup_logs_market', 20.0);
            $multiplier = 1 + ($newMarkup / 100);
            \App\Models\LogProduct::where('type', 'api')
                ->where('base_api_price', '>', 0)
                ->each(function($prod) use ($multiplier) {
                    $cost = (float) $prod->base_api_price;
                    $prod->update(['our_price' => round($cost * $multiplier, 2)]);
                });
        }

        $sanitizedLog = $request->except([
            '_token', 'password', 'mail_password',
            'korapay_live_secret_key', 'korapay_test_secret_key',
            'billstack_secret_key', 'smspool_api_key', 'grizzly_api_key', 'gongozconcept_api_key',
            'exchangerate_api_key'
        ]);

        \App\Models\ActivityLog::log("Updated global site settings", auth()->user(), $sanitizedLog);

        return back()->with('success', 'Settings updated successfully.');
    }

    public function hqSendTestEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email'
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($request->test_email)->send(new \App\Mail\TestSmtpMail());
            
            \App\Models\ActivityLog::log("Sent a test SMTP email to " . $request->test_email, auth()->user());

            return back()->with('success', 'Test email sent successfully to ' . $request->test_email . '! Please check your inbox (and spam folder).');
        } catch (\Throwable $e) {
            return back()->with('error', 'SMTP test email failed! Error: ' . $e->getMessage());
        }
    }

    /**
     * Show background queue status, failed jobs, and scheduled tasks
     */
    public function queueStatus()
    {
        $queueSize = 0;
        $connection = config('queue.default');
        
        try {
            if ($connection === 'redis') {
                // Production logic: Monitor Redis queue size
                $queueSize = \Illuminate\Support\Facades\Redis::llen('queues:default');
            } else if ($connection === 'database') {
                // Local/Database logic: Monitor jobs table
                $queueSize = \Illuminate\Support\Facades\DB::table('jobs')->count();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Queue Monitor Error ({$connection}): " . $e->getMessage());
        }

        $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->latest()->paginate(15);
        
        // Get Scheduled Tasks (Cron Jobs) - Bootstrapping for Laravel 11
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
        
        // Ensure console routes are loaded to populate the schedule
        if (file_exists(base_path('routes/console.php'))) {
            require base_path('routes/console.php');
        }

        $scheduledTasks = collect($schedule->events())->map(function ($event) {
            return [
                'command' => $event->command,
                'expression' => $event->expression,
                'next_run' => $event->nextRunDate()->format('Y-m-d H:i:s'),
                'description' => $event->description,
            ];
        });

        return view('hq.queues', compact('queueSize', 'failedJobs', 'scheduledTasks'));
    }

    /**
     * Manually trigger the SMM service synchronization command
     */
    public function triggerServiceSync()
    {
        try {
            // Run the command via Artisan to reuse the hardened logic
            \Illuminate\Support\Facades\Artisan::call('smm:sync-services');
            $output = \Illuminate\Support\Facades\Artisan::output();
            
            \App\Models\ActivityLog::log("Manually triggered SMM service synchronization", auth()->user());
            
            return back()->with('success', 'Synchronization completed successfully! Check the services list for new entries.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Manual Sync Failed: " . $e->getMessage());
            return back()->with('error', 'Synchronization failed: ' . $e->getMessage());
        }
    }
}
