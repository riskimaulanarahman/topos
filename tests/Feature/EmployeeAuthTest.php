<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_pin_login_success(): void
    {
        $emp = Employee::create([
            'name' => 'Emp',
            'email' => 'emp@example.com',
            'phone' => '0800000000',
            'pin' => Hash::make('1234'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $res = $this->postJson('/api/auth/pin-login', [
            'phone_or_email' => 'emp@example.com',
            'pin' => '1234',
        ]);

        $res->assertStatus(200)->assertJsonStructure(['token','employee' => ['id','name','email']]);
    }

    public function test_pin_login_failed(): void
    {
        $emp = Employee::create([
            'name' => 'Emp',
            'email' => 'emp@example.com',
            'phone' => '0800000000',
            'pin' => Hash::make('1234'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $res = $this->postJson('/api/auth/pin-login', [
            'phone_or_email' => 'emp@example.com',
            'pin' => '9999',
        ]);

        $res->assertStatus(401);
    }
}

