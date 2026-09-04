<?php

namespace App\Http\Controllers;

use App\Models\VtuOrder;
use App\Models\User;
use Illuminate\Http\Request;

class AdminVtuController extends Controller
{
    /**
     * Display a listing of the VTU orders.
     */
    public function index(Request $request)
    {
        $query = VtuOrder::with('user')->latest();

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

        return view('hq.vtu.index', compact('vtuOrders'));
    }

    /**
     * Refund a failed/stuck VTU order manually.
     */
    public function refund($id)
    {
        $order = VtuOrder::findOrFail($id);

        if ($order->status === 'refunded') {
            return back()->with('error', 'This order has already been refunded.');
        }

        // Add funds back to user
        $order->user->balance += $order->cost;
        $order->user->save();

        // Log transaction
        \App\Models\Transaction::add(
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
    }
}
