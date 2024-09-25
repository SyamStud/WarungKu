<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Unit::create(['name' => 'PCS']);
        Unit::create(['name' => 'PAK']);
        Unit::create(['name' => 'DUS']);
        Unit::create(['name' => 'KG']);
        Unit::create(['name' => 'LITER']);
    }
}
