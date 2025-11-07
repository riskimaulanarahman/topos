<?php

namespace App\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOutletPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pin' => ['nullable', 'string', 'digits:6', 'confirmed'],
            'pin_confirmation' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pin' => $this->filled('pin') ? $this->input('pin') : null,
            'pin_confirmation' => $this->filled('pin_confirmation') ? $this->input('pin_confirmation') : null,
        ]);
    }
}
