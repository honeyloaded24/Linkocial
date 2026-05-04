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

        // Order Metrics
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $completedOrders = Order::where('status', 'completed')->count();

        // Financial Metrics
        $totalDeposited = \App\Models\Payment::where('status', 'success')->sum('amount');
        $totalSpent = Order::whereIn('status', ['completed', 'partial'])->where('is_refunded', false)->sum('charge');
        $totalCost = Order::whereIn('status', ['completed', 'partial'])->where('is_refunded', false)->sum('provider_cost');
        $totalProfit = $totalSpent - $totalCost;

        // Affiliate Metrics
        $totalCommissionEarned = User::sum('total_earned_commission');
        $totalCommissionWithdrawn = User::sum('total_withdrawn_commission');
        $pendingCommission = User::sum('commission_balance');

        $pendingServices = Service::where('is_active', false)->count();

        // --- CHART DATA (Last 30 Days) ---
        $days = [];
        $orderData = [];
        $profitData = [];
        $userData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $days[] = now()->subDays($i)->format('M d');

            $orderData[] = Order::whereDate('created_at', $date)->count();
            $profitData[] = Order::whereDate('created_at', $date)->sum('profit');
            $userData[] = User::whereDate('created_at', $date)->count();
        }

        $chartData = [
            'labels' => $days,
            'orders' => $orderData,
            'profits' => $profitData,
            'users' => $userData,
        ];

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
            'chartData'
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
    public function users()
    {
        $users = User::latest()->paginate(20);
        return view('hq.users.index', compact('users'));
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
        $oldRole = $user->role;

        $user->update([
            'role' => $request->role,
            'is_admin' => ($request->role === 'admin')
        ]);

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
     * Get Provider Services Count via AJAX
     */
    public function providerServicesCount($id, SmmProviderService $smmService)
    {
        $provider = Provider::findOrFail($id);
        $services = $smmService->getServices($provider);

        return response()->json([
            'count' => $services && is_array($services) ? count($services) : 'Error'
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
     * Trigger background synchronization of services
     */
    public function triggerServiceSync()
    {
        \App\Jobs\SyncSmmServicesJob::dispatch();
        return back()->with('success', 'Service synchronization has been queued in the background. Refresh the page in a few moments to see updates.');
    }
    /**
     * Update user balance manually
     */
    public function updateUserBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:add,subtract'
        ]);

        $user = User::findOrFail($id);

        if ($request->type === 'add') {
            $user->balance += $request->amount;
        } else {
            $user->balance -= $request->amount;
        }

        $user->save();

        \App\Models\Transaction::add(
            $user->id,
            $request->amount,
            $request->type === 'add' ? 'credit' : 'debit',
            'manual',
            "Admin adjustment (" . ($request->type === 'add' ? 'Added' : 'Deducted') . ")"
        );

        \App\Models\ActivityLog::log("Manually updated user balance", $user, [
            'type' => $request->type,
            'amount' => $request->amount,
            'new_balance' => $user->balance
        ]);

        return back()->with('success', "User balance updated successfully. New balance: ₦" . number_format((float) $user->balance, 2));
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

            // Check if they have enough balance to be re-charged
            if ((float) $user->balance < (float) $order->charge) {
                return back()->with('error', "Cannot force complete. User has already been refunded and now has insufficient balance (₦" . number_format((float) $user->balance, 2) . ") to be re-charged.");
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $user) {
                \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->decrement('balance', (float) $order->charge);
                $user->refresh();

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

        \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
            $user = $order->user;

            // Only refund if not already marked as refunded
            if (!$order->is_refunded) {
                \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->increment('balance', (float) $order->charge);
                $user->refresh(); // Sync the user object

                \App\Models\Transaction::add(
                    $user->id,
                    $order->charge,
                    'credit',
                    'refund',
                    "Manual refund for order #{$order->id} by Admin"
                );

                $order->is_refunded = true;
            }

            $order->status = 'canceled';
            $order->save();

            \App\Models\ActivityLog::log("Manually canceled order", $order, [
                'was_refunded_now' => !$order->was_already_refunded_before_this_call, // Simplified logic
                'refund_amount' => $order->charge,
                'new_balance' => $user->balance
            ]);

            // Send Notification to user
            \App\Models\Notification::send(
                $order->user_id,
                "Order Refunded",
                "Order #{$order->id} has been canceled. ₦" . number_format((float) $order->charge, 2) . " has been refunded to your wallet.",
                'warning',
                route('orders.index')
            );
        });

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

            if ((float) $user->balance < (float) $order->charge) {
                return back()->with('error', "Insufficient user balance to resubmit this order. (Needed: ₦" . number_format((float) $order->charge, 2) . ")");
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $user) {
                \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->decrement('balance', (float) $order->charge);
                $user->refresh(); // Force refresh to prevent stale data overwrites

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

        // Stats
        $totalSpent = Order::where('user_id', $id)->whereIn('status', ['completed', 'partial'])->where('is_refunded', false)->sum('charge');
        $totalDeposits = \App\Models\Payment::where('user_id', $id)->where('status', 'success')->sum('amount');
        $totalOrders = Order::where('user_id', $id)->count();
        $totalTransactions = $totalOrders + \App\Models\Payment::where('user_id', $id)->count();

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

        // Store original admin ID in session to allow "returning"
        session(['impersonator_id' => Auth::id()]);

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
        $admin = User::findOrFail($adminId);

        Auth::login($admin);

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
        $orders = Order::where('user_id', $id)->with('service')->latest()->get();

        return view('hq.users.transactions', compact('user', 'deposits', 'orders'));
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
     * Show site settings page
     */
    public function hqSettings()
    {
        $channels = Service::getChannels();
        return view('hq.settings', compact('channels'));
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
        $brandFiles = ['site_logo_light', 'site_logo_dark', 'site_favicon'];
        foreach ($brandFiles as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store('branding', 'public');
                \App\Models\Setting::set($field, '/storage/' . $path);
            }
        }

        // Handle all other settings
        $settings = $request->except(['_token', 'platforms', 'descriptions', 'site_logo_light', 'site_logo_dark', 'site_favicon']);

        foreach ($settings as $key => $value) {
            \App\Models\Setting::set($key, $value);
        }

        \App\Models\ActivityLog::log("Updated global site settings", auth()->user(), $request->all());

        return back()->with('success', 'Settings updated successfully.');
        /**
     * Show background queue status and failed jobs
     */
    public function queueStatus()
    {
        $queueSize = 0;
        try {
            // Using the discovered prefix: linkocial-database-
            // Laravel's Redis facade prepends the prefix automatically if configured
            $queueSize = \Illuminate\Support\Facades\Redis::llen('queues:default');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Redis Queue Monitor Error: " . $e->getMessage());
        }

        $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->latest()->paginate(15);
        
        return view('hq.queues', compact('queueSize', 'failedJobs'));
    }
}
