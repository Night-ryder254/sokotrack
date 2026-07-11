<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorTier extends Model
{
    /** @use HasFactory<\Database\Factories\VendorTierFactory> */
    use HasFactory;

    protected $fillable = ['name', 'min_points', 'discount_percentage'];

    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }
}
