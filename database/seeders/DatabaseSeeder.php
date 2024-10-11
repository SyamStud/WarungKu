<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Setting::create([
            'key' => 'is_tax',
            'value' => false,
        ]);

        Setting::create([
            'key' => 'tax_percentage',
            'value' => 0,
        ]);

        $categoryNames = [
            'Electronics',
            'Clothing',
            'Home & Kitchen',
            'Books',
            'Health & Beauty'
        ];

        foreach ($categoryNames as $item) {
            Category::create([
                'name' => $item,
                'slug' => Str::slug($item),
            ]);
        }

        $this->call(RoleSeeder::class);
        $this->call(UnitSeeder::class);

        Product::factory()->count(10)->create();

        // Setiap produk akan memiliki 3 varian
        Product::all()->each(function ($product) {
            ProductVariant::factory()->count(1)->create([
                'product_id' => $product->id,
            ]);
        });

        $user = User::create([
            'nik' => '1234567890',
            'name' => 'Test User',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 123',
            'photo' => 'default.jpg',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settings = [
            'sound_product_not_found' => true,
            'sound_payment_method' => false,
            'sound_alert_qris_payment' => true,
            'sound_change' => true,
            'sound_cancel_transaction' => true,
            'sound_success_transaction' => true,
        ];

        foreach ($settings as $key => $value) {
            $user->settings()->create([
                'key' => $key,
                'value' => $value,
            ]);
        }

        $user->assignRole('admin');
    }
}
