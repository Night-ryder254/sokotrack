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
        return [
            'category_id' => \App\Models\Category::factory(),
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->bothify('SKU-####-???')),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 50, 5000), // KES 50 - 5000
            'stock_quantity' => $stock,
            'low_stock_threshold' => fake()->numberBetween(5, 30),
            'image_path' => null, // populated later once S3 is wired up
        ];
    }
}
