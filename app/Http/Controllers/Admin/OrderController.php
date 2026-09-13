<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    /**
     * List all orders across vendors, filterable by status and payment method.
     */
    public function index(Request $request)
    {
        $query = Order::with(['vendor', 'payment'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => ['pending', 'confirmed', 'cancelled'],
            'paymentMethods' => ['mpesa', 'bank_transfer', 'credit'],
        ]);
    }

    /**
     * Full order detail — matches the printable receipt styling.
     */
    public function show(Order $order)
    {
        $order->load(['vendor', 'items.product', 'payment']);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Confirm a pending bank transfer payment.
     * Vendor already supplied the bank_reference at checkout — this just verifies it.
     */
    public function confirmPayment(Order $order)
    {
        $payment = $order->payment;

        if (! $payment) {
            return back()->with('error', 'No payment record found for this order.');
        }

        if ($payment->status === 'confirmed') {
            return back()->with('error', 'This payment is already confirmed.');
        }

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Cannot confirm payment on a cancelled order.');
        }

        DB::transaction(function () use ($order, $payment) {
            $payment->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            $order->update(['status' => 'confirmed']);
        });

        Mail::to($order->vendor->user->email)->send(new \App\Mail\PaymentConfirmed($order));

        return back()->with('success', "Payment confirmed for order {$order->order_number}.");
    }

    /**
     * Cancel an order: reverse stock, reverse loyalty points earned,
     * and reverse any credit extended — all inside a locked transaction
     * so the audit trail (stock_movements / loyalty_transactions) stays correct.
     */
    public function cancel(Order $order)
    {
        if ($order->status === 'cancelled') {
            return back()->with('error', 'This order is already cancelled.');
        }

        DB::transaction(function () use ($order) {
            // Lock the order and its vendor row to prevent concurrent modification
            $order = Order::where('id', $order->id)->lockForUpdate()->first();
            $vendor = $order->vendor()->lockForUpdate()->first();

            // 1. Reverse stock for every line item
            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                $product->increment('stock_quantity', $item->quantity);

                StockMovement::create([
                    'product_id' => $product->id,
                    'quantity_change' => $item->quantity, // positive = returned to stock
                    'reason' => 'return',
                    'order_id' => $order->id,
                    'notes' => "Reversed on cancellation of order {$order->order_number}",
                ]);
            }

            // 2. Reverse loyalty points earned on this order (if any)
            $pointsEarned = LoyaltyTransaction::where('order_id', $order->id)
                ->where('reason', 'order_earned')
                ->sum('points_change');

            if ($pointsEarned > 0) {
                $vendor->decrement('loyalty_points', $pointsEarned);

                LoyaltyTransaction::create([
                    'vendor_id' => $vendor->id,
                    'points_change' => -$pointsEarned,
                    'reason' => 'admin_adjustment',
                    'order_id' => $order->id,
                ]);
            }

            // 3. Reverse credit extended, if this was a credit-terms order
            if ($order->payment_method === 'credit') {
                $vendor->decrement('outstanding_balance', $order->total_amount);
            }

            // 4. Mark payment as failed (if one exists and isn't already confirmed)
            if ($order->payment && $order->payment->status !== 'confirmed') {
                $order->payment->update(['status' => 'failed']);
            }

            // 5. Flip the order itself
            $order->update(['status' => 'cancelled']);
        });

        Mail::to($order->vendor->user->email)->send(new \App\Mail\OrderCancelled($order));
        return redirect()
            ->route('admin.orders.index')
            ->with('success', "Order {$order->order_number} cancelled and stock restored.");
    }
}
