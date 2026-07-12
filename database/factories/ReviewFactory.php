<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'vendor_id' => \App\Models\Vendor::factory(),
            'rating' => fake()->numberBetween(3, 5), // realistic: most reviews skew positive
            'comment' => fake()->optional(0.7)->sentence(), // 70% leave a comment, 30% just rate
        ];
    }
}
