<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\Payment;
use App\Models\LoyaltyTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $products = Product::whereIn('id', array_keys($cart))->get();
        $items = $products->map(fn ($product) => [
            'product' => $product,
            'quantity' => $cart[$product->id],
            'subtotal' => $product->price * $cart[$product->id],
        ]);

        $total = $items->sum('subtotal');
        $vendor = $request->user()->vendor;

        return view('checkout.index', compact('items', 'total', 'vendor'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:mpesa,bank_transfer,credit',
        ]);

        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $vendor = $request->user()->vendor;

        // Credit orders must not exceed the vendor's available credit
        $products = Product::whereIn('id', array_keys($cart))->get();
        $orderTotal = $products->sum(fn ($p) => $p->price * $cart[$p->id]);

        if ($validated['payment_method'] === 'credit' && $orderTotal > $vendor->availableCredit()) {
            return back()->with('error', 'This order exceeds your available credit limit. Available: KES ' . number_format($vendor->availableCredit(), 2));
        }

        try {
            $order = DB::transaction(function () use ($cart, $vendor, $validated, $orderTotal) {

                $order = Order::create([
                    'vendor_id' => $vendor->id,
                    'order_number' => 'ORD-' . strtoupper(uniqid()),
                    'total_amount' => 0, // updated below once items are confirmed
                    'payment_method' => $validated['payment_method'],
                    'status' => 'confirmed',
                ]);

                $confirmedTotal = 0;

                foreach ($cart as $productId => $quantity) {
                    // Lock the row so no other checkout can read/modify this product's
                    // stock until this transaction commits — this is what prevents overselling.
                    $product = Product::where('id', $productId)->lockForUpdate()->first();

                    if (!$product || $product->stock_quantity < $quantity) {
                        throw new \Exception("Insufficient stock for {$product?->name}. Only {$product?->stock_quantity} left.");
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->price,
                    ]);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'quantity_change' => -$quantity,
                        'reason' => 'sale',
                        'order_id' => $order->id,
                        'notes' => null,
                    ]);

                    $product->decrement('stock_quantity', $quantity);

                    $confirmedTotal += $product->price * $quantity;
                }

                $order->update(['total_amount' => $confirmedTotal]);

                if ($validated['payment_method'] === 'credit') {
                    $vendor->increment('outstanding_balance', $confirmedTotal);
                } else {
                    Payment::create([
                        'order_id' => $order->id,
                        'method' => $validated['payment_method'],
                        'amount' => $confirmedTotal,
                        'status' => 'confirmed',
                        'mpesa_receipt_number' => $validated['payment_method'] === 'mpesa' ? strtoupper(fake()->bothify('??#########')) : null,
                        'bank_reference' => $validated['payment_method'] === 'bank_transfer' ? fake()->bothify('REF-######') : null,
                        'confirmed_at' => now(),
                    ]);
                }

                $pointsEarned = intdiv((int) $confirmedTotal, 100);

                LoyaltyTransaction::create([
                    'vendor_id' => $vendor->id,
                    'points_change' => $pointsEarned,
                    'reason' => 'order_earned',
                    'order_id' => $order->id,
                ]);

                $vendor->increment('loyalty_points', $pointsEarned);

                return $order;
            });

            session()->forget('cart');

            return redirect()->route('checkout.success', $order)->with('success', 'Order placed successfully!');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function success(Order $order)
    {
        $order->load('items.product', 'payment');
        return view('checkout.success', compact('order'));
    }
}