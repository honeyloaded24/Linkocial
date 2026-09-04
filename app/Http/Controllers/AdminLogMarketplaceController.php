<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LogProduct;
use App\Models\LogCategory;
use App\Models\LogOrder;
use Illuminate\Support\Facades\Artisan;

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

    public function sync()
    {
        Artisan::call('fadded:sync');
        return back()->with('success', 'Sync completed: ' . Artisan::output());
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
            'log_category_id' => 'required|exists:log_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'our_price' => 'required|numeric|min:0'
        ]);

        LogProduct::create([
            'type' => 'local',
            'log_category_id' => $request->log_category_id,
            'name' => $request->name,
            'description' => $request->description,
            'base_api_price' => 0,
            'unit_price' => $request->our_price,
            'our_price' => $request->our_price,
            'is_active' => true,
            'in_stock' => 0,
        ]);

        return back()->with('success', 'Local product created successfully.');
    }

    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'log_category_id' => 'required|exists:log_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'our_price' => 'required|numeric|min:0'
        ]);

        $product = LogProduct::where('type', 'local')->findOrFail($id);
        $product->update([
            'log_category_id' => $request->log_category_id,
            'name' => $request->name,
            'description' => $request->description,
            'unit_price' => $request->our_price,
            'our_price' => $request->our_price,
        ]);

        return back()->with('success', 'Local product updated successfully.');
    }

    public function deleteProduct($id)
    {
        $prod = LogProduct::findOrFail($id);
        foreach ($prod->items as $item) {
            if ($item->file_path) {
                $cleanPath = str_replace('/storage/', '', $item->file_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($cleanPath);
            }
            $item->delete();
        }
        $prod->delete();
        return back()->with('success', 'Product and its inventory deleted.');
    }

    public function stock($id)
    {
        $product = LogProduct::where('type', 'local')->findOrFail($id);
        $items = \App\Models\LogItem::where('log_product_id', $product->id)->latest()->paginate(50);
        return view('hq.logs.stock', compact('product', 'items'));
    }

    public function addStock(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
            'attachment' => 'nullable|file|max:25600|mimes:jpeg,png,jpg,pdf,txt,zip,doc,docx'
        ]);
        
        $product = LogProduct::where('type', 'local')->findOrFail($id);
        
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
        
        $product->in_stock = \App\Models\LogItem::where('log_product_id', $product->id)->where('is_sold', false)->count();
        $product->save();

        return back()->with('success', "Account successfully added to stock.");
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
