<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        \App\Models\Category::factory(10)->create();
        $this->call(UnitSeeder::class);

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

        $user->assignRole('admin');
    }
}
