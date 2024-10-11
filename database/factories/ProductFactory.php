<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
        // Daftar nama produk yang akan digunakan
        $productNames = [
            'Laptop',
            'Smartphone',
            'Tablet',
            'Headphones',
            'Smartwatch',
            'Camera',
            'Printer',
            'Monitor',
            'Keyboard',
            'Mouse'
        ];

        return [
            'sku' => strtoupper($this->faker->unique()->bothify('???###')), // Format SKU yang lebih baik
            'name' => $this->faker->unique()->randomElement($productNames), // Mengambil dari daftar nama yang didefinisikan
            'category_id' => $this->faker->numberBetween(1, 5), // Asumsikan ada 5 kategori
        ];
    }
}
