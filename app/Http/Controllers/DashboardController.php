<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isVendor()) {
            $vendor = $user->vendor()->with('tier')->first();
            $recentOrders = $vendor->orders()->latest()->take(5)->get();

            return view('dashboard-vendor', compact('vendor', 'recentOrders'));
        }

        return view('dashboard');
    }
}