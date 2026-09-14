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
use Illuminate\Support\Facades\Mail;

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

        $products = Product::whereIn('id', array_keys($cart))->get();
        $orderTotal = $products->sum(fn ($p) => $p->price * $cart[$p->id]);

        if ($validated['payment_method'] === 'credit' && $orderTotal > $vendor->availableCredit()) {
            return back()->with('error', 'This order exceeds your available credit limit. Available: KES ' . number_format($vendor->availableCredit(), 2));
        }

        // Products that newly cross into low-stock territory during this checkout.
        // Collected during the transaction, emailed only after it commits successfully —
        // sending inside the transaction risks alerting on stock changes that later roll back.
        $productsThatCrossedThreshold = collect();

        try {
            $order = DB::transaction(function () use ($cart, $vendor, $validated, $orderTotal, &$productsThatCrossedThreshold) {

                $order = Order::create([
                    'vendor_id' => $vendor->id,
                    'order_number' => 'ORD-' . strtoupper(uniqid()),
                    'total_amount' => 0, // updated below once items are confirmed
                    'payment_method' => $validated['payment_method'],
                    'status' => 'confirmed', // overwritten below for mpesa/bank_transfer, stays for credit
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

                    $previousStock = $product->stock_quantity;
                    $product->decrement('stock_quantity', $quantity);
                    $product->refresh();

                    $justCrossedThreshold = $previousStock > $product->low_stock_threshold
                        && $product->stock_quantity <= $product->low_stock_threshold;

                    if ($justCrossedThreshold) {
                        $productsThatCrossedThreshold->push($product);
                    }

                    $confirmedTotal += $product->price * $quantity;
                }

                $order->update(['total_amount' => $confirmedTotal]);

                if ($validated['payment_method'] === 'credit') {
                    $vendor->increment('outstanding_balance', $confirmedTotal);
                } elseif ($validated['payment_method'] === 'mpesa') {
                    // Real M-Pesa: payment starts pending, confirmed later by the STK callback.
                    // The actual push is triggered AFTER this transaction commits (see below) —
                    // never call an external API from inside a DB transaction.
                    Payment::create([
                        'order_id' => $order->id,
                        'method' => 'mpesa',
                        'amount' => $confirmedTotal,
                        'status' => 'pending',
                        'mpesa_receipt_number' => null,
                        'bank_reference' => null,
                        'confirmed_at' => null,
                    ]);
                    $order->update(['status' => 'pending']);
                } else {
                    // bank_transfer: vendor supplies reference manually, admin confirms later
                    Payment::create([
                        'order_id' => $order->id,
                        'method' => $validated['payment_method'],
                        'amount' => $confirmedTotal,
                        'status' => 'pending',
                        'mpesa_receipt_number' => null,
                        'bank_reference' => fake()->bothify('REF-######'),
                        'confirmed_at' => null,
                    ]);
                    $order->update(['status' => 'pending']);
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

            // Transaction committed successfully — now safe to send low-stock alerts
            foreach ($productsThatCrossedThreshold as $product) {
                app(ProductController::class)->sendLowStockAlert($product);
            }

            // For M-Pesa: trigger the real STK push now that the order/payment
            // rows exist and the DB transaction has safely committed.
            if ($validated['payment_method'] === 'mpesa') {
                $darajaService = new \App\Services\DarajaService();
                $pushResult = $darajaService->stkPush(
                    $vendor->phone,
                    $order->total_amount,
                    $order->order_number
                );

                if ($pushResult && ($pushResult['ResponseCode'] ?? null) === '0') {
                    $order->payment->update([
                        'checkout_request_id' => $pushResult['CheckoutRequestID'],
                        'merchant_request_id' => $pushResult['MerchantRequestID'],
                    ]);

                    return redirect()->route('checkout.pending', $order)
                        ->with('success', 'Check your phone to complete payment.');
                }

                // Push failed to even send — mark payment failed, let vendor retry
                $order->payment->update(['status' => 'failed', 'result_desc' => 'Failed to initiate STK push']);

                return redirect()->route('checkout.pending', $order)
                    ->with('error', 'Could not start M-Pesa payment. Please try again.');
            }

            // Non-mpesa paths (bank_transfer, credit) still show the immediate success page
            Mail::to($vendor->user->email)->send(new \App\Mail\OrderReceipt($order));

            $staffEmails = \App\Models\User::whereIn('role', ['admin', 'warehouse_staff'])->pluck('email');
            if ($staffEmails->isNotEmpty()) {
                Mail::to($staffEmails)->send(new \App\Mail\NewOrderAlert($order));
            }

            return redirect()->route('checkout.success', $order)->with('success', 'Order placed successfully!');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function pending(Order $order)
    {
        $order->load('payment');

        return view('checkout.pending', compact('order'));
    }

    public function success(Order $order)
    {
        $order->load('items.product', 'payment');
        return view('checkout.success', compact('order'));
    }
}
