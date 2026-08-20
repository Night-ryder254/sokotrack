<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    /** @use HasFactory<\Database\Factories\VendorFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'vendor_tier_id', 'business_name', 'phone', 'credit_limit', 'outstanding_balance', 'loyalty_points'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tier()
    {
        return $this->belongsTo(VendorTier::class, 'vendor_tier_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(
            \App\Models\Payment::class,
            \App\Models\Order::class,
            'vendor_id', // foreign key on orders table
            'order_id',  // foreign key on payments table
            'id',        // local key on vendors table
            'id'         // local key on orders table
        );
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(\App\Models\LoyaltyTransaction::class);
    }

    public function termsAcceptances()
    {
        return $this->hasMany(TermsAcceptance::class);
    }

    public function availableCredit()
    {
        return $this->credit_limit - $this->outstanding_balance;
    }
}
