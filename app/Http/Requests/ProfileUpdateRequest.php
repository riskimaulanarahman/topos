<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['operating_hours', 'store_addresses', 'map_links'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $lines = preg_split("/\r\n|\n|\r/", $value);
                $this->merge([
                    $field => array_filter(array_map('trim', $lines ?? []), fn ($line) => $line !== ''),
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'store_name' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'store_description' => ['nullable', 'string'],
            'operating_hours' => ['nullable', 'array'],
            'operating_hours.*' => ['nullable', 'string', 'max:255'],
            'store_addresses' => ['nullable', 'array'],
            'store_addresses.*' => ['nullable', 'string', 'max:255'],
            'map_links' => ['nullable', 'array'],
            'map_links.*' => ['nullable', 'url', 'max:2048'],
            'store_logo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
