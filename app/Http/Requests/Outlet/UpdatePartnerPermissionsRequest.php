<?php

namespace App\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'can_manage_stock' => ['required', 'boolean'],
            'can_manage_expense' => ['required', 'boolean'],
            'can_manage_sales' => ['required', 'boolean'],
        ];
    }

    public function permissions(): array
    {
        return [
            'can_manage_stock' => $this->boolean('can_manage_stock'),
            'can_manage_expense' => $this->boolean('can_manage_expense'),
            'can_manage_sales' => $this->boolean('can_manage_sales'),
        ];
    }
}
