<?php

namespace App\Http\Controllers;

use App\Models\VtuOrder;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVtuController extends Controller
{
    /**
     * Display a listing of the VTU orders and financial records.
     */
    public function index(Request $request)
    {
        $query = VtuOrder::with('user')->latest();

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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone_or_meter', 'like', "%{$search}%")
                  ->orWhere('api_reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('username', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        $vtuOrders = $query->paginate(20)->withQueryString();

        // Authoritative VTU product financial summary
        $vtuStats = app(\App\Services\FinancialMetricsService::class)->getVtuSummary($startDate, $endDate);

        return view('hq.vtu.index', compact('vtuOrders', 'vtuStats'));
    }

    /**
     * Refund a failed/stuck VTU order manually.
     */
    public function refund($id)
    {
        return DB::transaction(function () use ($id) {
            $order = VtuOrder::lockForUpdate()->findOrFail($id);

            if ($order->status === 'refunded') {
                return back()->with('error', 'This order has already been refunded.');
            }

            $user = User::lockForUpdate()->findOrFail($order->user_id);

            // Add funds back to user atomically
            $user->addBalance($order->cost);

            // Log transaction
            Transaction::add(
                $order->user_id,
                $order->cost,
                'credit',
                'vtu_refund',
                'Refund for VTU order #' . $order->id,
                'REF-VTU-' . $order->id . '-' . time()
            );

            $order->status = 'refunded';
            $order->save();

            return back()->with('success', 'Order has been refunded successfully.');
        });
    }
}
