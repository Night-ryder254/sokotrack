<?php

namespace App\Http\Controllers;

use App\Mail\LowStockAlert;
use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($validated['image']);

        $product = Product::create($validated);

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
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                \Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($validated['image']);

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

        $previousStock = $product->stock_quantity;
        $newStock = $previousStock + $validated['quantity_change'];

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

        // Fire a real-time alert only when this adjustment causes the product
        // to newly cross into low-stock territory — not on every sale while already low.
        $justCrossedThreshold = $previousStock > $product->low_stock_threshold
            && $newStock <= $product->low_stock_threshold;

        if ($justCrossedThreshold) {
            $this->sendLowStockAlert($product);
        }

        return back()->with('success', 'Stock adjusted successfully.');
    }

    /**
     * Send a real-time low-stock email to admin/staff.
     * Shared by adjustStock() here and CheckoutController's stock decrement,
     * so the "just crossed threshold" logic and recipient list stay in one place.
     */
    public function sendLowStockAlert(Product $product): void
    {
        $recipients = User::whereIn('role', ['admin', 'warehouse_staff'])->pluck('email');

        if ($recipients->isNotEmpty()) {
            Mail::to($recipients)->send(new LowStockAlert(collect([$product]), 'realtime'));
        }
    }
}
