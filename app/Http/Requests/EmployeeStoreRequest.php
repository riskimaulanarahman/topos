<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:employees,email'],
            'phone' => ['nullable','string','max:50'],
            'pin' => ['required','digits_between:4,8'],
            'role' => ['required','in:owner,manager,staff'],
            'is_active' => ['sometimes','boolean']
        ];
    }
}

