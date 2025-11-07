<?php

namespace App\Http\Requests;

use App\Models\RawMaterial;
use App\Services\PartnerCategoryAccessService;
use App\Support\OutletContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RawMaterialUpdateRequest extends FormRequest
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
        $rawMaterialId = $this->resolveRawMaterialId();

        return [
            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('raw_materials', 'sku')->ignore($rawMaterialId),
            ],
            'name' => ['sometimes','string','max:255'],
            'unit' => ['sometimes','exists:units,code'],
            'min_stock' => ['sometimes','numeric','min:0'],
            'category_ids' => ['sometimes','array','min:1'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at');

                    $outlet = OutletContext::currentOutlet();
                    if ($outlet) {
                        $query->where(function ($scoped) use ($outlet) {
                            $scoped->where('outlet_id', $outlet->id)
                                ->orWhereNull('outlet_id');
                        });
                    }
                }),
            ],
        ];
    }

    protected function passedValidation(): void
    {
        $restricted = $this->restrictedCategoryIds();
        if (! is_array($restricted)) {
            return;
        }

        $categoryIds = $this->validated('category_ids', null);

        if ($categoryIds === null) {
            return;
        }

        $invalid = array_diff($categoryIds, $restricted);

        if (! empty($invalid)) {
            $this->failedAuthorization();
        }
    }

    private function resolveRawMaterialId(): ?int
    {
        $raw = $this->route('raw_material') ?? $this->route('id');

        if ($raw instanceof RawMaterial) {
            return $raw->id;
        }

        if (is_numeric($raw)) {
            return (int) $raw;
        }

        return null;
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
