<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LogProduct;
use App\Models\LogCategory;
use App\Models\LogOrder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AdminLogMarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $query = LogProduct::with('category');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('log_category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query
            ->orderByRaw("CASE WHEN type = 'local' THEN 0 ELSE 1 END ASC")
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $categories = LogCategory::all();

        $counts = [
            'all' => LogProduct::count(),
            'local' => LogProduct::where('type', 'local')->count(),
            'api' => LogProduct::where('type', 'api')->count(),
        ];

        return view('hq.logs.index', compact('products', 'categories', 'counts'));
    }

    public function sync(Request $request)
    {
        $apiKey = \App\Models\Setting::get('fadded_api_key') ?: config('services.fadded.key');
        $api = app(\App\Services\FaddedApi::class);

        if (empty($apiKey) && !$api->isConfigured()) {
            return redirect()->route('hq.logs.index')->with('error', 'Fadded API key is not configured. Please set your API key in Settings or .env first.');
        }

        try {
            $exitCode = Artisan::call('fadded:sync');
            $output = trim(Artisan::output());

            if ($exitCode !== 0) {
                return redirect()->route('hq.logs.index')->with('error', $output ?: 'Failed to sync products from Fadded provider.');
            }

            return redirect()->route('hq.logs.index')->with('success', $output ?: 'API Products synchronized successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Fadded sync error: ' . $e->getMessage());
            return redirect()->route('hq.logs.index')->with('error', 'Sync encountered an error: ' . $e->getMessage());
        }
    }

    public function updatePrice(Request $request, $id)
    {
        $request->validate([
            'our_price' => 'required|numeric|min:0'
        ]);

        $product = LogProduct::findOrFail($id);
        $product->our_price = $request->our_price;
        $product->save();

        return back()->with('success', 'Price updated successfully.');
    }

    public function toggleStatus($id)
    {
        $product = LogProduct::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();

        return back()->with('success', 'Product status updated.');
    }

    public function orders(Request $request)
    {
        $query = LogOrder::with('user', 'product')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('external_order_id', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(20);
        return view('hq.logs.orders', compact('orders'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        LogCategory::create(['name' => $request->name, 'is_active' => true]);
        return back()->with('success', 'Local category created successfully.');
    }

    public function deleteCategory($id)
    {
        $cat = LogCategory::findOrFail($id);
        $cat->delete();
        return back()->with('success', 'Category deleted.');
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'log_category_id' => 'nullable|exists:log_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'our_price' => 'required|numeric|min:0'
        ]);

        $iconPath = null;
        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            $path = $file->store('log_icons', 'public');
            $iconPath = '/storage/' . $path;
        }

        LogProduct::create([
            'type' => 'local',
            'log_category_id' => $request->log_category_id,
            'name' => $request->name,
            'description' => $request->description,
            'icon' => $iconPath,
            'base_api_price' => 0,
            'unit_price' => $request->our_price,
            'our_price' => $request->our_price,
            'is_active' => true,
            'in_stock' => 0,
        ]);

        return redirect()->route('hq.logs.index')->with('success', 'Local product created successfully.');
    }

    public function showProductRedirect($id)
    {
        return redirect()->route('hq.logs.index');
    }

    public function updateProduct(Request $request, $id)
    {
        try {
            // Auto-heal missing icon column if migration was not yet run on live database
            if (Schema::hasTable('log_products') && !Schema::hasColumn('log_products', 'icon')) {
                try {
                    Schema::table('log_products', function ($table) {
                        $table->string('icon')->nullable()->after('description');
                    });
                } catch (\Throwable $ignored) {}
            }

            $request->validate([
                'log_category_id' => 'nullable',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'our_price' => 'required|numeric|min:0'
            ]);

            $product = LogProduct::findOrFail($id);

            $hasIconColumn = Schema::hasColumn('log_products', 'icon');
            $iconPath = $hasIconColumn ? $product->icon : null;

            if ($request->boolean('remove_icon')) {
                if ($iconPath && str_starts_with($iconPath, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $iconPath));
                }
                $iconPath = null;
            }

            if ($request->hasFile('icon')) {
                if ($iconPath && str_starts_with($iconPath, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $iconPath));
                }
                $file = $request->file('icon');
                $path = $file->store('log_icons', 'public');
                $iconPath = '/storage/' . $path;
            }

            $updateData = [
                'name' => $request->name,
                'description' => $request->description,
                'unit_price' => $request->our_price,
                'our_price' => $request->our_price,
            ];

            // Safely resolve category ID
            if ($request->filled('log_category_id')) {
                if (LogCategory::where('id', $request->log_category_id)->exists()) {
                    $updateData['log_category_id'] = $request->log_category_id;
                } else {
                    $updateData['log_category_id'] = null;
                }
            } else {
                $updateData['log_category_id'] = null;
            }

            if ($hasIconColumn) {
                $updateData['icon'] = $iconPath;
            }

            $product->update($updateData);

            return redirect()->route('hq.logs.index')->with('success', "Product #{$product->id} ('{$product->name}') updated successfully.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::warning("Update Log Product Validation Error: " . json_encode($e->validator->errors()->all()));
            return redirect()->route('hq.logs.index')->withErrors($e->validator)->withInput()->with('error', 'Validation error: ' . implode(', ', $e->validator->errors()->all()));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Update Log Product Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return redirect()->route('hq.logs.index')->with('error', 'Could not update product: ' . $e->getMessage());
        }
    }

    public function deleteProduct($id)
    {
        try {
            $prod = LogProduct::findOrFail($id);

            // Nullify order references so foreign key constraints don't block deletion
            \App\Models\LogOrder::where('log_product_id', $prod->id)->update(['log_product_id' => null]);

            if (Schema::hasColumn('log_products', 'icon') && $prod->icon && str_starts_with($prod->icon, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $prod->icon));
            }

            foreach ($prod->items as $item) {
                if ($item->file_path) {
                    $cleanPath = str_replace('/storage/', '', $item->file_path);
                    Storage::disk('public')->delete($cleanPath);
                }
                $item->delete();
            }
            $prod->delete();
            return redirect()->route('hq.logs.index')->with('success', 'Product and its inventory deleted successfully.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Delete Log Product Error: " . $e->getMessage());
            return redirect()->route('hq.logs.index')->with('error', 'Could not delete product: ' . $e->getMessage());
        }
    }

    public function stock($id)
    {
        $product = LogProduct::where('type', 'local')->findOrFail($id);
        $items = \App\Models\LogItem::where('log_product_id', $product->id)->latest()->paginate(50);
        return view('hq.logs.stock', compact('product', 'items'));
    }

    public function addStock(Request $request, $id)
    {
        $product = LogProduct::findOrFail($id);

        $createdCount = 0;

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. If multiple accounts array submitted (from continuous '+' rows)
            if ($request->has('accounts') && is_array($request->accounts)) {
                foreach ($request->accounts as $index => $accData) {
                    $content = trim($accData['content'] ?? '');
                    if (empty($content)) {
                        continue;
                    }

                    $filePath = null;
                    $fileName = null;

                    if ($request->hasFile("accounts.{$index}.attachment")) {
                        $file = $request->file("accounts.{$index}.attachment");
                        $fileName = $file->getClientOriginalName();
                        $path = $file->store('log_attachments', 'public');
                        $filePath = '/storage/' . $path;
                    }

                    \App\Models\LogItem::create([
                        'log_product_id' => $product->id,
                        'content' => $content,
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                        'is_sold' => false,
                    ]);

                    $createdCount++;
                }
            } elseif ($request->filled('content')) {
                // 2. Single account submission
                $filePath = null;
                $fileName = null;

                if ($request->hasFile('attachment')) {
                    $file = $request->file('attachment');
                    $fileName = $file->getClientOriginalName();
                    $path = $file->store('log_attachments', 'public');
                    $filePath = '/storage/' . $path;
                }

                \App\Models\LogItem::create([
                    'log_product_id' => $product->id,
                    'content' => trim($request->content),
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'is_sold' => false,
                ]);

                $createdCount++;
            }

            if ($createdCount === 0) {
                \Illuminate\Support\Facades\DB::rollBack();
                return back()->with('error', 'Please provide account details for at least one account.');
            }

            // Recalculate total available stock
            $product->in_stock = \App\Models\LogItem::where('log_product_id', $product->id)->where('is_sold', false)->count();
            $product->save();

            \Illuminate\Support\Facades\DB::commit();

            $msg = $createdCount === 1 ? '1 account successfully added to stock.' : "{$createdCount} accounts successfully added to stock!";
            return back()->with('success', $msg);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Add Stock Error: " . $e->getMessage());
            return back()->with('error', 'Failed to add accounts to stock: ' . $e->getMessage());
        }
    }

    public function updateStockItem(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
            'attachment' => 'nullable|file|max:25600|mimes:jpeg,png,jpg,pdf,txt,zip,doc,docx',
            'remove_attachment' => 'nullable'
        ]);

        $item = \App\Models\LogItem::findOrFail($id);

        if ($request->has('remove_attachment') && $item->file_path) {
            $cleanPath = str_replace('/storage/', '', $item->file_path);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($cleanPath);
            $item->file_path = null;
            $item->file_name = null;
        }

        if ($request->hasFile('attachment')) {
            if ($item->file_path) {
                $cleanPath = str_replace('/storage/', '', $item->file_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($cleanPath);
            }
            $file = $request->file('attachment');
            $item->file_name = $file->getClientOriginalName();
            $path = $file->store('log_attachments', 'public');
            $item->file_path = '/storage/' . $path;
        }

        $item->content = trim($request->content);
        $item->save();

        return back()->with('success', 'Account credentials updated successfully.');
    }

    public function deleteStockItem($id)
    {
        $item = \App\Models\LogItem::findOrFail($id);
        $product = $item->product;

        if ($item->file_path) {
            $cleanPath = str_replace('/storage/', '', $item->file_path);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($cleanPath);
        }

        $item->delete();

        if ($product) {
            $product->in_stock = \App\Models\LogItem::where('log_product_id', $product->id)->where('is_sold', false)->count();
            $product->save();
        }

        return back()->with('success', 'Account item removed from inventory.');
    }
}
