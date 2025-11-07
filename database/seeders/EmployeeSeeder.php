<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['name' => 'Owner One', 'email' => 'owner@example.com', 'phone' => '0811111111', 'pin' => '1234', 'role' => 'owner', 'is_active' => true],
            ['name' => 'Manager One', 'email' => 'manager@example.com', 'phone' => '0822222222', 'pin' => '2345', 'role' => 'manager', 'is_active' => true],
            ['name' => 'Staff One', 'email' => 'staff@example.com', 'phone' => '0833333333', 'pin' => '3456', 'role' => 'staff', 'is_active' => true],
        ];

        foreach ($employees as $e) {
            Employee::firstOrCreate(
                ['email' => $e['email']],
                array_merge($e, ['pin' => Hash::make($e['pin'])])
            );
        }
    }
}

