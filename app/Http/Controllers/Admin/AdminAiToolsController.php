<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiToolCategory;
use App\Models\AiToolOrder;
use App\Models\AiToolProduct;
use App\Models\Setting;
use App\Services\AlaricApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminAiToolsController extends Controller
{
    public function __construct()
    {
    }

    // ─── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard()
    {
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
        $order = AiToolOrder::with(['user', 'product', 'items'])->findOrFail($id);
        return view('hq.ai-tools.order-detail', compact('order'));
    }

    // ─── Provider settings ─────────────────────────────────────────────────────

    public function settings()
    {
        $apiKey        = (string) Setting::get('alaric_api_key', '');
        $maskedKey     = $apiKey
            ? substr($apiKey, 0, 6) . str_repeat('*', max(0, strlen($apiKey) - 10)) . substr($apiKey, -4)
            : '';
        $exchangeRate  = Setting::get('usdt_to_ngn_rate', Setting::get('usd_to_ngn_rate', 1500));
        $markupPercent = Setting::get('markup_ai_tools', 20);
        $lastSync      = Schema::hasTable('ai_tool_products') ? AiToolProduct::max('last_synced_at') : null;
        $apiConfigured = !empty($apiKey);

        return view('hq.ai-tools.settings', compact(
            'maskedKey', 'exchangeRate', 'markupPercent', 'lastSync', 'apiConfigured'
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

        return response()->json([
            'success' => $ok,
            'message' => $ok
                ? 'Connection successful. API key is valid.'
                : 'Connection failed. Please check your API key.',
        ]);
    }

    public function checkBalance()
    {
        /** @var AlaricApiService $service */
        $service = app(AlaricApiService::class);
        $result  = $service->checkBalance();

        return response()->json($result);
    }

    public function syncProducts()
    {
        try {
            Artisan::call('ai-tools:sync-products', ['--force' => true]);
            $output = trim(Artisan::output());

            return response()->json([
                'success' => true,
                'message' => 'Product synchronization completed.',
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
