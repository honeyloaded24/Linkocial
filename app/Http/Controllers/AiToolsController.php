<?php

namespace App\Http\Controllers;

use App\Jobs\PollAiToolOrderStatus;
use App\Models\AiToolCategory;
use App\Models\AiToolOrder;
use App\Models\AiToolOrderItem;
use App\Models\AiToolProduct;
use App\Models\Transaction;
use App\Services\AlaricApiService;
use App\Services\ProductPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AiToolsController extends Controller
{
    public function __construct(
        private readonly ProductPricingService $pricingService,
        private readonly AlaricApiService $alaricService
    ) {}

    // ─── Customer-facing pages ─────────────────────────────────────────────────

    /**
     * Browse the AI Tools marketplace.
     */
    public function index(Request $request)
    {
        if (!Schema::hasTable('ai_tool_products')) {
            return view('ai-tools.index', [
                'products'   => collect(),
                'categories' => collect(),
            ]);
        }

        $query = AiToolProduct::with('category')
            ->active()
            ->inStock()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($categoryId = $request->get('category')) {
            $query->where('category_id', (int) $categoryId);
        }

        $products   = $query->paginate(20)->withQueryString();
        $categories = Schema::hasTable('ai_tool_categories')
            ? AiToolCategory::active()->get()
            : collect();

        return view('ai-tools.index', compact('products', 'categories'));
    }

    /**
     * Product detail page.
     */
    public function show(int $id)
    {
        $product = AiToolProduct::with('category')
            ->active()
            ->findOrFail($id);

        $pricing = $product->getSellingPriceNgn(1);

        return view('ai-tools.show', compact('product', 'pricing'));
    }

    /**
     * Process a purchase.
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|min:1',
            'quantity'   => 'required|integer|min:1|max:9999',
        ]);

        /** @var \App\Models\User $user */
        $user    = auth()->user();
        $product = AiToolProduct::active()->find($request->product_id);

        if (!$product) {
            return back()->with('error', 'Product not found or unavailable.');
        }

        if (!$product->isAvailableForPurchase()) {
            return back()->with('error', 'This product is currently out of stock or unavailable.');
        }

        $qty = (int) $request->quantity;

        if ($product->max_quantity && $qty > $product->max_quantity) {
            return back()->with('error', "Maximum quantity for this product is {$product->max_quantity}.");
        }

        // ── Price calculation ──────────────────────────────────────────────────
        $pricingResult   = $product->getSellingPriceNgn($qty);
        $unitPriceNgn    = (float) ($pricingResult['selling_price'] ?? 0);
        $totalNgn        = round($unitPriceNgn * $qty, 2);
        $costUsdt        = $product->getPriceForQuantity($qty);
        $totalUsdt       = round($costUsdt * $qty, 8);
        $exchangeRate    = (float) ($pricingResult['exchange_rate'] ?? $pricingResult['fix_rate'] ?? 1500);
        $providerCostNgn = round($totalUsdt * $exchangeRate, 2);
        $profitNgn       = round($totalNgn - $providerCostNgn, 2);
        $maxTotalUsdt    = round($totalUsdt * 1.05, 8); // 5% tolerance buffer

        // ── Balance check ─────────────────────────────────────────────────────
        if ((float) $user->balance < $totalNgn) {
            return back()->with('error', 'Insufficient wallet balance. Please fund your wallet and try again.');
        }

        try {
            $order = DB::transaction(function () use (
                $user, $product, $qty,
                $unitPriceNgn, $totalNgn,
                $costUsdt, $totalUsdt,
                $exchangeRate, $providerCostNgn, $profitNgn,
                $maxTotalUsdt
            ) {
                // Generate idempotency key BEFORE any API call; store it with the order
                $idempotencyKey = (string) Str::uuid();

                // Create pending order record
                $order = AiToolOrder::create([
                    'user_id'             => $user->id,
                    'ai_tool_product_id'  => $product->id,
                    'provider_product_id' => $product->provider_product_id,
                    'product_name'        => $product->name,
                    'idempotency_key'     => $idempotencyKey,
                    'status'              => AiToolOrder::STATUS_PENDING,
                    'quantity'            => $qty,
                    'unit_price_usdt'     => $costUsdt,
                    'total_price_usdt'    => $totalUsdt,
                    'exchange_rate'       => $exchangeRate,
                    'unit_price_ngn'      => $unitPriceNgn,
                    'total_paid_ngn'      => $totalNgn,
                    'provider_cost_ngn'   => $providerCostNgn,
                    'profit_ngn'          => $profitNgn,
                    'max_total_usdt'      => $maxTotalUsdt,
                ]);

                // Deduct wallet atomically (uses DB decrement under the hood)
                $user->deductBalance($totalNgn);

                // Record debit in transaction ledger
                Transaction::add(
                    $user->id,
                    $totalNgn,
                    'debit',
                    'ai_tools',
                    "AI TOOLS purchase: {$product->name}" . ($qty > 1 ? " x{$qty}" : '')
                );

                // ── Submit provider order ─────────────────────────────────────
                $providerResponse = app(AlaricApiService::class)->createOrder(
                    $product->provider_product_id,
                    $qty,
                    $maxTotalUsdt,
                    $idempotencyKey
                );

                if (!($providerResponse['success'] ?? false)) {
                    if ($providerResponse['timed_out'] ?? false) {
                        // Timeout — do NOT refund; keep order as processing
                        $order->update([
                            'status'            => AiToolOrder::STATUS_PROCESSING,
                            'provider_response' => $providerResponse,
                        ]);
                        PollAiToolOrderStatus::dispatch($order)->delay(now()->addSeconds(10));
                        return $order;
                    }

                    // Hard provider rejection — refund customer
                    $user->addBalance($totalNgn);
                    Transaction::add(
                        $user->id,
                        $totalNgn,
                        'credit',
                        'ai_tools_refund',
                        "Refund — failed AI TOOLS order #{$order->order_number}"
                    );
                    $order->update([
                        'status'            => AiToolOrder::STATUS_FAILED,
                        'failure_reason'    => $providerResponse['error_message'] ?? 'Provider rejected order.',
                        'provider_response' => $providerResponse,
                    ]);
                    throw new \RuntimeException($providerResponse['error_message'] ?? 'Provider error. Please try again.');
                }

                // ── Handle provider response ──────────────────────────────────
                $providerData    = $providerResponse['data'] ?? $providerResponse['raw'] ?? [];
                $providerOrderId = $providerData['id'] ?? null;
                $providerStatus  = strtoupper((string) ($providerData['status'] ?? 'PROCESSING'));

                $order->update([
                    'provider_order_id' => $providerOrderId,
                    'provider_status'   => $providerStatus,
                    'provider_response' => $providerData,
                ]);

                if ($providerStatus === 'COMPLETED') {
                    foreach (($providerData['items'] ?? []) as $index => $item) {
                        AiToolOrderItem::create([
                            'ai_tool_order_id' => $order->id,
                            'item_index'       => $index,
                            'code'             => $item['code'] ?? null,
                            'details'          => is_array($item) ? json_encode($item) : null,
                        ]);
                    }
                    $order->update([
                        'status'       => AiToolOrder::STATUS_COMPLETED,
                        'completed_at' => now(),
                    ]);
                } else {
                    $order->update(['status' => AiToolOrder::STATUS_PROCESSING]);
                    PollAiToolOrderStatus::dispatch($order)->delay(now()->addSeconds(10));
                }

                return $order;
            });

            return redirect()
                ->route('ai-tools.order.detail', $order->id)
                ->with('success', 'Order placed successfully! Your credentials will appear below once delivered.');

        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('AiToolsController@purchase: Unexpected error', [
                'user_id'    => $user->id,
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'An unexpected error occurred. Please contact support if your wallet was debited.');
        }
    }

    /**
     * Order history for the authenticated customer.
     */
    public function history(Request $request)
    {
        if (!Schema::hasTable('ai_tool_orders')) {
            return view('ai-tools.history', ['orders' => collect()]);
        }

        $orders = AiToolOrder::with('product')
            ->forUser(auth()->id())
            ->latest()
            ->paginate(20);

        return view('ai-tools.history', compact('orders'));
    }

    /**
     * View a single order and its delivered credentials.
     */
    public function orderDetail(int $id)
    {
        $order = AiToolOrder::with(['product', 'items'])
            ->forUser(auth()->id())
            ->findOrFail($id);

        return view('ai-tools.order-detail', compact('order'));
    }
}
