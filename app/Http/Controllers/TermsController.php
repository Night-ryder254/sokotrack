<?php

namespace App\Http\Controllers;

use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use Illuminate\Http\Request;

class TermsController extends Controller
{
    public function show(Request $request)
    {
        $vendor = $request->user()->vendor;
        $currentVersion = TermsVersion::where('is_active', true)->firstOrFail();

        // Already accepted this exact version? No need to show the page again.
        if ($vendor->hasAcceptedCurrentTerms()) {
            return redirect()->route('dashboard');
        }

        return view('terms.show', compact('currentVersion'));
    }

    public function accept(Request $request)
    {
        $request->validate(['agree' => 'accepted']);

        $vendor = $request->user()->vendor;
        $currentVersion = TermsVersion::where('is_active', true)->firstOrFail();

        TermsAcceptance::create([
            'vendor_id' => $vendor->id,
            'terms_version_id' => $currentVersion->id,
            'accepted_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Thanks — you\'re all set.');
    }
}
