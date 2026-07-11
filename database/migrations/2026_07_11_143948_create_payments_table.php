<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('method', ['mpesa', 'bank_transfer', 'credit']);
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->string('mpesa_receipt_number')->nullable(); // from Daraja callback
            $table->string('bank_reference')->nullable(); // manually entered by vendor
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
