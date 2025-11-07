<?php

namespace App\Http\Requests;

use App\Services\PartnerCategoryAccessService;
use App\Support\OutletContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RawMaterialStoreRequest extends FormRequest
{
    private ?array $restrictedCategoryIds = null;

    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $restricted = $this->restrictedCategoryIds();

        if (is_array($restricted) && empty($restricted)) {
            return false;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sku') && trim((string) $this->input('sku')) === '') {
            $this->merge(['sku' => null]);
        }
        if ($this->has('min_stock') && $this->input('min_stock') === '') {
            $this->merge(['min_stock' => null]);
        }

        if ($this->has('category_ids')) {
            $categoryIds = array_map(function ($value) {
                if (is_numeric($value)) {
                    return (int) $value;
                }

                return $value;
            }, (array) $this->input('category_ids'));

            $categoryIds = array_values(array_filter($categoryIds, static function ($value) {
                return $value !== null && $value !== '';
            }));

            $this->merge(['category_ids' => $categoryIds]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'sku' => ['nullable','string','max:50','unique:raw_materials,sku'],
            'name' => ['required','string','max:255'],
            'unit' => ['required','exists:units,code'],
            'min_stock' => ['nullable','numeric','min:0'],
            'category_ids' => ['required','array','min:1'],
            'category_ids.*' => ['integer','distinct'],
        ];

        $outlet = OutletContext::currentOutlet();
        $categoryRule = Rule::exists('categories', 'id')
            ->where(fn ($query) => $query->whereNull('deleted_at'));

        if ($outlet) {
            $categoryRule = $categoryRule->where(fn ($query) => $query
                ->where(function ($scoped) use ($outlet) {
                    $scoped->where('outlet_id', $outlet->id)
                        ->orWhereNull('outlet_id');
                }));
        }

        $rules['category_ids.*'][] = $categoryRule;

        $restricted = $this->restrictedCategoryIds();
        if (is_array($restricted)) {
            $rules['category_ids.*'][] = Rule::in($restricted);
        }

        return $rules;
    }

    private function restrictedCategoryIds(): ?array
    {
        if ($this->restrictedCategoryIds !== null) {
            return $this->restrictedCategoryIds;
        }

        $role = OutletContext::currentRole();
        $outlet = OutletContext::currentOutlet();
        $user = $this->user();

        if (! $role || ! $outlet || ! $user) {
            return $this->restrictedCategoryIds = null;
        }

        if ($role->role !== 'partner') {
            return $this->restrictedCategoryIds = null;
        }

        /** @var PartnerCategoryAccessService $service */
        $service = app(PartnerCategoryAccessService::class);
        $categoryIds = $service->accessibleCategoryIdsFor($user, $outlet);

        if ($categoryIds === ['*']) {
            return $this->restrictedCategoryIds = null;
        }

        return $this->restrictedCategoryIds = array_values($categoryIds);
    }
}
