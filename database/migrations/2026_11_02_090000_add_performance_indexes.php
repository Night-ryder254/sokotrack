<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Looked up on every M-Pesa callback, and by every 3-second status poll from the
        // checkout "pending" page.
        if (! Schema::hasIndex('payments', ['checkout_request_id'])) {
            Schema::table('payments', fn (Blueprint $table) => $table->index('checkout_request_id'));
        }

        // The scheduled release of unanswered M-Pesa payments filters on all three columns.
        if (! Schema::hasIndex('payments', ['method', 'status', 'created_at'])) {
            Schema::table('payments', fn (Blueprint $table) => $table->index(['method', 'status', 'created_at']));
        }

        // Admin order list: filter by status, newest first.
        if (! Schema::hasIndex('orders', ['status', 'created_at'])) {
            Schema::table('orders', fn (Blueprint $table) => $table->index(['status', 'created_at']));
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['checkout_request_id']);
            $table->dropIndex(['method', 'status', 'created_at']);
        });

        Schema::table('orders', fn (Blueprint $table) => $table->dropIndex(['status', 'created_at']));
    }
};
