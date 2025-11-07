<?php

namespace App\Services;

use App\Models\OptionGroup;
use App\Models\OptionItem;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionItem;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductOptionService
{
    public function optionRules(?Product $product = null): array
    {
        $productExists = (bool) $product;

        return [
            'variant_groups' => ['sometimes', 'array'],
            'variant_groups.*.id' => $productExists ? ['nullable', 'integer', Rule::exists('product_option_groups', 'id')->where(fn ($query) => $query->where('product_id', $product->id)->whereIn('option_group_id', function ($subquery) {
                $subquery->select('id')->from('option_groups')->where('type', 'variant');
            }))] : ['prohibited'],
            'variant_groups.*.option_group_id' => ['sometimes', 'nullable', 'integer', Rule::exists('option_groups', 'id')->where(fn ($q) => $q->where('type', 'variant'))],
            'variant_groups.*.name' => ['required_without:variant_groups.*.option_group_id', 'string', 'max:150'],
            'variant_groups.*.is_required' => ['sometimes', 'boolean'],
            'variant_groups.*.selection_type' => ['sometimes', 'in:single,multiple'],
            'variant_groups.*.min_select' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variant_groups.*.max_select' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'variant_groups.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            'variant_groups.*.variants' => ['sometimes', 'array'],
            'variant_groups.*.variants.*.id' => $productExists ? ['nullable', 'integer', Rule::exists('product_option_items', 'id')->where(fn ($query) => $query->whereIn('product_option_group_id', function ($subquery) use ($product) {
                $subquery->select('id')->from('product_option_groups')->where('product_id', $product->id);
            }))] : ['nullable'],
            'variant_groups.*.variants.*.option_item_id' => ['sometimes', 'nullable', 'integer', Rule::exists('option_items', 'id')],
            'variant_groups.*.variants.*.name' => ['required_without:variant_groups.*.option_group_id', 'string', 'max:150'],
            'variant_groups.*.variants.*.price_adjustment' => ['sometimes', 'numeric'],
            'variant_groups.*.variants.*.stock' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variant_groups.*.variants.*.sku' => ['sometimes', 'nullable', 'string', 'max:120'],
            'variant_groups.*.variants.*.max_quantity' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'variant_groups.*.variants.*.is_default' => ['sometimes', 'boolean'],
            'variant_groups.*.variants.*.is_active' => ['sometimes', 'boolean'],
            'variant_groups.*.variants.*.sort_order' => ['sometimes', 'integer', 'min:0'],

            'addon_groups' => ['sometimes', 'array'],
            'addon_groups.*.id' => $productExists ? ['nullable', 'integer', Rule::exists('product_option_groups', 'id')->where(fn ($query) => $query->where('product_id', $product->id)->whereIn('option_group_id', function ($subquery) {
                $subquery->select('id')->from('option_groups')->where('type', 'addon');
            }))] : ['prohibited'],
            'addon_groups.*.option_group_id' => ['sometimes', 'nullable', 'integer', Rule::exists('option_groups', 'id')->where(fn ($q) => $q->where('type', 'addon'))],
            'addon_groups.*.name' => ['required_without:addon_groups.*.option_group_id', 'string', 'max:150'],
            'addon_groups.*.is_required' => ['sometimes', 'boolean'],
            'addon_groups.*.min_select' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'addon_groups.*.max_select' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'addon_groups.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            'addon_groups.*.addons' => ['sometimes', 'array'],
            'addon_groups.*.addons.*.id' => $productExists ? ['nullable', 'integer', Rule::exists('product_option_items', 'id')->where(fn ($query) => $query->whereIn('product_option_group_id', function ($subquery) use ($product) {
                $subquery->select('id')->from('product_option_groups')->where('product_id', $product->id);
            }))] : ['nullable'],
            'addon_groups.*.addons.*.option_item_id' => ['sometimes', 'nullable', 'integer', Rule::exists('option_items', 'id')],
            'addon_groups.*.addons.*.name' => ['required_without:addon_groups.*.option_group_id', 'string', 'max:150'],
            'addon_groups.*.addons.*.price_adjustment' => ['sometimes', 'numeric'],
            'addon_groups.*.addons.*.use_product_price' => ['sometimes', 'boolean'],
            'addon_groups.*.addons.*.max_quantity' => ['sometimes', 'integer', 'min:1'],
            'addon_groups.*.addons.*.is_default' => ['sometimes', 'boolean'],
            'addon_groups.*.addons.*.is_active' => ['sometimes', 'boolean'],
            'addon_groups.*.addons.*.sort_order' => ['sometimes', 'integer', 'min:0'],

            'preference_groups' => ['sometimes', 'array'],
            'preference_groups.*.id' => $productExists ? ['nullable', 'integer', Rule::exists('product_option_groups', 'id')->where(fn ($query) => $query->where('product_id', $product->id)->whereIn('option_group_id', function ($subquery) {
                $subquery->select('id')->from('option_groups')->where('type', 'preference');
            }))] : ['prohibited'],
            'preference_groups.*.option_group_id' => ['sometimes', 'nullable', 'integer', Rule::exists('option_groups', 'id')->where(fn ($q) => $q->where('type', 'preference'))],
            'preference_groups.*.name' => ['required_without:preference_groups.*.option_group_id', 'string', 'max:150'],
            'preference_groups.*.is_required' => ['sometimes', 'boolean'],
            'preference_groups.*.selection_type' => ['sometimes', 'in:single,multiple'],
            'preference_groups.*.min_select' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'preference_groups.*.max_select' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'preference_groups.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            'preference_groups.*.preferences' => ['sometimes', 'array'],
            'preference_groups.*.preferences.*.id' => $productExists ? ['nullable', 'integer', Rule::exists('product_option_items', 'id')->where(fn ($query) => $query->whereIn('product_option_group_id', function ($subquery) use ($product) {
                $subquery->select('id')->from('product_option_groups')->where('product_id', $product->id);
            }))] : ['nullable'],
            'preference_groups.*.preferences.*.option_item_id' => ['sometimes', 'nullable', 'integer', Rule::exists('option_items', 'id')],
            'preference_groups.*.preferences.*.name' => ['required_without:preference_groups.*.option_group_id', 'string', 'max:150'],
            'preference_groups.*.preferences.*.price_adjustment' => ['sometimes', 'numeric'],
            'preference_groups.*.preferences.*.max_quantity' => ['sometimes', 'integer', 'min:1'],
            'preference_groups.*.preferences.*.is_default' => ['sometimes', 'boolean'],
            'preference_groups.*.preferences.*.is_active' => ['sometimes', 'boolean'],
            'preference_groups.*.preferences.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function attachIntegrityChecks(ValidatorContract $validator, array $payload, ?Product $product = null): void
    {
        $validator->after(function (ValidatorContract $validator) use ($payload) {
            $this->validateOptionGroupsIntegrity($validator, $payload['variant_groups'] ?? [], 'variant');
            $this->validateOptionGroupsIntegrity($validator, $payload['addon_groups'] ?? [], 'addon');
            $this->validateOptionGroupsIntegrity($validator, $payload['preference_groups'] ?? [], 'preference');
        });
    }

    public function syncVariantGroups(Product $product, array $groups, string $syncStatus = 'pending'): void
    {
        $this->syncOptionGroups($product, $groups, 'variant', $syncStatus);
    }

    public function syncAddonGroups(Product $product, array $groups, string $syncStatus = 'pending'): void
    {
        $this->syncOptionGroups($product, $groups, 'addon', $syncStatus);
    }

    public function syncPreferenceGroups(Product $product, array $groups, string $syncStatus = 'pending'): void
    {
        $this->syncOptionGroups($product, $groups, 'preference', $syncStatus);
    }

    /**
     * @deprecated Use syncPreferenceGroups instead.
     */
    public function syncModifierGroups(Product $product, array $groups, string $syncStatus = 'pending'): void
    {
        $this->syncPreferenceGroups($product, $groups, $syncStatus);
    }

    protected function syncOptionGroups(Product $product, array $groups, string $type, string $syncStatus = 'pending'): void
    {
        $groups = array_values($groups);
        $itemsKey = $this->itemsKeyForType($type);

        $pivotIdsToKeep = [];

        foreach ($groups as $index => $groupData) {
            $optionGroup = $this->persistOptionGroup($product, $groupData, $type, $index);
            $pivot = $this->persistProductOptionGroup($product, $optionGroup, $groupData, $index, $syncStatus);
            $pivotIdsToKeep[] = $pivot->id;

            $items = Arr::get($groupData, $itemsKey, []);
            if (! is_array($items)) {
                $items = [];
            }

            $this->syncOptionItems($pivot, $optionGroup, $groupData, $items, $type, $syncStatus);
        }

        $product->optionGroups()
            ->whereHas('optionGroup', fn ($query) => $query->where('type', $type))
            ->whereNotIn('id', $pivotIdsToKeep)
            ->get()
            ->each(function (ProductOptionGroup $group) {
                $group->optionItems()->delete();
                $group->delete();
            });
    }

    protected function persistOptionGroup(Product $product, array $groupData, string $type, int $sortOrder): OptionGroup
    {
        $optionGroupId = Arr::get($groupData, 'option_group_id');
        $name = Arr::get($groupData, 'name');
        $groupKey = $this->groupKeyForType($type);
        $itemsKey = $this->itemsKeyForType($type);

        if ($optionGroupId) {
            $optionGroup = OptionGroup::where('type', $type)->find($optionGroupId);
            if (! $optionGroup) {
                throw ValidationException::withMessages([
                    $groupKey => __('Grup opsi tidak ditemukan.'),
                ]);
            }

            if ($optionGroup->user_id && $optionGroup->user_id !== $product->user_id) {
                throw ValidationException::withMessages([
                    $groupKey => __('Grup opsi tidak dapat digunakan oleh produk ini.'),
                ]);
            }

            if ($optionGroup->outlet_id && $optionGroup->outlet_id !== $product->outlet_id) {
                throw ValidationException::withMessages([
                    $groupKey => __('Grup opsi tidak dapat digunakan oleh produk ini.'),
                ]);
            }
        } elseif (! empty($groupData['id'])) {
            $pivot = ProductOptionGroup::where('id', $groupData['id'])
                ->whereHas('optionGroup', fn ($query) => $query->where('type', $type))
                ->first();
            $optionGroup = $pivot?->optionGroup ?? new OptionGroup(['type' => $type]);
        } else {
            $optionGroup = new OptionGroup(['type' => $type]);
        }

        $payloadIsNewGroup = ! $optionGroupId || $optionGroup->wasRecentlyCreated;

        $optionGroup->fill([
            'user_id' => $optionGroup->user_id ?? $product->user_id,
            'outlet_id' => $optionGroup->outlet_id ?? $product->outlet_id,
            'name' => $name ?? $optionGroup->name,
            'selection_type' => Arr::get($groupData, 'selection_type', $optionGroup->selection_type ?? $this->defaultSelectionType($type)),
            'is_required' => (bool) Arr::get($groupData, 'is_required', $optionGroup->is_required ?? $this->defaultIsRequired($type)),
            'min_select' => $this->normalizeNullableInt(Arr::get($groupData, 'min_select'), $optionGroup->min_select ?? $this->defaultMinSelect($type)),
            'max_select' => $this->normalizeNullableInt(Arr::get($groupData, 'max_select'), $optionGroup->max_select ?? $this->defaultMaxSelect($type)),
            'sort_order' => $this->normalizeInt(Arr::get($groupData, 'sort_order'), $sortOrder),
            'sync_status' => 'pending',
            'last_synced' => null,
            'client_version' => 'web',
        ]);

        if ($type === 'variant') {
            $optionGroup->selection_type = 'single';
            $optionGroup->is_required = true;
            $optionGroup->min_select = 1;
            $optionGroup->max_select = 1;
        }

        $optionGroup->version_id = $optionGroup->exists ? (int) $optionGroup->version_id + 1 : 1;
        $optionGroup->save();
        $optionGroup->refresh();

        $items = Arr::get($groupData, $itemsKey, []);
        if ($payloadIsNewGroup && empty($optionGroup->items)) {
            if (is_array($items)) {
                $this->replaceOptionItems($optionGroup, $items, $type);
            }
        } elseif (is_array($items) && count($items) > 0) {
            $this->replaceOptionItems($optionGroup, $items, $type, true);
        }

        return $optionGroup;
    }

    protected function replaceOptionItems(OptionGroup $optionGroup, array $items, string $type, bool $allowPartialUpdate = false): void
    {
        $items = array_values($items);
        $existingItems = $optionGroup->items()->get()->keyBy('id');
        $processedIds = [];

        $productPriceMap = collect();
        if ($type === 'addon') {
            $productIds = collect($items)->pluck('product_id')->filter()->unique();
            if ($productIds->isNotEmpty()) {
                $productPriceMap = Product::whereIn('id', $productIds)->pluck('price', 'id');
            }
        }

        foreach ($items as $index => $itemData) {
            $optionItemId = Arr::get($itemData, 'option_item_id');
            $optionItem = null;

            if ($optionItemId) {
                $optionItem = $existingItems->get($optionItemId) ?? OptionItem::where('option_group_id', $optionGroup->id)->find($optionItemId);
            }

            if (! $optionItem) {
                if ($allowPartialUpdate && ! empty($itemData['id'])) {
                    $pivotItem = ProductOptionItem::find($itemData['id']);
                    if ($pivotItem && $pivotItem->option_item_id) {
                        $optionItem = OptionItem::find($pivotItem->option_item_id);
                    }
                }
            }

            if (! $optionItem) {
                if ($allowPartialUpdate && $optionGroup->items()->exists() && empty($itemData['name'])) {
                    // Skip creating new item when updating shared group without explicit data.
                    continue;
                }
                $optionItem = new OptionItem(['option_group_id' => $optionGroup->id]);
            }

            $priceAdjustment = $type === 'preference'
                ? 0
                : (int) round(Arr::get($itemData, 'price_adjustment', $optionItem->price_adjustment ?? 0));

            $useProductPrice = $type === 'addon' && (bool) Arr::get($itemData, 'use_product_price', $optionItem->use_product_price);
            $productId = $type === 'addon'
                ? $this->normalizeNullableInt(Arr::get($itemData, 'product_id'), $optionItem->product_id)
                : null;

            if ($useProductPrice && ! $productId) {
                $useProductPrice = false;
            }

            if ($useProductPrice && $productId) {
                $priceAdjustment = (int) ($productPriceMap[$productId] ?? $priceAdjustment);
            }

            $stockValue = $type === 'preference'
                ? null
                : $this->normalizeNullableInt(Arr::get($itemData, 'stock'), $optionItem->stock);

            $optionItem->fill([
                'option_group_id' => $optionGroup->id,
                'name' => Arr::get($itemData, 'name', $optionItem->name),
                'price_adjustment' => $priceAdjustment,
                'stock' => $stockValue,
                'sku' => Arr::get($itemData, 'sku', $optionItem->sku),
                'product_id' => $type === 'addon'
                    ? $productId
                    : null,
                'use_product_price' => $type === 'addon'
                    ? $useProductPrice
                    : false,
                'max_quantity' => $this->normalizeInt(Arr::get($itemData, 'max_quantity'), $optionItem->max_quantity ?? ($type === 'variant' ? 1 : 1), 1),
                'is_default' => (bool) Arr::get($itemData, 'is_default', $optionItem->is_default ?? false),
                'is_active' => (bool) Arr::get($itemData, 'is_active', $optionItem->is_active ?? true),
                'sort_order' => $this->normalizeInt(Arr::get($itemData, 'sort_order'), $index),
                'sync_status' => 'pending',
                'last_synced' => null,
                'client_version' => 'web',
            ]);

            $optionItem->version_id = $optionItem->exists ? (int) $optionItem->version_id + 1 : 1;
            $optionItem->save();
            $processedIds[] = $optionItem->id;
        }

        if (! $allowPartialUpdate) {
            $optionGroup->items()->whereNotIn('id', $processedIds)->each(function (OptionItem $item) use ($optionGroup) {
                $item->delete();
            });
        }
    }

    protected function persistProductOptionGroup(Product $product, OptionGroup $optionGroup, array $groupData, int $sortOrder, string $syncStatus): ProductOptionGroup
    {
        $pivot = null;
        if (! empty($groupData['id'])) {
            $pivot = $product->optionGroups()->where('id', $groupData['id'])->first();
        }

        if (! $pivot) {
            $pivot = new ProductOptionGroup([
                'product_id' => $product->id,
                'option_group_id' => $optionGroup->id,
            ]);
        } else {
            $pivot->option_group_id = $optionGroup->id;
        }

        $pivot->fill([
            'sort_order' => $this->normalizeInt(Arr::get($groupData, 'sort_order'), $sortOrder),
            'is_required_override' => Arr::has($groupData, 'is_required') ? (bool) Arr::get($groupData, 'is_required') : $pivot->is_required_override,
            'selection_type_override' => Arr::get($groupData, 'selection_type', $pivot->selection_type_override),
            'min_select_override' => $this->normalizeNullableInt(Arr::get($groupData, 'min_select'), $pivot->min_select_override),
            'max_select_override' => $this->normalizeNullableInt(Arr::get($groupData, 'max_select'), $pivot->max_select_override),
            'sync_status' => $syncStatus,
            'last_synced' => $syncStatus === 'synced' ? now() : null,
            'client_version' => 'web',
        ]);

        if (! in_array($pivot->selection_type_override, ['single', 'multiple'], true)) {
            $pivot->selection_type_override = null;
        }

        $groupType = $optionGroup->type ?? Arr::get($groupData, 'type');
        if ($groupType === 'variant') {
            $pivot->is_required_override = true;
            $pivot->selection_type_override = 'single';
            $pivot->min_select_override = 1;
            $pivot->max_select_override = 1;
        }

        $pivot->version_id = $pivot->exists ? (int) $pivot->version_id + 1 : 1;
        $pivot->save();

        return $pivot->fresh(['optionItems.optionItem.product']);
    }

    protected function syncOptionItems(ProductOptionGroup $pivot, OptionGroup $optionGroup, array $groupData, array $items, string $type, string $syncStatus): void
    {
        $existingPivotItems = $pivot->optionItems()->get()->keyBy('id');
        $processedPivotIds = [];
        $groupKey = $this->groupKeyForType($type);

        $usingSharedGroup = ! empty($groupData['option_group_id']);

        if (empty($items)) {
            $this->ensurePivotContainsAllGroupItems($pivot, $optionGroup, $syncStatus);
            return;
        }

        foreach ($items as $index => $itemData) {
            $pivotItem = null;
            if (! empty($itemData['id'])) {
                $pivotItem = $existingPivotItems->get($itemData['id']);
            }

            $optionItem = null;
            $optionItemId = Arr::get($itemData, 'option_item_id');
            if ($optionItemId) {
                $optionItem = OptionItem::where('option_group_id', $optionGroup->id)->find($optionItemId);
                if (! $optionItem) {
                    throw ValidationException::withMessages([
                        $groupKey => __('Item opsi tidak valid untuk grup terpilih.'),
                    ]);
                }
            } elseif ($pivotItem && $pivotItem->option_item_id) {
                $optionItem = $pivotItem->optionItem;
            }

            if (! $optionItem) {
                if ($usingSharedGroup) {
                    throw ValidationException::withMessages([
                        $groupKey => __('Item opsi tidak valid untuk grup terpilih.'),
                    ]);
                }

                $optionItem = new OptionItem(['option_group_id' => $optionGroup->id]);
            }

            if (! $usingSharedGroup) {
                $priceAdjustment = $type === 'preference'
                    ? 0
                    : (int) round(Arr::get($itemData, 'price_adjustment', $optionItem->price_adjustment ?? 0));

                $stockValue = $type === 'preference'
                    ? null
                    : $this->normalizeNullableInt(Arr::get($itemData, 'stock'), $optionItem->stock);

                $optionItem->fill([
                    'option_group_id' => $optionGroup->id,
                    'name' => Arr::get($itemData, 'name', $optionItem->name),
                    'price_adjustment' => $priceAdjustment,
                    'stock' => $stockValue,
                    'sku' => Arr::get($itemData, 'sku', $optionItem->sku),
                    'max_quantity' => $this->normalizeInt(Arr::get($itemData, 'max_quantity'), $optionItem->max_quantity ?? 1, 1),
                    'is_default' => (bool) Arr::get($itemData, 'is_default', $optionItem->is_default ?? false),
                    'is_active' => (bool) Arr::get($itemData, 'is_active', $optionItem->is_active ?? true),
                    'sort_order' => $this->normalizeInt(Arr::get($itemData, 'sort_order'), $index),
                    'sync_status' => 'pending',
                    'last_synced' => null,
                    'client_version' => 'web',
                ]);
                $optionItem->version_id = $optionItem->exists ? (int) $optionItem->version_id + 1 : 1;
                $optionItem->save();
            }

            if (! $pivotItem) {
                $pivotItem = new ProductOptionItem([
                    'product_option_group_id' => $pivot->id,
                    'option_item_id' => $optionItem->id,
                ]);
            } else {
                $pivotItem->option_item_id = $optionItem->id;
            }

            $priceOverride = $type === 'preference'
                ? 0
                : ($usingSharedGroup
                    ? $this->normalizeNullableInt(Arr::get($itemData, 'price_adjustment_override'), null)
                    : $this->normalizeNullableInt(Arr::get($itemData, 'price_adjustment_override'), $pivotItem->price_adjustment_override));

            $stockOverride = $type === 'preference'
                ? null
                : ($usingSharedGroup
                    ? $this->normalizeNullableInt(Arr::get($itemData, 'stock'), null)
                    : $this->normalizeNullableInt(Arr::get($itemData, 'stock_override'), $pivotItem->stock_override));

            $pivotItem->fill([
                'sort_order' => $this->normalizeInt(Arr::get($itemData, 'sort_order'), $index),
                'price_adjustment_override' => $priceOverride,
                'stock_override' => $stockOverride,
                'sku_override' => $usingSharedGroup ? Arr::get($itemData, 'sku', null) : Arr::get($itemData, 'sku_override', $pivotItem->sku_override),
                'max_quantity_override' => $usingSharedGroup ? $this->normalizeNullableInt(Arr::get($itemData, 'max_quantity'), null) : $this->normalizeNullableInt(Arr::get($itemData, 'max_quantity_override'), $pivotItem->max_quantity_override),
                'is_default_override' => $this->resolveOverrideBoolean($itemData, 'is_default', $usingSharedGroup ? $pivotItem->is_default_override : $pivotItem->is_default_override),
                'is_active_override' => $this->resolveOverrideBoolean($itemData, 'is_active', $usingSharedGroup ? $pivotItem->is_active_override : $pivotItem->is_active_override),
                'sync_status' => $syncStatus,
                'last_synced' => $syncStatus === 'synced' ? now() : null,
                'client_version' => 'web',
            ]);
            $pivotItem->version_id = $pivotItem->exists ? (int) $pivotItem->version_id + 1 : 1;
            $pivotItem->save();

            $processedPivotIds[] = $pivotItem->id;
        }

        $pivot->optionItems()
            ->whereNotIn('id', $processedPivotIds)
            ->delete();
    }

    protected function ensurePivotContainsAllGroupItems(ProductOptionGroup $pivot, OptionGroup $optionGroup, string $syncStatus): void
    {
        $existing = $pivot->optionItems()->get()->keyBy('option_item_id');
        foreach ($optionGroup->items as $index => $item) {
            $pivotItem = $existing->get($item->id) ?? new ProductOptionItem([
                'product_option_group_id' => $pivot->id,
                'option_item_id' => $item->id,
            ]);
            $pivotItem->fill([
                'sort_order' => $index,
                'sync_status' => $syncStatus,
                'last_synced' => $syncStatus === 'synced' ? now() : null,
                'client_version' => 'web',
            ]);
            $pivotItem->version_id = $pivotItem->exists ? (int) $pivotItem->version_id + 1 : 1;
            $pivotItem->save();
        }
    }

    protected function validateOptionGroupsIntegrity(ValidatorContract $validator, array $groups, string $type): void
    {
        $itemsKey = $this->itemsKeyForType($type);
        $groupKey = $this->groupKeyForType($type);

        foreach ($groups as $groupIndex => $group) {
            $optionGroupId = Arr::get($group, 'option_group_id');
            $items = Arr::get($group, $itemsKey, []);
            $groupPath = "{$groupKey}.{$groupIndex}";

            if (! $optionGroupId && (! is_array($items) || count($items) < 1)) {
                $validator->errors()->add("{$groupPath}.{$itemsKey}", __('Minimal satu opsi diperlukan.'));
                continue;
            }

            $min = Arr::get($group, 'min_select');
            $max = Arr::get($group, 'max_select');
            if ($min !== null && $max !== null && (int) $min > (int) $max) {
                $validator->errors()->add("{$groupPath}.min_select", __('Jumlah minimum tidak boleh melebihi maksimum.'));
            }

            $selectionType = Arr::get($group, 'selection_type', $this->defaultSelectionType($type));
            if ($selectionType === 'single') {
                if ($max !== null && (int) $max > 1) {
                    $validator->errors()->add("{$groupPath}.max_select", __('Pemilihan single maksimal 1.'));
                }
                if ($min !== null && (int) $min > 1) {
                    $validator->errors()->add("{$groupPath}.min_select", __('Pemilihan single minimal tidak boleh lebih dari 1.'));
                }
            }

            if (! is_array($items)) {
                continue;
            }

            $defaults = 0;
            foreach ($items as $itemIndex => $item) {
                $name = Arr::get($item, 'name');
                if (! $optionGroupId && (! $name || trim((string) $name) === '')) {
                    $validator->errors()->add("{$groupPath}.{$itemsKey}.{$itemIndex}.name", __('Nama opsi wajib diisi.'));
                }
                if (Arr::get($item, 'is_default')) {
                    $defaults++;
                }
            }

            if ($defaults > 1) {
                $validator->errors()->add("{$groupPath}.{$itemsKey}", __('Hanya satu opsi yang boleh menjadi default.'));
            }
        }
    }

    protected function groupKeyForType(string $type): string
    {
        switch ($type) {
            case 'variant':
                return 'variant_groups';
            case 'addon':
                return 'addon_groups';
            case 'preference':
                return 'preference_groups';
            default:
                return "{$type}_groups";
        }
    }

    protected function itemsKeyForType(string $type): string
    {
        switch ($type) {
            case 'variant':
                return 'variants';
            case 'addon':
                return 'addons';
            case 'preference':
                return 'preferences';
            default:
                return "{$type}_items";
        }
    }

    protected function defaultSelectionType(string $type): string
    {
        return $type === 'variant' ? 'single' : 'multiple';
    }

    protected function defaultIsRequired(string $type): bool
    {
        return $type === 'variant';
    }

    protected function defaultMinSelect(string $type): int
    {
        return $type === 'variant' ? 1 : 0;
    }

    protected function defaultMaxSelect(string $type): ?int
    {
        return $type === 'variant' ? 1 : null;
    }

    protected function normalizeInt($value, int $default = 0, ?int $min = null): int
    {
        $normalized = is_numeric($value) ? (int) $value : $default;
        if ($min !== null && $normalized < $min) {
            return $min;
        }

        return $normalized;
    }

    protected function normalizeNullableInt($value, $default = null): ?int
    {
        if ($value === null || $value === '') {
            return $default === null ? null : (int) $default;
        }

        return (int) $value;
    }

    protected function resolveOverrideBoolean(array $itemData, string $key, ?bool $current): ?bool
    {
        if (Arr::has($itemData, $key)) {
            return (bool) Arr::get($itemData, $key);
        }

        $overrideKey = $key . '_override';
        if (Arr::exists($itemData, $overrideKey)) {
            $value = Arr::get($itemData, $overrideKey);
            if ($value === null || $value === '') {
                return null;
            }

            return (bool) $value;
        }

        return $current;
    }
}
