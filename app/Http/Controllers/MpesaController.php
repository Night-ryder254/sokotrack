<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MpesaController extends Controller
{
    /**
     * Safaricom calls this after the customer responds to STK push
     * (or after their attempt times out, as in sandbox testing).
     * ResultCode 0 = success (real receipt attached), anything else = failure.
     */
    public function stkCallback(Request $request)
    {
        Log::info('M-Pesa STK callback received', $request->all());

        $callback = $request->input('Body.stkCallback');

        if (! $callback) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
        $resultCode = $callback['ResultCode'] ?? null;

        $payment = Payment::where('checkout_request_id', $checkoutRequestId)->first();

        if (! $payment) {
            Log::warning('M-Pesa callback for unknown checkout_request_id', ['checkout_request_id' => $checkoutRequestId]);
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        if ($resultCode === 0) {
            $metadata = collect($callback['CallbackMetadata']['Item'] ?? []);
            $receiptNumber = $metadata->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;

            $payment->update([
                'status' => 'confirmed',
                'mpesa_receipt_number' => $receiptNumber,
                'result_desc' => $callback['ResultDesc'] ?? 'Success',
                'confirmed_at' => now(),
            ]);

            $order = $payment->order;
            $order->update(['status' => 'confirmed']);

            $order->load('vendor.user', 'items.product');

            Mail::to($order->vendor->user->email)->send(new \App\Mail\OrderReceipt($order));

            $staffEmails = \App\Models\User::whereIn('role', ['admin', 'warehouse_staff'])->pluck('email');
            if ($staffEmails->isNotEmpty()) {
                Mail::to($staffEmails)->send(new \App\Mail\NewOrderAlert($order));
            }
        } else {
            $payment->update([
                'status' => 'failed',
                'result_desc' => $callback['ResultDesc'] ?? 'Payment failed',
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Polled by the checkout pending page in the browser to check
     * whether the callback has landed yet.
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
}
