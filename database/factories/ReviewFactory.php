<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    public function definition(): array
    {
        $rating = $this->faker->numberBetween(1, 5);

        $positiveComments = [
            'Great quality product, exactly as described. Will order again.',
            'Fast turnaround and the packaging was excellent.',
            'Good value for money. My customers are happy with this.',
            'Reliable product, consistent quality every time we order.',
            'Excellent — this has become one of our best sellers.',
            'Very satisfied with this product. Highly recommend.',
        ];

        $neutralComments = [
            'Decent product overall, does the job.',
            'Average quality, nothing special but works fine.',
            'It\'s okay. Delivery could have been faster.',
            'Meets expectations, though packaging could be better.',
        ];

        $negativeComments = [
            'Quality was not what I expected for the price.',
            'Had some issues with this batch — inconsistent quality.',
            'Below average. Would not reorder this particular product.',
            'Packaging was damaged on arrival, product itself was fine.',
        ];

        $comment = match (true) {
            $rating >= 4 => $this->faker->randomElement($positiveComments),
            $rating === 3 => $this->faker->randomElement($neutralComments),
            default => $this->faker->randomElement($negativeComments),
        };

        return [
            'product_id' => Product::factory(),
            'vendor_id' => Vendor::factory(),
            'rating' => $rating,
            'comment' => $comment,
        ];
    }
}
