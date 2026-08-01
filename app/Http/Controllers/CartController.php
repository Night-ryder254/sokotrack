<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session('cart', []);
        $products = Product::whereIn('id', array_keys($cart))->get();

        $items = $products->map(function ($product) use ($cart) {
            return [
                'product' => $product,
                'quantity' => $cart[$product->id],
                'subtotal' => $product->price * $cart[$product->id],
            ];
        });

        $total = $items->sum('subtotal');

        return view('cart.index', compact('items', 'total'));
    }

    public function add(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session('cart', []);
        $cart[$product->id] = ($cart[$product->id] ?? 0) + $validated['quantity'];
        session(['cart' => $cart]);

        if ($request->wantsJson()) {
            $cartCount = collect($cart)->sum();
            $cartProducts = Product::whereIn('id', array_keys($cart))->get();
            $cartTotal = $cartProducts->sum(fn ($p) => $p->price * $cart[$p->id]);

            return response()->json([
                'success' => true,
                'cartCount' => $cartCount,
                'cartTotal' => $cartTotal,
            ]);
        }

        return back()->with('success', "{$product->name} added to cart.");
    }
    public function remove(Product $product)
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);

        return back()->with('success', 'Item removed from cart.');
    }
}
