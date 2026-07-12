<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'vendor_tier_id' => null, // assigned in seeder based on points
            'business_name' => fake()->company(),
            'phone' => '2547' . fake()->numerify('########'), // Kenyan format
            'credit_limit' => fake()->randomElement([0, 10000, 50000, 100000]),
            'outstanding_balance' => 0,
            'loyalty_points' => 0, // built up via seeded orders, not random
        ];
    }
}
