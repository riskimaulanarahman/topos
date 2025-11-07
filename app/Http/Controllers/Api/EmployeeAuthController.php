<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PinLoginRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class EmployeeAuthController extends Controller
{
    public function pinLogin(PinLoginRequest $request)
    {
        $payload = $request->validated();
        $employee = Employee::query()
            ->when(str_contains($payload['phone_or_email'], '@'), fn($q) => $q->where('email', $payload['phone_or_email']))
            ->when(!str_contains($payload['phone_or_email'], '@'), fn($q) => $q->orWhere('phone', $payload['phone_or_email']))
            ->first();

        if (!$employee || !$employee->is_active) {
            return response()->json(['message' => 'Akun tidak ditemukan atau tidak aktif'], 404);
        }

        if (!Hash::check($payload['pin'], $employee->pin)) {
            return response()->json(['message' => 'PIN salah'], 401);
        }

        $token = $employee->createToken('employee-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'role' => $employee->role,
                'is_active' => $employee->is_active,
            ],
        ]);
    }
}

