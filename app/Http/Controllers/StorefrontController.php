<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->where('stock_quantity', '>', 0);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->orderBy('name')->paginate(12);
        $categories = Category::withCount('products')->get();
        $vendor = $request->user()->vendor()->with('tier')->first();

        // Calculate real cart total, not just item count
        $cart = session('cart', []);
        $cartCount = collect($cart)->sum();
        $cartTotal = 0;
        if (!empty($cart)) {
            $cartProducts = Product::whereIn('id', array_keys($cart))->get();
            $cartTotal = $cartProducts->sum(fn ($p) => $p->price * $cart[$p->id]);
        }

        return view('storefront.index', compact('products', 'categories', 'vendor', 'cartCount', 'cartTotal'));
    }

    public function show(Product $product)
    {
        if ($product->stock_quantity <= 0) {
            abort(404);
        }

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('stock_quantity', '>', 0)
            ->take(4)
            ->get();

        return view('storefront.show', compact('product', 'relatedProducts'));
    }
}
