<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
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

        if ($user->isInternalStaff()) {
            $todayRevenue = Order::whereDate('created_at', today())->sum('total_amount');
            $todayOrderCount = Order::whereDate('created_at', today())->count();

            $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->orderBy('stock_quantity')
                ->take(5)
                ->get();

            $totalOutstandingCredit = Vendor::sum('outstanding_balance');

            $recentOrders = Order::with('vendor')->latest()->take(8)->get();

            return view('dashboard-admin', compact(
                'todayRevenue', 'todayOrderCount', 'lowStockProducts',
                'totalOutstandingCredit', 'recentOrders'
            ));
        }

        return view('dashboard');
    }
}
