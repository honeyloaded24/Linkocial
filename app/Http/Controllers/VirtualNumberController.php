<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SmsPoolService;
use App\Models\VirtualNumberOrder;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VirtualNumberController extends Controller
{
    protected SmsPoolService $smspool;

    public function __construct(SmsPoolService $smspool)
    {
        $this->smspool = $smspool;
    }

    /**
     * Show the main Virtual Numbers purchasing interface.
     */
    public function index()
    {
        $activeOrders = VirtualNumberOrder::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('services.virtual_numbers', compact('activeOrders'));
    }

    /**
     * Show dedicated virtual numbers history/archive page.
     */
    public function history()
    {
        $orders = VirtualNumberOrder::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('services.virtual_numbers_history', compact('orders'));
    }

    /**
     * API: Get cached countries list from SMSPool.
     */
    public function getCountries()
    {
        $cacheKey = "smspool_countries";
        
        $data = Cache::remember($cacheKey, 86400, function () {
            try {
                return $this->smspool->getCountries();
            } catch (\Exception $e) {
                Log::error('SMSPool getCountries failed in controller', ['error' => $e->getMessage()]);
                return [];
            }
        });

        return response()->json($data);
    }

    /**
     * API: Get dynamic products list from SMSPool for a specific country.
     * Caches the results for 5 minutes.
     */
    public function getProducts(Request $request)
    {
        $country = $request->query('country');
        
        if (!$country) {
            return response()->json(['error' => 'Country ID is required.'], 400);
        }

        $markupFee = $this->getMarkupFee();
        
        try {
            $cacheKey = "smspool_prices_{$country}";
            $pricesData = Cache::remember($cacheKey, 600, function () use ($country) {
                return $this->smspool->getPricesForCountry($country);
            });
            
            // Group by service ID/name and choose the lowest price across different pools
            $grouped = [];
            foreach ($pricesData as $item) {
                $serviceId = $item['service'] ?? null;
                $serviceName = $item['service_name'] ?? null;
                $price = isset($item['price']) ? (float)$item['price'] : null;

                if (!$serviceId || !$serviceName || $price === null) {
                    continue;
                }

                // Keep the cheapest price for each unique service (comparing raw wholesale prices)
                if (!isset($grouped[$serviceName]) || $price < $grouped[$serviceName]['raw_price']) {
                    $grouped[$serviceName] = [
                        'id' => $serviceId,
                        'name' => $serviceName,
                        'raw_price' => $price
                    ];
                }
            }
            
            // Map to final array with markup fee applied
            $finalServices = [];
            foreach ($grouped as $service) {
                $finalServices[] = [
                    'id' => $service['id'],
                    'name' => $service['name'],
                    'price' => round($service['raw_price'] + $markupFee, 2)
                ];
            }
            
            return response()->json($finalServices);
        } catch (\Exception $e) {
            Log::error('SMSPool getPricesForCountry failed in controller', ['country' => $country, 'error' => $e->getMessage()]);
            return response()->json([]);
        }
    }


    /**
     * Process the purchase of a virtual number
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'country' => 'required',
            'product' => 'required',
        ]);

        $country = $request->country; // Country ID
        $product = $request->product; // Service ID or Name
        $user = Auth::user();

        try {
            // Retrieve pricing for this country to estimate cost (check cache first)
            $cacheKey = "smspool_prices_{$country}";
            $pricesData = Cache::remember($cacheKey, 600, function () use ($country) {
                return $this->smspool->getPricesForCountry($country);
            });
            
            $cheapestPrice = null;
            $serviceName = null;
            foreach ($pricesData as $item) {
                if ((string)$item['service'] === (string)$product || strtolower($item['service_name']) === strtolower($product)) {
                    $price = (float)$item['price'];
                    if ($cheapestPrice === null || $price < $cheapestPrice) {
                        $cheapestPrice = $price;
                        $serviceName = $item['service_name'];
                        $productId = $item['service'];
                    }
                }
            }

            if ($cheapestPrice === null) {
                return response()->json(['error' => 'Product not available for this country.'], 400);
            }

            // Calculate estimated user cost (SMSPool is natively in USD)
            $markupFee = $this->getMarkupFee(); 
            $userCostUsd = $cheapestPrice + $markupFee;  

            // Ensure user has enough balance and reserve it before the slow external API call
            $finalCheckCost = max(0.00, $userCostUsd);

            DB::beginTransaction();
            try {
                // Lock the user record to prevent concurrent check-out race conditions
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();

                if ($lockedUser->balance < $finalCheckCost) {
                    DB::rollBack();
                    return response()->json(['error' => 'Insufficient wallet balance.'], 402);
                }

                // Reserve the estimated balance immediately
                $lockedUser->decrement('balance', $finalCheckCost);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            // Execute Purchase
            try {
                // Pass cheapestPrice as the maxPrice parameter to avoid paying more if pool prices spike
                $buyResponse = $this->smspool->buyNumber($country, $productId, $cheapestPrice);
            } catch (\Exception $e) {
                // Refund the reserved balance if purchase fails
                DB::beginTransaction();
                try {
                    $refundUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                    $refundUser->increment('balance', $finalCheckCost);
                    DB::commit();
                } catch (\Exception $ex) {
                    DB::rollBack();
                    Log::error('Failed to refund reserved balance after failed purchase', [
                        'user_id' => $user->id,
                        'amount' => $finalCheckCost,
                        'error' => $ex->getMessage()
                    ]);
                }
                throw $e;
            }

            // Extract order details
            $apiOrderId = $buyResponse['order_id'] ?? null;
            $phoneNumber = '+' . ($buyResponse['cc'] ?? '') . ($buyResponse['phonenumber'] ?? '');
            $actualCostUsd = isset($buyResponse['cost']) ? (float)$buyResponse['cost'] : $cheapestPrice;
            $countryName = $buyResponse['country'] ?? 'Unknown';
            $serviceName = $buyResponse['service'] ?? $serviceName;

            if (!$apiOrderId) {
                DB::beginTransaction();
                try {
                    $refundUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                    $refundUser->increment('balance', $finalCheckCost);
                    DB::commit();
                } catch (\Exception $ex) {
                    DB::rollBack();
                }
                return response()->json(['error' => 'Failed to obtain an order ID from SMSPool.'], 500);
            }

            // Recalculate final user cost based on the real price returned by SMSPool
            $realUserCostUsd = $actualCostUsd + $markupFee;
            
            $finalUserCostUsd = max(0.00, $realUserCostUsd);

            // Database Transaction
            DB::beginTransaction();
            try {
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();

                // Adjust balance based on the actual final cost vs our estimate
                $costDifference = $finalUserCostUsd - $finalCheckCost;
                if ($costDifference > 0) {
                    $lockedUser->decrement('balance', $costDifference);
                } elseif ($costDifference < 0) {
                    $lockedUser->increment('balance', abs($costDifference));
                }

                // Record Financial Transaction
                if ($finalUserCostUsd > 0) {
                    Transaction::add(
                        $lockedUser->id, 
                        $finalUserCostUsd, 
                        'debit', 
                        'virtual_number', 
                        "Virtual Number Purchase ({$serviceName} - {$countryName})"
                    );
                }

                $expiresInSeconds = $buyResponse['time_left'] ?? $buyResponse['expires_in'] ?? 1200; // Default to 20 mins if missing
                
                $order = VirtualNumberOrder::create([
                    'user_id' => $lockedUser->id,
                    'api_order_id' => $apiOrderId,
                    'phone_number' => $phoneNumber,
                    'country' => $countryName,
                    'operator' => 'SMSPool',
                    'product' => $serviceName,
                    'cost' => $finalUserCostUsd,
                    'provider_cost' => $actualCostUsd,
                    'status' => 'pending',
                    'coupon_code' => null,
                    'discount' => 0.00,
                    'expires_at' => now()->addSeconds((int)$expiresInSeconds)
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'order' => $order
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            // Log the actual error for admin review
            Log::error('Virtual Number Purchase Exception', [
                'user_id' => Auth::id(),
                'country' => $country ?? 'Unknown',
                'product' => $product ?? 'Unknown',
                'error' => $e->getMessage()
            ]);
            
            $ticketsUrl = route('tickets.index');
            $errorMessage = (Auth::user() && Auth::user()->is_admin)
                ? "<strong>Admin Debug:</strong> " . htmlspecialchars($e->getMessage())
                : "This service is currently unavailable or out of stock. <a href=\"{$ticketsUrl}\" class=\"alert-link text-decoration-underline fw-bold\">Open a ticket</a> if error persists.";
                
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Poll the status of an active number order
     */
    public function checkStatus($id)
    {
        $order = VirtualNumberOrder::where('user_id', Auth::id())->findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json(['order' => $order]);
        }

        try {
            $checkData = $this->smspool->checkOrder($order->api_order_id);

            // SMSPool status values: 1 = pending, 3 = completed (SMS received), 5/6 = refunded/canceled
            $status = isset($checkData['status']) ? (int)$checkData['status'] : 1;

            if ($status === 3) {
                DB::transaction(function () use ($order, $checkData) {
                    $lockedOrder = VirtualNumberOrder::where('id', $order->id)
                        ->lockForUpdate()
                        ->first();
                    
                    if ($lockedOrder && $lockedOrder->status === 'pending') {
                        $lockedOrder->update([
                            'status' => 'received',
                            'sms_text' => $checkData['full_sms'] ?? $checkData['sms'] ?? '',
                            'sms_code' => $checkData['sms'] ?? ''
                        ]);
                    }
                });
            } elseif ($status === 5 || $status === 6) {
                // Refund the user if it failed
                $this->refundOrder($order);
            }

            return response()->json(['order' => $order->fresh()]);

        } catch (\Exception $e) {
            Log::error('SMSPool checkStatus failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to check status.'], 500);
        }
    }

    /**
     * Cancel an active number (User initiated)
     */
    public function cancelOrder($id)
    {
        $order = VirtualNumberOrder::where('user_id', Auth::id())->where('status', 'pending')->findOrFail($id);

        try {
            // Call SMSPool cancel endpoint
            $this->smspool->cancelOrder($order->api_order_id);
            
            // Perform the refund
            $this->refundOrder($order);

            return response()->json(['success' => true, 'order' => $order->fresh()]);

        } catch (\Exception $e) {
            Log::error('SMSPool cancelOrder failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Could not cancel the order at this time.'], 400);
        }
    }

    /**
     * Internal method to refund user if number fails/cancels
     */
    protected function refundOrder(VirtualNumberOrder $order)
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = VirtualNumberOrder::where('id', $order->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedOrder || $lockedOrder->status !== 'pending') {
                return;
            }

            $lockedOrder->update(['status' => 'canceled']);
            $lockedOrder->user->increment('balance', $lockedOrder->cost);

            if ($lockedOrder->cost > 0) {
                Transaction::add(
                    $lockedOrder->user_id, 
                    $lockedOrder->cost, 
                    'credit', 
                    'refund', 
                    "Refund for Virtual Number Purchase ({$lockedOrder->product} - {$lockedOrder->country})"
                );
            }
        });
    }

    /**
     * Get the markup flat fee.
     */
    protected function getMarkupFee(): float
    {
        $markupSetting = (float) \App\Models\Setting::get('vn_markup', 1.00);
        
        // If the legacy value was a percentage (e.g. 65 or 200) or multiplier (e.g. 1.65),
        // we should fallback to a safe default markup fee (like 1.00) to prevent pricing breaks
        if ($markupSetting > 5.0 || ($markupSetting > 1.0 && $markupSetting < 2.0)) {
            $markupSetting = 1.00;
        }
        
        return $markupSetting;
    }
}
