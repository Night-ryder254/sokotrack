<?php

namespace Database\Seeders;

use App\Models\TermsVersion;
use Illuminate\Database\Seeder;

class TermsVersionSeeder extends Seeder
{
    public function run(): void
    {
        TermsVersion::create([
            'version_label' => 'v1.0',
            'content' => <<<'MARKDOWN'
# SokoTrack Vendor Terms & Conditions

**Last updated: September 2026**

## 1. Account & Eligibility
By registering as a vendor on SokoTrack, you confirm you are operating a legitimate business in Kenya and agree to provide accurate business information.

## 2. Orders & Payments
Orders placed through SokoTrack may be paid via M-Pesa, bank transfer, or approved credit terms. Credit limits are set at SokoTrack's discretion based on payment history.

## 3. Credit Terms
Vendors using credit terms agree to settle outstanding balances promptly. SokoTrack reserves the right to adjust or revoke credit limits for accounts with repeated late payments.

## 4. Product Returns & Cancellations
Order cancellations are handled through SokoTrack's admin team. Stock and loyalty point reversals are processed automatically upon cancellation approval.

## 5. Loyalty Program
Loyalty points are earned on confirmed orders and may be used toward tier benefits. Points have no cash value and cannot be transferred between accounts.

## 6. Data & Communication
SokoTrack may contact vendors via email or SMS regarding order status, payment confirmations, and account updates.

## 7. Changes to These Terms
SokoTrack may update these terms from time to time. Continued use of the platform after an update requires re-acceptance of the current terms.
MARKDOWN,
            'is_active' => true,
        ]);
    }
}
