<?php

namespace App\Http\Requests;

use App\Models\Outlet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogDuplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $sourceId = (int) $this->input('source_outlet_id');
        $targetId = (int) $this->input('target_outlet_id');

        if ($sourceId === 0 || $targetId === 0) {
            return false;
        }

        return $user->ownedOutlets()->whereIn('outlets.id', [$sourceId, $targetId])->count() === 2;
    }

    public function rules(): array
    {
        return [
            'source_outlet_id' => ['required', 'integer', Rule::exists(Outlet::class, 'id')],
            'target_outlet_id' => ['required', 'integer', Rule::exists(Outlet::class, 'id'), 'different:source_outlet_id'],
            'resources' => ['required', 'array'],
            'resources.categories' => ['nullable'],
            'resources.raw_materials' => ['nullable'],
            'resources.products' => ['nullable'],
            'resources.category_ids' => ['array'],
            'resources.category_ids.*' => ['integer'],
            'resources.raw_material_ids' => ['array'],
            'resources.raw_material_ids.*' => ['integer'],
            'resources.product_ids' => ['array'],
            'resources.product_ids.*' => ['integer'],
            'options' => ['array'],
            'options.copy_stock' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $resources = $this->input('resources', []);

        foreach (['categories', 'raw_materials', 'products'] as $flag) {
            if (array_key_exists($flag, $resources)) {
                $resources[$flag] = (bool) $resources[$flag];
            }
        }

        foreach (['category_ids', 'raw_material_ids', 'product_ids'] as $listKey) {
            if (! array_key_exists($listKey, $resources) || ! is_array($resources[$listKey])) {
                $resources[$listKey] = [];
            }
        }

        $options = $this->input('options', []);
        if (array_key_exists('copy_stock', $options)) {
            $options['copy_stock'] = (bool) $options['copy_stock'];
        }

        $this->merge([
            'resources' => $resources,
            'options' => $options,
        ]);
    }
}
