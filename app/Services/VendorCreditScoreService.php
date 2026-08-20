<?php

namespace App\Services;

use App\Models\Vendor;

class VendorCreditScoreService
{
    /**
     * Compute a live credit health score from payment reliability,
     * credit utilization, and cancellation history — no new tables,
     * just reading signal already sitting in orders/payments.
     */
    public static function calculate(Vendor $vendor): array
    {
        $totalOrders = $vendor->total_orders_count ?? $vendor->orders()->count();
        $cancelledOrders = $vendor->cancelled_orders_count ?? $vendor->orders()->where('status', 'cancelled')->count();
        $totalPayments = $vendor->total_payments_count ?? $vendor->payments()->count();
        $confirmedPayments = $vendor->confirmed_payments_count ?? $vendor->payments()->where('status', 'confirmed')->count();

        // No payment history yet = neutral, not penalized
        $paymentReliability = $totalPayments > 0 ? $confirmedPayments / $totalPayments : 1.0;

        // Cap at 150% so one badly over-limit vendor doesn't blow the scale.
        // A vendor with outstanding balance but NO credit limit set is maximally risky,
        // not risk-free — treat that as 150% (max) utilization, not 0%.
        if ($vendor->credit_limit > 0) {
            $creditUtilization = min($vendor->outstanding_balance / $vendor->credit_limit, 1.5);
        } elseif ($vendor->outstanding_balance > 0) {
            $creditUtilization = 1.5;
        } else {
            $creditUtilization = 0;
        }

        $cancellationRate = $totalOrders > 0 ? $cancelledOrders / $totalOrders : 0;

        $score = ($paymentReliability * 50)
            + ((1 - min($creditUtilization, 1)) * 30)
            + ((1 - $cancellationRate) * 20);

        $score = (int) round(max(0, min(100, $score)));

        $label = match (true) {
            $score >= 75 => 'Low risk',
            $score >= 50 => 'Medium risk',
            default => 'High risk',
        };

        $reasonParts = [
            $totalPayments > 0
                ? "{$confirmedPayments} of {$totalPayments} payments confirmed"
                : 'No payment history yet',
            'Credit utilization at ' . round($creditUtilization * 100) . '%',
        ];

        if ($totalOrders > 0) {
            $reasonParts[] = "{$cancelledOrders} of {$totalOrders} orders cancelled";
        }

        $suggestion = null;

        if ($label === 'Low risk' && $creditUtilization >= 0.7 && $vendor->credit_limit > 0) {
            $suggested = round($vendor->credit_limit * 1.2, -2);
            $suggestion = [
                'type' => 'increase',
                'amount' => $suggested,
                'message' => 'Reliable and using most of their limit — consider raising credit limit to KES ' . number_format($suggested) . '.',
            ];
        } elseif ($label === 'High risk' && $vendor->credit_limit > 0) {
            $suggested = round($vendor->credit_limit * 0.8, -2);
            $suggestion = [
                'type' => 'decrease',
                'amount' => $suggested,
                'message' => 'Payment reliability or cancellations are a concern — consider lowering credit limit to KES ' . number_format($suggested) . '.',
            ];
        } elseif ($label === 'High risk' && $vendor->credit_limit == 0 && $vendor->outstanding_balance > 0) {
            $suggestion = [
                'type' => 'review',
                'amount' => null,
                'message' => 'This vendor has an outstanding balance with no credit limit set — review their account before extending further credit.',
            ];
        }

        return [
            'score' => $score,
            'label' => $label,
            'reason' => implode('. ', $reasonParts) . '.',
            'suggestion' => $suggestion,
        ];
    }
}
