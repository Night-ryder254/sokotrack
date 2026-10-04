<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermsVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TermsVersionController extends Controller
{
    public function index()
    {
        $versions = TermsVersion::withCount('acceptances')->latest()->get();
        return view('admin.terms.index', compact('versions'));
    }

    public function create()
    {
        return view('admin.terms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'version_label' => 'required|string|max:50',
            'content' => 'required|string',
        ]);

        DB::transaction(function () use ($validated) {
            TermsVersion::where('is_active', true)->update(['is_active' => false]);

            TermsVersion::create([
                'version_label' => $validated['version_label'],
                'content' => $validated['content'],
                'is_active' => true,
            ]);
        });

        return redirect()->route('admin.terms.index')
            ->with('success', "New terms published — vendors will be prompted to re-accept on next login.");
    }
}
