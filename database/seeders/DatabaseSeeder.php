<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\VendorTier;
use App\Models\Vendor;
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
        // Your permanent admin account — recreated automatically on every fresh seed
        $admin = User::create([
            'name' => 'Nigel Matekwa',
            'email' => 'admin@sokotrack.test',
            'password' => bcrypt('password'), // change if you want a real password
            'role' => 'admin',
        ]);

        // Optional: a standing "denied" test account.
        User::create([
            'name' => 'Test Manager',
            'email' => 'staff@sokotrack.test',
            'password' => bcrypt('password'),
            'role' => 'warehouse_staff',
        ]);

        // 1. Vendor tiers — fixed business rules, not random
        $bronze = VendorTier::create(['name' => 'Bronze', 'min_points' => 0, 'discount_percentage' => 0]);
        $silver = VendorTier::create(['name' => 'Silver', 'min_points' => 500, 'discount_percentage' => 5]);
        $gold = VendorTier::create(['name' => 'Gold', 'min_points' => 2000, 'discount_percentage' => 10]);

        // A known, permanent vendor account for easy storefront testing
        $vendorUser = User::create([
            'name' => 'Test Vendor',
            'email' => 'vendor@sokotrack.test',
            'password' => bcrypt('password'),
            'role' => 'none',
        ]);

        Vendor::create([
            'user_id' => $vendorUser->id,
            'vendor_tier_id' => $bronze->id,
            'business_name' => 'Mama Njeri General Store',
            'phone' => '254712345678',
            'credit_limit' => 50000,
            'outstanding_balance' => 0,
            'loyalty_points' => 0,
        ]);

        // 2. Terms & Conditions — one real active version
        $terms = TermsVersion::create([
            'version_label' => 'v1.0',
            'content' => 'Standard SokoTrack vendor terms: payment due within agreed terms, returns accepted within 7 days of delivery for damaged goods, credit accounts subject to review.',
            'is_active' => true,
        ]);

        // 3. Categories and products
        $categoryNames = ['Beverages', 'Snacks', 'Cleaning Supplies', 'Stationery', 'Cosmetics', 'Dairy'];
        $categories = collect($categoryNames)->map(fn ($name) => Category::create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
        ]));
        $products = collect();
        $productsByCategory = [
            'Beverages' => ['Coca-Cola 500ml', 'Del Monte Juice 1L', 'Keringet Water 500ml', 'Tusker Lager 500ml', 'Java House Ground Coffee 250g', 'Alvaro Malt Drink 350ml', 'Dasani Water 1L', 'Minute Maid Mango 1L', 'Fanta Orange 500ml', 'Sprite 500ml'],
            'Snacks' => ['Tropical Heat Crisps', 'Britania Digestive Biscuits', 'Cadbury Dairy Milk 80g', 'Farmers Choice Sausages', 'Kenchic Chicken Nuggets 500g', 'Nice Biscuits 200g', 'Big G Bubble Gum', 'Softa Fruit Chews', 'Weetabix Cereal 430g', 'Uji Flour 2kg'],
            'Cleaning Supplies' => ['Omo Washing Powder 1kg', 'Jik Bleach 750ml', 'Sunlight Dishwashing Liquid 500ml', 'Harpic Toilet Cleaner 500ml', 'Ariel Detergent 1kg', 'Dettol Antiseptic 250ml', 'Vim Scouring Powder 500g', 'Toilet Paper 10-Pack', 'Kiwi Shoe Polish', 'Mr Muscle Glass Cleaner'],
            'Stationery' => ['Bic Biro Pens (Box of 12)', 'Foolscap Exercise Book', 'Camlin Pencils (Pack of 10)', 'Manila File Folders', 'Stapler with Pins', 'A4 Printing Paper Ream', 'Highlighter Set', 'Correction Fluid', 'Office Envelopes (50-Pack)', 'Sticky Notes Pack'],
            'Cosmetics' => ['Nivea Body Lotion 400ml', 'Fair & White Soap', 'Vaseline Petroleum Jelly 250ml', 'Dark & Lovely Relaxer', 'Colgate Toothpaste 100ml', 'Geisha Beauty Soap', 'Sunsilk Shampoo 350ml', 'Rexona Deodorant Roll-On', 'Nice & Lovely Body Wash', 'Palmers Cocoa Butter'],
            'Dairy' => ['Brookside Fresh Milk 500ml', 'KCC Butter 250g', 'Tuzo Yoghurt 500ml', 'Fresha Long Life Milk 1L', 'Daima Ghee 500g', 'Bio Yoghurt 1L', 'Kenya Creamery Cheese 200g', 'Ilara Fresh Milk 1L', 'Delamere Cream 250ml', 'Kanga Milk Powder 400g'],
        ];

        foreach ($categories as $category) {
            $names = $productsByCategory[$category->name] ?? [];

            foreach ($names as $name) {
                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'sku' => strtoupper(fake()->bothify('SKU-####-???')),
                    'description' => "Quality {$category->name} product, popular with retail and wholesale customers across Kenya. Sold in standard retail packaging, suitable for shelf display.",
                    'price' => fake()->randomFloat(2, 50, 5000),
                    'stock_quantity' => fake()->numberBetween(0, 500),
                    'low_stock_threshold' => fake()->numberBetween(5, 30),
                    'image_path' => null,
                ]);

                $products->push($product);
            }
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
