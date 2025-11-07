<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IncomeCategory;
use App\Models\ExpenseCategory;

class FinanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Penjualan Lainnya', 'description' => 'Pemasukan non-penjualan utama'],
            ['name' => 'Modal Disetor', 'description' => 'Setoran modal'],
        ] as $cat) {
            IncomeCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }

        foreach ([
            ['name' => 'Operasional', 'description' => 'Biaya operasional harian'],
            ['name' => 'Gaji', 'description' => 'Penggajian karyawan'],
        ] as $cat) {
            ExpenseCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }
}

