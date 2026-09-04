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

class LogMarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $categories = LogCategory::where('is_active', true)->get();
        
        $query = LogProduct::with('category')->where('is_active', true);

        if ($request->filled('category_id')) {
            $query->where('log_category_id', $request->category_id);
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

        return view('logs.index', compact('categories', 'products', 'hotProducts'));
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
        $priceToCharge = $product->our_price > 0 ? $product->our_price : $product->unit_price * $markupMultiplier;
        $totalCostUsd = $priceToCharge * $request->quantity;

        if ($user->balance < $totalCostUsd) {
            return back()->with('error', 'Insufficient balance. Please fund your account.');
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

                // Deduct balance
                $user->balance -= $totalCostUsd;
                $user->save();
                Transaction::add($user->id, $totalCostUsd, 'debit', 'log_purchase', "Purchased {$request->quantity}x {$product->name}");

                $externalOrderId = 'LOG-' . auth()->id() . '-' . time() . rand(10, 99);
                $order = LogOrder::create([
                    'user_id' => $user->id,
                    'log_product_id' => $product->id,
                    'api_product_key' => null,
                    'product_name' => $product->name,
                    'quantity' => $request->quantity,
                    'unit_cost' => $priceToCharge,
                    'total_cost' => $totalCostUsd,
                    'external_order_id' => $externalOrderId,
                    'status' => 'completed',
                ]);

                $deliveredItems = [];
                foreach ($items as $item) {
                    $item->is_sold = true;
                    $item->user_id = $user->id;
                    $item->log_order_id = $order->id;
                    $item->save();
                    
                    $deliveredItems[] = [
                        'details' => $item->content,
                        'content' => $item->content,
                        'file_path' => $item->file_path,
                        'file_name' => $item->file_name,
                    ];
                }

                $order->update(['delivered_items' => json_encode($deliveredItems)]);
                
                $product->in_stock -= $request->quantity;
                $product->save();

                DB::commit();
                return redirect()->route('logs.order', $order->id)->with('success', 'Purchase successful! Here are your items.');

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Logs Local Purchase Error: " . $e->getMessage());
                return back()->with('error', 'An error occurred during transaction.');
            }
        } else {
            // API Product Purchase
            DB::beginTransaction();

            try {
                // Deduct from user balance
                $user->balance -= $totalCostUsd;
                $user->save();

                Transaction::add($user->id, $totalCostUsd, 'debit', 'log_purchase', "Purchased {$request->quantity}x {$product->name}");

                $externalOrderId = 'LOG-' . auth()->id() . '-' . time() . rand(10, 99);

                $order = LogOrder::create([
                    'user_id' => $user->id,
                    'log_product_id' => $product->id,
                    'api_product_key' => $product->api_product_key,
                    'product_name' => $product->name,
                    'quantity' => $request->quantity,
                    'unit_cost' => $priceToCharge,
                    'total_cost' => $totalCostUsd,
                    'external_order_id' => $externalOrderId,
                    'status' => 'pending',
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Logs Marketplace Error: " . $e->getMessage());
                return back()->with('error', 'An error occurred during transaction.');
            }

            // Call the provider API
            $response = $api->order($product->api_product_key, $request->quantity, $externalOrderId);

            if (isset($response['success']) && $response['success']) {
                $order->update([
                    'status' => 'completed',
                    'delivered_items' => json_encode($response['data']['items'] ?? []),
                ]);

                // Deduct local stock
                $product->in_stock -= $request->quantity;
                $product->save();

                return redirect()->route('logs.order', $order->id)->with('success', 'Purchase successful! Here are your items.');
            } else {
                // Refund
                $user->balance += $totalCostUsd;
                $user->save();
                Transaction::add($user->id, $totalCostUsd, 'credit', 'log_refund', "Refund for failed log purchase: {$product->name}");

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
}
