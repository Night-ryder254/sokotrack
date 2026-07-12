<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => \App\Models\Vendor::factory(),
            'order_number' => 'ORD-' . strtoupper(fake()->bothify('####-????')),
            'total_amount' => 0, // calculated in seeder after items are attached
            'payment_method' => fake()->randomElement(['mpesa', 'bank_transfer', 'credit']),
            'status' => fake()->randomElement(['pending', 'confirmed', 'confirmed', 'confirmed']), // weighted toward confirmed
        ];
    }
}
