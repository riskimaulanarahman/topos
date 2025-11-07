<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RawMaterialOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'counted_qty' => ['required','numeric','min:0'],
            'notes' => ['nullable','string'],
            'occurred_at' => ['nullable','date'],
        ];
    }
}

