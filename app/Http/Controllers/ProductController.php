<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('low_stock')) {
            $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        }

        $products = $query->orderBy('name')->paginate(15);
        $categories = Category::all();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
        ]);

        $product = Product::create($validated);

        // Log the initial stock as a real movement, not just a silent number
        if ($product->stock_quantity > 0) {
            StockMovement::create([
                'product_id' => $product->id,
                'quantity_change' => $product->stock_quantity,
                'reason' => 'restock',
                'order_id' => null,
                'notes' => 'Initial stock on product creation',
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
        ]);

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists()) {
            return back()->with('error', 'Cannot delete a product with existing order history. Consider archiving instead.');
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function adjustStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity_change' => 'required|integer',
            'reason' => 'required|in:restock,adjustment,return',
            'notes' => 'nullable|string',
        ]);

        $newStock = $product->stock_quantity + $validated['quantity_change'];

        if ($newStock < 0) {
            return back()->with('error', 'Stock adjustment would result in negative stock. Adjustment rejected.');
        }

        StockMovement::create([
            'product_id' => $product->id,
            'quantity_change' => $validated['quantity_change'],
            'reason' => $validated['reason'],
            'order_id' => null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $product->update(['stock_quantity' => $newStock]);

        return back()->with('success', 'Stock adjusted successfully.');
    }
}
