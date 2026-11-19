<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiToolCategory;
use App\Models\AiToolOrder;
use App\Models\AiToolProduct;
use App\Models\Setting;
use App\Services\AlaricApiService;
use App\Services\PixelSubApiService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminAiToolsController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Ensure database tables exist, running migrations automatically if needed.
     */
    private function ensureTablesExist(): void
    {
        if (
            !Schema::hasTable('ai_tool_products') ||
            !Schema::hasTable('ai_tool_orders') ||
            !Schema::hasTable('ai_tool_categories') ||
            !Schema::hasTable('ai_tool_order_items')
        ) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::warning('AdminAiToolsController: auto-migrate failed: ' . $e->getMessage());
            }
        }
    }

    // ─── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $this->ensureTablesExist();

        $aiStats = [
            'total_products'    => Schema::hasTable('ai_tool_products') ? AiToolProduct::count() : 0,
            'active_products'   => Schema::hasTable('ai_tool_products') ? AiToolProduct::active()->count() : 0,
            'inactive_products' => Schema::hasTable('ai_tool_products') ? AiToolProduct::where('is_active', false)->count() : 0,
            'total_orders'      => Schema::hasTable('ai_tool_orders') ? AiToolOrder::count() : 0,
            'completed_orders'  => Schema::hasTable('ai_tool_orders') ? AiToolOrder::completed()->count() : 0,
            'processing_orders' => Schema::hasTable('ai_tool_orders') ? AiToolOrder::processing()->count() : 0,
            'pending_orders'    => Schema::hasTable('ai_tool_orders') ? AiToolOrder::pending()->count() : 0,
            'under_review'      => Schema::hasTable('ai_tool_orders') ? AiToolOrder::where('status', AiToolOrder::STATUS_UNDER_REVIEW)->count() : 0,
            'total_revenue_ngn' => Schema::hasTable('ai_tool_orders') ? (float) AiToolOrder::completed()->sum('total_paid_ngn') : 0,
            'total_cost_ngn'    => Schema::hasTable('ai_tool_orders') ? (float) AiToolOrder::completed()->sum('provider_cost_ngn') : 0,
            'total_profit_ngn'  => Schema::hasTable('ai_tool_orders') ? (float) AiToolOrder::completed()->sum('profit_ngn') : 0,
        ];

        $recentOrders = Schema::hasTable('ai_tool_orders')
            ? AiToolOrder::with(['user', 'product'])->latest()->limit(10)->get()
            : collect();

        $lastSync = Schema::hasTable('ai_tool_products')
            ? AiToolProduct::max('last_synced_at')
            : null;

        return view('hq.ai-tools.dashboard', compact('aiStats', 'recentOrders', 'lastSync'));
    }

    // ─── Products ──────────────────────────────────────────────────────────────

    public function products(Request $request)
    {
        $this->ensureTablesExist();

        if (!Schema::hasTable('ai_tool_products')) {
            $products = new LengthAwarePaginator([], 0, 50);
            $categories = collect();
            return view('hq.ai-tools.products', compact('products', 'categories'));
        }

        $query = AiToolProduct::with('category');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orWhere('provider_product_id', 'like', '%' . $search . '%');
        }

        if ($status = $request->get('status')) {
            if ($status === 'active')   $query->where('is_active', true);
            if ($status === 'inactive') $query->where('is_active', false);
        }

        $products   = $query->orderBy('name')->paginate(50)->withQueryString();
        $categories = Schema::hasTable('ai_tool_categories') ? AiToolCategory::all() : collect();

        return view('hq.ai-tools.products', compact('products', 'categories'));
    }

    public function updateProduct(Request $request, int $id)
    {
        $this->ensureTablesExist();

        $product = AiToolProduct::findOrFail($id);

        $validated = $request->validate([
            'is_active'                => 'sometimes|boolean',
            'is_featured'              => 'sometimes|boolean',
            'category_id'              => 'sometimes|nullable|integer',
            'description'              => 'sometimes|nullable|string|max:5000',
            'sort_order'               => 'sometimes|integer|min:0',
            'custom_markup_percentage' => 'sometimes|nullable|numeric|min:0|max:9999',
            'custom_markup_fixed'      => 'sometimes|nullable|numeric|min:0',
        ]);

        // Normalise checkbox booleans
        $validated['is_active']   = $request->has('is_active')   ? (bool) $request->is_active   : $product->is_active;
        $validated['is_featured'] = $request->has('is_featured') ? (bool) $request->is_featured : $product->is_featured;

        $product->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Product updated.']);
        }

        return back()->with('success', 'Product updated successfully.');
    }

    // ─── Orders ────────────────────────────────────────────────────────────────

    public function orders(Request $request)
    {
        $this->ensureTablesExist();

        if (!Schema::hasTable('ai_tool_orders')) {
            $orders = new LengthAwarePaginator([], 0, 50);
            return view('hq.ai-tools.orders', compact('orders'));
        }

        $query = AiToolOrder::with(['user', 'product']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhereHas('user', fn($u) => $u->where('email', 'like', '%' . $search . '%')
                      ->orWhere('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%'));
            });
        }

        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders = $query->latest()->paginate(50)->withQueryString();

        return view('hq.ai-tools.orders', compact('orders'));
    }

    public function orderDetail(int $id)
    {
        $this->ensureTablesExist();

        $order = AiToolOrder::with(['user', 'product', 'items'])->findOrFail($id);
        return view('hq.ai-tools.order-detail', compact('order'));
    }

    public function manualFulfill(Request $request, int $id)
    {
        $this->ensureTablesExist();

        if ($request->isMethod('GET')) {
            return redirect()->route('hq.ai-tools.orders.show', $id);
        }

        $request->validate([
            'credentials'  => 'required|string',
            'instructions' => 'nullable|string|max:2000',
        ]);

        try {
            $order = AiToolOrder::findOrFail($id);
            $order->fulfillWithCredentials($request->credentials, $request->instructions);
            return back()->with('success', "Order #{$order->order_number} has been manually fulfilled and credentials delivered.");
        } catch (\Throwable $e) {
            Log::error('AdminAiToolsController@manualFulfill failed', [
                'order_id' => $id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Failed to fulfill order: ' . $e->getMessage())->withInput();
        }
    }

    public function manualRefund(Request $request, int $id)
    {
        $this->ensureTablesExist();

        if ($request->isMethod('GET')) {
            return redirect()->route('hq.ai-tools.orders.show', $id);
        }

        $order = AiToolOrder::findOrFail($id);
        if (!in_array($order->status, [AiToolOrder::STATUS_FAILED, AiToolOrder::STATUS_UNDER_REVIEW], true)) {
            return back()->with('error', 'Only failed or under-review orders can be manually refunded. Completed and processing orders cannot be refunded manually.');
        }
        $reason = $request->input('reason', 'Refunded by administrator');

        if ($order->refundCustomer($reason)) {
            return back()->with('success', "Order #{$order->order_number} has been refunded to the customer's wallet.");
        }

        return back()->with('error', 'Order could not be refunded (may already be refunded).');
    }

    public function syncOrder(Request $request, int $id)
    {
        $this->ensureTablesExist();

        if ($request->isMethod('GET')) {
            return redirect()->route('hq.ai-tools.orders.show', $id);
        }

        $order = AiToolOrder::findOrFail($id);
        $result = $order->livePollStatus(app(AlaricApiService::class));

        if ($result['success'] ?? false) {
            return back()->with('success', 'Provider status check: ' . ($result['message'] ?? 'Status updated.'));
        }

        return back()->with('error', 'Could not sync provider status: ' . ($result['message'] ?? 'Provider error.'));
    }

    // ─── Provider settings ─────────────────────────────────────────────────────

    public function settings()
    {
        $this->ensureTablesExist();

        $apiKey        = (string) Setting::get('alaric_api_key', '');
        $maskedKey     = $apiKey
            ? substr($apiKey, 0, 6) . str_repeat('*', max(0, strlen($apiKey) - 10)) . substr($apiKey, -4)
            : '';
        $exchangeRate  = Setting::get('usdt_to_ngn_rate', Setting::get('usd_to_ngn_rate', 1500));
        $markupPercent = Setting::get('markup_ai_tools', 20);
        $lastSync      = Schema::hasTable('ai_tool_products') ? AiToolProduct::max('last_synced_at') : null;
        $pixelSubKey = (string) Setting::get('pixelsub_api_key', '');
        $maskedPixelSubKey = $pixelSubKey ? substr($pixelSubKey, 0, 4) . str_repeat('*', max(0, strlen($pixelSubKey) - 8)) . substr($pixelSubKey, -4) : '';
        $pixelSubConfigured = !empty($pixelSubKey);
        $apiConfigured = !empty($apiKey);

        return view('hq.ai-tools.settings', compact(
            'maskedKey', 'maskedPixelSubKey', 'pixelSubConfigured', 'exchangeRate', 'markupPercent', 'lastSync', 'apiConfigured'
        ));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'alaric_api_key'   => 'sometimes|nullable|string|max:255',
            'usdt_to_ngn_rate' => 'required|numeric|min:1|max:9999999',
            'markup_ai_tools'  => 'required|numeric|min:0|max:9999',
        ]);

        // Only update API key if explicitly provided (not masked)
        if ($request->filled('alaric_api_key') && !str_contains($request->alaric_api_key, '***')) {
            Setting::set('alaric_api_key', $request->alaric_api_key);
        }

        if ($request->filled('pixelsub_api_key') && !str_contains($request->pixelsub_api_key, '***')) {
            Setting::set('pixelsub_api_key', $request->pixelsub_api_key);
        }

        Setting::set('usdt_to_ngn_rate', $request->usdt_to_ngn_rate);
        Setting::set('markup_ai_tools',  $request->markup_ai_tools);

        return back()->with('success', 'AI TOOLS settings saved successfully.');
    }

    // ─── Provider API actions ──────────────────────────────────────────────────

    public function testConnection()
    {
        /** @var AlaricApiService $service */
        $service = app(AlaricApiService::class);
        $ok      = $service->testConnection();
        $pixelSubOk = app(PixelSubApiService::class)->testConnection();

        return response()->json([
            'success' => $ok || $pixelSubOk,
            'message' => ($ok && $pixelSubOk) ? 'Both API connections are successful.' : (($ok || $pixelSubOk) ? 'At least one API connection is successful.' : 'Both API connections failed. Please check the configured keys.'),
            'connections' => ['alaric' => $ok, 'pixelsub' => $pixelSubOk],
        ]);
    }

    public function checkBalance()
    {
        /** @var AlaricApiService $service */
        $service = app(AlaricApiService::class);
        $result  = $service->checkBalance();
        $pixelSubResult = app(PixelSubApiService::class)->checkBalance();

        return response()->json(['success' => ($result['success'] ?? false) || ($pixelSubResult['success'] ?? false), 'data' => ['alaric' => $result['data'] ?? $result, 'pixelsub' => $pixelSubResult['data'] ?? $pixelSubResult]]);
    }

    public function syncProducts()
    {
        try {
            $this->ensureTablesExist();

            $exitCode = Artisan::call('ai-tools:sync-products', ['--force' => true]);
            $output   = trim(Artisan::output());

            if ($exitCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync issue: ' . ($output ?: 'Command failed'),
                    'output'  => $output,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product synchronization completed. ' . $output,
                'output'  => $output,
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminAiToolsController@syncProducts: failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
