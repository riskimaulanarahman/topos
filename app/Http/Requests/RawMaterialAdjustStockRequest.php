<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RawMaterialAdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'qty_change' => ['required','numeric','not_in:0'],
            'unit_cost' => ['nullable','numeric','min:0'],
            'notes' => ['nullable','string'],
        ];
    }
}

