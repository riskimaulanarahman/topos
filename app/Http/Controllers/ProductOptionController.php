<?php

namespace App\Http\Controllers;

use App\Models\OptionGroup;
use App\Models\OptionItem;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionItem;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class ProductOptionController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageProducts($context);

        $type = $request->string('type')->toString();
        if (! in_array($type, ['variant', 'addon', 'preference'], true)) {
            $type = 'variant';
        }

        $optionGroups = OptionGroup::with(['items.product'])
            ->where('type', $type)
            ->where(function ($query) use ($context) {
                $ownerIds = $context['owner_user_ids'];
                $outlet = $context['outlet'];

                $query->whereNull('user_id');

                if (! empty($ownerIds)) {
                    $query->orWhereIn('user_id', $ownerIds);
                }

                if ($outlet) {
                    $query->orWhere('outlet_id', $outlet->id);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        $productHasSku = Schema::hasColumn('products', 'sku');

        return view('pages.product-options.index', [
            'optionGroups' => $optionGroups,
            'type' => $type,
            'activeOutlet' => $context['outlet'],
            'productHasSku' => $productHasSku,
        ]);
    }

    public function create(Request $request)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageProducts($context);

        $type = $request->string('type')->toString();
        if (! in_array($type, ['variant', 'addon', 'preference'], true)) {
            $type = 'variant';
        }

        return view('pages.product-options.create', [
            'type' => $type,
            'activeOutlet' => $context['outlet'],
            'products' => $this->getSelectableProducts($context),
        ]);
    }

    public function store(Request $request)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageProducts($context);

        [$type, $validator] = $this->makeGroupValidator($request);

        $validator->validate();
        $payload = $validator->validated();

        $optionGroup = DB::transaction(function () use ($payload, $context, $type) {
            $outlet = $context['outlet'];
            $userId = auth()->id();

            $optionGroup = new OptionGroup([
                'user_id' => $userId,
                'outlet_id' => $outlet?->id,
                'name' => $payload['name'],
                'type' => $type,
                'selection_type' => $this->resolveSelectionType($type, $payload['selection_type'] ?? null),
                'is_required' => (bool) ($payload['is_required'] ?? ($type === 'variant')),
                'min_select' => $this->normalizeNullableInt($payload['min_select'] ?? null, $type === 'variant' ? 1 : 0),
                'max_select' => $this->normalizeNullableInt($payload['max_select'] ?? null, $type === 'variant' ? 1 : null),
                'sort_order' => $this->resolveNextSortOrder($context, $type),
                'sync_status' => 'pending',
                'last_synced' => null,
                'client_version' => 'web',
                'version_id' => 1,
            ]);

            $optionGroup->save();

            $this->syncOptionItems($optionGroup, $payload['items'], $type);

            return $optionGroup->fresh('items');
        });

        return redirect()
            ->route('product-options.index', ['type' => $optionGroup->type])
            ->with('success', __('Opsi produk berhasil dibuat.'));
    }

    public function edit(OptionGroup $productOption)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageProducts($context);
        $this->authorizeOptionGroup($productOption, $context);

        $productOption->load('items.product');

        return view('pages.product-options.edit', [
            'optionGroup' => $productOption,
            'type' => $productOption->type,
            'activeOutlet' => $context['outlet'],
            'products' => $this->getSelectableProducts($context),
        ]);
    }

    public function update(Request $request, OptionGroup $productOption)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageProducts($context);
        $this->authorizeOptionGroup($productOption, $context);

        [$type, $validator] = $this->makeGroupValidator($request, $productOption);

        $validator->validate();
        $payload = $validator->validated();

        DB::transaction(function () use ($productOption, $payload, $type) {
            $productOption->fill([
                'name' => $payload['name'],
                'selection_type' => $this->resolveSelectionType($type, $payload['selection_type'] ?? null),
                'is_required' => (bool) ($payload['is_required'] ?? ($type === 'variant')),
                'min_select' => $this->normalizeNullableInt($payload['min_select'] ?? null, $type === 'variant' ? 1 : 0),
                'max_select' => $this->normalizeNullableInt($payload['max_select'] ?? null, $type === 'variant' ? 1 : null),
                'sync_status' => 'pending',
                'last_synced' => null,
                'client_version' => 'web',
            ]);
            $productOption->version_id = (int) $productOption->version_id + 1;
            $productOption->save();

            $this->syncOptionItems($productOption, $payload['items'], $type, true);
            $this->markAssignmentsPending($productOption);
        });

        return redirect()
            ->route('product-options.index', ['type' => $productOption->type])
            ->with('success', __('Opsi produk berhasil diperbarui.'));
    }

    public function destroy(OptionGroup $productOption)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageProducts($context);
        $this->authorizeOptionGroup($productOption, $context);

        DB::transaction(function () use ($productOption) {
            $productOption->items()->delete();
            $productOption->productAssignments()->each(function (ProductOptionGroup $assignment) {
                $assignment->optionItems()->delete();
                $assignment->delete();
            });
            $productOption->delete();
        });

        return redirect()
            ->route('product-options.index', ['type' => $productOption->type])
            ->with('success', __('Opsi produk berhasil dihapus.'));
    }

    protected function makeGroupValidator(Request $request, ?OptionGroup $group = null): array
    {
        $input = $request->all();
        $type = $group?->type ?? Arr::get($input, 'type', 'variant');
        if (! in_array($type, ['variant', 'addon', 'preference'], true)) {
            $type = 'variant';
        }
        $context = $this->resolveOutletContext();

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['variant', 'addon', 'preference'])],
            'selection_type' => ['nullable', Rule::in(['single', 'multiple'])],
            'is_required' => ['sometimes', 'boolean'],
            'min_select' => ['nullable', 'integer', 'min:0'],
            'max_select' => ['nullable', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:150'],
            'items.*.price_adjustment' => ['nullable', 'numeric'],
            'items.*.stock' => ['nullable', 'integer', 'min:0'],
            'items.*.sku' => ['nullable', 'string', 'max:120'],
            'items.*.max_quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.is_default' => ['sometimes', 'boolean'],
            'items.*.is_active' => ['sometimes', 'boolean'],
            'items.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ];

        if ($type === 'addon') {
            $rules['items.*.product_id'] = [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) use ($context) {
                    $outlet = $context['outlet'];
                    $ownerIds = $context['owner_user_ids'];
                    if ($outlet) {
                        $query->where('outlet_id', $outlet->id);
                    } elseif (! empty($ownerIds)) {
                        $query->whereIn('user_id', $ownerIds);
                    }
                }),
            ];
            $rules['items.*.use_product_price'] = ['sometimes', 'boolean'];
        }

        if ($group) {
            $rules['type'] = ['required', Rule::in([$group->type])];
            $rules['items.*.id'] = [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('option_items', 'id')->where(fn ($query) => $query->where('option_group_id', $group->id)),
            ];
        }

        $validator = Validator::make($input, $rules, [], [
            'name' => __('Nama opsi'),
            'selection_type' => __('Tipe pilihan'),
            'min_select' => __('Minimal pilih'),
            'max_select' => __('Maksimal pilih'),
            'items' => __('Daftar opsi'),
            'items.*.name' => __('Nama item'),
            'items.*.price_adjustment' => __('Penyesuaian harga'),
            'items.*.stock' => __('Stok'),
            'items.*.sku' => __('SKU'),
            'items.*.max_quantity' => __('Jumlah maksimal'),
            'items.*.product_id' => __('Produk terkait'),
            'items.*.use_product_price' => __('Gunakan harga produk'),
        ]);

        $validator->after(function ($validator) use ($input, $type) {
            $selectionType = $input['selection_type'] ?? $this->defaultSelectionType($type);
            if ($type === 'variant' && $selectionType !== 'single') {
                $validator->errors()->add('selection_type', __('Varian hanya mendukung pilihan tunggal.'));
            }

            $min = $this->normalizeNullableInt(Arr::get($input, 'min_select'), $type === 'variant' ? 1 : 0);
            $max = $this->normalizeNullableInt(Arr::get($input, 'max_select'), $type === 'variant' ? 1 : null);

            if ($max !== null && $min !== null && $max < $min) {
                $validator->errors()->add('max_select', __('Maksimal pilih tidak boleh kurang dari minimal pilih.'));
            }

            if ($selectionType === 'single' && $max !== null && $max > 1) {
                $validator->errors()->add('max_select', __('Untuk pilihan tunggal, maksimal pilih harus 1.'));
            }

            if ($type === 'addon' && !empty($input['items']) && is_array($input['items'])) {
                foreach ($input['items'] as $index => $item) {
                    $useProductPrice = !empty($item['use_product_price']);
                    $productId = $item['product_id'] ?? null;
                    if ($useProductPrice && empty($productId)) {
                        $validator->errors()->add("items.$index.product_id", __('Pilih produk terlebih dahulu ketika menggunakan harga produk.'));
                    }
                }
            }
        });

        return [$type, $validator];
    }

    protected function syncOptionItems(OptionGroup $group, array $items, string $type, bool $allowUpdate = false): void
    {
        $normalizedItems = collect($items)
            ->map(function ($item, $index) use ($type) {
                $price = (int) round($item['price_adjustment'] ?? 0);

                return [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name'],
                    'price_adjustment' => $price,
                    'stock' => Arr::get($item, 'stock'),
                    'sku' => Arr::get($item, 'sku'),
                    'product_id' => $type === 'addon'
                        ? $this->normalizeNullableInt(Arr::get($item, 'product_id'), null)
                        : null,
                    'use_product_price' => $type === 'addon'
                        ? (bool) ($item['use_product_price'] ?? false)
                        : false,
                    'max_quantity' => max(1, $this->normalizeNullableInt(Arr::get($item, 'max_quantity'), 1) ?? 1),
                    'is_default' => (bool) ($item['is_default'] ?? false),
                    'is_active' => array_key_exists('is_active', $item) ? (bool) $item['is_active'] : true,
                    'sort_order' => Arr::get($item, 'sort_order', $index),
                ];
            })
            ->values();

        $productPrices = collect();
        if ($type === 'addon') {
            $productIds = $normalizedItems->pluck('product_id')->filter()->unique();
            if ($productIds->isNotEmpty()) {
                $productPrices = Product::whereIn('id', $productIds)->pluck('price', 'id');
            }
        }

        $existing = $group->items()->get()->keyBy('id');
        $processedIds = [];

        foreach ($normalizedItems as $index => $itemData) {
            $optionItem = null;
            if (! empty($itemData['id'])) {
                $optionItem = $existing->get($itemData['id']);
            }

            if (! $optionItem) {
                $optionItem = new OptionItem([
                    'option_group_id' => $group->id,
                    'version_id' => 1,
                ]);
            }

            $useProductPrice = $type === 'addon' && $itemData['use_product_price'] && !empty($itemData['product_id']);
            $priceAdjustment = $itemData['price_adjustment'];
            if ($type === 'addon' && $useProductPrice) {
                $priceAdjustment = (int) ($productPrices[$itemData['product_id']] ?? $priceAdjustment ?? 0);
            }

            $optionItem->fill([
                'option_group_id' => $group->id,
                'name' => $itemData['name'],
                'price_adjustment' => $priceAdjustment,
                'stock' => $this->normalizeNullableInt($itemData['stock'] ?? null, null),
                'sku' => $itemData['sku'] ?? null,
                'product_id' => $type === 'addon'
                    ? $this->normalizeNullableInt($itemData['product_id'] ?? null, null)
                    : null,
                'use_product_price' => $type === 'addon'
                    ? $useProductPrice
                    : false,
                'max_quantity' => max(1, $this->normalizeNullableInt($itemData['max_quantity'], 1) ?? 1),
                'is_default' => $itemData['is_default'],
                'is_active' => $itemData['is_active'],
                'sort_order' => $itemData['sort_order'] ?? $index,
                'sync_status' => 'pending',
                'last_synced' => null,
                'client_version' => 'web',
            ]);

            $optionItem->version_id = $optionItem->exists ? (int) $optionItem->version_id + 1 : 1;
            $optionItem->save();

            $processedIds[] = $optionItem->id;
        }

        if ($allowUpdate) {
            $group->items()
                ->whereNotIn('id', $processedIds)
                ->each(function (OptionItem $item) {
                    $item->delete();
                });
        }
    }

    protected function markAssignmentsPending(OptionGroup $group): void
    {
        $group->productAssignments()->with('optionItems')->get()->each(function (ProductOptionGroup $assignment) {
            $assignment->fill([
                'sync_status' => 'pending',
                'last_synced' => null,
                'client_version' => 'web',
            ]);
            $assignment->version_id = (int) $assignment->version_id + 1;
            $assignment->save();

            $assignment->optionItems->each(function (ProductOptionItem $item) {
                $item->fill([
                    'sync_status' => 'pending',
                    'last_synced' => null,
                    'client_version' => 'web',
                ]);
                $item->version_id = (int) $item->version_id + 1;
                $item->save();
            });
        });
    }

    protected function authorizeOptionGroup(OptionGroup $group, array $context): void
    {
        $ownerIds = $context['owner_user_ids'];
        $outlet = $context['outlet'];

        $belongsToUser = $group->user_id === null || in_array($group->user_id, $ownerIds ?? [], true);
        $belongsToOutlet = $group->outlet_id === null || ($outlet && $group->outlet_id === $outlet->id);

        if (! $belongsToUser || ! $belongsToOutlet) {
            abort(403, __('Anda tidak memiliki akses ke opsi produk ini.'));
        }
    }

    private function defaultSelectionType(string $type): string
    {
        return $type === 'variant' ? 'single' : 'multiple';
    }

    private function resolveSelectionType(string $type, ?string $selectionType): string
    {
        if ($type === 'variant') {
            return 'single';
        }

        $normalized = $selectionType && in_array($selectionType, ['single', 'multiple'], true)
            ? $selectionType
            : $this->defaultSelectionType($type);

        return $normalized;
    }

    private function resolveNextSortOrder(array $context, string $type): int
    {
        $outlet = $context['outlet'];

        $query = OptionGroup::where('type', $type);
        if ($outlet) {
            $query->where('outlet_id', $outlet->id);
        } else {
            $ownerIds = $context['owner_user_ids'];
            if (! empty($ownerIds)) {
                $query->whereIn('user_id', $ownerIds);
            }
        }

        $max = $query->max('sort_order');

        return $max !== null ? (int) $max + 1 : 0;
    }

    private function normalizeNullableInt($value, $default = null): ?int
    {
        if ($value === null || $value === '') {
            return $default === null ? null : (int) $default;
        }

        return (int) $value;
    }

    private function getSelectableProducts(array $context)
    {
        $ownerIds = $context['owner_user_ids'];
        $outlet = $context['outlet'];

        $query = Product::query()->orderBy('name');
        $query->where(function ($query) use ($outlet, $ownerIds) {
            if ($outlet) {
                $query->where('outlet_id', $outlet->id);
            }

            if (! empty($ownerIds)) {
                $query->orWhereIn('user_id', $ownerIds);
            }
        });

        $columns = ['id', 'name', 'price'];
        if (Schema::hasColumn('products', 'sku')) {
            $columns[] = 'sku';
        }

        return $query->get($columns);
    }

    private function resolveOutletContext(): array
    {
        $user = auth()->user();
        $activeOutlet = OutletContext::currentOutlet();
        $currentRole = OutletContext::currentRole();

        $ownerUserIds = [];
        if ($activeOutlet) {
            $ownerUserIds = $activeOutlet->owners()->pluck('users.id')->unique()->values()->all();
        }
        if (empty($ownerUserIds)) {
            $ownerUserIds = [$user?->id];
        }

        $isPartner = $currentRole && $currentRole->role === 'partner';
        $canManageProducts = $user?->roles === 'admin'
            || ($currentRole && $currentRole->role === 'owner');

        return [
            'outlet' => $activeOutlet,
            'owner_user_ids' => $ownerUserIds,
            'is_partner' => $isPartner,
            'can_manage_products' => $canManageProducts,
        ];
    }

    private function ensureCanManageProducts(array $context): void
    {
        if (! $context['can_manage_products']) {
            abort(403, __('Hanya owner outlet yang dapat mengelola opsi produk.'));
        }
    }
}
