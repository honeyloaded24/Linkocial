<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SmsPoolService;
use App\Services\GrizzlySmsService;
use App\Services\SmsBowerService;
use App\Services\DaisySmsService;
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
    protected SmsBowerService $smsbower;
    protected DaisySmsService $daisysms;

    public function __construct(SmsPoolService $smspool, GrizzlySmsService $grizzly, SmsBowerService $smsbower, DaisySmsService $daisysms)
    {
        $this->smspool = $smspool;
        $this->grizzly = $grizzly;
        $this->smsbower = $smsbower;
        $this->daisysms = $daisysms;
    }

    protected function isDaisyService(string $server, string $country, string $product): bool
    {
        if ($server !== 'server4' || !isset(DaisySmsService::SUPPORTED_COUNTRIES[$country]) || $product === '') {
            return false;
        }

        try {
            return collect($this->daisysms->getPricesForCountry($country))
                ->contains(fn ($row) => (string) ($row['service'] ?? '') === $product);
        } catch (\Throwable $e) {
            Log::warning('DaisySMS live service validation failed.', [
                'country' => $country,
                'service' => $product,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function getOrderProvider(VirtualNumberOrder $order)
    {
        if (($order->provider_key ?? null) === 'daisysms') return $this->daisysms;
        if (($order->provider_key ?? null) === 'smsbower') return $this->smsbower;
        return $this->getProvider($order->server ?? 'server1');
    }

    /**
     * Get the service provider instance based on server key ('server1' = SMSPool, 'server2' = Grizzly SMS).
     */
    protected function getProvider(string $server = 'server1')
    {
        return match ($server) {
            'server2' => $this->grizzly,
            'server3' => $this->smsbower,
            'server4' => $this->daisysms,
            default => $this->smspool,
        };
    }

    /**
     * Show the main Virtual Numbers purchasing interface.
     */
    public function index()
    {
        $activeOrders = VirtualNumberOrder::where('user_id', Auth::id())
            ->where(function ($query) {
                $query->where('status', 'pending')
                      ->orWhere('status', 'unresolved')
                      ->orWhere(function ($q) {
                          $q->where('status', 'received')
                            ->where('created_at', '>=', now()->subMinutes(20));
                      });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $server3Enabled = !empty(\App\Models\Setting::get('smsbower_api_key'));
        $server4Enabled = !empty(\App\Models\Setting::get('daisysms_api_key'));

        return view('services.virtual_numbers', compact('activeOrders', 'server3Enabled', 'server4Enabled'));
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
        $cacheKey = match ($server) { 'server2' => 'grizzly_countries', 'server3' => 'smsbower_countries', 'server4' => 'daisysms_countries', default => 'smspool_countries' };

        if ($server === 'server4') {
            if (empty(\App\Models\Setting::get('daisysms_api_key'))) {
                return response()->json([]);
            }

            try {
                $countries = Cache::remember(
                    'daisysms_countries_live_v2',
                    now()->addMinutes(5),
                    fn () => $this->daisysms->getAvailableCountries()
                );
                return response()->json($countries);
            } catch (\Throwable $e) {
                Log::warning('DaisySMS Server 4 country catalogue failed.', ['error' => $e->getMessage()]);
                return response()->json(Cache::get('daisysms_countries_live_v2_stale', []));
            }
        }

        $cached = Cache::get($cacheKey);
        if ($cached && is_array($cached) && !empty($cached)) {
            if ($server === 'server3') {
                $cached = $this->ensureSmsBowerCoreCountries($cached);
                Cache::put($cacheKey, $cached, 86400);
            }
            return response()->json($cached);
        }

        try {
            $data = $this->getProvider($server)->getCountries();
            if ($server === 'server3') {
                $data = $this->ensureSmsBowerCoreCountries($data);
            }
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
     * SMSBower's documented country IDs include United Kingdom (16) and
     * United States (187). Keep these selectable even when an account's
     * country response/cache omits a row temporarily.
     */
    protected function ensureSmsBowerCoreCountries(array $countries): array
    {
        $required = [
            ['ID' => '16', 'name' => 'United Kingdom', 'cc' => '44'],
            ['ID' => DaisySmsService::USA_COUNTRY_ID, 'name' => 'United States', 'cc' => '1'],
        ];

        $existingIds = array_map(
            fn ($row) => (string) ($row['ID'] ?? $row['id'] ?? ''),
            $countries
        );

        foreach ($required as $country) {
            if (!in_array($country['ID'], $existingIds, true)) {
                $countries[] = $country;
            }
        }

        return $countries;
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
        } catch (\Throwable $e) {
            Log::error("getPricesForCountry failed for {$server}: " . $e->getMessage(), ['country' => $country]);
            if (in_array($server, ['server3', 'server4'], true)) {
                return response()->json(['error' => ($server === 'server4' ? 'Server 4' : 'Server 3') . ' services could not be loaded. Please try again shortly.'], 502);
            }
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
                return response()->json(['error' => 'Product not available for this country on ' . ($server === 'server2' ? 'Server 2' : ($server === 'server3' ? 'Server 3' : ($server === 'server4' ? 'Server 4' : 'Server 1'))) . '.'], 400);
            }

            if ($server === 'server2' && $productId) {
                $serviceName = $this->grizzly->getServiceName($productId);
            }

            $activeWholesalePrice = $cheapestPrice;

            // Server 3 must revalidate live stock and pricing against current Gold IDs
            // before any wallet debit or purchase-attempt ledger entry is created.
            if ($server === 'server3') {
                Cache::forget('smsbower_gold_top_' . md5((string) $productId));
                $liveGoldOffer = $this->smsbower->getLiveGoldOffer($country, (string) $productId);
                if (empty($liveGoldOffer['provider_ids']) || (float) ($liveGoldOffer['price'] ?? 0) <= 0) {
                    return response()->json([
                        'error' => 'No verified Gold supplier currently has stock for this service and country on Server 3.',
                        'out_of_stock' => true,
                    ], 400);
                }
                $activeWholesalePrice = (float) $liveGoldOffer['price'];
            }

            // For Server 1 (SMSPool), always use current valid pool prices.
            // Do not infer the wholesale price from a previous customer quote.
            if ($server === 'server1') {
                $livePools = $this->smspool->getValidPools($country, $productId);
                $validPoolPrices = [];
                foreach ($livePools as $pool) {
                    if (!is_array($pool)) continue;
                    $poolPrice = (float) ($pool['price'] ?? $pool['cost'] ?? $pool['rate'] ?? 0);
                    if ($poolPrice > 0 && (!isset($pool['stock']) || (int) $pool['stock'] > 0)) {
                        $validPoolPrices[] = $poolPrice;
                    }
                }

                if (!empty($validPoolPrices)) {
                    $activeWholesalePrice = min($validPoolPrices);
                    Cache::put("smspool_live_active_price_{$country}_{$productId}", $activeWholesalePrice, now()->addMinutes(5));
                }

                $liveInfo = $this->smspool->getServiceLiveInfo($productId, $country);
                if ($liveInfo !== null) {
                    $stock = $liveInfo['stock'] ?? 0;
                    if ($stock <= 0 && empty($validPoolPrices)) {
                        return response()->json([
                            'error' => "{$serviceName} is currently out of stock for this country on Server 1. Please try Server 2 or select another country."
                        ], 400);
                    }
                    if (empty($validPoolPrices)) {
                        if (isset($liveInfo['low_price']) && (float) $liveInfo['low_price'] > 0) {
                            $activeWholesalePrice = (float) $liveInfo['low_price'];
                        } elseif (isset($liveInfo['price']) && (float) $liveInfo['price'] > 0) {
                            $activeWholesalePrice = (float) $liveInfo['price'];
                        }
                    }
                }
            }

            // Refresh Server 2 and DaisySMS prices immediately before quoting/debiting.
            // Cached catalogues are discovery aids, never the final purchase quote.
            if ($server === 'server2' && $productId) {
                $freshRows = $this->grizzly->getPricesForCountry($country);
                $freshOffer = collect($freshRows)->first(fn ($row) => (string) ($row['service'] ?? '') === (string) $productId);
                if ($freshOffer && (float) ($freshOffer['price'] ?? 0) > 0) {
                    $activeWholesalePrice = (float) $freshOffer['price'];
                }
            } elseif ($this->isDaisyService($server, (string) $country, (string) $productId)) {
                $freshRows = $this->daisysms->getPricesForCountry((string) $country);
                $freshOffer = collect($freshRows)->firstWhere('service', (string) $productId);
                if (!$freshOffer || (int) ($freshOffer['stock'] ?? 0) <= 0 || (float) ($freshOffer['price'] ?? 0) <= 0) {
                    return response()->json([
                        'error' => 'This DaisySMS service is temporarily unavailable or out of stock. Please refresh the quote and try again.',
                        'out_of_stock' => true,
                    ], 400);
                }
                $activeWholesalePrice = (float) $freshOffer['price'];
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
                    'operator' => $server === 'server2' ? 'Server 2' : ($server === 'server3' ? 'Server 3' : ($server === 'server4' ? 'Server 4' : 'Server 1')),
                    'server' => $server,
                    'provider_key' => $server === 'server4' ? 'daisysms' : ($server === 'server3' ? 'smsbower' : null),
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
                    // Preserve SMSPool's native automatic cheapest-pool routing.
                    // Explicit pool forcing is avoided because pool metadata can be stale.
                    try {
                        $buyResponse = $this->smspool->buyNumber($country, $productId, null, 0, null);
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
                            stripos($firstError, 'no numbers') !== false ||
                            stripos($firstError, 'below the price of') !== false ||
                            stripos($firstError, 'carrier pricing') !== false ||
                            stripos($firstError, 'pricing update') !== false ||
                            stripos($firstError, 'max_price') !== false ||
                            stripos($firstError, 'maximum price') !== false
                        );

                        $fallbackResponse = null;
                        if ($isPoolEligibleForFallback) {
                                                        $validPools = $this->smspool->getValidPools($country, $productId);
                            if (!empty($validPools)) {
                                // SMSPool /pool/retrieve_valid returns pool, name, and price
                                // directly. Do not re-join against /request/pricing.
                                $validPools = array_values(array_filter(array_map(function ($pool) {
                                    if (!is_array($pool)) {
                                        return null;
                                    }

                                    $poolId = $pool['pool'] ?? $pool['pool_id'] ?? $pool['id'] ?? null;
                                    $price = $pool['price'] ?? $pool['cost'] ?? $pool['rate'] ?? null;
                                    if ($poolId === null || $price === null || (float) $price <= 0) {
                                        return null;
                                    }

                                    $pool['pool_id'] = (string) $poolId;
                                    $pool['name'] = $pool['name'] ?? $pool['pool_name'] ?? '';
                                    $pool['price'] = (float) $price;
                                    return $pool;
                                }, $validPools)));

                                // Sort SMSPool's own valid pool records by returned price, cheapest first.
                                usort($validPools, function ($a, $b) {
                                    return (float) $a['price'] <=> (float) $b['price'];
                                });

                                $exhaustedPools = [];
                                if (stripos($firstError, 'foxtrot') !== false) $exhaustedPools[] = 'foxtrot';
                                if (stripos($firstError, 'sierra') !== false) $exhaustedPools[] = 'sierra';
                                if (stripos($firstError, 'mike') !== false) $exhaustedPools[] = 'mike';

                                // Pools are sorted cheapest-first. Try the cheapest eligible pool first,
                                // and only move upward when a cheaper pool is unavailable.
                                foreach ($validPools as $poolCandidate) {
                                    $cId = $poolCandidate['pool_id'] ?? $poolCandidate['id'] ?? $poolCandidate['pool'] ?? null;
                                    $cName = strtolower($poolCandidate['name'] ?? $poolCandidate['pool'] ?? '');
                                    $cPrice = (float)($poolCandidate['price'] ?? $poolCandidate['cost'] ?? $poolCandidate['rate'] ?? 0);

                                    if ($cPrice <= 0 || empty($cId)) {
                                        continue;
                                    }

                                    // Do not retry pools known to be exhausted
                                    if (in_array($cName, $exhaustedPools)) {
                                        continue;
                                    }

                                    // Ask the customer to confirm a higher retail quote before attempting it.
                                    if ($cPrice > $maxAllowedProviderUsd) {
                                        $updatedRetailPrice = $this->calculateRetailPrice($cPrice);
                                        $expectedPrice = $request->filled('expected_price')
                                            ? (float) $request->input('expected_price')
                                            : 0.0;

                                        if ($updatedRetailPrice > ($expectedPrice + 1.00)) {
                                            DB::transaction(function () use ($attemptRecord, $user, $finalCheckCost) {
                                                $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                                                if ($lockedOrder && $lockedOrder->status === 'processing') {
                                                    $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                                                    $lockedUser->addBalance($finalCheckCost);
                                                    \App\Models\Transaction::where('reference', 'VN-DEBIT-' . $lockedOrder->id)->delete();
                                                    $lockedOrder->delete();
                                                }
                                            });

                                            return response()->json([
                                                'error' => "The cheapest available carrier pool has updated the price from " . money($finalCheckCost) . " to " . money($updatedRetailPrice) . ". Please review and confirm the updated quotation.",
                                                'price_changed' => true,
                                                'old_price' => $finalCheckCost,
                                                'new_price' => $updatedRetailPrice,
                                                'formatted_new_price' => money($updatedRetailPrice),
                                            ], 409);
                                        }

                                        // The confirmed quote still cannot breach the configured profit floor.
                                        continue;
                                    }

                                    try {
                                        $fallbackResponse = $this->smspool->buyNumber($country, $productId, null, 0, (string)$cName);
                                        Log::info("SMSPool intelligent fallback succeeded with pool {$cName} ({$cId}) at cost {$cPrice}");
                                        break;
                                    } catch (\Exception $fallbackEx) {
                                        // A fallback timeout is ambiguous: SMSPool may have created
                                        // the activation even though this request received no response.
                                        // Do not continue to another pool or refund the customer.
                                        if ($fallbackEx instanceof \App\Exceptions\ProviderTimeoutException
                                            || $fallbackEx instanceof \Illuminate\Http\Client\ConnectionException) {
                                            DB::transaction(function () use ($attemptRecord, $fallbackEx) {
                                                $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                                                if ($lockedOrder && $lockedOrder->status === 'processing') {
                                                    $lockedOrder->update([
                                                        'status' => 'unresolved',
                                                        'sms_text' => 'SMSPool fallback purchase timed out. Activation outcome unknown; retained for reconciliation. No automatic refund.',
                                                    ]);
                                                }
                                            });
                                            Log::alert('SMSPool fallback purchase timed out; preserving virtual-number debit for reconciliation.', [
                                                'attempt_id' => $attemptRecord->id,
                                                'pool' => $cName,
                                                'error' => $fallbackEx->getMessage(),
                                            ]);
                                            return response()->json([
                                                'error' => 'The carrier request timed out while attempting an available number. Your purchase is being verified to prevent duplicate charges. Please check order history shortly.',
                                                'order_id' => $attemptRecord->id,
                                                'status' => 'unresolved',
                                            ], 504);
                                        }

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
                } elseif ($server === 'server2') {
                    try {
                        // Preserve Grizzly's established automatic price-tier selection.
                        // A quote-derived maxPrice may be stale by the time the request arrives.
                        $buyResponse = $this->grizzly->buyNumber($country, $productId);
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
                } else {
                    try {
                        $buyResponse = $server === 'server4'
                            ? $this->daisysms->buyNumber((string) $country, (string) $productId, null, $activeWholesalePrice)
                            : $this->smsbower->buyNumber($country, (string) $productId);
                    } catch (\App\Exceptions\ProviderTimeoutException $timeoutEx) {
                        DB::transaction(function () use ($attemptRecord, $server) {
                            $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                            if ($lockedOrder && $lockedOrder->status === 'processing') {
                                $lockedOrder->update(['status' => 'unresolved', 'sms_text' => ($server === 'server4' ? 'DaisySMS Server 4 purchase timed out. Activation outcome is unknown; retained for reconciliation. No automatic refund.' : 'Server 3 purchase timed out. Gold supplier activation outcome is unknown; retained for reconciliation. No automatic refund.')]);
                            }
                        });
                        return response()->json([
                            'error' => 'Your request timed out while communicating with ' . ($server === 'server4' ? 'Server 4' : 'Server 3') . '. The purchase is being verified to prevent a duplicate charge. Please check order history shortly.',
                            'order_id' => $attemptRecord->id,
                            'status' => 'unresolved',
                        ], 504);
                    }
                }
            } catch (\Exception $e) {
                $providerError = strtolower($e->getMessage());
                $isDaisyPurchase = $this->isDaisyService($server, (string) $country, (string) $productId);
                if ($isDaisyPurchase && ($e instanceof \App\Exceptions\ProviderTimeoutException || str_contains($providerError, 'timed out') || str_contains($providerError, 'timeout') || str_contains($providerError, 'connection'))) {
                    DB::transaction(function () use ($attemptRecord) {
                        $lockedOrder = VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                        if ($lockedOrder && $lockedOrder->status === 'processing') $lockedOrder->update(['status' => 'unresolved', 'sms_text' => 'DaisySMS purchase outcome is unknown after a network failure. Funds retained for reconciliation; no automatic refund.']);
                    });
                    return response()->json(['error' => 'The ' . ($server === 'server4' ? 'Server 4' : 'Server 3') . ' purchase is being verified because the carrier response was interrupted. Please check order history before retrying.', 'order_id' => $attemptRecord->id, 'status' => 'unresolved'], 504);
                }
                $noPurchaseDueToQuote = (!$isDaisyPurchase && $server === 'server3')
                    || str_contains($providerError, 'below the price of')
                    || str_contains($providerError, 'suitable pool')
                    || ($isDaisyPurchase && (
                        str_contains($providerError, 'no numbers')
                        || str_contains($providerError, 'max price exceeded')
                        || str_contains($providerError, 'too many active rentals')
                        || str_contains($providerError, 'insufficient')
                        || str_contains($providerError, 'authentication failed')
                        || str_contains($providerError, 'not enabled')
                        || str_contains($providerError, 'spending limit')
                    ));

                if (isset($attemptRecord) && $attemptRecord) {
                    DB::beginTransaction();
                    try {
                        $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                        if ($lockedOrder && $lockedOrder->status === 'processing') {
                            $refundUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();

                            if ($noPurchaseDueToQuote) {
                                // Provider rejected the quote before creating an activation.
                                // Restore the reserved wallet balance and remove the attempt
                                // and debit ledger row: this was not a purchase or a refund.
                                $refundUser->addBalance($finalCheckCost);
                                \App\Models\Transaction::where('reference', 'VN-DEBIT-' . $lockedOrder->id)->delete();
                                $lockedOrder->delete();
                            } else {
                                $lockedOrder->update([
                                    'status' => 'failed',
                                    'sms_text' => 'Provider error: ' . substr($e->getMessage(), 0, 200),
                                ]);
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
                        }
                        DB::commit();
                    } catch (\Exception $ex) {
                        DB::rollBack();
                        Log::error('Failed to restore wallet after virtual number purchase failure', [
                            'user_id' => $user->id,
                            'amount' => $finalCheckCost,
                            'no_purchase_due_to_quote' => $noPurchaseDueToQuote,
                            'error' => $ex->getMessage(),
                        ]);
                    }
                }

                if (str_contains($providerError, 'out_of_stock') || str_contains($providerError, 'no numbers')) {
                    Cache::forget("smspool_live_quote_{$country}_{$productId}");
                }

                throw $e;
            }

            // Extract order details
            $apiOrderId = $buyResponse['order_id'] ?? null;

            // A confirmed rental without a verifiable effective price must remain
            // unresolved. Preserve its activation and number; never refund it as failed.
            if ($apiOrderId && $this->isDaisyService($server, (string) $country, (string) $productId) && !empty($buyResponse['price_unverified'])) {
                $rawPhone = trim((string) ($buyResponse['phonenumber'] ?? ''));
                $digits = preg_replace('/\\D+/', '', $rawPhone);
                $safePhone = str_starts_with($rawPhone, '+') ? '+' . $digits : ($digits !== '' ? '+' . $digits : 'PENDING');

                DB::transaction(function () use ($attemptRecord, $apiOrderId, $safePhone, $buyResponse) {
                    $lockedOrder = VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                    if ($lockedOrder && $lockedOrder->status === 'processing') {
                        $lockedOrder->update([
                            'api_order_id' => (string) $apiOrderId,
                            'phone_number' => $safePhone,
                            'status' => 'unresolved',
                            'provider_cost' => 0,
                            'provider_quote_usd' => (float) ($buyResponse['provider_quote_usd'] ?? 0),
                            'sms_text' => 'Provider confirmed the rental but omitted its effective price header. Activation preserved; funds retained for reconciliation. No automatic refund.',
                        ]);
                    }
                });

                return response()->json([
                    'error' => 'The carrier issued the number but did not return a verifiable price. Your payment is retained while the order is reconciled.',
                    'order_id' => $attemptRecord->id,
                    'status' => 'unresolved',
                ], 202);
            }

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
                                'provider_supplier_id' => $server === 'server3' ? ($buyResponse['requested_provider_id'] ?? null) : null,
                                'provider_quote_usd' => $server === 'server3' ? ($buyResponse['provider_quote_usd'] ?? null) : null,
                                'provider_returned_operator' => $server === 'server3' ? ($buyResponse['provider_returned_operator'] ?? null) : null,
                                'provider_cost' => (float) ($buyResponse['cost'] ?? $buyResponse['provider_quote_usd'] ?? 0),
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
            $providerPhoneNumber = trim((string) ($buyResponse['phonenumber'] ?? ''));
            if ($server === 'server3') {
                // SMSBower's phoneNumber is the number to present to the customer.
                // Its countryCode field is not safe to prepend here: it can be a
                // provider country identifier rather than an international dial prefix.
                $phoneDigits = preg_replace('/\\D+/', '', $providerPhoneNumber);
                $phoneNumber = str_starts_with($providerPhoneNumber, '+')
                    ? '+' . $phoneDigits
                    : ($phoneDigits !== '' ? '+' . $phoneDigits : '');
            } else {
                $phoneNumber = str_starts_with($providerPhoneNumber, '+')
                    ? $providerPhoneNumber
                    : ('+' . ($buyResponse['cc'] ?? '') . $providerPhoneNumber);
            }
            $actualCostUsd = (isset($buyResponse['cost']) && (float)$buyResponse['cost'] > 0)
                ? (float) $buyResponse['cost']
                : (float) ($buyResponse['provider_quote_usd'] ?? $activeWholesalePrice);

            // All providers are called without stale provider-side price caps.
            // Live quotes and the configured minimum-profit ceiling are validated
            // before purchase; if the provider's actual charge changes beyond that
            // ceiling after issuing an activation, preserve it for reconciliation.
            if ($actualCostUsd > ($maxAllowedProviderUsd + 0.0001)) {
                $safePhone = str_starts_with($providerPhoneNumber, '+')
                    ? '+' . preg_replace('/\\D+/', '', $providerPhoneNumber)
                    : '+' . ($buyResponse['cc'] ?? '') . preg_replace('/\\D+/', '', $providerPhoneNumber);

                DB::transaction(function () use ($attemptRecord, $apiOrderId, $safePhone, $actualCostUsd, $activeWholesalePrice) {
                    $lockedOrder = VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                    if ($lockedOrder && $lockedOrder->status === 'processing') {
                        $lockedOrder->update([
                            'api_order_id' => (string) $apiOrderId,
                            'phone_number' => $safePhone,
                            'provider_cost' => $actualCostUsd,
                            'provider_quote_usd' => $activeWholesalePrice,
                            'status' => 'unresolved',
                            'sms_text' => 'Provider confirmed the activation, but its actual charge exceeded the pre-purchase safe spend ceiling. Activation and customer payment preserved for reconciliation; no automatic refund.',
                        ]);
                    }
                });

                return response()->json([
                    'error' => 'The carrier issued the number above the confirmed safe spending limit. Your payment and activation have been preserved while the order is verified. Please contact support before purchasing again.',
                    'order_id' => $attemptRecord->id,
                    'status' => 'unresolved',
                ], 202);
            }

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
                        DB::transaction(function () use ($attemptRecord, $buyResponse, $server) {
                            $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                            if ($lockedOrder && $lockedOrder->status === 'processing') {
                                $lockedOrder->update([
                                    'status' => 'unresolved',
                                    'provider_supplier_id' => $server === 'server3' ? ($buyResponse['requested_provider_id'] ?? null) : null,
                                    'provider_quote_usd' => $server === 'server3' ? ($buyResponse['provider_quote_usd'] ?? null) : null,
                                    'provider_returned_operator' => $server === 'server3' ? ($buyResponse['provider_returned_operator'] ?? null) : null,
                                    'provider_cost' => (float) ($buyResponse['cost'] ?? $buyResponse['provider_quote_usd'] ?? 0),
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
                $operatorLabel = $server === 'server2' ? 'Server 2' : ($server === 'server3' ? 'Server 3' : ($server === 'server4' ? 'Server 4' : 'Server 1'));

                $lockedOrder = \App\Models\VirtualNumberOrder::where('id', $attemptRecord->id)->lockForUpdate()->first();
                $lockedOrder->update([
                    'api_order_id' => $apiOrderId,
                    'phone_number' => $phoneNumber,
                    'country' => $countryName,
                    'operator' => $operatorLabel,
                    'server' => $server,
                    'provider_key' => $server === 'server4' ? 'daisysms' : ($server === 'server3' ? 'smsbower' : null),
                    'product' => $serviceName,
                    'cost' => $finalUserCostUsd,
                    'provider_cost' => $actualCostUsd,
                    'provider_supplier_id' => $server === 'server3' ? ($buyResponse['requested_provider_id'] ?? null) : null,
                    'provider_quote_usd' => $server === 'server3' ? ($buyResponse['provider_quote_usd'] ?? null) : null,
                    'provider_returned_operator' => $server === 'server3' ? ($buyResponse['provider_returned_operator'] ?? null) : null,
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
            if ($server === 'server3' && (
                stripos($rawError, 'Gold suppliers') !== false
                || stripos($rawError, 'no numbers') !== false
                || stripos($rawError, 'out of stock') !== false
            )) {
                $userFacingError = "No verified Gold supplier currently has stock for this service and country. Your wallet was not charged. Please try another service, country, or server.";
            } elseif ($server === 'server3' || $server === 'server4') {
                $userFacingError = ($server === 'server4' ? 'Server 4' : 'Server 3') . " is temporarily unable to fulfil this request. Your wallet was not charged. Please try again shortly or choose another server.";
            } elseif (stripos($rawError, 'below the price of') !== false || stripos($rawError, 'suitable pool') !== false) {
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
        $isDaisyExtra = ($order->provider_key ?? null) === 'daisysms' && !empty($order->daisy_extra_activation_id);
        if (!$isDaisyExtra && $order->status !== 'pending' && !($order->status === 'received' && empty($order->sms_code))) {
            return response()->json(['order' => $order]);
        }

        $actionLock = Cache::lock("vn_order_action_{$order->id}", 60);
        if (!$actionLock->get()) return response()->json(['order' => $order->fresh(), 'polling' => true]);

        try {
            $order = $order->fresh();
            if (!$order) return response()->json(['error' => 'Order not found.'], 404);

            // DaisySMS extra activations are independent rentals. Poll their activation ID
            // separately and never overwrite the first SMS code/message.
            if (($order->provider_key ?? null) === 'daisysms' && !empty($order->daisy_extra_activation_id)
                && empty($order->daisy_extra_sms_code)
                && !in_array($order->daisy_extra_activation_status, ['failed', 'canceled'], true)) {
                $extraCheck = $this->daisysms->checkOrder((string) $order->daisy_extra_activation_id);
                $extraStatus = (int) ($extraCheck['status'] ?? 1);

                if ($extraStatus === 3) {
                    $extraCode = $extraCheck['sms_code'] ?? $extraCheck['sms'] ?? $extraCheck['code'] ?? '';
                    $extraText = $extraCheck['full_sms'] ?? $extraCheck['sms_text'] ?? $extraCode;
                    if (is_array($extraCode)) $extraCode = $extraCode['code'] ?? $extraCode['sms'] ?? '';
                    $extraCode = trim((string) $extraCode);
                    $extraText = is_scalar($extraText) ? trim((string) $extraText) : '';
                    if ($extraCode === '' && $extraText !== '') {
                        $decoded = trim(strip_tags(html_entity_decode($extraText, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                        if (preg_match('/(?:code|otp|verification)\\D{0,50}(\\d{3,10})/i', $decoded, $matches)
                            || preg_match('/\\b\\d{3,10}\\b/', $decoded, $matches)) {
                            $extraCode = $matches[1] ?? $matches[0] ?? '';
                        }
                    }
                    if ($extraCode !== '') {
                        DB::transaction(function () use ($order, $extraCode, $extraText) {
                            $locked = VirtualNumberOrder::whereKey($order->id)->lockForUpdate()->first();
                            if ($locked && empty($locked->daisy_extra_sms_code)) {
                                $locked->update([
                                    'daisy_extra_sms_code' => $extraCode,
                                    'daisy_extra_sms_text' => $extraText !== '' ? $extraText : $extraCode,
                                    'daisy_extra_activation_status' => 'received',
                                ]);
                            }
                        });
                    }
                } elseif (in_array($extraStatus, [2, 5, 6], true)) {
                    VirtualNumberOrder::whereKey($order->id)
                        ->whereNull('daisy_extra_sms_code')
                        ->update(['daisy_extra_activation_status' => 'failed']);
                } else {
                    VirtualNumberOrder::whereKey($order->id)->update([
                        'daisy_extra_activation_status' => $order->daisy_extra_activation_status === 'waiting' ? 'waiting' : 'ready',
                    ]);
                }

                $order = $order->fresh();
            }

            // Once the original SMS exists, only the additional activation needs polling.
            if ($order->status === 'received' && !empty($order->sms_code)) {
                return response()->json(['order' => $order]);
            }
            if ($order->status !== 'pending' && !($order->status === 'received' && empty($order->sms_code))) {
                return response()->json(['order' => $order]);
            }

            $provider = $this->getOrderProvider($order);
            $checkData = $provider->checkOrder($order->api_order_id);
            $status = isset($checkData['status']) ? (int) $checkData['status'] : 1;

            if ($status === 3) {
                $smsCode = $checkData['sms'] ?? $checkData['sms_code'] ?? $checkData['code'] ?? '';
                $fullSms = $checkData['full_sms'] ?? $checkData['sms_text'] ?? $smsCode;
                if (is_array($smsCode)) $smsCode = $smsCode['code'] ?? $smsCode['sms'] ?? '';
                $smsCode = trim((string) $smsCode);
                $fullSms = is_scalar($fullSms) ? trim((string) $fullSms) : '';
                $decodedSms = trim(strip_tags(html_entity_decode($fullSms, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                if ($smsCode === '' && $decodedSms !== '') {
                    if (preg_match('/(?:code|验证码|otp|verification)\\D{0,50}(\\d{3,10})/i', $decodedSms, $matches)) {
                        $smsCode = $matches[1];
                    } elseif (preg_match('/\\b\\d{3,10}\\b/', $decodedSms, $matches)) {
                        $smsCode = $matches[0];
                    }
                }
                if ($smsCode !== '') {
                    DB::transaction(function () use ($order, $smsCode, $fullSms) {
                        $lockedOrder = VirtualNumberOrder::where('id', $order->id)->lockForUpdate()->first();
                        if ($lockedOrder && in_array($lockedOrder->status, ['pending', 'received'], true) && empty($lockedOrder->sms_code)) {
                            $lockedOrder->update([
                                'status' => 'received',
                                'sms_text' => $fullSms !== '' ? $fullSms : $smsCode,
                                'sms_code' => $smsCode,
                            ]);
                        }
                    });
                } else {
                    Log::warning('Provider reported SMS received but no code was extractable; keeping order recoverable.', [
                        'order_id' => $order->id, 'server' => $order->server, 'response_keys' => array_keys($checkData),
                    ]);
                }
            } elseif (in_array($status, [2, 5, 6], true) && $order->status === 'pending') {
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
        if (!empty($order->sms_code)) return response()->json(['error' => 'Cannot cancel a number that has already received an SMS code.'], 400);

        $actionLock = Cache::lock("vn_order_action_{$order->id}", 60);
        if (!$actionLock->get()) return response()->json(['error' => 'Another action is being processed for this order. Please retry shortly.'], 409);

        try {
            $order = $order->fresh();
            if (!$order || $order->status !== 'pending' || !empty($order->sms_code)) {
                return response()->json(['error' => 'This order is no longer eligible for cancellation.'], 409);
            }
            $provider = $this->getOrderProvider($order);

            // Verify provider state immediately before cancellation. If the provider
            // reports an SMS or an unknown state, fail closed and never refund.
            $providerStatus = $provider->checkOrder($order->api_order_id);
            $status = isset($providerStatus['status']) ? (int) $providerStatus['status'] : -1;
            if ($status === 3) {
                return response()->json(['error' => 'An SMS has already been received by this number. It cannot be canceled or refunded.'], 409);
            }
            if ($status !== 1) {
                return response()->json(['error' => 'The provider could not confirm that this number is still waiting for an SMS. Cancellation is blocked to protect your wallet. Please retry shortly.'], 409);
            }

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

        $resendLock = Cache::lock("vn_order_action_{$order->id}", 60);
        if (!$resendLock->get()) {
            return response()->json(['error' => 'A resend request is already in progress for this order.'], 409);
        }

        // Each rejected attempt can be retried, so ledger references must be
        // unique per attempt while remaining paired for reconciliation.
        $attemptReference = strtoupper((string) \Illuminate\Support\Str::uuid());
        $feeReference = 'VN-EXTRA-DEBIT-' . $order->id . '-' . $attemptReference;
        $refundReference = 'VN-EXTRA-REFUND-' . $order->id . '-' . $attemptReference;

        try {
            $order = $order->fresh();

            if (($order->provider_key ?? null) === 'daisysms') {
                if ($order->status !== 'received' || empty($order->sms_code)) {
                    return response()->json(['error' => 'Receive the first SMS before requesting another SMS.'], 409);
                }
                if (!empty($order->daisy_extra_activation_id) ||
                    in_array($order->daisy_extra_activation_status, ['requesting', 'requested', 'waiting', 'ready', 'unknown'], true)) {
                    return response()->json(['error' => 'An additional SMS has already been requested for this number.'], 409);
                }

                // Pass through exactly USD $0.20 at the configured virtual-number
                // exchange rate. No virtual-number markup is added.
                $feeNgn = round(0.20 * max(1.0, $this->getProviderRate()), 4);

                $charged = DB::transaction(function () use ($order, $feeNgn, $feeReference) {
                    $lockedOrder = VirtualNumberOrder::where('id', $order->id)->lockForUpdate()->first();
                    if (!$lockedOrder || $lockedOrder->provider_key !== 'daisysms' ||
                        $lockedOrder->status !== 'received' || empty($lockedOrder->sms_code) ||
                        !empty($lockedOrder->daisy_extra_activation_id) ||
                        in_array($lockedOrder->daisy_extra_activation_status, ['requesting', 'requested', 'waiting', 'ready', 'unknown'], true)) {
                        return ['ok' => false, 'message' => 'This number is not eligible for another SMS request.'];
                    }

                    $user = \App\Models\User::where('id', $lockedOrder->user_id)->lockForUpdate()->first();
                    if (!$user || !$user->deductBalance($feeNgn)) {
                        return ['ok' => false, 'message' => 'Insufficient wallet balance for the additional SMS fee of ' . money($feeNgn) . '.'];
                    }

                    Transaction::add(
                        $user->id,
                        $feeNgn,
                        'debit',
                        'virtual_number',
                        'Additional SMS request fee (USD 0.20)',
                        $feeReference
                    );

                    $lockedOrder->update([
                        'daisy_extra_activation_status' => 'requesting',
                        'daisy_extra_activation_requested_at' => now(),
                        'daisy_extra_activation_fee' => $feeNgn,
                    ]);

                    return ['ok' => true];
                });

                if (!$charged['ok']) {
                    return response()->json(['error' => $charged['message']], 409);
                }

                try {
                    $extra = $this->daisysms->requestExtraActivation((string) $order->api_order_id);
                } catch (\App\Exceptions\ProviderTimeoutException $timeout) {
                    // The provider may have accepted the request before the connection
                    // was interrupted. Keep the fee and mark the attempt for reconciliation.
                    VirtualNumberOrder::whereKey($order->id)->update([
                        'daisy_extra_activation_status' => 'unknown',
                    ]);
                    Log::warning('DaisySMS extra activation outcome is unknown after timeout.', [
                        'order_id' => $order->id,
                        'error' => $timeout->getMessage(),
                    ]);
                    return response()->json([
                        'error' => 'The additional SMS request is being verified. The fee has been reserved to prevent a duplicate request. Refresh this order shortly.',
                        'status' => 'unknown',
                    ], 202);
                } catch (\Throwable $providerError) {
                    DB::transaction(function () use ($order, $feeNgn, $refundReference, $feeReference) {
                        $lockedOrder = VirtualNumberOrder::whereKey($order->id)->lockForUpdate()->first();
                        if (!$lockedOrder || $lockedOrder->daisy_extra_activation_status !== 'requesting') {
                            return;
                        }

                        $user = \App\Models\User::whereKey($lockedOrder->user_id)->lockForUpdate()->first();
                        if ($user) {
                            $user->addBalance($feeNgn);
                            Transaction::add(
                                $user->id,
                                $feeNgn,
                                'credit',
                                'refund',
                                'Reversal of unsuccessful additional SMS request fee',
                                $refundReference
                            );
                        }

                        $lockedOrder->update([
                            'daisy_extra_activation_status' => 'failed',
                            'daisy_extra_activation_fee' => 0,
                        ]);
                    });

                    Log::warning('DaisySMS extra activation request failed; fee reversed.', [
                        'order_id' => $order->id,
                        'error' => $providerError->getMessage(),
                    ]);
                    return response()->json([
                        'error' => $providerError->getMessage() ?: 'The additional SMS request failed. Your fee has been refunded.',
                        'fee_refunded' => true,
                    ], 400);
                }

                try {
                    VirtualNumberOrder::whereKey($order->id)->update([
                        'daisy_extra_activation_id' => $extra['activation_id'],
                        'daisy_extra_activation_status' => $extra['status'],
                    ]);
                } catch (\Throwable $persistError) {
                    // The provider has confirmed a new activation, but its ID could
                    // not be persisted. Never refund or issue another request blindly.
                    VirtualNumberOrder::whereKey($order->id)->update([
                        'daisy_extra_activation_status' => 'unknown',
                    ]);
                    Log::critical('DaisySMS accepted extra activation but local persistence failed.', [
                        'order_id' => $order->id,
                        'provider_activation_id' => $extra['activation_id'] ?? null,
                        'error' => $persistError->getMessage(),
                    ]);
                    return response()->json([
                        'error' => 'The provider accepted your additional SMS request, but Linkocial is still synchronizing its status. Your fee has been retained to prevent a duplicate charge.',
                        'status' => 'unknown',
                    ], 202);
                }

                return response()->json([
                    'success' => true,
                    'message' => $extra['status'] === 'waiting'
                        ? 'Your additional SMS request is queued. The fee has been charged.'
                        : 'Your additional SMS request is ready. Waiting for the new code.',
                    'extra_sms_status' => $extra['status'],
                    'ready_at' => $extra['ready_at'],
                    'fee' => $feeNgn,
                    'formatted_fee' => money($feeNgn),
                    'order' => $order->fresh(),
                ]);
            }

            $eligible = DB::transaction(function () use ($order) {
                $lockedOrder = VirtualNumberOrder::where('id', $order->id)->lockForUpdate()->first();
                return $lockedOrder && in_array($lockedOrder->status, ['received', 'pending'], true);
            });

            if (!$eligible) {
                return response()->json(['error' => 'This order is no longer eligible for SMS resend.'], 409);
            }

            $provider = $this->getOrderProvider($order);
            $resendData = $provider->resendOrder($order->api_order_id);

            $updated = DB::transaction(function () use ($order) {
                $lockedOrder = VirtualNumberOrder::where('id', $order->id)->lockForUpdate()->first();
                if (!$lockedOrder || !in_array($lockedOrder->status, ['received', 'pending'], true)) {
                    return false;
                }
                $lockedOrder->update(['status' => 'pending', 'sms_code' => null]);
                return true;
            });

            if (!$updated) {
                return response()->json([
                    'error' => 'The provider accepted the resend, but this order changed concurrently. Refresh order history.',
                    'provider_resend_confirmed' => true,
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => $resendData['message'] ?? 'SMS resend requested successfully. Waiting for new code.',
                'order' => $order->fresh(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Virtual number resendOrder exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage() ?: 'Could not request SMS resend at this time.'], 400);
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
            if (!$lockedOrder || $lockedOrder->status !== 'pending' || !empty($lockedOrder->sms_code)) {
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
                    'message' => 'Service currently out of stock or unavailable for this country on ' . ($server === 'server2' ? 'Server 2' : ($server === 'server3' ? 'Server 3' : ($server === 'server4' ? 'Server 4' : 'Server 1'))) . '.'
                ], 404);
            }

            if ($server === 'server2' && $productId) {
                $serviceName = $this->grizzly->getServiceName($productId);
            }

            $activeWholesalePrice = $cheapestPrice;
            $stockCount = null;
            $successRate = null;

            // DaisySMS handles only the four allowlisted USA service variants.
            if ($this->isDaisyService($server, (string) $country, (string) $productId)) {
                $daisyOffer = collect($this->daisysms->getPricesForCountry((string) $country))->firstWhere('service', (string) $productId);
                if (!$daisyOffer || (int) ($daisyOffer['stock'] ?? 0) <= 0) {
                    return response()->json(['success' => false, 'out_of_stock' => true, 'stock' => 0, 'service_name' => $serviceName, 'message' => 'This service is currently out of stock on Server 4.']);
                }
                $activeWholesalePrice = (float) $daisyOffer['price'];
                $stockCount = (int) $daisyOffer['stock'];
            } elseif ($server === 'server3') {
                Cache::forget('smsbower_gold_top_' . md5((string) $productId));
                $liveGoldOffer = $this->smsbower->getLiveGoldOffer($country, (string) $productId);
                if (empty($liveGoldOffer['provider_ids']) || (float) ($liveGoldOffer['price'] ?? 0) <= 0) {
                    return response()->json([
                        'success' => false,
                        'out_of_stock' => true,
                        'stock' => 0,
                        'service_name' => $serviceName,
                        'message' => 'No verified Gold supplier currently has stock for this service and country on Server 3.',
                    ]);
                }
                $activeWholesalePrice = (float) $liveGoldOffer['price'];
                $stockCount = (int) ($liveGoldOffer['stock'] ?? 0);
            }

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
        if ($server === 'server4') {
            if (!isset(DaisySmsService::SUPPORTED_COUNTRIES[$country])) return [];
            $cacheKey = "daisysms_prices_{$country}";
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && !empty($cached)) return $cached;
            try {
                $rows = $this->daisysms->getPricesForCountry($country);
                if (!empty($rows)) {
                    Cache::put($cacheKey, $rows, now()->addMinute());
                    Cache::put($cacheKey . '_stale', $rows, now()->addHours(2));
                    return $rows;
                }
            } catch (\Throwable $e) {
                Log::warning('DaisySMS Server 4 catalogue refresh failed.', [
                    'country' => $country,
                    'error' => $e->getMessage(),
                ]);
            }
            return Cache::get($cacheKey . '_stale', []);
        }

        $cacheKey = match ($server) {
            'server2' => "grizzly_prices_{$country}",
            'server3' => "smsbower_only_prices_v1_{$country}",
            default => "smspool_prices_{$country}",
        };
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) return $cached;

        try {
            $pricesData = $this->getProvider($server)->getPricesForCountry($country);
            if (!empty($pricesData) && is_array($pricesData)) {
                Cache::put($cacheKey, $pricesData, $server === 'server3' ? 5 : 60);
                Cache::put($cacheKey . '_stale', $pricesData, now()->addHours(2));
                return $pricesData;
            }
        } catch (\Throwable $e) {
            Log::warning("Provider catalogue refresh failed for {$country} on {$server}; attempting last known catalogue.", ['error' => $e->getMessage()]);
        }
        return Cache::get($cacheKey . '_stale', []);
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
