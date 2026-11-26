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

        // Authoritative Virtual Number financial summary
        $summary = app(\App\Services\FinancialMetricsService::class)->getVirtualNumberSummary($startDate, $endDate);

        $vnStats = array_merge($summary, [
            'total_revenue' => $summary['revenue'],
            'total_cost'    => $summary['provider_cost'],
            'received'      => $summary['completed'],
            'canceled'      => $summary['failed'],
            'finished'      => 0,
        ]);

        return view('hq.virtual_numbers.index', compact('orders', 'vnStats'));
    }

    /**
     * Force refund/cancel an order
     */
    public function refund($id, SmsPoolService $smspool, \App\Services\GrizzlySmsService $grizzly, \App\Services\SmsBowerService $smsbower, \App\Services\DaisySmsService $daisysms)
    {
        try {
            DB::beginTransaction();

            $order = VirtualNumberOrder::with('user')
                ->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            $refundReference = 'REF-VN-ADMIN-' . $order->id;
            $automaticRefundReference = 'REF-VN-' . $order->id;
            if (in_array($order->status, ['canceled', 'failed'], true)
                || Transaction::whereIn('reference', [$refundReference, $automaticRefundReference])->exists()) {
                DB::rollBack();
                return back()->with('error', 'That order is already terminal or has already been refunded.');
            }

            // Admin refunds are not allowed after an SMS has been received or for
            // unresolved/terminal provider states. For a pending order, provider
            // cancellation must be confirmed before issuing a customer refund.
            if ($order->status !== 'pending'
                || !empty($order->sms_code)
                ) {
                DB::rollBack();
                return back()->with('error', 'Only pending virtual-number orders without received SMS can be refunded.');
            }

            $provider = match ($order->provider_key ?? null) {
                'daisysms' => $daisysms,
                'smsbower' => $smsbower,
                default => match ($order->server) {
                    'server2' => $grizzly,
                    'server3' => $smsbower,
                    default => $smspool,
                },
            };
            $provider->cancelOrder($order->api_order_id);

            // Perform Refund
            $order->user->addBalance($order->cost);
            
            if ($order->cost > 0) {
                Transaction::add(
                    $order->user_id,
                    $order->cost,
                    'credit',
                    'refund',
                    "Admin manual refund for Virtual Number #{$order->id} ({$order->product})",
                    $refundReference
                );
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

    /**
     * Save the DaisySMS credential for the independent Server 4 integration.
     */
    public function updateDaisySmsKey(Request $request)
    {
        $validated = $request->validate([
            'daisysms_api_key' => 'required|string|max:500',
        ]);

        \App\Models\Setting::set('daisysms_api_key', trim($validated['daisysms_api_key']));

        return redirect()->route('hq.virtual-numbers.pricing')
            ->with('success', 'DaisySMS API key saved securely. Server 4 is now available independently of Server 3.');
    }


    /**
     * Read the live USA service catalogue for Server 4 admin verification.
     * The API credential is never returned or logged.
     */
    public function daisySmsCatalogue(\App\Services\DaisySmsService $daisysms)
    {
        try {
            return response()->json([
                'success' => true,
                'country_id' => \App\Services\DaisySmsService::USA_COUNTRY_ID,
                'services' => $daisysms->getCatalogueForCountry(\App\Services\DaisySmsService::USA_COUNTRY_ID),
                'catalogue_diagnostics' => $daisysms->getCatalogueDiagnostics(),
                'routing_enabled' => true,
                'server' => 'Server 4',
                'message' => 'Live Server 4 catalogue. This diagnostic does not purchase a number.',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DaisySMS admin catalogue check failed: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to read the live Server 4 catalogue. Check the API key and provider response.',
            ], 502);
        }
    }

    /**
     * Show Virtual Numbers Pricing & Markup Settings
     */
    public function pricing(\App\Services\ProductPricingService $pricingService)
    {
        $settings = $pricingService->getPricingSettings(\App\Services\ProductPricingService::PRODUCT_VIRTUAL_NUMBERS);

        $vnStats = app(\App\Services\FinancialMetricsService::class)->getVirtualNumberSummary(null, null);

        $hasServer1 = !empty(\App\Models\Setting::get('smspool_api_key'));
        $hasServer2 = !empty(\App\Models\Setting::get('grizzly_api_key'));
        $hasServer3 = !empty(\App\Models\Setting::get('smsbower_api_key'));
        $hasDaisySmsKey = !empty(\App\Models\Setting::get('daisysms_api_key'));

        return view('hq.virtual_numbers.pricing', compact(
            'settings',
            'vnStats',
            'hasServer1',
            'hasServer2',
            'hasServer3',
            'hasDaisySmsKey'
        ));
    }

    /**
     * Update Virtual Numbers Pricing & Markup Settings
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
            \App\Services\ProductPricingService::PRODUCT_VIRTUAL_NUMBERS,
            $request->all(),
            auth()->user()
        );

        return redirect()->route('hq.virtual-numbers.pricing')
            ->with('success', 'Pricing & markup settings for Virtual Numbers saved successfully.');
    }
}

