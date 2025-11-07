<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_in_and_clock_out_flow(): void
    {
        $emp = Employee::create([
            'name' => 'Emp',
            'email' => 'emp@example.com',
            'phone' => '0800000000',
            'pin' => Hash::make('1234'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        Sanctum::actingAs($emp, [], 'employee');

        $resIn = $this->postJson('/api/attendances/clock-in', []);
        $resIn->assertStatus(200);

        // double clock-in should be rejected
        $resIn2 = $this->postJson('/api/attendances/clock-in', []);
        $resIn2->assertStatus(422);

        $resOut = $this->postJson('/api/attendances/clock-out', []);
        $resOut->assertStatus(200);

        $this->assertEquals(1, Attendance::count());
        $this->assertNotNull(Attendance::first()->work_minutes);
    }
}

