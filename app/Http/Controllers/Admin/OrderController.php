<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderCancelled;
use App\Mail\PaymentConfirmed;
use App\Models\Order;
use App\Services\OrderCancellationService;
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

        Mail::to($order->vendor->user->email)->send(new PaymentConfirmed($order));

        return back()->with('success', "Payment confirmed for order {$order->order_number}.");
    }

    /**
     * Cancel an order. The reversal of stock, loyalty points and credit is shared with the
     * M-Pesa failure path, so it lives in OrderCancellationService.
     */
    public function cancel(Order $order, OrderCancellationService $cancellations)
    {
        if (! $cancellations->cancel($order, 'cancelled by admin')) {
            return back()->with('error', 'This order is already cancelled.');
        }

        $order->refresh();

        Mail::to($order->vendor->user->email)->send(new OrderCancelled($order));

        return redirect()
            ->route('admin.orders.index')
            ->with('success', "Order {$order->order_number} cancelled and stock restored.");
    }
}
