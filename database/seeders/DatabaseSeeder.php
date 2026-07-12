<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\VendorTier;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\Payment;
use App\Models\LoyaltyTransaction;
use App\Models\Review;
use App\Models\TermsVersion;
use App\Models\TermsAcceptance;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Vendor tiers — fixed business rules, not random
        $bronze = VendorTier::create(['name' => 'Bronze', 'min_points' => 0, 'discount_percentage' => 0]);
        $silver = VendorTier::create(['name' => 'Silver', 'min_points' => 500, 'discount_percentage' => 5]);
        $gold = VendorTier::create(['name' => 'Gold', 'min_points' => 2000, 'discount_percentage' => 10]);

        // 2. Terms & Conditions — one real active version
        $terms = TermsVersion::create([
            'version_label' => 'v1.0',
            'content' => 'Standard SokoTrack vendor terms: payment due within agreed terms, returns accepted within 7 days of delivery for damaged goods, credit accounts subject to review.',
            'is_active' => true,
        ]);

        // 3. Categories and products
        $categories = Category::factory(6)->create();
        $products = collect();
        foreach ($categories as $category) {
            $categoryProducts = Product::factory(10)->create(['category_id' => $category->id]);
            $products = $products->merge($categoryProducts);
        }

        // 4. Vendors — start everyone on Bronze, all balances/points at zero
        $vendors = collect();
        foreach (range(1, 40) as $i) {
            $user = User::factory()->create();
            $vendor = Vendor::factory()->create([
                'user_id' => $user->id,
                'vendor_tier_id' => $bronze->id,
                'loyalty_points' => 0,
                'outstanding_balance' => 0,
            ]);
            $vendors->push($vendor);

            // every vendor accepts the current terms on signup
            TermsAcceptance::create([
                'vendor_id' => $vendor->id,
                'terms_version_id' => $terms->id,
                'accepted_at' => now(),
            ]);
        }

        // 5. Orders — each vendor places 3-8 orders, each order gets 1-5 real line items
        foreach ($vendors as $vendor) {
            $orderCount = rand(3, 8);

            foreach (range(1, $orderCount) as $i) {
                $order = Order::create([
                    'vendor_id' => $vendor->id,
                    'order_number' => 'ORD-' . strtoupper(uniqid()),
                    'total_amount' => 0, // will update after items are attached
                    'payment_method' => fake()->randomElement(['mpesa', 'bank_transfer', 'credit']),
                    'status' => 'confirmed',
                ]);

                $itemCount = rand(1, 5);
                $orderTotal = 0;

                $orderProducts = $products->random(min($itemCount, $products->count()));

                foreach ($orderProducts as $product) {
                    $quantity = rand(1, 10);
                    $unitPrice = $product->price; // snapshot price AT THIS MOMENT

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ]);

                    $orderTotal += $quantity * $unitPrice;

                    // Record the stock movement — a real sale event
                    StockMovement::create([
                        'product_id' => $product->id,
                        'quantity_change' => -$quantity, // negative = stock leaving
                        'reason' => 'sale',
                        'order_id' => $order->id,
                        'notes' => null,
                    ]);

                    // Actually deduct from the product's live stock count
                    // (only if enough stock exists — keeps numbers realistic, never negative)
                    if ($product->stock_quantity >= $quantity) {
                        $product->decrement('stock_quantity', $quantity);
                    }
                }

                // Now that we know the real total, update the order
                $order->update(['total_amount' => $orderTotal]);

                // Create a matching payment record
                if ($order->payment_method === 'credit') {
                    // credit orders don't get an immediate payment — they add to outstanding balance
                    $vendor->increment('outstanding_balance', $orderTotal);
                } else {
                    Payment::create([
                        'order_id' => $order->id,
                        'method' => $order->payment_method,
                        'amount' => $orderTotal,
                        'status' => 'confirmed',
                        'mpesa_receipt_number' => $order->payment_method === 'mpesa' ? strtoupper(fake()->bothify('??#########')) : null,
                        'bank_reference' => $order->payment_method === 'bank_transfer' ? fake()->bothify('REF-######') : null,
                        'confirmed_at' => now(),
                    ]);
                }

                // Loyalty points: 1 point per KES 100 spent — a real, explainable earning rule
                $pointsEarned = intdiv((int) $orderTotal, 100);

                LoyaltyTransaction::create([
                    'vendor_id' => $vendor->id,
                    'points_change' => $pointsEarned,
                    'reason' => 'order_earned',
                    'order_id' => $order->id,
                ]);

                $vendor->increment('loyalty_points', $pointsEarned);
            }

            // 6. After all orders, recalculate the vendor's tier based on final points total
            $vendor->refresh();
            $newTier = match (true) {
                $vendor->loyalty_points >= $gold->min_points => $gold,
                $vendor->loyalty_points >= $silver->min_points => $silver,
                default => $bronze,
            };
            $vendor->update(['vendor_tier_id' => $newTier->id]);
        }

        // 7. Reviews — a subset of vendors review products they've actually purchased
        foreach ($vendors->random(20) as $vendor) {
            $purchasedProductIds = OrderItem::whereHas('order', fn ($q) => $q->where('vendor_id', $vendor->id))
                ->pluck('product_id')
                ->unique();

            foreach ($purchasedProductIds->random(min(2, $purchasedProductIds->count())) as $productId) {
                Review::create([
                    'product_id' => $productId,
                    'vendor_id' => $vendor->id,
                    'rating' => fake()->numberBetween(3, 5),
                    'comment' => fake()->optional(0.7)->sentence(),
                ]);
            }
        }
    }
}
