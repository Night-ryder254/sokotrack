<?php

namespace App\Console\Commands;

use App\Mail\LowStockAlert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendLowStockDigest extends Command
{
    protected $signature = 'sokotrack:low-stock-digest';
    protected $description = 'Email admin/staff a daily digest of all products at or below their low-stock threshold';

    public function handle()
    {
        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->get();

        if ($lowStockProducts->isEmpty()) {
            $this->info('No low-stock products today — no digest sent.');
            return;
        }

        $recipients = User::whereIn('role', ['admin', 'warehouse_staff'])->pluck('email');

        if ($recipients->isEmpty()) {
            $this->warn('No admin/staff recipients found — digest not sent.');
            return;
        }

        Mail::to($recipients)->send(new LowStockAlert($lowStockProducts, 'digest'));

        $this->info("Digest sent to {$recipients->count()} recipient(s) for {$lowStockProducts->count()} product(s).");
    }
}
