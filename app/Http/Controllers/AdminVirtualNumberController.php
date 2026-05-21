<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VirtualNumberOrder;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Services\SmsPoolService;

class AdminVirtualNumberController extends Controller
{
    /**
     * List all Virtual Number orders for admins
     */
    public function index(Request $request)
    {
        $query = VirtualNumberOrder::with('user')->latest();

        // Search by phone number, service, or user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('product', 'like', "%{$search}%")
                  ->orWhere('api_order_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(30)->withQueryString();

        $vnStats = [
            'total' => VirtualNumberOrder::count(),
            'pending' => VirtualNumberOrder::where('status', 'pending')->count(),
            'received' => VirtualNumberOrder::where('status', 'received')->count(),
            'canceled' => VirtualNumberOrder::where('status', 'canceled')->count(),
            'finished' => VirtualNumberOrder::where('status', 'finished')->count(),
            'total_revenue' => VirtualNumberOrder::whereIn('status', ['received', 'finished'])->sum('cost'),
            'total_cost' => VirtualNumberOrder::whereIn('status', ['received', 'finished'])->sum('provider_cost'),
        ];

        return view('hq.virtual_numbers.index', compact('orders', 'vnStats'));
    }

    /**
     * Force refund/cancel an order
     */
    public function refund($id, SmsPoolService $smspool)
    {
        $order = VirtualNumberOrder::with('user')->findOrFail($id);

        if ($order->status === 'canceled') {
            return back()->with('error', 'That order is already canceled and refunded.');
        }

        try {
            DB::beginTransaction();

            // Attempt to tell SMSPool to cancel it so we get our API money back too, if it's pending.
            if ($order->status === 'pending' || $order->status === 'received') {
                try {
                    $smspool->cancelOrder($order->api_order_id);
                } catch (\Exception $e) {
                    // Ignore API fails on manual admin overrides to ensure the user gets refunded regardless
                }
            }

            // Perform Refund
            $order->user->increment('balance', $order->cost);
            
            if ($order->cost > 0) {
                Transaction::add(
                    $order->user_id, 
                    $order->cost, 
                    'credit', 
                    'refund', 
                    "Admin manual refund for Virtual Number #{$order->id} ({$order->product})"
                );
            }

            // Rollback coupon usage if applicable
            if ($order->coupon_code) {
                $coupon = \App\Models\Coupon::where('code', $order->coupon_code)->first();
                if ($coupon) {
                    $usage = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                        ->where('virtual_number_order_id', $order->id)
                        ->first();
                    if ($usage) {
                        $usage->delete();
                        $coupon->decrement('usage_count');
                    }
                }
            }

            $order->update(['status' => 'canceled']);

            \App\Models\ActivityLog::log("Manually refunded Virtual Number #{$order->id}", auth()->user());

            DB::commit();

            return back()->with('success', "Order #{$order->id} successfully canceled and $ {$order->cost} refunded to user.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to process refund: ' . $e->getMessage());
        }
    }
}
