<?php

namespace App\Http\Requests;

use App\Support\OutletContext;
use Illuminate\Foundation\Http\FormRequest;

class RawMaterialTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = OutletContext::currentRole();

        return $role && $role->role === 'owner';
    }

    public function rules(): array
    {
        return [
            'destination_outlet_id' => ['required', 'integer'],
            'destination_raw_material_id' => ['required', 'integer'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}

