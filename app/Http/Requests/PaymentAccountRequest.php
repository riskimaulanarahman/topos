<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accountId = $this->route('payment_account')?->id ?? $this->route('payment_account');

        return [
            'label' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payment_accounts', 'account_number')->ignore($accountId),
            ],
            'account_holder' => ['nullable', 'string', 'max:100'],
            'channel' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $sortOrder = $this->input('sort_order');

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $sortOrder === null || $sortOrder === '' ? 0 : (int) $sortOrder,
        ]);
    }
}
