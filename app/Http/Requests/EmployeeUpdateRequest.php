<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => ['sometimes','string','max:255'],
            'email' => ['sometimes','email','max:255','unique:employees,email,'.$id],
            'phone' => ['sometimes','string','max:50'],
            'pin' => ['nullable','digits_between:4,8'],
            'role' => ['sometimes','in:owner,manager,staff'],
            'is_active' => ['sometimes','boolean']
        ];
    }
}

