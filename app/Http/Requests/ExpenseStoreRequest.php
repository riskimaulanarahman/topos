<?php

namespace App\Http\Requests;

use App\Models\RawMaterial;
use App\Support\OutletContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ExpenseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $rawItems = $this->input('items');
        if (is_string($rawItems)) {
            $decoded = json_decode($rawItems, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['items' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'date' => ['required','date'],
            'amount' => ['nullable','numeric','min:0'],
            'category_id' => ['nullable','exists:expense_categories,id'],
            'vendor' => ['nullable','string'],
            'notes' => ['nullable','string'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:5120'],
            'items' => ['required','array','min:1'],
            'items.*.raw_material_id' => ['nullable','exists:raw_materials,id'],
            'items.*.description' => ['nullable','string','max:255'],
            'items.*.unit' => ['nullable','string','max:50'],
            'items.*.qty' => ['required','numeric','min:0.0001'],
            'items.*.item_price' => ['nullable','numeric','min:0'],
            'items.*.unit_cost' => ['nullable','numeric','min:0'],
            'items.*.notes' => ['nullable','string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->ensureAccessibleRawMaterials($validator);
        });
    }

    private function ensureAccessibleRawMaterials(Validator $validator): void
    {
        $rawMaterialIds = collect($this->input('items', []))
            ->pluck('raw_material_id')
            ->filter()
            ->unique()
            ->values();

        if ($rawMaterialIds->isEmpty()) {
            return;
        }

        $user = $this->user();
        $role = OutletContext::currentRole();
        $outlet = OutletContext::currentOutlet();

        if (! $user || ! $role || ! $outlet || $role->role !== 'partner') {
            return;
        }

        $permittedIds = RawMaterial::query()
            ->accessibleBy($user)
            ->whereIn('id', $rawMaterialIds)
            ->pluck('id');

        $invalid = $rawMaterialIds->diff($permittedIds);

        if ($invalid->isNotEmpty()) {
            $validator->errors()->add('items', __('Beberapa bahan baku tidak tersedia untuk kategori yang ditugaskan kepada Anda.'));
        }
    }
}
