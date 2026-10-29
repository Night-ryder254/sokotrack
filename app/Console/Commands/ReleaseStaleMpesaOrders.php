<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\OrderCancellationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseStaleMpesaOrders extends Command
{
    protected $signature = 'orders:release-stale-mpesa {--minutes=10 : How long an M-Pesa payment may go unanswered}';

    protected $description = 'Cancel M-Pesa orders whose payment never got a response, releasing their stock and loyalty points';

    /**
     * Safaricom normally answers within a couple of minutes, but a callback can be lost.
     * Without this, such an order would hold its stock and points forever.
     */
    public function handle(OrderCancellationService $cancellations): int
    {
        $cutoff = now()->subMinutes(max(1, (int) $this->option('minutes')));
        $released = 0;

        $paymentIds = Payment::where('method', 'mpesa')
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->pluck('id');

        foreach ($paymentIds as $paymentId) {
            DB::transaction(function () use ($paymentId, $cancellations, &$released) {
                $payment = Payment::where('id', $paymentId)->lockForUpdate()->first();

                // The callback may have arrived since we listed it.
                if (! $payment || $payment->status !== 'pending') {
                    return;
                }

                $payment->update([
                    'status' => 'failed',
                    'result_desc' => 'No response from M-Pesa; released automatically',
                ]);

                if ($cancellations->cancel($payment->order, 'M-Pesa payment unanswered')) {
                    $released++;
                }
            });
        }

        $this->info("Released {$released} unanswered M-Pesa order(s).");

        return self::SUCCESS;
    }
}
