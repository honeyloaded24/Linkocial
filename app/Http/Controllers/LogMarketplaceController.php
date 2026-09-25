<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LogCategory;
use App\Models\LogProduct;
use App\Models\LogOrder;
use App\Models\Transaction;
use App\Services\FaddedApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

        $categories = LogCategory::where('is_active', true)->orWhereNull('is_active')->get();
        if ($categories->isEmpty()) {
            $categories = LogCategory::all();
        }
        
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

        $products = $query
            ->orderByRaw("CASE WHEN type = 'local' THEN 0 ELSE 1 END ASC")
            ->orderByRaw("CASE WHEN in_stock > 0 THEN 0 ELSE 1 END ASC")
            ->orderBy('name', 'asc')
            ->get();

        // Hot products (most sold recently, prioritizing local products)
        $hotProducts = LogProduct::where('is_active', true)
            ->withCount('logOrders as sales_count')
            ->orderByRaw("CASE WHEN type = 'local' THEN 0 ELSE 1 END ASC")
            ->orderBy('sales_count', 'desc')
            ->take(3)
            ->get();

        return view('logs.index', compact('categories', 'products', 'hotProducts', 'selectedCategory'));
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

        $markupRate = (float) \App\Models\Setting::get('markup_logs_market', 20);
        $markupMultiplier = 1 + ($markupRate / 100);

        // Prices are denominated in Nigerian Naira (₦ / NGN)
        $priceToChargeNgn = (float) ($product->our_price > 0 ? $product->our_price : $product->unit_price * $markupMultiplier);

        if ($priceToChargeNgn <= 0) {
            return back()->with('error', 'This product is currently unavailable for purchase because its pricing is not configured. Please contact support.');
        }

        $totalCostNgn = $priceToChargeNgn * $request->quantity;

        $ngnRate = (float) (\App\Models\Currency::where('code', 'NGN')->value('exchange_rate') ?? 1420.00);
        $amountUsd = $totalCostNgn / $ngnRate;

        if ($user->balance < $amountUsd) {
            return back()->with('error', 'Insufficient wallet balance. You need ₦' . number_format($totalCostNgn, 2) . ' (~$' . number_format($amountUsd, 2) . ') to complete this purchase. Please fund your account.');
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

                // Deduct balance atomically (USD base)
                if (!$user->deductBalance($amountUsd)) {
                    DB::rollBack();
                    return back()->with('error', 'Insufficient wallet balance. Please fund your account.');
                }
                Transaction::add(
                    $user->id, 
                    $amountUsd, 
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
                    $orderData['amount_usd'] = $amountUsd;
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
                // Deduct from user balance atomically (USD base)
                if (!$user->deductBalance($amountUsd)) {
                    DB::rollBack();
                    return back()->with('error', 'Insufficient wallet balance. Please fund your account.');
                }
                Transaction::add(
                    $user->id, 
                    $amountUsd, 
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
                    $orderData['amount_usd'] = $amountUsd;
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
                    'message' => 'Provider communication error: ' . $apiEx->getMessage()
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
                // Atomic refund
                $user->addBalance($amountUsd);
                Transaction::add(
                    $user->id, 
                    $amountUsd, 
                    'credit', 
                    'log_refund', 
                    "Refund for failed log purchase: {$product->name} (₦" . number_format($totalCostNgn, 2) . ")"
                );

                $order->update([
                    'status' => 'failed',
                    'delivered_items' => json_encode($response)
                ]);

                $message = $response['message'] ?? 'Failed to complete purchase with provider.';
                return back()->with('error', $message);
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
