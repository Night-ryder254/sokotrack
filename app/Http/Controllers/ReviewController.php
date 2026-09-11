<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Create or update the current vendor's review for a product.
     * One review per vendor per product — resubmitting updates it.
     */
    public function store(Request $request, Product $product)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            abort(403);
        }

        $hasPurchased = $vendor->orders()
            ->where('status', 'confirmed')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->exists();

        if (!$hasPurchased) {
            return back()->with('error', 'You can only review products you have purchased.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Review::updateOrCreate(
            ['product_id' => $product->id, 'vendor_id' => $vendor->id],
            $validated
        );

        return back()->with('success', 'Thanks for your review!');
    }

    public function destroy(Request $request, Review $review)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor || $review->vendor_id !== $vendor->id) {
            abort(403);
        }

        $review->delete();

        return back()->with('success', 'Review removed.');
    }
}
