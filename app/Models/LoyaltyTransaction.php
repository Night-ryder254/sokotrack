<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\LoyaltyTransactionFactory> */
    use HasFactory;

    protected $fillable = ['vendor_id', 'points_change', 'reason', 'order_id'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
