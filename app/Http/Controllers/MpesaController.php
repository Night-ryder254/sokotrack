<?php

namespace App\Http\Controllers;

use App\Mail\NewOrderAlert;
use App\Mail\OrderReceipt;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderCancellationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MpesaController extends Controller
{
    /**
     * Safaricom calls this after the customer responds to the STK push (or the prompt times out).
     * ResultCode 0 means paid; anything else means the payment did not happen.
     *
     * Safaricom can deliver the same callback more than once, so the payment row is locked and
     * only acted on while it is still "pending". A repeat is acknowledged and ignored.
     */
    public function stkCallback(Request $request)
    {
        Log::info('M-Pesa STK callback received', $request->all());

        $callback = $request->input('Body.stkCallback');

        if (! $callback) {
            return $this->acknowledge();
        }

        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
        $resultCode = $callback['ResultCode'] ?? null;

        $payment = Payment::where('checkout_request_id', $checkoutRequestId)->first();

        if (! $payment) {
            Log::warning('M-Pesa callback for unknown checkout_request_id', ['checkout_request_id' => $checkoutRequestId]);

            return $this->acknowledge();
        }

        $receipt = collect($callback['CallbackMetadata']['Item'] ?? [])
            ->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;

        $outcome = DB::transaction(function () use ($payment, $callback, $resultCode, $receipt) {
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($payment->status !== 'pending') {
                // A success arriving for a payment we already closed as failed (the order was
                // cancelled or released first) means the customer was charged for nothing.
                return ($resultCode === 0 && $payment->status === 'failed') ? 'paid_after_failure' : 'duplicate';
            }

            if ($resultCode === 0) {
                $payment->update([
                    'status' => 'confirmed',
                    'mpesa_receipt_number' => $receipt,
                    'result_desc' => $callback['ResultDesc'] ?? 'Success',
                    'confirmed_at' => now(),
                ]);

                $payment->order->update(['status' => 'confirmed']);

                return 'confirmed';
            }

            // Cancelled, wrong PIN, insufficient funds, timed out: give the stock and points back.
            $payment->update([
                'status' => 'failed',
                'result_desc' => $callback['ResultDesc'] ?? 'Payment failed',
            ]);

            app(OrderCancellationService::class)->cancel($payment->order, 'M-Pesa payment failed');

            return 'failed';
        });

        if ($outcome === 'paid_after_failure') {
            Log::error('M-Pesa payment received for an order that was already cancelled. A refund is needed.', [
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
                'mpesa_receipt' => $receipt,
                'checkout_request_id' => $checkoutRequestId,
            ]);
        }

        if ($outcome === 'confirmed') {
            $this->sendConfirmationEmails($payment->order_id);
        }

        return $this->acknowledge();
    }

    /**
     * Polled by the checkout pending page in the browser to check whether the callback has landed.
     */
    public function checkStatus(Request $request)
    {
        $payment = Payment::where('checkout_request_id', $request->query('checkout_request_id'))->first();

        if (! $payment) {
            return response()->json(['status' => 'pending']);
        }

        return response()->json([
            'status' => $payment->status,
            'result_desc' => $payment->result_desc,
            'order_id' => $payment->order_id,
        ]);
    }

    /**
     * Receipt for the vendor and an alert for staff, sent only once payment is really confirmed.
     * A mail problem must not turn the callback into an error, or Safaricom would keep retrying.
     */
    private function sendConfirmationEmails(int $orderId): void
    {
        try {
            $order = Order::with('vendor.user', 'items.product')->find($orderId);

            Mail::to($order->vendor->user->email)->send(new OrderReceipt($order));

            $staffEmails = User::whereIn('role', ['admin', 'warehouse_staff'])->pluck('email');

            if ($staffEmails->isNotEmpty()) {
                Mail::to($staffEmails)->send(new NewOrderAlert($order));
            }
        } catch (\Throwable $e) {
            Log::error('M-Pesa confirmation emails failed', ['order_id' => $orderId, 'error' => $e->getMessage()]);
        }
    }

    private function acknowledge()
    {
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
