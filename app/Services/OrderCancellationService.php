<?php

namespace App\Services;

use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * The one place an order is cancelled. It is used by the admin "Cancel Order" button, by the
 * M-Pesa callback when a payment fails, by the scheduled release of unanswered M-Pesa payments,
 * and by checkout when the M-Pesa prompt could not even be sent, so they all reverse exactly
 * the same things.
 */
class OrderCancellationService
{
    /**
     * Cancel an order and undo everything checkout did to it, in one locked transaction:
     * stock goes back (a `return` movement per line), loyalty points earned on the order are
     * reversed, credit extended is released, and a payment that never completed is marked failed.
     * A confirmed payment is left alone, since money has already moved.
     *
     * Safe to call twice: it changes nothing and returns false if the order is already cancelled.
     */
    public function cancel(Order $order, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($order, $reason) {
            // Lock the order first, so an admin click, a payment callback and the scheduled
            // release can never all cancel the same order at once.
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            if (! $order || $order->status === 'cancelled') {
                return false;
            }

            $vendor = $order->vendor()->lockForUpdate()->first();
            $note = "Reversed on cancellation of order {$order->order_number}".($reason ? " ({$reason})" : '');

            // 1. Stock goes back for every line
            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                $product->increment('stock_quantity', $item->quantity);

                StockMovement::create([
                    'product_id' => $product->id,
                    'quantity_change' => $item->quantity, // positive = returned to stock
                    'reason' => 'return',
                    'order_id' => $order->id,
                    'notes' => $note,
                ]);
            }

            // 2. Loyalty points earned on this order. Never take the balance below zero:
            //    an admin may have adjusted the vendor's points since the order was placed.
            $earned = (int) LoyaltyTransaction::where('order_id', $order->id)
                ->where('reason', 'order_earned')
                ->sum('points_change');

            $reversal = min($earned, (int) $vendor->loyalty_points);

            if ($reversal > 0) {
                $vendor->decrement('loyalty_points', $reversal);

                LoyaltyTransaction::create([
                    'vendor_id' => $vendor->id,
                    'points_change' => -$reversal,
                    'reason' => 'admin_adjustment',
                    'order_id' => $order->id,
                ]);
            }

            // 3. Credit extended, if this was a credit-terms order
            if ($order->payment_method === 'credit') {
                $vendor->decrement('outstanding_balance', $order->total_amount);
            }

            // 4. A payment that never completed is marked failed
            $payment = $order->payment;

            if ($payment && $payment->status !== 'confirmed') {
                $payment->update(['status' => 'failed']);
            }

            // 5. The order itself
            $order->update(['status' => 'cancelled']);

            return true;
        });
    }
}
