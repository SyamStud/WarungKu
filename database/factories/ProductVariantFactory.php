<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
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
            'price' => $this->faker->numberBetween(1, 5) * 1000,
            'unit_id' => 1,
            'quantity' => 1,
            'cost' => $this->faker->numberBetween(1, 5) * 1000,
            'status' => 'active',
        ];
    }
}
