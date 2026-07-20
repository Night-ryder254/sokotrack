<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stock = fake()->numberBetween(0, 500);

        $productsByCategory = [
            'Beverages' => ['Coca-Cola 500ml', 'Del Monte Juice 1L', 'Keringet Water 500ml', 'Tusker Lager 500ml', 'Java House Ground Coffee 250g', 'Alvaro Malt Drink 350ml', 'Dasani Water 1L', 'Minute Maid Mango 1L'],
            'Snacks' => ['Tropical Heat Crisps', 'Britania Digestive Biscuits', 'Cadbury Dairy Milk 80g', 'Farmers Choice Sausages', 'Kenchic Chicken Nuggets 500g', 'Nice Biscuits 200g', 'Big G Bubble Gum', 'Softa Fruit Chews'],
            'Cleaning Supplies' => ['Omo Washing Powder 1kg', 'Jik Bleach 750ml', 'Sunlight Dishwashing Liquid 500ml', 'Harpic Toilet Cleaner 500ml', 'Ariel Detergent 1kg', 'Dettol Antiseptic 250ml', 'Vim Scouring Powder 500g', 'Toilet Paper 10-Pack'],
            'Stationery' => ['Bic Biro Pens (Box of 12)', 'Foolscap Exercise Book', 'Camlin Pencils (Pack of 10)', 'Manila File Folders', 'Stapler with Pins', 'A4 Printing Paper Ream', 'Highlighter Set', 'Correction Fluid'],
            'Cosmetics' => ['Nivea Body Lotion 400ml', 'Fair & White Soap', 'Vaseline Petroleum Jelly 250ml', 'Dark & Lovely Relaxer', 'Colgate Toothpaste 100ml', 'Geisha Beauty Soap', 'Sunsilk Shampoo 350ml', 'Rexona Deodorant Roll-On'],
            'Dairy' => ['Brookside Fresh Milk 500ml', 'KCC Butter 250g', 'Tuzo Yoghurt 500ml', 'Fresha Long Life Milk 1L', 'Daima Ghee 500g', 'Bio Yoghurt 1L', 'Kenya Creamery Cheese 200g', 'Ilara Fresh Milk 1L'],
        ];

        // Fallback in case a category name doesn't match the map above
        $fallback = ['Assorted Product ' . fake()->numberBetween(100, 999)];

        return [
            'category_id' => \App\Models\Category::factory(),
            'name' => fake()->unique()->word(), // placeholder, overridden properly in seeder below
            'sku' => strtoupper(fake()->bothify('SKU-####-???')),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 50, 5000),
            'stock_quantity' => $stock,
            'low_stock_threshold' => fake()->numberBetween(5, 30),
            'image_path' => null,
        ];
    }
}
