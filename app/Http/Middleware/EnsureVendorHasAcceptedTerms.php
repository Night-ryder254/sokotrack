<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorHasAcceptedTerms
{
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = $request->user()?->vendor;

        if ($vendor && ! $vendor->hasAcceptedCurrentTerms()) {
            return redirect()->route('terms.show');
        }

        return $next($request);
    }
}
