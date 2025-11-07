<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeStoreRequest;
use App\Http\Requests\EmployeeUpdateRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        Gate::authorize('employees.manage');
        return response()->json(['data' => Employee::orderBy('name')->get()]);
    }

    public function store(EmployeeStoreRequest $request)
    {
        Gate::authorize('employees.manage');
        $data = $request->validated();
        $data['pin'] = Hash::make($data['pin']);
        $emp = Employee::create($data);
        return response()->json(['message' => 'Employee created', 'data' => $emp], 201);
    }

    public function update(EmployeeUpdateRequest $request, int $id)
    {
        Gate::authorize('employees.manage');
        $emp = Employee::findOrFail($id);
        $data = $request->validated();
        if (!empty($data['pin'])) {
            $data['pin'] = Hash::make($data['pin']);
        }
        $emp->update($data);
        return response()->json(['message' => 'Employee updated', 'data' => $emp]);
    }

    public function activate(int $id)
    {
        Gate::authorize('employees.manage');
        $emp = Employee::findOrFail($id);
        $emp->is_active = true;
        $emp->save();
        return response()->json(['message' => 'Employee activated']);
    }

    public function deactivate(int $id)
    {
        Gate::authorize('employees.manage');
        $emp = Employee::findOrFail($id);
        $emp->is_active = false;
        $emp->save();
        return response()->json(['message' => 'Employee deactivated']);
    }
}

