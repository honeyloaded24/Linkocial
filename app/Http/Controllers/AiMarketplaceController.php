<?php

namespace App\Http\Controllers;

use App\Models\AiOrder;
use App\Models\AiProduct;
use App\Models\Transaction;
use App\Services\AiPlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiMarketplaceController extends Controller
{
    protected AiPlugService $aiPlugService;

    public function __construct(AiPlugService $aiPlugService)
    {
        $this->aiPlugService = $aiPlugService;
    }

    /**
     * Browse AI Marketplace products.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $category = trim((string) $request->get('category'));
        $user = Auth::user();

        try {
            AiProduct::repairZeroPricesFromMetadata();

            if ($this->aiPlugService->isConfigured() && (AiProduct::count() === 0 || AiProduct::where('wholesale_price_kobo', '<=', 0)->exists())) {
                if (!\Illuminate\Support\Facades\Cache::has('aiplug_auto_sync_cooldown')) {
                    \Illuminate\Support\Facades\Cache::put('aiplug_auto_sync_cooldown', true, 120);
                    $this->aiPlugService->syncCatalog();
                }
            }

            $query = AiProduct::query()->where('is_active', true);

            if (!empty($category) && $category !== 'all') {
                $query->where('category', $category);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            $products = $query->orderBy('is_hot', 'desc')
                ->orderBy('name', 'asc')
                ->paginate(16)
                ->withQueryString();

            $categories = AiProduct::where('is_active', true)
                ->select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->orderBy('count', 'desc')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('AiMarketplaceController::index fallback: ' . $e->getMessage());
            $products = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 16);
            $categories = collect();
        }

        return view('ai.index', compact('products', 'categories', 'search', 'category', 'user'));
    }

    /**
     * Product details view.
     */
    public function show($id)
    {
        AiProduct::repairZeroPricesFromMetadata();
        $product = AiProduct::where('is_active', true)->findOrFail($id);
        $user = Auth::user();

        return view('ai.show', compact('product', 'user'));
    }

    /**
     * Purchase an AI product atomically.
     */
    public function buy(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        $product = AiProduct::where('is_active', true)->findOrFail($id);

        if (!$product->in_stock) {
            return back()->with('error', 'This AI service is currently out of stock.');
        }

        $quantity = (int) $request->quantity;

        if ($product->available_stock !== null && $quantity > $product->available_stock) {
            return back()->with('error', "Only {$product->available_stock} unit(s) currently available in stock.");
        }

        $unitPrice = (float) $product->effective_selling_price;
        $totalCost = round($unitPrice * $quantity, 2);
        $user = Auth::user();

        if ($user->balance < $totalCost) {
            return back()->with('error', 'Insufficient wallet balance (₦' . number_format($user->balance, 2) . '). Required: ₦' . number_format($totalCost, 2) . '. Please fund your wallet first.');
        }

        $idempotencyKey = 'aip_' . $user->id . '_' . time() . '_' . bin2hex(random_bytes(3));
        $providerCost = round(((float) $product->wholesale_price) * $quantity, 2);

        DB::beginTransaction();
        try {
            // Deduct balance atomically ensuring balance >= totalCost
            if (!$user->deductBalance($totalCost)) {
                DB::rollBack();
                return back()->with('error', 'Insufficient balance to complete this purchase.');
            }

            // Record ledger transaction
            Transaction::add(
                $user->id,
                $totalCost,
                'debit',
                'ai_purchase',
                "AI Marketplace: {$quantity}x {$product->name} (₦" . number_format($totalCost, 2) . ")"
            );

            // Create initial order in pending state
            $order = AiOrder::create([
                'user_id'             => $user->id,
                'ai_product_id'       => $product->id,
                'idempotency_key'     => $idempotencyKey,
                'product_name'        => $product->name,
                'quantity'            => $quantity,
                'unit_price'          => $unitPrice,
                'total_price'         => $totalCost,
                'provider_cost'       => $providerCost,
                'status'              => 'processing',
                'delivery_mode'       => 'portal_only',
                'delivery_payload'    => null,
                'metadata'            => [
                    'product_provider_id' => $product->provider_product_id,
                ],
            ]);

            if ($product->available_stock !== null) {
                $newStock = max(0, $product->available_stock - $quantity);
                $meta = is_array($product->metadata) ? $product->metadata : [];
                if (array_key_exists('stock', $meta) && $meta['stock'] !== null) {
                    $meta['stock'] = $newStock;
                }
                $product->update([
                    'stock_count' => $newStock,
                    'in_stock'    => $newStock > 0,
                    'metadata'    => $meta,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AI purchase transaction failed: ' . $e->getMessage());
            return back()->with('error', 'Could not process purchase transaction: ' . $e->getMessage());
        }

        // Dispatch order to AIPlug provider if configured
        if ($this->aiPlugService->isConfigured()) {
            try {
                $response = $this->aiPlugService->placeOrder(
                    $product->provider_product_id,
                    $quantity,
                    $idempotencyKey,
                    'portal_only'
                );

                if ($response['success']) {
                    $orderData = $response['data']['order'] ?? $response['data'] ?? [];
                    $providerOrderId = $orderData['id'] ?? null;
                    $status = $orderData['status'] ?? 'processing';
                    $delivery = $response['data']['delivery'] ?? $orderData['delivery'] ?? null;

                    $payloadText = null;
                    if ($delivery) {
                        $payloadText = is_array($delivery) ? json_encode($delivery, JSON_PRETTY_PRINT) : (string) $delivery;
                    }

                    $order->update([
                        'provider_order_id' => $providerOrderId,
                        'status'            => $status === 'fulfilled' ? 'fulfilled' : 'processing',
                        'delivery_payload'  => $payloadText,
                        'metadata'          => array_merge($order->metadata ?? [], ['api_response' => $response['data']]),
                    ]);
                } else {
                    Log::error("AIPlug placeOrder returned failure for Order #{$order->id}: " . ($response['message'] ?? ''));
                    // Mark awaiting fulfillment
                    $order->update([
                        'status'   => 'awaiting_manual',
                        'metadata' => array_merge($order->metadata ?? [], ['error' => $response['message'] ?? 'Order queued for provider fulfillment']),
                    ]);
                }
            } catch (\Throwable $apiEx) {
                Log::error("AIPlug API call threw exception for Order #{$order->id}: " . $apiEx->getMessage());
                $order->update([
                    'status'   => 'awaiting_manual',
                    'metadata' => array_merge($order->metadata ?? [], ['error' => $apiEx->getMessage()]),
                ]);
            }
        }

        return redirect()->route('ai.history')->with('success', "Order placed successfully! Order ID: #{$order->id}.");
    }

    /**
     * User order history.
     */
    public function history()
    {
        try {
            $orders = AiOrder::where('user_id', Auth::id())
                ->with('product')
                ->latest()
                ->paginate(15);
        } catch (\Throwable $e) {
            Log::warning('AiMarketplaceController::history fallback: ' . $e->getMessage());
            $orders = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        return view('ai.history', compact('orders'));
    }

    /**
     * View detailed order information and delivery payload.
     */
    public function orderDetail($id)
    {
        $order = AiOrder::where('user_id', Auth::id())->with('product')->findOrFail($id);

        // If still processing and has provider_order_id, attempt quick live poll
        if ($order->isPending() && !empty($order->provider_order_id) && $this->aiPlugService->isConfigured()) {
            try {
                $statusRes = $this->aiPlugService->getOrder($order->provider_order_id);
                if ($statusRes['success']) {
                    $orderData = $statusRes['data']['order'] ?? $statusRes['data'] ?? [];
                    $status = $orderData['status'] ?? $order->status;
                    $delivery = $statusRes['data']['delivery'] ?? $orderData['delivery'] ?? null;

                    $payloadText = $order->delivery_payload;
                    if ($delivery) {
                        $payloadText = is_array($delivery) ? json_encode($delivery, JSON_PRETTY_PRINT) : (string) $delivery;
                    }

                    $order->update([
                        'status'           => $status,
                        'delivery_payload' => $payloadText,
                    ]);
                }
            } catch (\Throwable $ignored) {}
        }

        return view('ai.detail', compact('order'));
    }
}
