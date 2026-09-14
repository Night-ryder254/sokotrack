<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'method',
        'amount',
        'status',
        'mpesa_receipt_number',
        'bank_reference',
        'confirmed_at',
        'checkout_request_id',
        'merchant_request_id',
        'result_desc',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
