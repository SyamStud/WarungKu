<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'nik' => '1234567890',
            'name' => 'Test User',
            'email' => 'cashier@gmail.com',
            'password' => bcrypt('12345678'),
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 123',
            'photo' => 'default.jpg',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'store_id' => 1,
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

        $user->assignRole('cashier');

        $user = User::create([
            'nik' => '1234567890',
            'name' => 'Test User',
            'email' => 'superadmin@gmail.com',
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

        $user->assignRole('super-admin');
    }
}
