<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AdditionalCharges;

class AdditionalChargesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $additionalCharges = [
            [
                'name' => 'Service Charge',
                'type' => 'fixed',
                'value' => 20000,
                'created_at' => now()
            ],
            [
                'name' => 'Tax',
                'type' => 'percentage',
                'value' => 5,
                'created_at' => now()
            ],
        ];

        AdditionalCharges::insert($additionalCharges);
    }
}
