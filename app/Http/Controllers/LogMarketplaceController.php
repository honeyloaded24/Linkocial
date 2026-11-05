<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LogCategory;
use App\Models\LogProduct;
use App\Models\LogOrder;
use App\Models\Transaction;
use App\Models\Setting;
use App\Services\FaddedApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class LogMarketplaceController extends Controller
{
    public function index(Request $request)
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('log_categories') && !\Illuminate\Support\Facades\Schema::hasColumn('log_categories', 'slug')) {
            try {
                \Illuminate\Support\Facades\Schema::table('log_categories', function ($table) {
                    $table->string('slug')->nullable()->after('name')->index();
                });
            } catch (\Throwable $ignored) {}
        }

        // Auto-sync log products in background if last sync is older than 30 minutes
        if (!Cache::has('fadded_auto_sync_cooldown') && app(FaddedApi::class)->isConfigured()) {
            Cache::put('fadded_auto_sync_cooldown', true, 1800); // 30 mins
            try {
                Artisan::call('fadded:sync');
            } catch (\Throwable $syncErr) {
                Log::warning('Auto-sync fadded:sync warning: ' . $syncErr->getMessage());
            }
        }

        $categoryQuery = LogCategory::query();
        if (Schema::hasColumn('log_categories', 'is_active')) {
            $categoryQuery->where(function($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            });
        }
        $categories = $categoryQuery
            ->withCount([
                'products as active_products_count' => function($q) {
                    $q->where('is_active', true);
                },
                'products' => function($q) {
                    $q->where('is_active', true);
                }
            ])
            ->orderBy('name')
            ->get();

        if ($categories->isEmpty()) {
            $categories = LogCategory::withCount([
                'products as active_products_count' => function($q) {
                    $q->where('is_active', true);
                },
                'products' => function($q) {
                    $q->where('is_active', true);
                }
            ])->orderBy('name')->get();
        }

        $totalActiveCount = LogProduct::where('is_active', true)->count();
        
        $query = LogProduct::with('category')->where('is_active', true);

        $selectedCategory = null;
        $catParam = trim($request->input('category', $request->input('category_id', '')));

        if (!empty($catParam)) {
            if (is_numeric($catParam)) {
                $selectedCategory = LogCategory::find($catParam);
            } else {
                if (\Illuminate\Support\Facades\Schema::hasColumn('log_categories', 'slug')) {
                    $selectedCategory = LogCategory::where('slug', $catParam)->first();
                }

                if (!$selectedCategory) {
                    $selectedCategory = $categories->first(function($c) use ($catParam) {
                        $normalizedSlug = !empty($c->slug) ? $c->slug : \Illuminate\Support\Str::slug($c->name);
                        return strcasecmp($c->name, $catParam) === 0
                            || strcasecmp($c->name, str_replace('-', ' ', $catParam)) === 0
                            || strcasecmp($normalizedSlug, $catParam) === 0
                            || (string)$c->id === (string)$catParam;
                    });
                }
            }

            if ($selectedCategory) {
                $query->where('log_category_id', $selectedCategory->id);
            }
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Regular catalogue pagination (16 items per page)
        $products = $query
            ->orderByRaw("CASE WHEN type = 'local' THEN 0 ELSE 1 END ASC")
            ->orderByRaw("CASE WHEN in_stock > 0 THEN 0 ELSE 1 END ASC")
            ->orderBy('name', 'asc')
            ->paginate(16)
            ->withQueryString();

        // HOT PICKS: Exclusively products explicitly selected by an administrator
        // No automatic selection based on sales, stock, price, or popularity!
        $hotPickIds = json_decode(Setting::get('marketplace_hot_picks', '[]'), true) ?: [];
        $hotPickIds = array_values(array_filter(array_map('intval', (array)$hotPickIds)));

        if (!empty($hotPickIds)) {
            $hotMap = LogProduct::with('category')
                ->where('is_active', true)
                ->whereIn('id', $hotPickIds)
                ->get()
                ->keyBy('id');

            $hotProducts = collect();
            foreach ($hotPickIds as $hId) {
                if ($hotMap->has($hId)) {
                    $hotProducts->push($hotMap->get($hId));
                }
            }
        } else {
            $hotProducts = collect();
        }

        $activeTab = $request->input('tab');
        if (empty($activeTab)) {
            if ($request->filled('category') || $request->filled('category_id') || $request->filled('search') || $request->filled('page')) {
                $activeTab = 'products';
            } else {
                $activeTab = 'categories';
            }
        } else {
            $activeTab = ($activeTab === 'products') ? 'products' : 'categories';
        }

        if ($request->ajax() || $request->wantsJson()) {
            $html = view('logs.partials.catalogue_section', compact('products', 'selectedCategory', 'totalActiveCount', 'hotPickIds'))->render();
            return response()->json([
                'success' => true,
                'html' => $html,
                'category_id' => $selectedCategory ? $selectedCategory->id : '',
                'category_name' => $selectedCategory ? $selectedCategory->name : 'All',
                'total' => $products->total(),
            ]);
        }

        return view('logs.index', compact('categories', 'products', 'hotProducts', 'selectedCategory', 'totalActiveCount', 'hotPickIds', 'activeTab'));
    }

    public function show($id)
    {
        $product = LogProduct::with('category')->findOrFail($id);
        
        if (!$product->is_active) {
            abort(404, 'Product not found or inactive');
        }

        return view('logs.show', compact('product'));
    }

    public function purchase(Request $request, FaddedApi $api)
    {
        $request->validate([
            'product_id' => 'required|exists:log_products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = LogProduct::findOrFail($request->product_id);
        $user = auth()->user();

        if (!$product->is_active) {
            return back()->with('error', 'This product is currently unavailable.');
        }

        if ($request->quantity > $product->in_stock) {
            return back()->with('error', 'Requested quantity exceeds available stock.');
        }

        // Retail price is dynamically computed with the admin global profit margin
        $priceToChargeNgn = $product->getRetailPrice();

        if ($priceToChargeNgn <= 0) {
            return back()->with('error', 'This product is currently unavailable for purchase because its pricing is not configured. Please contact support.');
        }

        $totalCostNgn = round($priceToChargeNgn * $request->quantity, 2);

        if ($user->balance < $totalCostNgn) {
            return back()->with('error', 'Insufficient wallet balance. You need ₦' . number_format($totalCostNgn, 2) . ' to complete this purchase. Please fund your account.');
        }

        // Auto-heal missing columns on production
        if (Schema::hasTable('log_orders') && !Schema::hasColumn('log_orders', 'amount_usd')) {
            try {
                Schema::table('log_orders', function ($table) {
                    $table->decimal('amount_usd', 10, 4)->nullable()->after('total_cost');
                });
            } catch (\Throwable $ignored) {}
        }

        if (Schema::hasTable('log_items')) {
            if (!Schema::hasColumn('log_items', 'log_order_id')) {
                try {
                    Schema::table('log_items', function ($table) {
                        $table->foreignId('log_order_id')->nullable()->constrained()->nullOnDelete();
                    });
                } catch (\Throwable $ignored) {}
            }
            if (!Schema::hasColumn('log_items', 'user_id')) {
                try {
                    Schema::table('log_items', function ($table) {
                        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                    });
                } catch (\Throwable $ignored) {}
            }
        }

        if ($product->type === 'local') {
            DB::beginTransaction();
            try {
                // Lock the requested amount of stock
                $items = \App\Models\LogItem::where('log_product_id', $product->id)
                    ->where('is_sold', false)
                    ->lockForUpdate()
                    ->take($request->quantity)
                    ->get();

                if ($items->count() < $request->quantity) {
                    DB::rollBack();
                    return back()->with('error', 'Not enough stock available at this moment. Please try a lower quantity.');
                }

                // Deduct balance atomically in NGN
                if (!$user->deductBalance($totalCostNgn)) {
                    DB::rollBack();
                    return back()->with('error', 'Insufficient wallet balance. Please fund your account.');
                }
                Transaction::add(
                    $user->id, 
                    $totalCostNgn, 
                    'debit', 
                    'log_purchase', 
                    "Purchased {$request->quantity}x {$product->name} (₦" . number_format($totalCostNgn, 2) . ")"
                );

                $externalOrderId = 'LOG-' . auth()->id() . '-' . time() . rand(10, 99);
                $hasAmountUsd = Schema::hasColumn('log_orders', 'amount_usd');

                $orderData = [
                    'user_id' => $user->id,
                    'log_product_id' => $product->id,
                    'api_product_key' => $product->api_product_key ?: ('local_' . $product->id),
                    'product_name' => $product->name,
                    'quantity' => $request->quantity,
                    'unit_cost' => $priceToChargeNgn,
                    'total_cost' => $totalCostNgn,
                    'external_order_id' => $externalOrderId,
                    'status' => 'completed',
                ];
                if ($hasAmountUsd) {
                    $orderData['amount_usd'] = 0;
                }

                $order = LogOrder::create($orderData);

                $deliveredItems = [];
                $hasUserId = Schema::hasColumn('log_items', 'user_id');
                $hasLogOrderId = Schema::hasColumn('log_items', 'log_order_id');

                foreach ($items as $item) {
                    $item->is_sold = true;
                    if ($hasUserId) {
                        $item->user_id = $user->id;
                    }
                    if ($hasLogOrderId) {
                        $item->log_order_id = $order->id;
                    }
                    $item->save();
                    
                    $deliveredItems[] = [
                        'details' => $item->content,
                        'content' => $item->content,
                        'file_path' => $item->file_path ?? null,
                        'file_name' => $item->file_name ?? null,
                    ];
                }

                $order->update(['delivered_items' => json_encode($deliveredItems)]);
                
                $product->decrement('in_stock', (int) $request->quantity);

                DB::commit();
                return redirect()->route('logs.order', $order->id)->with('success', 'Purchase successful! Here are your items.');

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Logs Local Purchase Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
                return back()->with('error', 'Could not complete purchase: ' . $e->getMessage());
            }
        } else {
            // API Product Purchase
            DB::beginTransaction();

            try {
                // Deduct from user balance atomically in NGN
                if (!$user->deductBalance($totalCostNgn)) {
                    DB::rollBack();
                    return back()->with('error', 'Insufficient wallet balance. Please fund your account.');
                }
                $debitTx = Transaction::add(
                    $user->id, 
                    $totalCostNgn, 
                    'debit', 
                    'log_purchase', 
                    "Purchased {$request->quantity}x {$product->name} (₦" . number_format($totalCostNgn, 2) . ")"
                );

                $externalOrderId = 'LOG-' . auth()->id() . '-' . time() . rand(10, 99);
                $hasAmountUsd = Schema::hasColumn('log_orders', 'amount_usd');

                $orderData = [
                    'user_id' => $user->id,
                    'log_product_id' => $product->id,
                    'api_product_key' => $product->api_product_key ?: ('api_' . $product->id),
                    'product_name' => $product->name,
                    'quantity' => $request->quantity,
                    'unit_cost' => $priceToChargeNgn,
                    'total_cost' => $totalCostNgn,
                    'external_order_id' => $externalOrderId,
                    'status' => 'pending',
                ];
                if ($hasAmountUsd) {
                    $orderData['amount_usd'] = 0;
                }

                $order = LogOrder::create($orderData);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Logs Marketplace Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
                return back()->with('error', 'Could not complete purchase: ' . $e->getMessage());
            }

            // Call the provider API with robust exception handling
            try {
                $response = $api->order($product->api_product_key, $request->quantity, $externalOrderId);
            } catch (\Throwable $apiEx) {
                Log::error("Logs Marketplace Provider API Exception: " . $apiEx->getMessage());
                $response = [
                    'success' => false,
                    'code' => 'EXCEPTION',
                    'admin_message' => 'Provider communication exception: ' . $apiEx->getMessage(),
                    'customer_message' => 'Unable to complete order with provider at this time. Your wallet balance has been refunded.',
                    'message' => 'Unable to complete order with provider at this time. Your wallet balance has been refunded.'
                ];
            }

            if (isset($response['success']) && $response['success']) {
                $order->update([
                    'status' => 'completed',
                    'delivered_items' => json_encode($response['data']['items'] ?? []),
                ]);

                // Deduct local stock
                $product->decrement('in_stock', (int) $request->quantity);

                return redirect()->route('logs.order', $order->id)->with('success', 'Purchase successful! Here are your items.');
            } else {
                // Atomic refund in NGN
                $user->addBalance($totalCostNgn);
                Transaction::add(
                    $user->id, 
                    $totalCostNgn, 
                    'credit', 
                    'log_refund', 
                    "Refund for failed log purchase: {$product->name} (₦" . number_format($totalCostNgn, 2) . ")",
                    null,
                    $debitTx->created_at ?? null
                );

                // Sanitize order delivered_items record to NOT contain API credentials
                $sanitizedResponse = $response;
                unset($sanitizedResponse['api_key'], $sanitizedResponse['key']);

                $order->update([
                    'status' => 'failed',
                    'delivered_items' => json_encode($sanitizedResponse)
                ]);

                // Admin diagnostic logging
                $adminMsg = $response['admin_message'] ?? ($response['message'] ?? 'Provider error');
                Log::warning("Logs Marketplace Provider Order Failed for order {$externalOrderId}: {$adminMsg}");

                // Customer-safe message: NEVER leak raw provider errors, API keys, or technical details
                $customerMessage = $response['customer_message'] ?? 'Unable to complete order with provider at this time. Your wallet balance has been refunded. Please try again later or contact support.';
                return back()->with('error', $customerMessage);
            }
        }
    }

    public function showOrder($id)
    {
        $order = LogOrder::where('user_id', auth()->id())->findOrFail($id);
        $items = json_decode($order->delivered_items, true) ?? [];
        return view('logs.order', compact('order', 'items'));
    }

    public function history(Request $request)
    {
        $query = LogOrder::where('user_id', auth()->id())->with('product')->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('external_order_id', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('logs.history', compact('orders'));
    }
}
