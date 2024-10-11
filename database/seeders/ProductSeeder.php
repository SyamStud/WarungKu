<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Product::factory()->count(100)->create();

        \App\Models\Product::all()->each(function ($product) {
            \App\Models\ProductVariant::factory()->count(3)->create([
                'product_id' => $product->id,
            ]);
        });
    }
}
