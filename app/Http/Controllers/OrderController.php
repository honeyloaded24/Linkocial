<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Order;
use App\Services\ProviderRouter;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessSmmOrder;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected ProviderRouter $router;

    public function __construct(ProviderRouter $router)
    {
        $this->router = $router;
    }

    /**
     * Show the order history for the user.
     */
    public function index()
    {
        $orders = Auth::user()->orders()->with('service')->latest()->paginate(15);
        return view('orders.index', compact('orders'));
    }

    /**
     * Show the form to place a new order.
     */
    public function create()
    {
        $services = Service::where('is_active', true)->get();
        $channels = Service::getChannels();
        return view('orders.create', compact('services', 'channels'));
    }

    /**
     * Place a new SMM Order
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'link'       => 'required|url',
            'quantity'   => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $service = Service::findOrFail($request->service_id);

        if (!$service->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'This service is currently disabled.'], 400);
            }
            return back()->with('error', 'This service is currently disabled.')->withInput();
        }

        // Check if quantity is within limits
        if ($request->quantity < $service->min || $request->quantity > $service->max) {
            $msg = "Quantity must be between {$service->min} and {$service->max}.";
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        // Calculate base cost to user (Assuming selling_price is per 1000 items)
        $baseCharge = ($service->selling_price / 1000) * $request->quantity;

        // --- Apply Tiered Discounts ---
        $discountPercent = 0;
        if ($user->role === 'reseller') {
            $discountPercent = (float) \App\Models\Setting::get('reseller_discount', 5);
        } elseif ($user->role === 'vip') {
            $discountPercent = (float) \App\Models\Setting::get('vip_discount', 10);
        }

        $discountAmount = ($baseCharge * $discountPercent) / 100;
        $charge = $baseCharge - $discountAmount;

        // 1. Check user balance internally
        if ($user->balance < $charge) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Insufficient wallet balance.'], 402); // Payment Required
            }
            return back()->with('error', 'Insufficient wallet balance. Please fund your account to continue.')->withInput();
        }

        // 2. Begin Transaction for local changes
        DB::beginTransaction();

        try {
            // Force a direct database update to bypass any model session caching issues
            DB::table('users')->where('id', $user->id)->decrement('balance', (float)$charge);
            $user->refresh(); // Refresh the in-memory object for the current request

            // 3. Record in Financial Ledger
            \App\Models\Transaction::add(
                $user->id, 
                $charge, 
                'debit', 
                'order', 
                "Order placement for {$service->name}" . ($discountPercent > 0 ? " ({$discountPercent}% Tier Discount applied)" : "")
            );

            // Create pending order record
            $order = Order::create([
                'user_id'    => $user->id,
                'service_id' => $service->id,
                'link'       => $request->link,
                'quantity'   => $request->quantity,
                'charge'     => $charge,
                'status'     => 'pending',
            ]);

            // Send Notification to user
            \App\Models\Notification::send(
                $user->id,
                "Order Placed Successfully",
                "Your order #{$order->id} for {$service->name} has been received and is pending processing.",
                'success',
                route('orders.index')
            );

            // Send Order Success Email
            try {
                \Illuminate\Support\Facades\Mail::send('emails.order_success', [
                    'order_id' => $order->id,
                    'service_name' => $service->name,
                    'quantity' => $order->quantity,
                    'amount' => $order->charge
                ], function($message) use ($user, $order) {
                    $message->to($user->email)->subject("Order Confirmed! #{$order->id} ✅");
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Order email failed: " . $e->getMessage());
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to process order internally.'], 500);
            }
            return back()->with('error', 'Something went wrong during order placement. Please try again.')->withInput();
        }

        // 3. Dispatch the Job to handle the external API call asynchronously
        ProcessSmmOrder::dispatch($order, $service);

        // 4. Instantly redirect back with a success message
        return redirect()->route('orders.index')->with('success', 'Order placed successfully! ' . ($discountPercent > 0 ? "Your {$discountPercent}% Tier Discount was applied." : ""));
    }

    /**
     * Show the bulk order form
     */
    public function bulkCreate()
    {
        return view('orders.bulk');
    }

    /**
     * Process bulk orders
     * Format: service_id|quantity|link (one per line)
     */
    public function bulkStore(Request $request)
    {
        $request->validate(['bulk_data' => 'required|string']);

        $lines = explode("\n", str_replace("\r", "", $request->bulk_data));
        $successCount = 0;
        $errorLines = [];

        $user = Auth::user();
        $resellerDiscount = (float) \App\Models\Setting::get('reseller_discount', 5);
        $vipDiscount = (float) \App\Models\Setting::get('vip_discount', 10);

        foreach ($lines as $index => $line) {
            if (empty(trim($line))) continue;

            $parts = explode('|', $line);
            if (count($parts) < 3) {
                $errorLines[] = "Line " . ($index + 1) . ": Invalid format (Use ID|Quantity|Link)";
                continue;
            }

            $serviceId = trim($parts[0]);
            $quantity = (int) trim($parts[1]);
            $link = trim($parts[2]);

            $service = Service::find($serviceId);
            if (!$service || !$service->is_active) {
                $errorLines[] = "Line " . ($index + 1) . ": Service ID {$serviceId} not found or inactive.";
                continue;
            }

            if ($quantity < $service->min || $quantity > $service->max) {
                $errorLines[] = "Line " . ($index + 1) . ": Quantity {$quantity} out of range for service {$serviceId}.";
                continue;
            }

            // Calculate Charge
            $baseCharge = ($service->selling_price / 1000) * $quantity;
            $discountPercent = ($user->role === 'reseller') ? $resellerDiscount : (($user->role === 'vip') ? $vipDiscount : 0);
            $charge = $baseCharge - (($baseCharge * $discountPercent) / 100);

            if ($user->balance < $charge) {
                $errorLines[] = "Line " . ($index + 1) . ": Insufficient balance for this order.";
                continue;
            }

            // Process Order
            DB::beginTransaction();
            try {
                $user->decrement('balance', $charge);

                \App\Models\Transaction::add($user->id, $charge, 'debit', 'order', "Bulk Order: {$service->name}");

                $order = Order::create([
                    'user_id' => $user->id,
                    'service_id' => $service->id,
                    'link' => $link,
                    'quantity' => $quantity,
                    'charge' => $charge,
                    'status' => 'processing',
                ]);

                DB::commit();
                ProcessSmmOrder::dispatch($order, $service);
                $successCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $errorLines[] = "Line " . ($index + 1) . ": System error processing order.";
            }
        }

        if ($successCount > 0) {
            $msg = "{$successCount} orders placed successfully.";
            if (count($errorLines) > 0) {
                return redirect()->route('orders.index')->with('success', $msg)->with('error_list', $errorLines);
            }
            return redirect()->route('orders.index')->with('success', $msg);
        }

        return back()->withErrors($errorLines)->withInput();
    }
}
