<?php

namespace App\Http\Requests\Outlet;

use App\Models\Outlet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvitePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Outlet|null $outlet */
        $outlet = $this->route('outlet');

        return $outlet !== null && $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'can_manage_stock' => ['sometimes', 'boolean'],
            'can_manage_expense' => ['sometimes', 'boolean'],
            'can_manage_sales' => ['sometimes', 'boolean'],
        ];
    }

    public function permissions(): array
    {
        return [
            'can_manage_stock' => (bool) $this->boolean('can_manage_stock'),
            'can_manage_expense' => (bool) $this->boolean('can_manage_expense'),
            'can_manage_sales' => (bool) $this->boolean('can_manage_sales'),
        ];
    }
}
