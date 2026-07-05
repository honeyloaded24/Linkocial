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
        $cacheKey = "smspool_prices_" . $country;
        $data = Cache::remember($cacheKey, 300, function () use ($country, $markupFee) {
            try {
                $pricesData = $this->smspool->getPricesForCountry($country);
                
                // Group by service ID/name and choose the lowest price across different pools
                $grouped = [];
                foreach ($pricesData as $item) {
                    $serviceId = $item['service'] ?? null;
                    $serviceName = $item['service_name'] ?? null;
                    $price = isset($item['price']) ? (float)$item['price'] : null;

                    if (!$serviceId || !$serviceName || $price === null) {
                        continue;
                    }

                    // Keep the cheapest price for each unique service
                    if (!isset($grouped[$serviceName]) || $price < $grouped[$serviceName]['price']) {
                        $grouped[$serviceName] = [
                            'id' => $serviceId,
                            'name' => $serviceName,
                            'price' => round($price + $markupFee, 2)
                        ];
                    }
                }
                
                return array_values($grouped);
            } catch (\Exception $e) {
                Log::error('SMSPool getPricesForCountry failed in controller', ['country' => $country, 'error' => $e->getMessage()]);
                return [];
            }
        });

        return response()->json($data);
    }

    /**
     * API: Validate coupon code for virtual number purchase.
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'country' => 'required',
            'product' => 'required',
        ]);

        $code = strtoupper($request->coupon_code);
        $country = $request->country;
        $product = $request->product;
        $user = Auth::user();

        $coupon = \App\Models\Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json(['success' => false, 'error' => 'Invalid coupon code.'], 404);
        }

        if (!$coupon->status) {
            return response()->json(['success' => false, 'error' => 'This coupon is inactive.'], 400);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return response()->json(['success' => false, 'error' => 'This coupon has expired.'], 400);
        }

        if ($coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit) {
            return response()->json(['success' => false, 'error' => 'This coupon has reached its usage limit.'], 400);
        }

        $userUsageCount = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->count();

        if ($userUsageCount >= $coupon->user_limit) {
            return response()->json(['success' => false, 'error' => 'You have already used this coupon the maximum number of times.'], 400);
        }

        try {
            $pricesData = $this->smspool->getPricesForCountry($country);
            
            $cheapestPrice = null;
            foreach ($pricesData as $item) {
                if ((string)$item['service'] === (string)$product || strtolower($item['service_name']) === strtolower($product)) {
                    $price = (float)$item['price'];
                    if ($cheapestPrice === null || $price < $cheapestPrice) {
                        $cheapestPrice = $price;
                    }
                }
            }

            if ($cheapestPrice === null) {
                return response()->json(['success' => false, 'error' => 'Product not available for this country.'], 400);
            }

            $markupFee = $this->getMarkupFee();
            $userCostUsd = $cheapestPrice + $markupFee;

            if ($coupon->min_spend > 0 && $userCostUsd < $coupon->min_spend) {
                return response()->json(['success' => false, 'error' => 'Minimum spend for this coupon is $' . number_format($coupon->min_spend, 2) . '.'], 400);
            }

            $discount = 0.00;
            if ($coupon->type === 'fixed') {
                $discount = (float)$coupon->value;
            } elseif ($coupon->type === 'percentage') {
                $discount = $userCostUsd * ($coupon->value / 100);
            }

            $discount = min($discount, $userCostUsd);
            $finalPrice = max(0.00, $userCostUsd - $discount);

            return response()->json([
                'success' => true,
                'coupon_code' => $coupon->code,
                'original_price' => $userCostUsd,
                'discount' => $discount,
                'final_price' => $finalPrice
            ]);

        } catch (\Exception $e) {
            Log::error('Coupon validation failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to validate coupon: ' . $e->getMessage()], 500);
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
            'coupon_code' => 'nullable|string'
        ]);

        $country = $request->country; // Country ID
        $product = $request->product; // Service ID or Name
        $user = Auth::user();

        try {
            // Retrieve pricing for this country to estimate cost
            $pricesData = $this->smspool->getPricesForCountry($country);
            
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

            // Validate Coupon if applied
            $coupon = null;
            $discount = 0.00;
            if ($request->filled('coupon_code')) {
                $coupon = \App\Models\Coupon::where('code', strtoupper($request->coupon_code))->first();
                if ($coupon && $coupon->status && (!$coupon->expires_at || !$coupon->expires_at->isPast())) {
                    if (($coupon->usage_limit === null || $coupon->usage_count < $coupon->usage_limit) && 
                        ($coupon->min_spend == 0 || $userCostUsd >= $coupon->min_spend)) {
                        
                        $userUsageCount = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                            ->where('user_id', $user->id)
                            ->count();

                        if ($userUsageCount < $coupon->user_limit) {
                            if ($coupon->type === 'fixed') {
                                $discount = (float)$coupon->value;
                            } else {
                                $discount = $userCostUsd * ($coupon->value / 100);
                            }
                            $discount = min($discount, $userCostUsd);
                        } else {
                            $coupon = null; // Revert if user limit reached
                        }
                    } else {
                        $coupon = null;
                    }
                } else {
                    $coupon = null;
                }
            }

            // Ensure user has enough balance
            $finalCheckCost = max(0.00, $userCostUsd - $discount);
            if ($user->balance < $finalCheckCost) {
                return response()->json(['error' => 'Insufficient wallet balance.'], 402);
            }

            // Execute Purchase
            $buyResponse = $this->smspool->buyNumber($country, $productId);

            // Extract order details
            $apiOrderId = $buyResponse['order_id'] ?? null;
            $phoneNumber = '+' . ($buyResponse['cc'] ?? '') . ($buyResponse['phonenumber'] ?? '');
            $actualCostUsd = isset($buyResponse['cost']) ? (float)$buyResponse['cost'] : $cheapestPrice;
            $countryName = $buyResponse['country'] ?? 'Unknown';
            $serviceName = $buyResponse['service'] ?? $serviceName;

            if (!$apiOrderId) {
                return response()->json(['error' => 'Failed to obtain an order ID from SMSPool.'], 500);
            }
            
            // Recalculate final user cost based on the real deducted price
            $realUserCostUsd = $actualCostUsd + $markupFee;
            
            // Recalculate discount based on actual cost in case it shifted (percentage)
            if ($coupon && $coupon->type === 'percentage') {
                $discount = min($realUserCostUsd * ($coupon->value / 100), $realUserCostUsd);
            } elseif ($coupon && $coupon->type === 'fixed') {
                $discount = min((float)$coupon->value, $realUserCostUsd);
            }

            $finalUserCostUsd = max(0.00, $realUserCostUsd - $discount);

            // Database Transaction
            DB::beginTransaction();

            // Deduct balance
            $user->decrement('balance', $finalUserCostUsd);

            // Record Financial Transaction
            if ($finalUserCostUsd > 0) {
                Transaction::add(
                    $user->id, 
                    $finalUserCostUsd, 
                    'debit', 
                    'virtual_number', 
                    "Virtual Number Purchase ({$serviceName} - {$countryName})" . ($coupon ? " [Coupon: {$coupon->code}]" : "")
                );
            }

            $expiresInSeconds = $buyResponse['time_left'] ?? $buyResponse['expires_in'] ?? 1200; // Default to 20 mins if missing
            
            // Create Order Record
            $order = VirtualNumberOrder::create([
                'user_id' => $user->id,
                'api_order_id' => $apiOrderId,
                'phone_number' => $phoneNumber,
                'country' => $countryName,
                'operator' => 'SMSPool',
                'product' => $serviceName,
                'cost' => $finalUserCostUsd,
                'provider_cost' => $actualCostUsd,
                'status' => 'pending',
                'coupon_code' => $coupon ? $coupon->code : null,
                'discount' => $discount,
                'expires_at' => now()->addSeconds((int)$expiresInSeconds)
            ]);

            // Record Coupon Usage
            if ($coupon) {
                \App\Models\CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'virtual_number_order_id' => $order->id,
                    'discount_amount' => $discount,
                ]);
                $coupon->increment('usage_count');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'order' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the actual error for admin review
            Log::error('Virtual Number Purchase Exception', [
                'user_id' => $user->id,
                'country' => $country,
                'product' => $product,
                'error' => $e->getMessage()
            ]);
            
            // Determine what error message to show based on user role
            $ticketsUrl = route('tickets.index');
            $errorMessage = $user->is_admin 
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
                $order->update([
                    'status' => 'received',
                    'sms_text' => $checkData['full_sms'] ?? $checkData['sms'] ?? '',
                    'sms_code' => $checkData['sms'] ?? ''
                ]);
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
        if ($order->status !== 'pending') return;

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'canceled']);
            $order->user->increment('balance', $order->cost);
            
            if ($order->cost > 0) {
                Transaction::add(
                    $order->user_id, 
                    $order->cost, 
                    'credit', 
                    'refund', 
                    "Refund for canceled Virtual Number ({$order->product})"
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
