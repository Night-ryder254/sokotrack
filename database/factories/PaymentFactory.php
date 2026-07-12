<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'method' => fake()->randomElement(['mpesa', 'bank_transfer', 'credit']),
            'amount' => 0, // matched to order total in seeder
            'status' => 'confirmed',
            'mpesa_receipt_number' => fake()->bothify('??#########'),
            'bank_reference' => null,
            'confirmed_at' => now(),
        ];
    }
}
