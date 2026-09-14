<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('checkout_request_id')->nullable()->after('mpesa_receipt_number');
            $table->string('merchant_request_id')->nullable()->after('checkout_request_id');
            $table->string('result_desc')->nullable()->after('merchant_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['checkout_request_id', 'merchant_request_id', 'result_desc']);
        });
    }
};
