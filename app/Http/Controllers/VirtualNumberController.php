<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SmsPoolService;
use App\Services\GrizzlySmsService;
use App\Models\VirtualNumberOrder;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VirtualNumberController extends Controller
{
    protected SmsPoolService $smspool;
    protected GrizzlySmsService $grizzly;

    public function __construct(SmsPoolService $smspool, GrizzlySmsService $grizzly)
    {
        $this->smspool = $smspool;
        $this->grizzly = $grizzly;
    }

    /**
     * Get the service provider instance based on server key ('server1' = SMSPool, 'server2' = Grizzly SMS).
     */
    protected function getProvider(string $server = 'server1')
    {
        return ($server === 'server2') ? $this->grizzly : $this->smspool;
    }

    /**
     * Show the main Virtual Numbers purchasing interface.
     */
    public function index()
    {
        $activeOrders = VirtualNumberOrder::where('user_id', Auth::id())
            ->where(function ($query) {
                $query->where('status', 'pending')
                      ->orWhere(function ($q) {
                          $q->where('status', 'received')
                            ->where('created_at', '>=', now()->subMinutes(20));
                      });
            })
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
     * API: Get cached countries list (supports Server 1: SMSPool, Server 2: Grizzly SMS).
     */
    public function getCountries(Request $request)
    {
        $server = $request->query('server', 'server1');
        $cacheKey = ($server === 'server2') ? "grizzly_countries" : "smspool_countries";

        $cached = Cache::get($cacheKey);
        if ($cached && is_array($cached) && !empty($cached)) {
            return response()->json($cached);
        }

        try {
            $data = $this->getProvider($server)->getCountries();
            if (!empty($data) && is_array($data)) {
                Cache::put($cacheKey, $data, 86400);
            }
            return response()->json($data ?: []);
        } catch (\Exception $e) {
            Log::error("Virtual numbers getCountries failed for {$server}: " . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * API: Get dynamic products list for a specific country from chosen server.
     * Caches the results for 5 minutes.
     */
    public function getProducts(Request $request)
    {
        $country = $request->query('country');
        $server = $request->query('server', 'server1');

        if (!$country) {
            return response()->json(['error' => 'Country ID is required.'], 400);
        }

        try {
            $pricesData = $this->getCachedPricesForCountry($country, $server);

            // Group by service ID/name and choose a realistic active pool price
            $grouped = [];
            foreach ($pricesData as $item) {
                $serviceId = $item['service'] ?? null;
                $serviceName = $item['service_name'] ?? null;
                $price = isset($item['price']) ? (float)$item['price'] : null;

                if (!$serviceId || !$serviceName || $price === null) {
                    continue;
                }

                // If Server 2, ensure service name is never an abbreviation
                if ($server === 'server2' && (strlen($serviceName) <= 3 || strtoupper($serviceName) === $serviceName)) {
                    $serviceName = $this->grizzly->getServiceName($serviceId);
                }

                if (!isset($grouped[$serviceName])) {
                    $grouped[$serviceName] = [
                        'id' => $serviceId,
                        'name' => $serviceName,
                        'prices' => [$price]
                    ];
                } else {
                    $grouped[$serviceName]['prices'][] = $price;
                }
            }

            // Map to final array with dynamic markup fee applied
            $finalServices = [];
            foreach ($grouped as $service) {
                // Use minimum pool price (matches SMSPool low_price entry quote)
                $chosenWholesale = min($service['prices']);

                $retailPrice = $this->calculateRetailPrice($chosenWholesale);
                $finalServices[] = [
                    'id' => $service['id'],
                    'name' => $service['name'],
                    'price' => $retailPrice,
                    'formatted_price' => money($retailPrice)
                ];
            }

            return response()->json($finalServices);
        } catch (\Exception $e) {
            Log::error("getPricesForCountry failed for {$server}: " . $e->getMessage(), ['country' => $country]);
            return response()->json([]);
        }
    }


    /**
     * Process the purchase of a virtual number (supports Server 1 and Server 2)
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'country' => 'required',
            'product' => 'required',
        ]);

        $country = $request->country; // Country ID
        $product = $request->product; // Service ID or Name
        $server = $request->input('server', 'server1');
        $user = Auth::user();

        try {
            // Retrieve pricing for this country to estimate cost (check cache first)
            $pricesData = $this->getCachedPricesForCountry($country, $server);
            
            $cheapestPrice = null;
            $serviceName = null;
            $productId = null;
            $allPoolPrices = [];

            foreach ($pricesData as $item) {
                if ((string)($item['service'] ?? '') === (string)$product || strtolower($item['service_name'] ?? '') === strtolower($product)) {
                    $price = (float)$item['price'];
                    $allPoolPrices[] = $price;
                    if ($cheapestPrice === null || $price < $cheapestPrice) {
                        $cheapestPrice = $price;
                        $serviceName = $item['service_name'] ?? $product;
                        $productId = $item['service'] ?? $product;
                    }
                }
            }

            if ($cheapestPrice === null) {
                return response()->json(['error' => 'Product not available for this country on ' . ($server === 'server2' ? 'Server 2' : 'Server 1') . '.'], 400);
            }

            if ($server === 'server2' && $productId) {
                $serviceName = $this->grizzly->getServiceName($productId);
            }

            $activeWholesalePrice = $cheapestPrice;

            // For Server 1 (SMSPool): verify real-time stock and active in-stock pool price
            if ($server === 'server1') {
                $liveInfo = $this->smspool->getServiceLiveInfo($productId, $country);

                if ($liveInfo !== null) {
                    $stock = $liveInfo['stock'] ?? 0;
                    if ($stock <= 0) {
                        return response()->json([
                            'error' => "{$serviceName} is currently out of stock for this country on Server 1. Please try Server 2 or select another country."
                        ], 400);
                    }

                    // Prioritize active live price over theoretical floor price
                    if (isset($liveInfo['price']) && (float)$liveInfo['price'] > 0) {
                        $activeWholesalePrice = (float)$liveInfo['price'];
                    } elseif (isset($liveInfo['low_price']) && (float)$liveInfo['low_price'] > 0) {
                        $activeWholesalePrice = (float)$liveInfo['low_price'];
                    }
                } elseif (count($allPoolPrices) > 0) {
                    $activeWholesalePrice = min($allPoolPrices);
                }
            }

            // Calculate verified user cost with dynamic markup
            $userCostUsd = $this->calculateRetailPrice($activeWholesalePrice);  
            $finalCheckCost = max(0.00, $userCostUsd);

            $totalAvailableBalance = 0.0;
            DB::beginTransaction();
            try {
                // Lock the user record to prevent concurrent check-out race conditions
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                $totalAvailableBalance = (float) $lockedUser->balance;

                if (!$lockedUser->deductBalance($finalCheckCost)) {
                    DB::rollBack();
                    $shortfall = max(0.00, round($finalCheckCost - $totalAvailableBalance, 2));
                    return response()->json([
                        'error' => "Insufficient wallet balance. This number costs " . money($finalCheckCost) . " (your balance is " . money($totalAvailableBalance) . "). Please fund your wallet with at least " . money($shortfall) . " to proceed."
                    ], 402);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            // Provide a realistic pool buffer for SMSPool without exceeding what the user can afford
            $flatMarkup = $this->getMarkupFee();
            $maxAffordableWholesale = max(0.00, round($totalAvailableBalance - $flatMarkup, 2));
            $maxWholesaleWithBuffer = round($activeWholesalePrice + 0.15, 2);
            $maxWholesale = min($maxAffordableWholesale, $maxWholesaleWithBuffer);

            // Execute Purchase on chosen server (pricing_option = 0 for SMSPool cheapest pool)
            try {
                $buyResponse = ($server === 'server1')
                    ? $this->smspool->buyNumber($country, $productId, $maxWholesale, 0)
                    : $this->grizzly->buyNumber($country, $productId, $maxWholesale);
            } catch (\Exception $e) {
                // Refund the reserved balance if purchase fails
                DB::beginTransaction();
                try {
                    $refundUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                    $refundUser->addBalance($finalCheckCost);
                    DB::commit();
                } catch (\Exception $ex) {
                    DB::rollBack();
                    Log::error('Failed to refund reserved balance after failed purchase', [
                        'user_id' => $user->id,
                        'amount' => $finalCheckCost,
                        'error' => $ex->getMessage()
                    ]);
                }

                // If error indicates out of stock, invalidate the cached quote so new status is retrieved
                if (str_contains(strtolower($e->getMessage()), 'out_of_stock') || str_contains(strtolower($e->getMessage()), 'no numbers')) {
                    Cache::forget("smspool_live_quote_{$country}_{$productId}");
                }

                throw $e;
            }

            // Extract order details
            $apiOrderId = $buyResponse['order_id'] ?? null;
            $phoneNumber = str_starts_with($buyResponse['phonenumber'] ?? '', '+') 
                ? $buyResponse['phonenumber'] 
                : ('+' . ($buyResponse['cc'] ?? '') . ($buyResponse['phonenumber'] ?? ''));
            $actualCostUsd = (isset($buyResponse['cost']) && (float)$buyResponse['cost'] > 0) ? (float)$buyResponse['cost'] : $activeWholesalePrice;

            // Resolve human-friendly country name from provider list
            $countryName = null;
            try {
                $allCountries = $this->getProvider($server)->getCountries();
                if (is_array($allCountries)) {
                    foreach ($allCountries as $c) {
                        if ((string)($c['ID'] ?? '') === (string)$country || (string)($c['id'] ?? '') === (string)$country) {
                            $countryName = $c['name'] ?? null;
                            break;
                        }
                    }
                }
            } catch (\Throwable $cErr) {
                Log::warning("Failed resolving country name: " . $cErr->getMessage());
            }
            if (!$countryName) {
                $countryName = $buyResponse['country'] ?? (string)$country;
            }

            if (!empty($buyResponse['service']) && strlen($buyResponse['service']) > 3) {
                $serviceName = $buyResponse['service'];
            } elseif ($server === 'server2') {
                $serviceName = $this->grizzly->getServiceName($buyResponse['service'] ?? $productId ?? $serviceName);
            }

            if (!$apiOrderId) {
                DB::beginTransaction();
                try {
                    $refundUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                    $refundUser->addBalance($finalCheckCost);
                    DB::commit();
                } catch (\Exception $ex) {
                    DB::rollBack();
                }
                return response()->json(['error' => 'Failed to obtain an order ID from SMS provider.'], 500);
            }

            // User is charged the exact contracted retail quote confirmed on screen
            $finalUserCostUsd = $finalCheckCost;

            // Database Transaction for finalizing order and transaction
            DB::beginTransaction();
            try {
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();

                // Record Financial Transaction for the exact quoted price
                if ($finalUserCostUsd > 0) {
                    $serverLabel = ($server === 'server2') ? 'Server 2 (Grizzly)' : 'Server 1';
                    Transaction::add(
                        $lockedUser->id, 
                        $finalUserCostUsd, 
                        'debit', 
                        'virtual_number', 
                        "Virtual Number Purchase ({$serviceName} - {$countryName} [{$serverLabel}])"
                    );
                }

                $expiresInSeconds = $buyResponse['time_left'] ?? $buyResponse['expires_in'] ?? 1200; // Default to 20 mins if missing
                $operatorLabel = ($server === 'server2') ? 'Grizzly SMS' : 'SMSPool';

                $order = VirtualNumberOrder::create([
                    'user_id' => $lockedUser->id,
                    'api_order_id' => $apiOrderId,
                    'phone_number' => $phoneNumber,
                    'country' => $countryName,
                    'operator' => $operatorLabel,
                    'server' => $server,
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
                'server' => $server ?? 'server1',
                'country' => $country ?? 'Unknown',
                'product' => $product ?? 'Unknown',
                'error' => $e->getMessage()
            ]);
            
            $ticketsUrl = route('tickets.index');
            $rawError = $e->getMessage();
            $userFacingError = "This service is currently unavailable or out of stock. <a href=\"{$ticketsUrl}\" class=\"alert-link text-decoration-underline fw-bold\">Open a ticket</a> if error persists.";

            if (stripos($rawError, 'no numbers') !== false || stripos($rawError, 'out of stock') !== false) {
                $userFacingError = "No numbers are currently available in stock for this service from the provider. Your wallet was not charged. Please try Server 2 or select another country.";
            } elseif (stripos($rawError, 'Insufficient') !== false) {
                $userFacingError = $rawError;
            }

            $errorMessage = (Auth::user() && Auth::user()->is_admin)
                ? "<strong>Admin Debug:</strong> " . htmlspecialchars($rawError) . "<br><small class='text-muted'>User error: " . $userFacingError . "</small>"
                : $userFacingError;
                
            return response()->json(['error' => $errorMessage], 400);
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
            $provider = $this->getProvider($order->server ?? 'server1');
            $checkData = $provider->checkOrder($order->api_order_id);

            // Status values: 1 = pending, 2 = expired, 3 = completed (SMS received), 5/6 = refunded/canceled
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
            } elseif ($status === 2 || $status === 5 || $status === 6) {
                // Refund the user if it failed, expired, or was canceled upstream
                $this->refundOrder($order);
            }

            return response()->json(['order' => $order->fresh()]);

        } catch (\Exception $e) {
            Log::error('Virtual number checkStatus failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to check status.'], 500);
        }
    }

    /**
     * Cancel an active number (User initiated)
     */
    public function cancelOrder($id)
    {
        $order = VirtualNumberOrder::where('user_id', Auth::id())->where('status', 'pending')->findOrFail($id);

        if (!empty($order->sms_code) || !empty($order->sms_text)) {
            return response()->json(['error' => 'Cannot cancel a number that has already received an SMS code.'], 400);
        }

        try {
            $provider = $this->getProvider($order->server ?? 'server1');
            $provider->cancelOrder($order->api_order_id);
            
            // Perform the refund
            $this->refundOrder($order);

            return response()->json(['success' => true, 'order' => $order->fresh()]);

        } catch (\Exception $e) {
            Log::error('Virtual number cancelOrder failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage() ?: 'Could not cancel the order at this time.'], 400);
        }
    }

    /**
     * Resend SMS / Reuse active number for another code.
     */
    public function resendOrder($id)
    {
        $order = VirtualNumberOrder::where('user_id', Auth::id())
            ->whereIn('status', ['received', 'pending'])
            ->findOrFail($id);

        try {
            $provider = $this->getProvider($order->server ?? 'server1');
            $resendData = $provider->resendOrder($order->api_order_id);

            // Set order status back to pending to wait for the next SMS (retaining sms_text as audit trail of initial delivery)
            DB::transaction(function () use ($order) {
                $lockedOrder = VirtualNumberOrder::where('id', $order->id)->lockForUpdate()->first();
                if ($lockedOrder) {
                    $lockedOrder->update([
                        'status' => 'pending',
                        'sms_code' => null,
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => $resendData['message'] ?? 'SMS resend requested successfully. Waiting for new code.',
                'order' => $order->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Virtual number resendOrder exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage() ?: 'Could not request SMS resend at this time.'
            ], 400);
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

            // Guard: Cannot refund if order is not pending or if it has ever received an SMS
            if (!$lockedOrder || $lockedOrder->status !== 'pending' || !empty($lockedOrder->sms_code) || !empty($lockedOrder->sms_text)) {
                return;
            }

            $lockedOrder->update(['status' => 'canceled']);

            $lockedUser = \App\Models\User::where('id', $lockedOrder->user_id)
                ->lockForUpdate()
                ->first();

            if ($lockedUser) {
                $lockedUser->addBalance($lockedOrder->cost);

                if ($lockedOrder->cost > 0) {
                    Transaction::add(
                        $lockedUser->id, 
                        $lockedOrder->cost, 
                        'credit', 
                        'refund', 
                        "Refund for Virtual Number Purchase ({$lockedOrder->product} - {$lockedOrder->country})"
                    );
                }
            }
        });
    }

    /**
     * API: Get real-time verified price quote for a selected country and service.
     * Prevents pricing surprises, stale cached data, and balance discrepancies.
     */
    public function getLiveQuote(Request $request)
    {
        $request->validate([
            'country' => 'required',
            'product' => 'required',
        ]);

        $country = $request->country;
        $product = $request->product;
        $server = $request->input('server', 'server1');
        $user = Auth::user();

        try {
            $pricesData = $this->getCachedPricesForCountry($country, $server);

            $cheapestPrice = null;
            $serviceName = null;
            $productId = null;
            $allPoolPrices = [];

            foreach ($pricesData as $item) {
                if ((string)($item['service'] ?? '') === (string)$product || strtolower($item['service_name'] ?? '') === strtolower($product)) {
                    $price = (float) $item['price'];
                    $allPoolPrices[] = $price;
                    if ($cheapestPrice === null || $price < $cheapestPrice) {
                        $cheapestPrice = $price;
                        $serviceName = $item['service_name'] ?? $product;
                        $productId = $item['service'] ?? $product;
                    }
                }
            }

            if ($cheapestPrice === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service currently out of stock or unavailable for this country on ' . ($server === 'server2' ? 'Server 2' : 'Server 1') . '.'
                ], 404);
            }

            if ($server === 'server2' && $productId) {
                $serviceName = $this->grizzly->getServiceName($productId);
            }

            $activeWholesalePrice = $cheapestPrice;
            $stockCount = null;
            $successRate = null;

            // For Server 1 (SMSPool): query real-time live price & stock availability directly on customer pick
            if ($server === 'server1') {
                $liveInfo = $this->smspool->getServiceLiveInfo($productId, $country);

                if ($liveInfo !== null) {
                    $stockCount = $liveInfo['stock'] ?? 0;
                    $successRate = $liveInfo['success_rate'] ?? null;

                    // If provider explicitly reports 0 stock, notify customer immediately
                    if ($stockCount <= 0) {
                        return response()->json([
                            'success' => false,
                            'out_of_stock' => true,
                            'stock' => 0,
                            'service_name' => $serviceName,
                            'message' => "{$serviceName} is currently out of stock for this country on Server 1. Please select another country or try Server 2."
                        ]);
                    }

                    // Prioritize real-time active price over theoretical floor price
                    if (isset($liveInfo['price']) && (float)$liveInfo['price'] > 0) {
                        $activeWholesalePrice = (float)$liveInfo['price'];
                    } elseif (isset($liveInfo['low_price']) && (float)$liveInfo['low_price'] > 0) {
                        $activeWholesalePrice = (float)$liveInfo['low_price'];
                    }
                } elseif (count($allPoolPrices) > 0) {
                    $activeWholesalePrice = min($allPoolPrices);
                }
            }

            $userCost = $this->calculateRetailPrice($activeWholesalePrice);
            $userBalance = (float) $user->balance;
            $sufficient = $userBalance >= $userCost;
            $remainingBalance = max(0.00, round($userBalance - $userCost, 4));
            $shortfall = max(0.00, round($userCost - $userBalance, 2));

            return response()->json([
                'success' => true,
                'server' => $server,
                'country' => $country,
                'product_id' => $productId,
                'service_name' => $serviceName,
                'wholesale_price' => $activeWholesalePrice,
                'stock' => $stockCount,
                'success_rate' => $successRate,
                'quote_price' => $userCost,
                'formatted_quote' => money($userCost),
                'markup_fee' => $this->getMarkupFee(),
                'formatted_markup' => money($this->getMarkupFee()),
                'user_balance' => $userBalance,
                'formatted_balance' => money($userBalance),
                'sufficient_balance' => $sufficient,
                'remaining_balance' => $remainingBalance,
                'formatted_remaining' => money($remainingBalance),
                'shortfall' => $shortfall,
                'formatted_shortfall' => money($shortfall),
            ]);
        } catch (\Throwable $e) {
            Log::error('SMS Live Quote Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch real-time quote: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to get and cache prices for a specific country and server.
     * Prevents caching empty arrays on API transient errors.
     */
    protected function getCachedPricesForCountry(string $country, string $server = 'server1'): array
    {
        $cacheKey = ($server === 'server2') ? "grizzly_prices_{$country}" : "smspool_prices_{$country}";
        $cached = Cache::get($cacheKey);
        if ($cached && is_array($cached) && !empty($cached)) {
            return $cached;
        }

        try {
            $pricesData = $this->getProvider($server)->getPricesForCountry($country);
            if (!empty($pricesData) && is_array($pricesData)) {
                Cache::put($cacheKey, $pricesData, 60);
            }
            return $pricesData ?: [];
        } catch (\Throwable $e) {
            Log::error("Failed fetching prices for country {$country} on {$server}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the flat markup fee for virtual numbers (e.g. $1.00).
     */
    public function getMarkupFee(): float
    {
        return (float) \App\Models\Setting::get('vn_markup', 1.00);
    }

    /**
     * Calculate user retail price from wholesale price with flat markup (just like SMSPool)
     */
    public function calculateRetailPrice(float $wholesalePrice): float
    {
        $flatFee = $this->getMarkupFee();
        return round($wholesalePrice + $flatFee, 2);
    }

    /**
     * Legacy percentage helper kept for backward compatibility.
     */
    public function getMarkupPercent(): float
    {
        return (float) \App\Models\Setting::get('vn_markup_percent', 0.0);
    }
}
