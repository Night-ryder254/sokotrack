<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermsAcceptance extends Model
{
    /** @use HasFactory<\Database\Factories\TermsAcceptanceFactory> */
    use HasFactory;

    protected $fillable = ['vendor_id', 'terms_version_id', 'accepted_at'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function termsVersion()
    {
        return $this->belongsTo(TermsVersion::class);
    }
}
