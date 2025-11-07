<?php

namespace App\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;

class PartnerCategoryRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'add' => ['array'],
            'add.*' => ['integer', 'exists:categories,id'],
            'remove' => ['array'],
            'remove.*' => ['integer', 'exists:categories,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function validatedPayload(): array
    {
        $data = $this->validated();

        return [
            'add' => array_values(array_unique($data['add'] ?? [])),
            'remove' => array_values(array_unique($data['remove'] ?? [])),
            'notes' => $data['notes'] ?? null,
        ];
    }
}
