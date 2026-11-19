<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Service;
use App\Models\Order;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;
use App\Services\SmmProviderService;
use App\Services\SmmPricingService;

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

        // Order Metrics (Aggregated SMM + Virtual Numbers + VTU + Logs + AI Tools)
        $aiToolOrdersCount = \Illuminate\Support\Facades\Schema::hasTable('ai_tool_orders') ? \App\Models\AiToolOrder::count() : 0;
        $totalOrders = Order::count() 
            + \App\Models\VirtualNumberOrder::count()
            + \App\Models\VtuOrder::count()
            + ( \Illuminate\Support\Facades\Schema::hasTable('log_orders') ? \App\Models\LogOrder::count() : 0 )
            + $aiToolOrdersCount;

        $aiToolPendingCount = \Illuminate\Support\Facades\Schema::hasTable('ai_tool_orders') ? \App\Models\AiToolOrder::whereIn('status', ['pending', 'processing'])->count() : 0;
        $pendingOrders = Order::where('status', 'pending')->count() 
            + \App\Models\VirtualNumberOrder::where('status', 'pending')->count()
            + \App\Models\VtuOrder::whereIn('status', ['pending', 'processing'])->count()
            + ( \Illuminate\Support\Facades\Schema::hasTable('log_orders') ? \App\Models\LogOrder::where('status', 'pending')->count() : 0 )
            + $aiToolPendingCount;

        $aiToolCompletedCount = \Illuminate\Support\Facades\Schema::hasTable('ai_tool_orders') ? \App\Models\AiToolOrder::where('status', 'completed')->count() : 0;
        $completedOrders = Order::where('status', 'completed')->count() 
            + \App\Models\VirtualNumberOrder::whereIn('status', ['received', 'finished'])->count()
            + \App\Models\VtuOrder::where('status', 'completed')->count()
            + ( \Illuminate\Support\Facades\Schema::hasTable('log_orders') ? \App\Models\LogOrder::where('status', 'completed')->count() : 0 )
            + $aiToolCompletedCount;

        // Financial Metrics (Authoritative calculation in NGN via FinancialMetricsService)
        $financialService = app(\App\Services\FinancialMetricsService::class);
        $financialPerformance = $financialService->getAdminFinancialPerformance();
        $totalDeposited = $financialPerformance['totalDeposited'];
        $totalSpent     = $financialPerformance['totalSpent'];
        $totalCost      = $financialPerformance['totalCost'];
        $totalProfit    = $financialPerformance['totalProfit'];

        // Affiliate Metrics
        $totalCommissionEarned = User::sum('total_earned_commission');
        $totalCommissionWithdrawn = User::sum('total_withdrawn_commission');
        $pendingCommission = User::sum('commission_balance');

        $pendingServices = Service::where('is_active', false)->count();

        // --- CHART DATA (Last 30 Days - Aggregated \& Cached with Accurate FX \& Realized Profit) ---
        $chartData = $financialService->getAdmin30DayChartData();

        // --- RECENT PLATFORM ORDERS (Unified across all products \& users) ---
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

        $recentLogs = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('log_orders')) {
            $recentLogs = \App\Models\LogOrder::with(['user', 'product'])->latest()->take(10)->get()->map(function ($order) {
                return (object) [
                    'id' => $order->id,
                    'display_id' => '#LOG-' . $order->id,
                    'type' => 'Logs \u0026 Accounts',
                    'type_key' => 'logs',
                    'user' => $order->user,
                    'service_name' => $order->product_name ?? ($order->product->name ?? 'Account Log'),
                    'detail' => $order->quantity ? $order->quantity . ' item' . ($order->quantity > 1 ? 's' : '') : null,
                    'charge' => (float) $order->total_cost,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'url' => route('hq.logs.orders'),
                ];
            });
        }

        $recentAiTools = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('ai_tool_orders')) {
            $recentAiTools = \App\Models\AiToolOrder::with(['user', 'product'])->latest()->take(10)->get()->map(function ($order) {
                return (object) [
                    'id' => $order->id,
                    'display_id' => $order->order_number,
                    'type' => 'AI TOOLS',
                    'type_key' => 'ai_tools',
                    'user' => $order->user,
                    'service_name' => $order->product->name ?? 'AI Tool',
                    'detail' => $order->quantity ? $order->quantity . ' unit' . ($order->quantity > 1 ? 's' : '') : null,
                    'charge' => (float) $order->total_paid_ngn,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'url' => route('hq.ai-tools.orders.show', $order->id),
                ];
            });
        }

        $recentOrders = $recentSmm
            ->concat($recentVn)
            ->concat($recentVtu)
            ->concat($recentLogs)
            ->concat($recentAiTools)
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
        $query = User::where(function($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'removed');
        })->latest();
        
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

        $query = User::where(function($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'removed');
        });

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
                'Balance (NGN)',
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

        $ref = 'MANUAL_' . strtoupper(\Illuminate\Support\Str::random(16));

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id, &$user, $ref) {
                $user = User::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($request->type === 'add') {
                    $user->addBalance($request->amount);

                    // Admin wallet funding intentionally contributes to the user's total deposits.
                    \\App\\Models\\Payment::create([
                        'user_id' => $user->id,
                        'amount' => $request->amount,
                        'currency' => $user->currency_code ?? 'NGN',
                        'amount_usd' => $request->amount,
                        'reference' => $ref,
                        'status' => 'success',
                    ]);

                } elseif (!$user->deductBalance($request->amount)) {
                    throw new \RuntimeException('INSUFFICIENT_BALANCE');
                }

                \App\Models\Transaction::add(
                    $user->id,
                    $request->amount,
                    $request->type === 'add' ? 'credit' : 'debit',
                    'manual',
                    "Admin adjustment (" . ($request->type === 'add' ? 'Added' : 'Deducted') . ")",
                    $ref
                );
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'INSUFFICIENT_BALANCE') {
                $user = User::findOrFail($id);
                return back()->with('error', 'Cannot deduct: user balance (' . money($user->balance, true, $user->currency_code) . ') is insufficient for this deduction.');
            }
            throw $e;
        }

        \App\Models\ActivityLog::log("Manually updated user balance", $user, [
            'type' => $request->type,
            'amount' => $request->amount,
            'new_balance' => $user->balance
        ]);

        return back()->with('success', "User balance updated successfully. New balance: " . money($user->balance, true, $user->currency_code));
    }

    /**
     * List all platform orders (Social Media Boosting / Boost)
     */
    public function orders(Request $request)
    {
        $query = Order::with(['user', 'service'])->latest();

        // Date range filter
        $startDate = null;
        $endDate = null;
        if ($request->filled('from_date')) {
            $startDate = \Carbon\Carbon::parse($request->from_date)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }
        if ($request->filled('to_date')) {
            $endDate = \Carbon\Carbon::parse($request->to_date)->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }

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

        // Authoritative SMM financial summary for the specified date range
        $smmStats = app(\App\Services\FinancialMetricsService::class)->getSmmSummary($startDate, $endDate);

        // Calculate counts for tabs with consistent date filtering
        $countQuery = Order::query();
        if ($startDate) $countQuery->where('created_at', '>=', $startDate);
        if ($endDate) $countQuery->where('created_at', '<=', $endDate);

        $counts = [
            'all' => (clone $countQuery)->count(),
            'pending' => (clone $countQuery)->where('status', 'pending')->count(),
            'processing' => (clone $countQuery)->whereIn('status', ['processing', 'in_progress'])->count(),
            'completed' => (clone $countQuery)->where('status', 'completed')->count(),
            'failed' => (clone $countQuery)->whereIn('status', ['failed', 'canceled'])->count(),
        ];

        return view('hq.orders.index', compact('orders', 'counts', 'smmStats'));
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
        try {
            $order = \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                $lockedOrder = Order::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($lockedOrder->is_refunded) {
                    $user = User::whereKey($lockedOrder->user_id)->lockForUpdate()->firstOrFail();
                    if (!$user->deductBalance((float) $lockedOrder->charge)) {
                        throw new \RuntimeException("Cannot force complete. User has insufficient balance to re-charge this refunded order.");
                    }

                    \App\Models\Transaction::add(
                        $user->id,
                        $lockedOrder->charge,
                        'debit',
                        'order',
                        "Re-debit for Force-Completed order #{$lockedOrder->id}"
                    );
                    $lockedOrder->is_refunded = false;
                }

                $lockedOrder->status = 'completed';
                $lockedOrder->save();
                return $lockedOrder;
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
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
        try {
            $result = \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                $order = Order::with(['service'])->whereKey($id)->lockForUpdate()->firstOrFail();

                if (in_array($order->status, ['canceled', 'failed'])) {
                    $user = User::whereKey($order->user_id)->lockForUpdate()->firstOrFail();
                    if (!$user->deductBalance((float) $order->charge)) {
                        throw new \RuntimeException("Insufficient user balance to resubmit this order. (Needed: ₦" . number_format((float) $order->charge, 2) . ")");
                    }

                    \App\Models\Transaction::add(
                        $user->id,
                        $order->charge,
                        'debit',
                        'order',
                        "Re-debit for resubmitted order #{$order->id}"
                    );
                    $order->is_refunded = false;
                }

                $order->status = 'processing';
                $order->error_log = null;
                $order->save();

                \App\Jobs\ProcessSmmOrder::dispatch($order, $order->service);
                return $order;
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        \App\Models\ActivityLog::log("Resubmitted order to provider", $result);
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

        // Stats across all product categories in NGN using authoritative FinancialMetricsService
        $financialService = app(\App\Services\FinancialMetricsService::class);
        $totalSpent = $financialService->getUserTotalSpent($id);
        $totalDeposits = $financialService->getUserTotalDeposits($id);

        $aiUserOrdersCount = 0;
        try {
            if (class_exists(\App\Models\AiOrder::class) && \Illuminate\Support\Facades\Schema::hasTable('ai_orders')) {
                $aiUserOrdersCount = \App\Models\AiOrder::where('user_id', $id)->count();
            }
        } catch (\Throwable $e) {
            $aiUserOrdersCount = 0;
        }

        $logOrdersCount = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('log_orders')) {
            $logOrdersCount = \App\Models\LogOrder::where('user_id', $id)->count();
        }

        $totalOrders = Order::where('user_id', $id)->count()
            + \App\Models\VirtualNumberOrder::where('user_id', $id)->count()
            + \App\Models\VtuOrder::where('user_id', $id)->count()
            + $logOrdersCount
            + $aiUserOrdersCount;

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
     * Toggle user Two-Factor Authentication (2FA) status from Admin HQ
     */
    public function toggleUser2fa($id)
    {
        $user = User::findOrFail($id);
        $user->two_factor_enabled = !$user->two_factor_enabled;
        if (!$user->two_factor_enabled) {
            $user->two_factor_code = null;
            $user->two_factor_expires_at = null;
        }
        $user->save();

        $status = $user->two_factor_enabled ? 'enabled' : 'disabled';

        \App\Models\ActivityLog::log("Admin {$status} 2FA for user #{$user->id} ({$user->username})", Auth::user());

        return back()->with('success', "Two-Factor Authentication (2FA) has been {$status} for {$user->username}.");
    }

    /**
     * Remove a registered user from Admin HQ with financial protection and security guards.
     */
    public function destroyUser(Request $request, $id)
    {
        // 1. Enforce strict server-side administrator authorization
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access. Only administrators can remove users.');
        }

        // 2. Validate user ID format
        if (!is_numeric($id) || (int) $id <= 0) {
            return back()->with('error', 'Invalid user identifier provided.');
        }

        $user = User::find($id);
        if (!$user) {
            return back()->with('error', 'User not found or has already been removed.');
        }

        // 3. Security Guard: Prevent admin from deleting their own account
        if (Auth::id() == $user->id) {
            return back()->with('error', 'You cannot remove your own administrator account.');
        }

        // 4. Security Guard: Prevent removal of primary administrator account (ID: 1)
        if ($user->id == 1) {
            return back()->with('error', 'The primary administrator account is protected and cannot be removed.');
        }

        // 5. Security Guard: Prevent removal of the final active administrator
        if ($user->isAdmin()) {
            $activeAdminsCount = User::where(function ($q) {
                $q->where('role', 'admin')->orWhere('is_admin', true);
            })
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'removed');
            })
            ->count();

            if ($activeAdminsCount <= 1) {
                return back()->with('error', 'Cannot remove the last active administrator account.');
            }
        }

        $userIdentifier = $user->name ? "{$user->name} ({$user->email})" : $user->email;

        // 6. Inspect dependencies & financial history to protect financial records
        $hasAiOrders = false;
        try {
            if (class_exists(\App\Models\AiOrder::class) && \Illuminate\Support\Facades\Schema::hasTable('ai_orders')) {
                $hasAiOrders = \App\Models\AiOrder::where('user_id', $user->id)->exists();
            }
        } catch (\Throwable $e) {
            $hasAiOrders = false;
        }

        $hasLogOrders = false;
        if (\Illuminate\Support\Facades\Schema::hasTable('log_orders')) {
            $hasLogOrders = \App\Models\LogOrder::where('user_id', $user->id)->exists();
        }

        $hasFinancialHistory = $user->transactions()->exists()
            || $user->orders()->exists()
            || \App\Models\Payment::where('user_id', $user->id)->exists()
            || \App\Models\VirtualNumberOrder::where('user_id', $user->id)->exists()
            || \App\Models\VtuOrder::where('user_id', $user->id)->exists()
            || $hasLogOrders
            || $hasAiOrders
            || \App\Models\WithdrawalRequest::where('user_id', $user->id)->exists();

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // Terminate active sessions for this user so they cannot continue using the platform
            \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();

            if ($hasFinancialHistory) {
                // Safe account removal: preserve financial transactions, orders, and ledger records
                $user->forceFill([
                    'is_active' => false,
                    'status' => 'removed',
                    'api_key' => null,
                    'remember_token' => null,
                ])->save();

                $message = "User #{$user->id} ({$userIdentifier}) was successfully removed from registered users.";
            } else {
                // No financial or transactional records exist: safe to delete cleanly
                $user->delete();
                $message = "User #{$user->id} ({$userIdentifier}) was successfully removed.";
            }

            \App\Models\ActivityLog::log("Admin removed user #{$user->id} ({$userIdentifier})", Auth::user(), [
                'target_user_id' => $user->id,
                'target_email' => $user->email,
                'preserved_financial_history' => $hasFinancialHistory,
            ]);

            \Illuminate\Support\Facades\DB::commit();
            return back()->with('success', $message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Failed to remove user #{$id}: " . $e->getMessage());
            return back()->with('error', 'An error occurred while attempting to remove the user. Please try again.');
        }
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
        $transactions = \App\Models\Transaction::where('user_id', $id)->ordered()->get();

        $recentSmm = Order::where('user_id', $id)->with('service')->latest()->take(50)->get()->map(function ($order) {
            return (object) [
                'id' => $order->id,
                'display_id' => '#' . $order->id,
                'service_name' => $order->service->name ?? 'Social Boost Service',
                'quantity' => $order->quantity ? (string) $order->quantity : '1',
                'charge' => (float) $order->charge,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        });

        $recentVn = \App\Models\VirtualNumberOrder::where('user_id', $id)->latest()->take(50)->get()->map(function ($order) {
            $serviceName = ($order->country ? $order->country . ' - ' : '') . ($order->product ?? 'Virtual Number');
            return (object) [
                'id' => $order->id,
                'display_id' => '#VN-' . $order->id,
                'service_name' => $serviceName,
                'quantity' => '1',
                'charge' => (float) $order->cost,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        });

        $recentVtu = \App\Models\VtuOrder::where('user_id', $id)->latest()->take(50)->get()->map(function ($order) {
            $serviceType = ucfirst($order->service_type ?? 'Utility');
            return (object) [
                'id' => $order->id,
                'display_id' => '#VTU-' . $order->id,
                'service_name' => "VTU {$serviceType}",
                'quantity' => '1',
                'charge' => (float) $order->cost,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        });

        $recentLogs = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('log_orders')) {
            $recentLogs = \App\Models\LogOrder::where('user_id', $id)->with('product')->latest()->take(50)->get()->map(function ($order) {
                return (object) [
                    'id' => $order->id,
                    'display_id' => '#LOG-' . $order->id,
                    'service_name' => $order->product_name ?? ($order->product->name ?? 'Account Log'),
                    'quantity' => $order->quantity ? (string) $order->quantity : '1',
                    'charge' => (float) $order->total_cost,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ];
            });
        }

        $recentAi = collect();
        try {
            if (class_exists(\App\Models\AiOrder::class) && \Illuminate\Support\Facades\Schema::hasTable('ai_orders')) {
                $recentAi = \App\Models\AiOrder::where('user_id', $id)->with('product')->latest()->take(50)->get()->map(function ($order) {
                    return (object) [
                        'id' => $order->id,
                        'display_id' => '#AI-' . $order->id,
                        'service_name' => $order->product_name ?? ($order->product->name ?? 'AI Service'),
                        'quantity' => $order->quantity ? (string) $order->quantity : '1',
                        'charge' => (float) $order->total_price,
                        'status' => $order->status === 'fulfilled' ? 'completed' : $order->status,
                        'created_at' => $order->created_at,
                    ];
                });
            }
        } catch (\Throwable $e) {
            $recentAi = collect();
        }

        $orders = collect($recentSmm)
            ->concat(collect($recentVn))
            ->concat(collect($recentVtu))
            ->concat(collect($recentLogs))
            ->concat(collect($recentAi))
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
        $spamPattern = '/(https?:\/\/|www\.|\.com|\.org|\.net|\.io|\.xyz|\.ru|\.top|\.online|t\.me\/|telegram|crypto|bitcoin|usdt|ethereum|wallet|giveaway|investment)/i';
        if (
            preg_match($spamPattern, (string)$request->input('first_name')) ||
            preg_match($spamPattern, (string)$request->input('last_name')) ||
            preg_match($spamPattern, (string)$request->input('username'))
        ) {
            return back()->withErrors(['first_name' => 'Name or username contains prohibited promotional, crypto, or link content.'])->withInput();
        }

        $request->validate([
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s\-\'\.]+$/u'],
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'username' => ['required', 'string', 'alpha_dash', 'max:30', 'unique:users,username,' . $id],
        ], [
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, and apostrophes.',
            'first_name.max' => 'The first name may not be greater than 50 characters.',
            'last_name.max' => 'The last name may not be greater than 50 characters.',
            'username.alpha_dash' => 'The username may only contain letters, numbers, dashes, and underscores.',
            'username.max' => 'The username may not be greater than 30 characters.',
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'first_name' => trim(strip_tags($request->input('first_name'))),
            'last_name' => trim(strip_tags($request->input('last_name'))),
            'email' => strtolower(trim($request->input('email'))),
            'username' => strtolower(trim(strip_tags($request->input('username')))),
        ]);

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
        $query = \App\Models\Transaction::with('user')->ordered();

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
        $result = \\Illuminate\\Support\\Facades\\DB::transaction(function () use ($id, $request) {
            $withdrawal = \\App\\Models\\WithdrawalRequest::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($withdrawal->status !== 'pending') {
                return 'processed';
            }

            $user = \\App\\Models\\User::where('id', $withdrawal->user_id)->lockForUpdate()->firstOrFail();
            $amount = round((float) $withdrawal->amount, 2);

            if ($amount <= 0 || (float) $user->commission_balance < $amount) {
                return 'insufficient';
            }

            $user->decrement('commission_balance', $amount);
            $user->increment('total_withdrawn_commission', $amount);

            \\App\\Models\\AffiliateTransaction::log(
                $user->id,
                $amount,
                'debit',
                "Withdrawal #{$withdrawal->id} Approved"
            );

            $withdrawal->update([
                'status' => 'approved',
                'admin_note' => $request->admin_note ?? 'Paid successfully.'
            ]);

            \\App\\Models\\ActivityLog::log("Approved withdrawal request", $user, [
                'amount' => $amount,
                'withdrawal_id' => $withdrawal->id
            ]);

            return 'approved';
        });

        if ($result === 'processed') {
            return back()->with('error', 'This request is already processed.');
        }

        if ($result === 'insufficient') {
            return back()->with('error', 'User has insufficient commission balance to fulfill this request.');
        }

        return back()->with('success', 'Withdrawal request approved and balance updated.');
    }

    /**
     * Decline a withdrawal request
     */
    public function hqDeclineWithdrawal(Request $request, $id)
    {
        $result = \\Illuminate\\Support\\Facades\\DB::transaction(function () use ($id, $request) {
            $withdrawal = \\App\\Models\\WithdrawalRequest::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($withdrawal->status !== 'pending') {
                return false;
            }

            $withdrawal->update([
                'status' => 'declined',
                'admin_note' => $request->admin_note ?? 'Declined by administrator.'
            ]);

            return true;
        });

        if (!$result) {
            return back()->with('error', 'This request is already processed.');
        }

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
            if (is_string($value)) {
                $value = trim($value);
            }
            if ($key === 'fadded_api_url' && !empty($value)) {
                $value = \App\Services\FaddedApi::normalizeApiUrl($value);
            }
            if ($key === 'fadded_api_key' && !empty($value)) {
                $value = \App\Services\FaddedApi::normalizeApiKey($value);
            }
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
            'fadded_api_key'
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

    /**
     * Show Currency & Exchange Rate Settings
     */
    public function hqCurrencySettings()
    {
        $conversionService = app(\App\Services\CurrencyConversionService::class);
        $usdToNgnRate = $conversionService->getUsdToNgnRate();
        $ngnToUsdRate = $conversionService->getNgnToUsdRate();
        $lastUpdatedAt = \App\Models\Setting::get('exchange_rate_updated_at');
        $lastUpdatedBy = \App\Models\Setting::get('exchange_rate_updated_by');

        // Audit stats - verify tracking columns exist or run migrations
        $hasTracking = \Illuminate\Support\Facades\Schema::hasColumn('users', 'currency_reconciliation_status');
        if (!$hasTracking) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $hasTracking = \Illuminate\Support\Facades\Schema::hasColumn('users', 'currency_reconciliation_status');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Could not auto-migrate currency tracking columns: " . $e->getMessage());
            }
        }

        $totalUsers = \App\Models\User::count();
        $migratedUsersCount = 0;
        $ambiguousUsers = collect();

        if ($hasTracking) {
            $migratedUsersCount = \App\Models\User::where('currency_reconciliation_status', 'migrated')
                ->orWhereNotNull('legacy_balance_recovered_at')
                ->count();
            $ambiguousUsers = \App\Models\User::where('currency_reconciliation_status', 'flagged_ambiguous')->get();
        }

        return view('hq.settings.currency', compact(
            'usdToNgnRate', 
            'ngnToUsdRate', 
            'lastUpdatedAt', 
            'lastUpdatedBy',
            'totalUsers',
            'migratedUsersCount',
            'ambiguousUsers'
        ));
    }

    /**
     * Save exchange rate settings
     */
    public function hqUpdateCurrencySettings(Request $request)
    {
        $request->validate([
            'usd_to_ngn_rate' => 'required|numeric|gt:0',
            'ngn_to_usd_rate' => 'nullable|numeric|gt:0',
        ]);

        $usdRate = (float) $request->usd_to_ngn_rate;

        // Mathematical consistency validation
        if ($request->filled('ngn_to_usd_rate')) {
            $reverseRate = (float) $request->ngn_to_usd_rate;
            $product = $usdRate * $reverseRate;
            // Ensure product is approximately 1 (within 5% tolerance)
            if (abs($product - 1.0) > 0.05) {
                return back()->withInput()->with('error', 'Inconsistent exchange rates. 1 NGN to USD must mathematically approximate 1 / (USD to NGN rate). Product was ' . round($product, 4) . ' instead of ~1.0.');
            }
        }

        $conversionService = app(\App\Services\CurrencyConversionService::class);
        $adminIdentifier = auth()->user()->username ?? auth()->user()->email ?? 'Administrator';

        $conversionService->updateRates($usdRate, $adminIdentifier);

        return back()->with('success', 'Exchange rates successfully updated and saved. Active rate: 1 USD = ₦' . number_format($usdRate, 2) . ' NGN.');
    }

    /**
     * Reconcile an individual ambiguous user account manually
     */
    public function hqReconcileUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'action' => 'required|in:convert_as_usd,mark_as_ngn,set_custom',
            'custom_balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);
        $rate = app(\App\Services\CurrencyConversionService::class)->getUsdToNgnRate();

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $request, $rate) {
            $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->firstOrFail();
            $oldBalance = (float) $lockedUser->balance;

            if ($request->action === 'convert_as_usd') {
                // Currency migration is a one-time operation. Re-running it on an
                // already recovered NGN balance would multiply the balance again.
                if ($lockedUser->legacy_balance_recovered_at !== null
                    || $lockedUser->currency_reconciliation_status === 'migrated') {
                    return 'already_migrated';
                }

                $newBalance = round($oldBalance * $rate, 2);
                $lockedUser->balance = $newBalance;
                $lockedUser->legacy_balance_usd = $oldBalance;
                $lockedUser->legacy_recovery_rate = $rate;
                $lockedUser->legacy_balance_recovered_at = now();
                $lockedUser->currency_reconciliation_status = 'migrated';
                $lockedUser->currency_code = 'NGN';
                $lockedUser->reconciliation_notes = "Manual reconciliation by " . auth()->user()->username . ": Converted \${$oldBalance} USD at ₦{$rate} = ₦{$newBalance}. " . ($request->notes ?? '');
                $lockedUser->save();

                $difference = round($newBalance - $oldBalance, 4);
                if ($difference != 0.0) {
                    \App\Models\Transaction::create([
                        'user_id' => $lockedUser->id,
                        'amount' => abs($difference),
                        'type' => $difference > 0 ? 'credit' : 'debit',
                        'transaction_type' => 'currency_migration',
                        'balance_before' => $oldBalance,
                        'balance_after' => $newBalance,
                        'description' => "Manual reconciliation: Converted \${$oldBalance} USD to ₦" . number_format($newBalance, 2) . " NGN",
                        'reference' => 'MANREC_' . strtoupper(\Illuminate\Support\Str::random(8)),
                    ]);
                }
            } elseif ($request->action === 'mark_as_ngn') {
                $lockedUser->currency_reconciliation_status = 'reconciled_ngn';
                $lockedUser->currency_code = 'NGN';
                $lockedUser->reconciliation_notes = "Manual reconciliation by " . auth()->user()->username . ": Confirmed balance ₦{$oldBalance} was already NGN. " . ($request->notes ?? '');
                $lockedUser->save();
            } elseif ($request->action === 'set_custom') {
                $newBalance = round((float) $request->custom_balance, 4);
                $lockedUser->balance = $newBalance;
                $lockedUser->currency_reconciliation_status = 'manually_reconciled';
                $lockedUser->currency_code = 'NGN';
                $lockedUser->reconciliation_notes = "Manual override by " . auth()->user()->username . ": Adjusted from {$oldBalance} to ₦{$newBalance}. " . ($request->notes ?? '');
                $lockedUser->save();

                $difference = round($newBalance - $oldBalance, 4);
                if ($difference != 0.0) {
                    \App\Models\Transaction::create([
                        'user_id' => $lockedUser->id,
                        'amount' => abs($difference),
                        'type' => $difference > 0 ? 'credit' : 'debit',
                        'transaction_type' => 'manual_adjustment',
                        'balance_before' => $oldBalance,
                        'balance_after' => $newBalance,
                        'description' => "Manual balance reconciliation adjustment",
                        'reference' => 'MANREC_' . strtoupper(\Illuminate\Support\Str::random(8)),
                    ]);
                }
            }

            \App\Models\ActivityLog::log("Manually reconciled user #{$lockedUser->id} currency balance", auth()->user(), [
                'action' => $request->action,
                'old_balance' => $oldBalance,
                'new_balance' => $lockedUser->balance,
                'notes' => $request->notes,
            ]);
        });

        return back()->with('success', 'User balance successfully reconciled.');
    }

    /**
     * Run wallet recovery command directly from Admin interface (Dry Run or Apply)
     */
    public function hqRunWalletRecovery(Request $request)
    {
        $action = $request->input('recovery_action', 'dry_run');
        $params = [];

        if ($action === 'apply') {
            $params['--apply'] = true;
            $params['--force'] = true;
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('wallet:recover-legacy-balances', $params);
            $output = \Illuminate\Support\Facades\Artisan::output();

            \App\Models\ActivityLog::log("Executed wallet recovery command via Admin HQ ({$action})", auth()->user(), [
                'action' => $action,
                'output' => substr($output, 0, 500)
            ]);

            return back()->with('recovery_output', $output)
                         ->with($action === 'apply' ? 'success' : 'info', $action === 'apply' ? 'Wallet recovery successfully executed!' : 'Dry run audit completed. See output log below.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Execution error: ' . $e->getMessage());
        }
    }

    /**
     * Show Provider Pricing & Markup Settings
     */
    public function hqProviderPricingSettings(Request $request)
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'pricing_mode')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Artisan migrate auto-call failed: " . $e->getMessage());
            }

            if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'pricing_mode')) {
                try {
                    \Illuminate\Support\Facades\Schema::table('providers', function (\Illuminate\Database\Schema\Blueprint $table) {
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'currency')) {
                            $table->string('currency', 10)->default('USD');
                        }
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'pricing_mode')) {
                            $table->string('pricing_mode', 30)->default('percentage');
                        }
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'percentage_markup')) {
                            $table->decimal('percentage_markup', 8, 2)->default(30.00);
                        }
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'fixed_markup_per_k')) {
                            $table->decimal('fixed_markup_per_k', 12, 2)->default(0.00);
                        }
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'min_selling_price')) {
                            $table->decimal('min_selling_price', 12, 2)->nullable();
                        }
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'min_profit')) {
                            $table->decimal('min_profit', 12, 2)->nullable();
                        }
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'rounding_rule')) {
                            $table->string('rounding_rule', 30)->default('2_decimal');
                        }
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'last_synced_at')) {
                            $table->timestamp('last_synced_at')->nullable();
                        }
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'last_sync_status')) {
                            $table->string('last_sync_status', 50)->nullable();
                        }
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('providers', 'last_sync_message')) {
                            $table->text('last_sync_message')->nullable();
                        }
                    });
                } catch (\Throwable $e2) {
                    \Illuminate\Support\Facades\Log::warning("Direct schema alter failed: " . $e2->getMessage());
                }
            }
        }

        $providers = Provider::all();
        $providerId = $request->query('provider_id');
        $selectedProvider = $providerId ? Provider::find($providerId) : ($providers->firstWhere('name', 'Momopanel') ?? $providers->first());

        $usdToNgnRate = app(\App\Services\SmmPricingService::class)->getExchangeRate();

        $mappedCount = 0;
        $activeMappedCount = 0;
        if ($selectedProvider) {
            try {
                $mappedCount = $selectedProvider->mappings()->count();
                $activeMappedCount = $selectedProvider->mappings()->where('is_active', true)->count();
            } catch (\Throwable $e) {
                $mappedCount = 0;
                $activeMappedCount = 0;
            }
        }

        return view('hq.providers.pricing', compact(
            'providers',
            'selectedProvider',
            'usdToNgnRate',
            'mappedCount',
            'activeMappedCount'
        ));
    }

    /**
     * Update Provider Pricing & Markup Settings
     */
    public function hqUpdateProviderPricingSettings(Request $request)
    {
        $request->validate([
            'provider_id'        => 'required|exists:providers,id',
            'fix_rate'           => 'nullable|numeric|min:0.0001',
            'currency'           => 'required|string|in:USD,NGN',
            'pricing_mode'       => 'required|string|in:percentage,fixed_per_k,combined',
            'percentage_markup'  => 'required|numeric|min:0|max:5000',
            'fixed_markup_per_k' => 'required|numeric|min:0',
            'min_selling_price'  => 'nullable|numeric|min:0',
            'min_profit'         => 'nullable|numeric|min:0',
            'rounding_rule'      => 'required|string|in:2_decimal,whole_naira,nearest_10',
        ]);

        if ($request->filled('fix_rate') && (float) $request->fix_rate > 0) {
            $boostRate = (float) $request->fix_rate;
            \App\Models\Setting::set('pricing_boost_fix_rate', (string) $boostRate);
            \App\Models\Setting::set('boost_fix_rate', (string) $boostRate);
            \App\Models\Setting::clearCache();

            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('product_pricing_settings')) {
                    \App\Models\ProductPricingSetting::updateOrCreate(
                        ['product_key' => 'boost'],
                        [
                            'fix_rate'           => $boostRate,
                            'currency'           => strtoupper($request->currency),
                            'pricing_mode'       => $request->pricing_mode,
                            'percentage_markup'  => (float) $request->percentage_markup,
                            'fixed_markup'       => (float) $request->fixed_markup_per_k,
                            'min_selling_price'  => $request->filled('min_selling_price') ? (float) $request->min_selling_price : null,
                            'min_profit'         => $request->filled('min_profit') ? (float) $request->min_profit : null,
                            'rounding_rule'      => $request->rounding_rule,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Could not persist boost fix_rate: " . $e->getMessage());
            }
        }

        $provider = Provider::findOrFail($request->provider_id);
        
        $oldSettings = [
            'pricing_mode'      => $provider->getPricingMode(),
            'percentage_markup' => $provider->getPercentageMarkup(),
            'fixed_markup_per_k'=> $provider->getFixedMarkupPerK(),
        ];

        $provider->update([
            'currency'           => strtoupper($request->currency),
            'pricing_mode'       => $request->pricing_mode,
            'percentage_markup'  => (float) $request->percentage_markup,
            'fixed_markup_per_k' => (float) $request->fixed_markup_per_k,
            'min_selling_price'  => $request->filled('min_selling_price') ? (float) $request->min_selling_price : null,
            'min_profit'         => $request->filled('min_profit') ? (float) $request->min_profit : null,
            'rounding_rule'      => $request->rounding_rule,
        ]);

        \App\Models\ActivityLog::log("Updated markup settings for provider {$provider->name}", auth()->user(), [
            'provider_id'  => $provider->id,
            'old_settings' => $oldSettings,
            'new_settings' => [
                'pricing_mode'      => $request->pricing_mode,
                'percentage_markup' => (float) $request->percentage_markup,
                'fixed_markup_per_k'=> (float) $request->fixed_markup_per_k,
                'min_selling_price' => $request->min_selling_price,
                'min_profit'        => $request->min_profit,
                'rounding_rule'     => $request->rounding_rule,
            ]
        ]);

        return redirect()->route('hq.providers.pricing', ['provider_id' => $provider->id])
            ->with('success', "Pricing & markup settings for {$provider->name} saved successfully.");
    }

    /**
     * Preview Momopanel price recalculation with live rates
     */
    public function hqPreviewProviderSync(Request $request, SmmPricingService $pricingService)
    {
        $request->validate([
            'provider_id' => 'required|exists:providers,id',
        ]);

        $provider = Provider::findOrFail($request->provider_id);
        $preview = $pricingService->previewProviderSync($provider);

        if ($request->wantsJson()) {
            return response()->json($preview);
        }

        if (!$preview['success']) {
            return back()->with('error', $preview['error']);
        }

        return back()->with('sync_preview', $preview);
    }

    /**
     * Execute live Momopanel service synchronization & update catalog selling prices
     */
    public function hqExecuteProviderSync(Request $request, SmmPricingService $pricingService)
    {
        $request->validate([
            'provider_id' => 'required|exists:providers,id',
        ]);

        $provider = Provider::findOrFail($request->provider_id);
        $result = $pricingService->executeProviderSync($provider);

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['message']);
    }
}


