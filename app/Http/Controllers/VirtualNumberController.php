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
                // Check if a live active pool price was recently cached for this service
                $cachedLiveWholesale = ($server === 'server1') ? Cache::get("smspool_live_active_price_{$country}_{$service['id']}") : null;
                if ($cachedLiveWholesale !== null && (float)$cachedLiveWholesale > 0) {
                    $chosenWholesale = (float)$cachedLiveWholesale;
                } else {
                    $chosenWholesale = min($service['prices']);
                }

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

        // Idempotency & Concurrency Protection: Prevent concurrent double-clicks from debiting twice
        // 60-second lock window comfortably covers the maximum HTTP provider timeout (15s) while releasing immediately in finally
        $purchaseLock = Cache::lock("vn_purchase_user_{$user->id}", 60);
        if (!$purchaseLock->get()) {
            return response()->json([
                'error' => 'A purchase request is currently in progress. Please wait a few moments before trying again.'
            ], 429);
        }

        $attemptRecord = null;

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
                // 1. Check if an active live pool quote was recently established (e.g. Sierra at $1.96)
                $cachedLiveWholesale = Cache::get("smspool_live_active_price_{$country}_{$productId}");
                if ($cachedLiveWholesale !== null && (float)$cachedLiveWholesale > 0) {
                    $activeWholesalePrice = (float)$cachedLiveWholesale;
                }

                // 2. If customer confirmed an expected_price that reflects a higher live quote,
                // deduce the corresponding wholesale rate to guarantee cost protection
                if ($request->filled('expected_price')) {
                    $expPrice = (float) $request->input('expected_price');
                    $providerRate = max(1.0, $this->getProviderRate());
                    $markupFee = $this->getMarkupFee();
                    if ($expPrice > $markupFee) {
                        $deducedWholesale = round(($expPrice - $markupFee) / $providerRate, 2);
                        if ($deducedWholesale > $activeWholesalePrice) {
                            $activeWholesalePrice = $deducedWholesale;
                        }
                    }
                }

                $liveInfo = $this->smspool->getServiceLiveInfo($productId, $country);

                if ($liveInfo !== null) {
                    $stock = $liveInfo['stock'] ?? 0;
                    if ($stock <= 0) {
                        return response()->json([
                            'error' => "{$serviceName} is currently out of stock for this country on Server 1. Please try Server 2 or select another country."
                        ], 400);
                    }

                    if ($cachedLiveWholesale === null && !$request->filled('expected_price')) {
                        if (isset($liveInfo['low_price']) && (float)$liveInfo['low_price'] > 0) {
                            $activeWholesalePrice = (float)$liveInfo['low_price'];
                        } elseif (isset($liveInfo['price']) && (float)$liveInfo['price'] > 0) {
                            $activeWholesalePrice = (float)$liveInfo['price'];
                        }
                    }
                } elseif ($cachedLiveWholesale === null && !$request->filled('expected_price') && count($allPoolPrices) > 0) {
                    $activeWholesalePrice = min($allPoolPrices);
                }
            }

            // Calculate authoritative verified user cost in NGN with dynamic markup
            $userCostNgn = $this->calculateRetailPrice($activeWholesalePrice);  
            $finalCheckCost = max(0.00, $userCostNgn);

            // Dynamic Safe Pricing: Protect customer against material price increases between quotation and purchase
            if ($request->filled('expected_price')) {
                $expectedPrice = (float) $request->input('expected_price');
                // If current dynamic price exceeds quoted price by more than ₦1.00 tolerance, require re-confirmation
                if ($finalCheckCost > ($expectedPrice + 1.00)) {
                    return response()->json([
                        'error' => "Current price for {$serviceName} has updated from " . money($expectedPrice) . " to " . money($finalCheckCost) . ". Please review the updated quotation and confirm your order.",
                        'price_changed' => true,
                        'old_price' => $expectedPrice,
                        'new_price' => $finalCheckCost,
                        'formatted_new_price' => money($finalCheckCost),
                    ], 409);
                }

                // If customer confirmed and expected a higher valid live quote (from getLiveQuote pool check),
                // honor the customer's confirmed quote so provider spending limit and wallet debit match the active pool.
                if ($expectedPrice > $finalCheckCost && $expectedPrice <= ($finalCheckCost * 1.5)) {
                    $finalCheckCost = $expectedPrice;
                }
            }

            $totalAvailableBalance = 0.0;
            $clientRef = 'ATT_' . $user->id . '_' . time() . '_' . \Illuminate\Support\Str::random(8);

            // Duplicate-Purchase Guard: reject new orders while any previous one remains 'unresolved' on this server.
            // 'unresolved' means a provider timeout occurred and the outcome has not been definitively established.
            // Unlike temporary locks, this restriction does NOT expire on a timer — it persists until
            // the order is definitively resolved (reconciled by cron or reviewed by admin).
            // This prevents duplicate provider charges and ensures provider-specific isolation.
            $existingUnresolved = \App\Models\VirtualNumberOrder::where('user_id', $user->id)
                ->where('status', 'unresolved')
                ->where('server', $server)
                ->exists();

            if ($existingUnresolved) {
                return response()->json([
                    'error' => 'You have a purchase that is currently being verified with the carrier. Please check your order history in a few minutes. If the order does not appear, contact support before trying again.',
                    'status' => 'duplicate_blocked',
                ], 409);
            }

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

                // Persist the purchase attempt BEFORE contacting external provider (Crash Recovery Protection)
                $attemptRecord = \App\Models\VirtualNumberOrder::create([
                    'user_id' => $lockedUser->id,
                    'api_order_id' => $clientRef,
                    'phone_number' => 'PENDING',
                    'country' => (string) $country,
                    'operator' => ($server === 'server2') ? 'Server 2' : 'Server 1',
                    'server' => $server,
                    'product' => (string) ($serviceName ?? $product),
                    'cost' => $finalCheckCost,
                    'provider_cost' => 0.0,
                    'status' => 'processing',
                    'expires_at' => now()->addMinutes(20),
                ]);

                // Record the wallet debit with the persisted purchase attempt before contacting the provider.
                // This ensures failed and unresolved purchases have a debit entry to reconcile against.
                if ($finalCheckCost > 0) {
                    Transaction::add(
                        $lockedUser->id,
                        $finalCheckCost,
                        'debit',
                        'virtual_number',
                        "Virtual Number Purchase ({$serviceName} - {$country})",
                        'VN-DEBIT-' . $attemptRecord->id,
                        $attemptRecord->created_at
                    );
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            // Business Pricing Protection: calculate provider spending limit in USD
            $providerRate = max(1.0, $this->getProviderRate());
            $pricingSettings = app(\App\Services\ProductPricingService::class)->getPricingSettings(\App\Services\ProductPricingService::PRODUCT_VIRTUAL_NUMBERS);
            $configuredMinProfit = $pricingSettings->getMinProfit();

            // Linkocial requires at least this minimum profit margin in NGN for this order
            if ($configuredMinProfit !== null && $configuredMinProfit > 0) {
                $minRequiredProfitNgn = (float) $configuredMinProfit;
            } else {
                // Protect a baseline margin: at least 70% of the standard markup fee (minimum ₦150).
                // This ensures fallback can absorb minor supplier variance (e.g. up to $0.20) but NEVER allows
                // an expensive pool (like Sierra at $1.96) to erode Linkocial's markup profit on an order paid at base rates ($1.56).
                $markupFee = $this->getMarkupFee();
                $minRequiredProfitNgn = max(150.0, round($markupFee * 0.70, 2));
            }

            // Strict Financial Safety: Provider spending must NEVER exceed (Customer Paid - Required Profit)
            $maxSpendNgn = max(0.00, $finalCheckCost - $minRequiredProfitNgn);
            // Decimal Rounding Safety: floor to 2 decimal places to guarantee ceiling is never exceeded
            $maxAllowedProviderUsd = floor(($maxSpendNgn / $providerRate) * 100) / 100;

            if ($maxAllowedProviderUsd <= 0.0) {
                // Refund and reject immediately if provider spending ceiling cannot maintain profit
                DB::beginTransaction();
                try {
                    $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                    if ($lockedOrder && $lockedOrder->status === 'processing') {
                        $lockedOrder->update(['status' => 'failed', 'sms_text' => 'Rejected: Provider spending ceiling would violate minimum profit margins.']);
                        $refundUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                        $refundUser->addBalance($finalCheckCost);
                        if ($finalCheckCost > 0) {
                            Transaction::add(
                                $refundUser->id,
                                $finalCheckCost,
                                'credit',
                                'refund',
                                "Refund for failed Virtual Number Purchase ({$serviceName} - {$country})",
                                'REF-VN-' . $lockedOrder->id,
                                $lockedOrder->created_at
                            );
                        }
                    }
                    DB::commit();
                } catch (\Exception $ex) {
                    DB::rollBack();
                }
                return response()->json([
                    'error' => 'This service cannot be fulfilled within configured safe profit margins. Your wallet was not charged.'
                ], 400);
            }

            // Execute Purchase on chosen server
            try {
                if ($server === 'server1') {
                    // Step 1: Automatic pool routing (pricing_option = 0 for cheapest available pool under max_price)
                    try {
                        $buyResponse = $this->smspool->buyNumber($country, $productId, $maxAllowedProviderUsd, 0, null);
                    } catch (\App\Exceptions\ProviderTimeoutException $timeoutEx) {
                        // CRITICAL FINANCIAL SAFETY – TIMEOUT HANDLING:
                        // A network timeout means we do NOT know whether SMSPool created a number or not.
                        //
                        // IMPORTANT: SMSPool documents an "Activating" phase (~1 minute) during which
                        // a successfully created order may NOT yet appear in /request/active.
                        // This means an EMPTY active-orders response is NOT proof that no order was created.
                        //
                        // Safe policy: ALWAYS preserve as 'unresolved' on timeout, regardless of
                        // what /request/active returns. The cron job (vn:check-expired) will re-query
                        // after a safe delay (≥2 minutes) to determine the actual outcome.
                        // We log the active-order count for admin context only.
                        Log::warning("SMSPool purchase timed out. Preserving as UNRESOLVED regardless of active-order visibility.", [
                            'country'   => $country,
                            'product'   => $productId,
                            'user_id'   => $user->id,
                            'attempt'   => $attemptRecord->id ?? null,
                        ]);

                        $activeOrderCount = 0;
                        try {
                            $activeOrders = $this->smspool->getActiveOrders();
                            if (is_array($activeOrders)) {
                                foreach ($activeOrders as $ao) {
                                    $aoOrderId = (string)($ao['order_id'] ?? $ao['orderid'] ?? '');
                                    $aoCountry = (string)($ao['country_id'] ?? $ao['country'] ?? '');
                                    $aoService = (string)($ao['service_id'] ?? $ao['service'] ?? '');

                                    if (!empty($aoOrderId) && !\App\Models\VirtualNumberOrder::where('api_order_id', $aoOrderId)->exists()) {
                                        $countryMatch = ($aoCountry === (string)$country || empty($aoCountry));
                                        $serviceMatch = ($aoService === (string)$productId || empty($aoService));
                                        if ($countryMatch && $serviceMatch) {
                                            $activeOrderCount++;
                                        }
                                    }
                                }
                            }
                        } catch (\Throwable $reconErr) {
                            Log::error("SMSPool timeout reconciliation query failed: " . $reconErr->getMessage());
                        }

                        // Preserve as 'unresolved' in ALL cases: empty list may just be Activating delay.
                        // The cron will confirm outcome after the activation window has passed.
                        if ($attemptRecord) {
                            DB::beginTransaction();
                            try {
                                $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                                if ($lockedOrder && $lockedOrder->status === 'processing') {
                                    $lockedOrder->update([
                                        'status'   => 'unresolved',
                                        'sms_text' => 'Provider timeout. Active orders visible at reconciliation time: ' . $activeOrderCount
                                            . '. Preserved for safe cron audit (SMSPool Activating delay may hide newly created orders).',
                                    ]);
                                }
                                DB::commit();
                            } catch (\Throwable $unresolvedErr) {
                                DB::rollBack();
                            }
                        }

                        Log::alert("SMSPool timeout: attempt {$attemptRecord->id} placed in UNRESOLVED state. Active-order count at reconcile: {$activeOrderCount}.", [
                            'user_id'             => $user->id,
                            'active_order_count'  => $activeOrderCount,
                        ]);

                        return response()->json([
                            'error'    => 'Your request timed out while communicating with the carrier network. To protect your purchase, the order has been preserved in verification state. Please check your order history in 2–3 minutes or contact support.',
                            'order_id' => $attemptRecord->id ?? null,
                            'status'   => 'unresolved',
                        ], 504);
                    } catch (\Exception $firstEx) {
                        $firstError = $firstEx->getMessage();
                        Log::info("SMSPool automatic purchase failed, testing intelligent pool fallback", [
                            'country' => $country,
                            'product' => $productId,
                            'max_allowed_usd' => $maxAllowedProviderUsd,
                            'error' => $firstError
                        ]);

                        // Determine if failure is due to pool occupancy or pricing ceiling
                        $isPoolEligibleForFallback = (
                            stripos($firstError, 'occupied') !== false ||
                            stripos($firstError, 'suitable pool') !== false ||
                            stripos($firstError, 'out of stock') !== false ||
                            stripos($firstError, 'no numbers') !== false
                        );

                        $fallbackResponse = null;
                        if ($isPoolEligibleForFallback) {
                            $validPools = $this->smspool->getValidPools($country, $productId);
                            if (!empty($validPools)) {
                                // Sort pools ascending by price
                                usort($validPools, function ($a, $b) {
                                    $pa = (float)($a['price'] ?? 999);
                                    $pb = (float)($b['price'] ?? 999);
                                    return $pa <=> $pb;
                                });

                                $exhaustedPools = [];
                                if (stripos($firstError, 'foxtrot') !== false) $exhaustedPools[] = 'foxtrot';
                                if (stripos($firstError, 'sierra') !== false) $exhaustedPools[] = 'sierra';
                                if (stripos($firstError, 'mike') !== false) $exhaustedPools[] = 'mike';

                                $maxFallbackAttempts = 2; // Strict bound: at most 2 fallback attempts
                                $attemptsMade = 0;

                                foreach ($validPools as $poolCandidate) {
                                    if ($attemptsMade >= $maxFallbackAttempts) {
                                        break;
                                    }

                                    $cId = $poolCandidate['pool_id'] ?? $poolCandidate['id'] ?? $poolCandidate['pool'] ?? null;
                                    $cName = strtolower($poolCandidate['name'] ?? $poolCandidate['pool'] ?? '');
                                    $cPrice = (float)($poolCandidate['price'] ?? $poolCandidate['cost'] ?? $poolCandidate['rate'] ?? 0);

                                    // Must be within safe provider spending ceiling
                                    if ($cPrice > $maxAllowedProviderUsd || $cPrice <= 0) {
                                        continue;
                                    }

                                    // Do not retry pools known to be exhausted
                                    if (in_array($cName, $exhaustedPools)) {
                                        continue;
                                    }

                                    $attemptsMade++;
                                    try {
                                        $fallbackResponse = $this->smspool->buyNumber($country, $productId, $maxAllowedProviderUsd, 0, (string)$cId);
                                        Log::info("SMSPool intelligent fallback succeeded with pool {$cName} ({$cId}) at cost {$cPrice}");
                                        break;
                                    } catch (\Exception $fallbackEx) {
                                        $exhaustedPools[] = $cName;
                                        Log::warning("SMSPool fallback pool {$cName} failed: " . $fallbackEx->getMessage());
                                    }
                                }
                            }
                        }

                        if ($fallbackResponse !== null) {
                            $buyResponse = $fallbackResponse;
                        } else {
                            throw $firstEx;
                        }
                    }
                } else {
                    try {
                        $buyResponse = $this->grizzly->buyNumber($country, $productId, $maxAllowedProviderUsd);
                    } catch (\Illuminate\Http\Client\ConnectionException $timeoutEx) {
                        // A timeout from Grizzly's getNumberV2 endpoint is ambiguous:
                        // the provider may have created an activation before the response was lost.
                        // Never refund or permit another Server 2 purchase until reconciled.
                        Log::warning('Grizzly purchase timed out; preserving attempt as unresolved.', [
                            'user_id' => $user->id,
                            'attempt' => $attemptRecord->id ?? null,
                            'country' => $country,
                            'product' => $productId,
                        ]);

                        if ($attemptRecord) {
                            DB::transaction(function () use ($attemptRecord, $timeoutEx) {
                                $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                                if ($lockedOrder && $lockedOrder->status === 'processing') {
                                    $lockedOrder->update([
                                        'status' => 'unresolved',
                                        'sms_text' => 'Grizzly provider timeout. Activation outcome unknown; retained for manual reconciliation. No automatic refund.',
                                    ]);
                                }
                            });
                        }

                        return response()->json([
                            'error' => 'Your request timed out while communicating with Server 2. The purchase is being verified to prevent a duplicate charge. Please check order history or contact support.',
                            'order_id' => $attemptRecord->id ?? null,
                            'status' => 'unresolved',
                        ], 504);
                    }
                }
            } catch (\Exception $e) {
                // Refund the reserved balance safely and idempotently if purchase fails
                if (isset($attemptRecord) && $attemptRecord) {
                    DB::beginTransaction();
                    try {
                        $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                        if ($lockedOrder && $lockedOrder->status === 'processing') {
                            $lockedOrder->update([
                                'status' => 'failed',
                                'sms_text' => 'Provider error: ' . substr($e->getMessage(), 0, 200),
                            ]);

                            $refundUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                            $refundUser->addBalance($finalCheckCost);

                        if ($finalCheckCost > 0) {
                            \App\Models\Transaction::add(
                                $refundUser->id,
                                $finalCheckCost,
                                'credit',
                                'refund',
                                "Refund for failed Virtual Number Purchase ({$serviceName} - {$country})",
                                'REF-VN-' . $lockedOrder->id,
                                $lockedOrder->created_at
                            );
                        }
                        }
                        DB::commit();
                    } catch (\Exception $ex) {
                        DB::rollBack();
                        Log::error('Failed to refund reserved balance after failed purchase', [
                            'user_id' => $user->id,
                            'amount' => $finalCheckCost,
                            'error' => $ex->getMessage()
                        ]);
                    }
                }

                // If error indicates out of stock, invalidate the cached quote so new status is retrieved
                if (str_contains(strtolower($e->getMessage()), 'out_of_stock') || str_contains(strtolower($e->getMessage()), 'no numbers')) {
                    Cache::forget("smspool_live_quote_{$country}_{$productId}");
                }

                throw $e;
            }

            // Extract order details
            $apiOrderId = $buyResponse['order_id'] ?? null;
            if ($apiOrderId && empty($buyResponse['phonenumber'])) {
                // Activation ID confirms provider-side purchase, but without a phone
                // number the activation cannot safely be presented as usable.
                try {
                    DB::transaction(function () use ($attemptRecord, $apiOrderId, $buyResponse, $server, $country, $productId) {
                        $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                        if ($lockedOrder && $lockedOrder->status === 'processing') {
                            $lockedOrder->update([
                                'api_order_id' => (string) $apiOrderId,
                                'server' => $server,
                                'country' => (string) $country,
                                'product' => (string) $productId,
                                'status' => 'unresolved',
                                'sms_text' => 'Provider confirmed activation ID but omitted phone number. Response preserved for manual reconciliation: ' . substr(json_encode($buyResponse) ?: 'unavailable', 0, 500),
                            ]);
                        }
                    });
                } catch (\Throwable $recoveryEx) {
                    Log::critical('Could not preserve provider activation with missing phone number', [
                        'attempt_id' => $attemptRecord->id,
                        'provider_order_id' => $apiOrderId,
                        'error' => $recoveryEx->getMessage(),
                    ]);
                }
                return response()->json([
                    'error' => 'The provider confirmed the activation but returned incomplete details. Your payment has been retained while this order is verified.',
                    'order_id' => $attemptRecord->id ?? null,
                    'status' => 'unresolved',
                ], 500);
            }
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
                // Provider returned a purchase response without an activation ID.
                // Treat the outcome as ambiguous and retain funds for reconciliation.
                if (isset($attemptRecord) && $attemptRecord) {
                    try {
                        DB::transaction(function () use ($attemptRecord, $buyResponse) {
                            $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                            if ($lockedOrder && $lockedOrder->status === 'processing') {
                                $lockedOrder->update([
                                    'status' => 'unresolved',
                                    'sms_text' => 'Provider returned a purchase response without an activation ID. Response preserved for manual reconciliation: ' . substr(json_encode($buyResponse) ?: 'unavailable', 0, 500),
                                ]);
                            }
                        });
                    } catch (\Throwable $recoveryEx) {
                        Log::critical('Could not preserve provider purchase with missing activation ID', [
                            'attempt_id' => $attemptRecord->id,
                            'error' => $recoveryEx->getMessage(),
                        ]);
                    }
                }
                return response()->json([
                    'error' => 'The provider returned an incomplete purchase confirmation. Your payment has been retained while this order is verified. Please check order history or contact support.',
                    'order_id' => $attemptRecord->id ?? null,
                    'status' => 'unresolved',
                ], 500);
            }

            // User is charged the exact contracted retail quote confirmed on screen
            $finalUserCostUsd = $finalCheckCost;

            // Database Transaction for finalizing order and transaction
            DB::beginTransaction();
            try {
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();

                $expiresInSeconds = $buyResponse['time_left'] ?? $buyResponse['expires_in'] ?? 1200; // Default to 20 mins if missing
                $operatorLabel = ($server === 'server2') ? 'Server 2' : 'Server 1';

                $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                $lockedOrder->update([
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
                    'order' => $lockedOrder
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                // Provider already returned an activation. A local persistence
                // failure must not refund the user because the number may be live.
                try {
                    DB::transaction(function () use ($attemptRecord, $apiOrderId, $phoneNumber, $countryName, $server, $serviceName, $actualCostUsd, $e) {
                        $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                        if ($lockedOrder && $lockedOrder->status === 'processing') {
                            $lockedOrder->update([
                                'api_order_id' => (string) $apiOrderId,
                                'phone_number' => $phoneNumber ?: 'PENDING',
                                'country' => $countryName ?: $lockedOrder->country,
                                'operator' => ($server === 'server2') ? 'Server 2' : 'Server 1',
                                'server' => $server,
                                'product' => $serviceName,
                                'provider_cost' => $actualCostUsd,
                                'status' => 'unresolved',
                                'sms_text' => 'Provider activation confirmed but local finalization failed. Retained without refund. Error: ' . substr($e->getMessage(), 0, 150),
                            ]);
                        }
                    });
                } catch (\Throwable $recoveryError) {
                    Log::critical('Failed to preserve provider-confirmed virtual number after local finalization error.', [
                        'attempt_id' => $attemptRecord->id ?? null,
                        'provider_order_id' => $apiOrderId,
                        'error' => $recoveryError->getMessage(),
                    ]);
                }

                Log::critical('Provider-confirmed virtual number could not be finalized locally; refund withheld.', [
                    'attempt_id' => $attemptRecord->id ?? null,
                    'provider_order_id' => $apiOrderId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'error' => 'The carrier issued your number, but we could not finish saving the order. Your payment has been retained for verification. Please contact support.',
                    'order_id' => $attemptRecord->id ?? null,
                    'status' => 'unresolved',
                ], 500);
            }

        } catch (\Exception $e) {
            $rawError = $e->getMessage();

            // Secure private diagnostic logging for administrators (never leaked to customer)
            Log::warning('Virtual Number Purchase Exception', [
                'user_id' => Auth::id(),
                'server' => $server ?? 'server1',
                'country' => $country ?? 'Unknown',
                'product' => $product ?? 'Unknown',
                'raw_error' => $rawError,
                'max_allowed_usd' => $maxAllowedProviderUsd ?? null,
                'customer_cost_ngn' => $finalCheckCost ?? null,
            ]);
            
            $ticketsUrl = route('tickets.index');

            // Sanitize customer-facing message (protect API keys, provider pool internals, and raw error traces)
            if (stripos($rawError, 'below the price of') !== false || stripos($rawError, 'suitable pool') !== false) {
                $userFacingError = "This service is temporarily unavailable due to carrier pricing updates. Your wallet was not charged. Please refresh for an updated quote or select Server 2.";
            } elseif (stripos($rawError, 'occupied') !== false || stripos($rawError, 'out of stock') !== false || stripos($rawError, 'no numbers') !== false) {
                $userFacingError = "All number slots are temporarily occupied for this service. Your wallet was not charged. Please try again in a few minutes or switch to Server 2.";
            } elseif (stripos($rawError, 'Insufficient') !== false) {
                $userFacingError = "Service is temporarily unavailable due to routine replenishment. Your wallet was not charged. Please select another route or try again shortly.";
            } else {
                $userFacingError = "This service is temporarily unavailable. Your wallet was not charged. Please try again shortly or select Server 2. <a href=\"{$ticketsUrl}\" class=\"alert-link text-decoration-underline fw-bold\">Contact support</a> if this persists.";
            }
                
            return response()->json(['error' => $userFacingError], 400);
        } finally {
            if (isset($purchaseLock) && $purchaseLock) {
                try {
                    $purchaseLock->release();
                } catch (\Throwable $lockEx) {
                    // Ignore lock release failures on shutdown
                }
            }
        }
    }

    /**
     * Poll the status of an active number order
     */
    public function checkStatus($id)
    {
        $order = VirtualNumberOrder::where('user_id', Auth::id())->findOrFail($id);
        if ($order->status !== 'pending') return response()->json(['order' => $order]);

        $actionLock = Cache::lock("vn_order_action_{$order->id}", 60);
        if (!$actionLock->get()) return response()->json(['error' => 'Another action is being processed for this order. Please retry shortly.'], 409);

        try {
            $order = $order->fresh();
            if (!$order || $order->status !== 'pending') return response()->json(['order' => $order]);

            $provider = $this->getProvider($order->server ?? 'server1');
            $checkData = $provider->checkOrder($order->api_order_id);
            $status = isset($checkData['status']) ? (int) $checkData['status'] : 1;

            if ($status === 3) {
                DB::transaction(function () use ($order, $checkData) {
                    $lockedOrder = VirtualNumberOrder::where('id', $order->id)->lockForUpdate()->first();
                    if ($lockedOrder && $lockedOrder->status === 'pending') {
                        $lockedOrder->update(['status' => 'received', 'sms_text' => $checkData['full_sms'] ?? $checkData['sms'] ?? '', 'sms_code' => $checkData['sms'] ?? '']);
                    }
                });
            } elseif (in_array($status, [2, 5, 6], true)) {
                $this->refundOrder($order);
            }
            return response()->json(['order' => $order->fresh()]);
        } catch (\Exception $e) {
            Log::error('Virtual number checkStatus failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to check status.'], 500);
        } finally {
            $actionLock->release();
        }
    }

    /**
     * Cancel an active number (User initiated)
     */
    public function cancelOrder($id)
    {
        $order = VirtualNumberOrder::where('user_id', Auth::id())->where('status', 'pending')->findOrFail($id);
        if (!empty($order->sms_code) || !empty($order->sms_text)) return response()->json(['error' => 'Cannot cancel a number that has already received an SMS code.'], 400);

        $actionLock = Cache::lock("vn_order_action_{$order->id}", 60);
        if (!$actionLock->get()) return response()->json(['error' => 'Another action is being processed for this order. Please retry shortly.'], 409);

        try {
            $order = $order->fresh();
            if (!$order || $order->status !== 'pending' || !empty($order->sms_code) || !empty($order->sms_text)) {
                return response()->json(['error' => 'This order is no longer eligible for cancellation.'], 409);
            }
            $provider = $this->getProvider($order->server ?? 'server1');
            $provider->cancelOrder($order->api_order_id);
            $this->refundOrder($order);
            return response()->json(['success' => true, 'order' => $order->fresh()]);
        } catch (\Exception $e) {
            Log::error('Virtual number cancelOrder failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage() ?: 'Could not cancel the order at this time.'], 400);
        } finally {
            $actionLock->release();
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

        // Prevent duplicate resend calls for the same order from concurrent requests.
        $resendLock = Cache::lock("vn_order_action_{$order->id}", 60);
        if (!$resendLock->get()) {
            return response()->json(['error' => 'A resend request is already in progress for this order.'], 409);
        }

        try {
            // Recheck eligibility under a row lock before contacting the provider.
            $eligible = DB::transaction(function () use ($order) {
                $lockedOrder = VirtualNumberOrder::where('id', $order->id)->lockForUpdate()->first();
                return $lockedOrder && in_array($lockedOrder->status, ['received', 'pending'], true);
            });

            if (!$eligible) {
                return response()->json(['error' => 'This order is no longer eligible for SMS resend.'], 409);
            }

            $provider = $this->getProvider($order->server ?? 'server1');
            $resendData = $provider->resendOrder($order->api_order_id);

            // Only transition back to pending if it is still eligible. Do not overwrite
            // a concurrent terminal state or a status update made while the provider call ran.
            $updated = DB::transaction(function () use ($order) {
                $lockedOrder = VirtualNumberOrder::where('id', $order->id)->lockForUpdate()->first();
                if (!$lockedOrder || !in_array($lockedOrder->status, ['received', 'pending'], true)) {
                    return false;
                }

                $lockedOrder->update([
                    'status' => 'pending',
                    'sms_code' => null,
                ]);
                return true;
            });

            if (!$updated) {
                Log::warning('Provider accepted resend but local order state changed concurrently', ['order_id' => $order->id]);
                return response()->json([
                    'error' => 'The provider accepted the resend, but this order changed concurrently. Refresh order history before taking further action.',
                    'provider_resend_confirmed' => true,
                ], 409);
            }

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
        } finally {
            $resendLock->release();
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
                        "Refund for Virtual Number Purchase ({$lockedOrder->product} - {$lockedOrder->country})",
                        'REF-VN-' . $lockedOrder->id,
                        $lockedOrder->created_at
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
                $livePools = $this->smspool->getValidPools($country, $productId);
                if (!empty($livePools)) {
                    $validPoolPrices = [];
                    foreach ($livePools as $p) {
                        if (!is_array($p)) {
                            continue;
                        }
                        $pPrice = (float)($p['price'] ?? $p['cost'] ?? $p['rate'] ?? 0);
                        if (isset($p['stock']) && (int)$p['stock'] <= 0) {
                            continue;
                        }
                        if ($pPrice > 0) {
                            $validPoolPrices[] = $pPrice;
                        }
                    }
                    if (!empty($validPoolPrices)) {
                        $activeWholesalePrice = min($validPoolPrices);
                        Cache::put("smspool_live_active_price_{$country}_{$productId}", $activeWholesalePrice, now()->addMinutes(5));
                    }
                }

                $liveInfo = $this->smspool->getServiceLiveInfo($productId, $country);

                if ($liveInfo !== null) {
                    $stockCount = $liveInfo['stock'] ?? 0;
                    $successRate = $liveInfo['success_rate'] ?? null;

                    // If provider explicitly reports 0 stock and no valid pools exist, notify customer immediately
                    if ($stockCount <= 0 && empty($livePools)) {
                        return response()->json([
                            'success' => false,
                            'out_of_stock' => true,
                            'stock' => 0,
                            'service_name' => $serviceName,
                            'message' => "{$serviceName} is currently out of stock for this country on Server 1. Please select another country or try Server 2."
                        ]);
                    }

                    // Fall back to liveInfo low_price/price if getValidPools returned no pricing
                    if (empty($livePools)) {
                        if (isset($liveInfo['low_price']) && (float)$liveInfo['low_price'] > 0) {
                            $activeWholesalePrice = (float)$liveInfo['low_price'];
                        } elseif (isset($liveInfo['price']) && (float)$liveInfo['price'] > 0) {
                            $activeWholesalePrice = (float)$liveInfo['price'];
                        }
                        Cache::put("smspool_live_active_price_{$country}_{$productId}", $activeWholesalePrice, now()->addMinutes(5));
                    }
                } elseif (empty($livePools) && count($allPoolPrices) > 0) {
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
                'quote_timestamp' => now()->timestamp,
            ]);
        } catch (\Throwable $e) {
            Log::error('SMS Live Quote Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch real-time quote. Please try again shortly or select Server 2.'
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
     * Get the provider wholesale exchange rate (USD to NGN).
     */
    public function getProviderRate(): float
    {
        try {
            return app(\App\Services\ProductPricingService::class)->getFixRate(\App\Services\ProductPricingService::PRODUCT_VIRTUAL_NUMBERS);
        } catch (\Throwable $e) {
            return (float) \App\Models\Setting::get('provider_usd_rate', 1500.0);
        }
    }

    /**
     * Get the flat markup fee for virtual numbers in NGN.
     */
    public function getMarkupFee(): float
    {
        try {
            $settings = app(\App\Services\ProductPricingService::class)->getPricingSettings(\App\Services\ProductPricingService::PRODUCT_VIRTUAL_NUMBERS);
            return $settings->getFixedMarkup();
        } catch (\Throwable $e) {
            $rate = $this->getProviderRate();
            $customNgn = \App\Models\Setting::get('vn_markup_ngn');
            if ($customNgn !== null && is_numeric($customNgn)) {
                return (float) $customNgn;
            }
            $usdMarkup = (float) \App\Models\Setting::get('vn_markup', 1.00);
            return round($usdMarkup * $rate, 2);
        }
    }

    /**
     * Calculate user retail price in NGN from wholesale USD quote with NGN markup.
     */
    public function calculateRetailPrice(float $wholesalePrice): float
    {
        try {
            $calc = app(\App\Services\ProductPricingService::class)->calculateSellingPrice($wholesalePrice, \App\Services\ProductPricingService::PRODUCT_VIRTUAL_NUMBERS);
            return (float) $calc['selling_price'];
        } catch (\Throwable $e) {
            $rate = $this->getProviderRate();
            $flatFeeNgn = $this->getMarkupFee();
            $wholesaleNgn = $wholesalePrice * $rate;
            return round($wholesaleNgn + $flatFeeNgn, 2);
        }
    }

    /**
     * Legacy percentage helper kept for backward compatibility.
     */
    public function getMarkupPercent(): float
    {
        return (float) \App\Models\Setting::get('vn_markup_percent', 0.0);
    }
}
