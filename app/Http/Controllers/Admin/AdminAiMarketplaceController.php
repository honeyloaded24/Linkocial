<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiOrder;
use App\Models\AiProduct;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\AiPlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminAiMarketplaceController extends Controller
{
    protected AiPlugService $aiPlugService;

    public function __construct(AiPlugService $aiPlugService)
    {
        $this->aiPlugService = $aiPlugService;
    }

    /**
     * List all AI products.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $category = trim((string) $request->get('category'));

        try {
            $query = AiProduct::query();

            if (!empty($category)) {
                $query->where('category', $category);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('provider_product_id', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            $products = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

            $categories = AiProduct::select('category')->distinct()->pluck('category');

            $productStats = [
                'total'    => AiProduct::count(),
                'active'   => AiProduct::where('is_active', true)->count(),
                'in_stock' => AiProduct::where('in_stock', true)->count(),
                'orders'   => AiOrder::count(),
            ];
        } catch (\Throwable $e) {
            Log::warning('AdminAiMarketplaceController::index fallback: ' . $e->getMessage());
            $products = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
            $categories = collect();
            $productStats = [
                'total'    => 0,
                'active'   => 0,
                'in_stock' => 0,
                'orders'   => 0,
            ];
        }

        return view('hq.ai.index', compact('products', 'categories', 'productStats', 'search', 'category'));
    }

    /**
     * Update product price, active state, or stock.
     */
    public function updateProduct(Request $request, $id)
    {
        $product = AiProduct::findOrFail($id);

        $request->validate([
            'retail_price' => 'nullable|numeric|min:0',
            'in_stock'     => 'boolean',
            'is_active'    => 'boolean',
            'is_hot'       => 'boolean',
        ]);

        $product->update([
            'retail_price' => $request->retail_price !== null ? (float) $request->retail_price : $product->retail_price,
            'in_stock'     => (bool) $request->input('in_stock', $product->in_stock),
            'is_active'    => (bool) $request->input('is_active', $product->is_active),
            'is_hot'       => (bool) $request->input('is_hot', $product->is_hot),
        ]);

        return back()->with('success', "Updated product '{$product->name}' successfully.");
    }

    /**
     * Trigger manual catalog sync from AIPlug API.
     */
    public function syncCatalog()
    {
        if (!$this->aiPlugService->isConfigured()) {
            return back()->with('error', 'AIPlug API key is not configured. Please set it in Settings first.');
        }

        try {
            $result = $this->aiPlugService->syncCatalog();

            if ($result['success']) {
                return back()->with('success', $result['message']);
            } else {
                return back()->with('error', 'Sync failed: ' . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Exception during sync: ' . $e->getMessage());
        }
    }

    /**
     * List all platform AI orders with filters.
     */
    public function orders(Request $request)
    {
        $status = trim((string) $request->get('status'));
        $search = trim((string) $request->get('search'));

        try {
            $query = AiOrder::with(['user', 'product'])->latest();

            if (!empty($status)) {
                $query->where('status', $status);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                      ->orWhere('provider_order_id', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('username', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            $orders = $query->paginate(20)->withQueryString();

            $orderStats = [
                'total'       => AiOrder::count(),
                'fulfilled'   => AiOrder::where('status', 'fulfilled')->count(),
                'pending'     => AiOrder::whereIn('status', ['pending', 'processing', 'awaiting_manual'])->count(),
                'total_sales' => (float) AiOrder::where('status', 'fulfilled')->sum('total_price'),
            ];
        } catch (\Throwable $e) {
            Log::warning('AdminAiMarketplaceController::orders fallback: ' . $e->getMessage());
            $orders = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
            $orderStats = [
                'total'       => 0,
                'fulfilled'   => 0,
                'pending'     => 0,
                'total_sales' => 0.0,
            ];
        }

        return view('hq.ai.orders', compact('orders', 'orderStats', 'status', 'search'));
    }

    /**
     * Manually fulfill an order with delivery details.
     */
    public function fulfillOrder(Request $request, $id)
    {
        $order = AiOrder::findOrFail($id);

        $request->validate([
            'delivery_payload' => 'required|string',
        ]);

        $order->update([
            'status'           => 'fulfilled',
            'delivery_payload' => $request->delivery_payload,
        ]);

        return back()->with('success', "Order #{$order->id} marked as fulfilled.");
    }

    /**
     * Refund an order back to the customer's wallet.
     */
    public function refundOrder(Request $request, $id)
    {
        $order = AiOrder::findOrFail($id);

        if ($order->status === 'refunded') {
            return back()->with('error', 'This order is already refunded.');
        }

        DB::beginTransaction();
        try {
            $user = $order->user;
            if ($user) {
                $refundAmount = (float) $order->total_price;
                $user->increment('balance', $refundAmount);

                Transaction::add(
                    $user->id,
                    $refundAmount,
                    'credit',
                    'refund',
                    "Refund for AI Order #{$order->id} ({$order->product_name})"
                );
            }

            $order->update(['status' => 'refunded']);
            DB::commit();

            return back()->with('success', "Order #{$order->id} refunded successfully (₦" . number_format($order->total_price, 2) . " credited to customer wallet).");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Refresh order status directly from AIPlug.
     */
    public function refreshOrder($id)
    {
        $order = AiOrder::findOrFail($id);

        if (empty($order->provider_order_id)) {
            return back()->with('error', 'Order does not have a provider order ID yet.');
        }

        try {
            $res = $this->aiPlugService->getOrder($order->provider_order_id);

            if ($res['success']) {
                $orderData = $res['data']['order'] ?? $res['data'] ?? [];
                $status = $orderData['status'] ?? $order->status;
                $delivery = $res['data']['delivery'] ?? $orderData['delivery'] ?? null;

                $payload = $order->delivery_payload;
                if ($delivery) {
                    $payload = is_array($delivery) ? json_encode($delivery, JSON_PRETTY_PRINT) : (string) $delivery;
                }

                $order->update([
                    'status'           => $status,
                    'delivery_payload' => $payload,
                    'metadata'         => array_merge($order->metadata ?? [], ['latest_poll' => $res['data']]),
                ]);

                return back()->with('success', "Order #{$order->id} refreshed. Current status: {$status}.");
            } else {
                return back()->with('error', 'Provider lookup failed: ' . ($res['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Refresh error: ' . $e->getMessage());
        }
    }

    /**
     * AI Marketplace settings page (API key, webhook secret, markup %, balance).
     */
    public function settings()
    {
        $apiKey = Setting::get('aiplug_api_key', '');
        $webhookSecret = Setting::get('aiplug_webhook_secret', '');
        $markup = Setting::get('ai_marketplace_markup', '20');
        $baseUrl = Setting::get('aiplug_base_url', AiPlugService::DEFAULT_BASE_URL);

        $providerBalance = null;
        if ($this->aiPlugService->isConfigured()) {
            try {
                $providerBalance = $this->aiPlugService->getBalance();
            } catch (\Throwable $e) {
                $providerBalance = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        $suggestedWebhookUrl = url('/webhooks/aiplug');

        return view('hq.ai.settings', compact('apiKey', 'webhookSecret', 'markup', 'baseUrl', 'providerBalance', 'suggestedWebhookUrl'));
    }

    /**
     * Save settings.
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'aiplug_api_key'        => 'nullable|string',
            'aiplug_webhook_secret' => 'nullable|string',
            'ai_marketplace_markup' => 'required|numeric|min:0|max:500',
            'aiplug_base_url'       => 'nullable|url',
        ]);

        Setting::set('aiplug_api_key', trim((string) $request->aiplug_api_key));
        Setting::set('aiplug_webhook_secret', trim((string) $request->aiplug_webhook_secret));
        Setting::set('ai_marketplace_markup', (string) $request->ai_marketplace_markup);
        Setting::set('aiplug_base_url', trim((string) $request->aiplug_base_url) ?: AiPlugService::DEFAULT_BASE_URL);

        return back()->with('success', 'AI Marketplace settings saved successfully.');
    }

    /**
     * Register webhook URL with AIPlug provider automatically.
     */
    public function registerWebhook(Request $request)
    {
        $url = $request->input('webhook_url', url('/webhooks/aiplug'));

        if (!$this->aiPlugService->isConfigured()) {
            return back()->with('error', 'Please configure and save your API Key first.');
        }

        try {
            $result = $this->aiPlugService->registerWebhook($url);

            if ($result['success']) {
                $msg = 'Webhook registered successfully.';
                if (!empty($result['signing_secret'])) {
                    $msg .= ' Webhook signing secret has been automatically saved.';
                }
                return back()->with('success', $msg);
            } else {
                return back()->with('error', 'Registration failed: ' . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Webhook registration error: ' . $e->getMessage());
        }
    }
}
