<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncomeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'date' => ['sometimes','date'],
            'amount' => ['sometimes','numeric','min:0.01'],
            'category_id' => ['nullable','exists:income_categories,id'],
            'notes' => ['nullable','string'],
        ];
    }
}

