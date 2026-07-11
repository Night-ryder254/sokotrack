<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermsVersion extends Model
{
    /** @use HasFactory<\Database\Factories\TermsVersionFactory> */
    use HasFactory;

    protected $fillable = ['version_label', 'content', 'is_active'];

    public function acceptances()
    {
        return $this->hasMany(TermsAcceptance::class);
    }
}
