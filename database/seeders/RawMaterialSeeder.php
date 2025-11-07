<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RawMaterial;

class RawMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            ['sku' => 'SUGAR-001', 'name' => 'Gula Pasir', 'unit' => 'g', 'unit_cost' => 0.0200, 'stock_qty' => 50000, 'min_stock' => 5000],
            ['sku' => 'MILK-001', 'name' => 'Susu', 'unit' => 'ml', 'unit_cost' => 0.0300, 'stock_qty' => 20000, 'min_stock' => 2000],
            ['sku' => 'BEANS-001', 'name' => 'Biji Kopi', 'unit' => 'g', 'unit_cost' => 0.1500, 'stock_qty' => 10000, 'min_stock' => 1000],
            ['sku' => 'CUP-001', 'name' => 'Cup', 'unit' => 'pcs', 'unit_cost' => 1000.0000, 'stock_qty' => 1000, 'min_stock' => 100],
        ];

        foreach ($materials as $m) {
            RawMaterial::firstOrCreate(['sku' => $m['sku']], $m);
        }
    }
}

