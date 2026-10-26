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

    /**
     * Show VTU Pricing & Markup Settings
     */
    public function pricing(\App\Services\ProductPricingService $pricingService)
    {
        $settings = $pricingService->getPricingSettings(\App\Services\ProductPricingService::PRODUCT_VTU);
        
        $rawPlans = \App\Services\VtuDataService::getRawPlans();
        $dataPlanCount = 0;
        foreach ($rawPlans as $net => $plans) {
            $dataPlanCount += count($plans);
        }

        $vtuStats = app(\App\Services\FinancialMetricsService::class)->getVtuSummary(null, null);

        return view('hq.vtu.pricing', compact(
            'settings',
            'dataPlanCount',
            'vtuStats'
        ));
    }

    /**
     * Update VTU Pricing & Markup Settings
     */
    public function updatePricing(Request $request, \App\Services\ProductPricingService $pricingService)
    {
        $request->validate([
            'fix_rate'           => 'required|numeric|min:0.0001',
            'currency'           => 'required|string|in:USD,NGN',
            'pricing_mode'       => 'required|string|in:percentage,fixed,combined,fixed_per_k',
            'percentage_markup'  => 'required|numeric|min:0|max:5000',
            'fixed_markup'       => 'required|numeric|min:0',
            'min_selling_price'  => 'nullable|numeric|min:0',
            'min_profit'         => 'nullable|numeric|min:0',
            'rounding_rule'      => 'required|string|in:2_decimal,whole_naira,nearest_10',
        ]);

        $pricingService->updatePricingSettings(
            \App\Services\ProductPricingService::PRODUCT_VTU,
            $request->all(),
            auth()->user()
        );

        return redirect()->route('hq.vtu.pricing')
            ->with('success', 'Pricing & markup settings for VTU saved successfully.');
    }
}

