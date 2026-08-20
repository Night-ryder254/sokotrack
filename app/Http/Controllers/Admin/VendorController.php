<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyTransaction;
use App\Models\Vendor;
use App\Models\VendorTier;
use App\Services\VendorCreditScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    /**
     * Full vendor list with search, tier filter, and health score badges.
     */
    public function index(Request $request)
    {
        $query = Vendor::with('tier')->withCount([
            'orders as total_orders_count',
            'orders as cancelled_orders_count' => fn ($q) => $q->where('orders.status', 'cancelled'),
            'payments as total_payments_count',
            'payments as confirmed_payments_count' => fn ($q) => $q->where('payments.status', 'confirmed'),
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tier')) {
            $query->where('vendor_tier_id', $request->tier);
        }

        $vendors = $query->orderBy('business_name')->paginate(20)->withQueryString();

        // Attach computed score to each row using counts already eager-loaded above (no N+1)
        $vendors->getCollection()->transform(function ($vendor) {
            $vendor->healthScore = VendorCreditScoreService::calculate($vendor);
            return $vendor;
        });

        return view('admin.vendors.index', [
            'vendors' => $vendors,
            'tiers' => VendorTier::orderBy('min_points')->get(),
        ]);
    }

    /**
     * Full vendor detail: profile, credit health, order history, loyalty log.
     */
    public function show(Vendor $vendor)
    {
        $vendor->load('tier', 'user');
        $vendor->loadCount([
            'orders as total_orders_count',
            'orders as cancelled_orders_count' => fn ($q) => $q->where('orders.status', 'cancelled'),
            'payments as total_payments_count',
            'payments as confirmed_payments_count' => fn ($q) => $q->where('payments.status', 'confirmed'),
        ]);

        $orders = $vendor->orders()->with('payment')->latest()->paginate(10, ['*'], 'orders_page');
        $loyaltyTransactions = $vendor->loyaltyTransactions()->latest()->limit(15)->get();
        $healthScore = VendorCreditScoreService::calculate($vendor);

        return view('admin.vendors.show', compact('vendor', 'orders', 'loyaltyTransactions', 'healthScore'));
    }

    /**
     * Edit credit_limit and tier manually.
     */
    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'credit_limit' => 'required|numeric|min:0',
            'vendor_tier_id' => 'nullable|exists:vendor_tiers,id',
        ]);

        $vendor->update($validated);

        return back()->with('success', "{$vendor->business_name}'s account updated.");
    }

    /**
     * Manual loyalty point adjustment, logged the same way order-earned points are.
     */
    public function adjustLoyalty(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'points_change' => 'required|integer|not_in:0',
        ]);

        if ($validated['points_change'] < 0 && ($vendor->loyalty_points + $validated['points_change']) < 0) {
            return back()->with('error', 'This adjustment would take the vendor below zero points.');
        }

        DB::transaction(function () use ($vendor, $validated) {
            $vendor->increment('loyalty_points', $validated['points_change']);

            LoyaltyTransaction::create([
                'vendor_id' => $vendor->id,
                'points_change' => $validated['points_change'],
                'reason' => 'admin_adjustment',
                'order_id' => null,
            ]);
        });

        $sign = $validated['points_change'] > 0 ? '+' : '';

        return back()->with('success', "Loyalty points adjusted by {$sign}{$validated['points_change']}.");
    }
}
