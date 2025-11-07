<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRecipeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'yield_qty' => ['required','numeric','min:0.0001'],
            'unit' => ['nullable','string','max:20'],
            'items' => ['required','array','min:1'],
            'items.*.raw_material_id' => ['required','exists:raw_materials,id'],
            'items.*.qty_per_yield' => ['required','numeric','min:0.0001'],
            'items.*.waste_pct' => ['nullable','numeric','min:0','max:100'],
            'notes' => ['nullable','string']
        ];
    }
}

