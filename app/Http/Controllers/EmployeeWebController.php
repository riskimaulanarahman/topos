<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeWebController extends Controller
{
    public function index()
    {
        $employees = Employee::orderBy('name')->paginate(15);
        return view('pages.employees.index', compact('employees'));
    }

    public function create()
    {
        return view('pages.employees.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','unique:employees,email'],
            'phone' => ['nullable','string','max:50'],
            'pin' => ['required','digits_between:4,8'],
            'role' => ['required','in:owner,manager,staff'],
            'is_active' => ['sometimes','boolean'],
        ]);
        $data['pin'] = Hash::make($data['pin']);
        Employee::create($data);
        return redirect()->route('employees.index')->with('success','Karyawan dibuat');
    }

    public function edit(Employee $employee)
    {
        return view('pages.employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','unique:employees,email,'.$employee->id],
            'phone' => ['nullable','string','max:50'],
            'pin' => ['nullable','digits_between:4,8'],
            'role' => ['required','in:owner,manager,staff'],
            'is_active' => ['sometimes','boolean'],
        ]);
        if (!empty($data['pin'])) {
            $data['pin'] = Hash::make($data['pin']);
        } else {
            unset($data['pin']);
        }
        $employee->update($data);
        return redirect()->route('employees.index')->with('success','Karyawan diperbarui');
    }

    public function activate(Employee $employee)
    {
        $employee->is_active = true; $employee->save();
        return back()->with('success','Diaktifkan');
    }

    public function deactivate(Employee $employee)
    {
        $employee->is_active = false; $employee->save();
        return back()->with('success','Dinonaktifkan');
    }
}

