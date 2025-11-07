<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'g', 'name' => 'Gram'],
            ['code' => 'ml', 'name' => 'Mililiter'],
            ['code' => 'pcs', 'name' => 'Pieces'],
            ['code' => 'kg', 'name' => 'Kilogram'],
            ['code' => 'l', 'name' => 'Liter'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['code' => $unit['code']], $unit);
        }
    }
}
