<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RawMaterialPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'qty' => ['required','numeric','gt:0'],
            'unit_cost' => ['required','numeric','min:0'],
            'notes' => ['nullable','string'],
            'occurred_at' => ['nullable','date'],
        ];
    }
}

