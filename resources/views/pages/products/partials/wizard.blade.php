
@php
    use Illuminate\Support\Arr;

    $isEdit = isset($product) && $product;
    $currentRecipe = $recipe ?? null;
    $rawRows = old('recipe');
    if (is_null($rawRows)) {
        $rawRows = $currentRecipe?->items?->map(fn($item) => [
            'raw_material_id' => $item->raw_material_id,
            'qty_per_yield' => (float) $item->qty_per_yield,
            'waste_pct' => (float) $item->waste_pct,
        ])->toArray() ?? [];
    }
    if (! is_array($rawRows)) {
        $rawRows = [];
    }
    if (empty($rawRows)) {
        $rawRows[] = ['raw_material_id' => null, 'qty_per_yield' => null, 'waste_pct' => 0];
    }

    $importableProducts = $importableProducts ?? collect();
    if (! $importableProducts instanceof \Illuminate\Support\Collection) {
        $importableProducts = collect($importableProducts);
    }
    $importProductsPayload = $importableProducts
        ->map(function ($product) {
            if (is_array($product)) {
                return [
                    'id' => $product['id'] ?? null,
                    'name' => $product['name'] ?? '',
                ];
            }

            return [
                'id' => $product?->id,
                'name' => $product?->name,
            ];
        })
        ->filter(fn ($product) => ! empty($product['id']) && $product['name'] !== null)
        ->values();

    $boolValue = static function ($value): bool {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    };

    $variantTemplates = collect($variantTemplates ?? []);
    $addonTemplates = collect($addonTemplates ?? []);
    $preferenceTemplates = collect($preferenceTemplates ?? []);

    $optionGroupCollections = [
        'variant' => $variantTemplates,
        'addon' => $addonTemplates,
        'preference' => $preferenceTemplates,
    ];

    if ($isEdit && isset($product)) {
        $productOptionGroups = $product->optionGroups->map->optionGroup->filter();
        foreach (['variant', 'addon', 'preference'] as $type) {
            $optionGroupCollections[$type] = $optionGroupCollections[$type]->merge(
                $productOptionGroups->filter(fn ($group) => $group && $group->type === $type)
            );
        }
    }

    foreach ($optionGroupCollections as $type => $collection) {
        $optionGroupCollections[$type] = $collection
            ->filter(fn ($group) => $group instanceof \App\Models\OptionGroup)
            ->keyBy('id');

        $optionGroupCollections[$type]->each(function ($group) {
            $group->loadMissing('items');
        });
    }

    $mapBaseGroup = function (\App\Models\OptionGroup $group) use ($boolValue) {
        $items = $group->items
            ->map(function (\App\Models\OptionItem $item) use ($boolValue) {
                $basePrice = (int) ($item->price_adjustment ?? 0);
                $baseStock = $item->stock;
                $baseSku = $item->sku;
                $baseMaxQuantity = $item->max_quantity;
                $baseIsDefault = $boolValue($item->is_default ?? false);
                $baseIsActive = $boolValue($item->is_active ?? true);

                return [
                    'id' => null,
                    'option_item_id' => $item->id,
                    'name' => $item->name,
                    'base_price_adjustment' => $basePrice,
                    'price_adjustment' => $basePrice,
                    'price_adjustment_override' => null,
                    'base_stock' => $baseStock,
                    'stock' => $baseStock,
                    'stock_override' => null,
                    'base_sku' => $baseSku,
                    'sku' => $baseSku,
                    'sku_override' => null,
                    'base_max_quantity' => $baseMaxQuantity,
                    'max_quantity' => $baseMaxQuantity,
                    'max_quantity_override' => null,
                    'base_is_default' => $baseIsDefault,
                    'is_default' => $baseIsDefault,
                    'is_default_override' => null,
                    'base_is_active' => $baseIsActive,
                    'is_active' => $baseIsActive,
                    'is_active_override' => null,
                    'sort_order' => $item->sort_order,
                ];
            })
            ->values()
            ->all();

        return [
            'id' => null,
            'option_group_id' => $group->id,
            'name' => $group->name,
            'type' => $group->type,
            'selection_type' => $group->selection_type ?? ($group->type === 'variant' ? 'single' : 'multiple'),
            'selection_type_override' => null,
            'is_required' => $boolValue($group->is_required ?? ($group->type === 'variant')),
            'is_required_override' => null,
            'min_select' => (int) ($group->min_select ?? ($group->type === 'variant' ? 1 : 0)),
            'min_select_override' => null,
            'max_select' => $group->max_select === null ? null : (int) $group->max_select,
            'max_select_override' => null,
            'sort_order' => (int) ($group->sort_order ?? 0),
            'items' => $items,
        ];
    };

    $mapPivotGroup = function (\App\Models\ProductOptionGroup $pivot, string $type) use ($boolValue, $mapBaseGroup) {
        $optionGroup = $pivot->optionGroup;
        $base = $optionGroup ? $mapBaseGroup($optionGroup) : [
            'id' => null,
            'option_group_id' => $pivot->option_group_id,
            'name' => null,
            'type' => $type,
            'selection_type' => $type === 'variant' ? 'single' : 'multiple',
            'selection_type_override' => null,
            'is_required' => $type === 'variant',
            'is_required_override' => null,
            'min_select' => $type === 'variant' ? 1 : 0,
            'min_select_override' => null,
            'max_select' => null,
            'max_select_override' => null,
            'sort_order' => $pivot->sort_order ?? 0,
            'items' => [],
        ];

        $base['id'] = $pivot->id;
        $base['option_group_id'] = $pivot->option_group_id ?? $base['option_group_id'];
        $base['name'] = $optionGroup?->name ?? $base['name'];
        $base['selection_type'] = $pivot->resolvedSelectionType();
        $base['selection_type_override'] = $pivot->selection_type_override;
        $base['is_required'] = $pivot->resolvedIsRequired();
        $base['is_required_override'] = $pivot->is_required_override;
        $base['min_select'] = $pivot->resolvedMinSelect();
        $base['min_select_override'] = $pivot->min_select_override;
        $base['max_select'] = $pivot->resolvedMaxSelect();
        $base['max_select_override'] = $pivot->max_select_override;
        $base['sort_order'] = $pivot->sort_order ?? $base['sort_order'];

        $base['items'] = $pivot->optionItems->map(function (\App\Models\ProductOptionItem $pivotItem) use ($boolValue) {
            $option = $pivotItem->optionItem;
            $basePrice = (int) ($option?->price_adjustment ?? 0);
            $baseStock = $option?->stock;
            $baseSku = $option?->sku;
            $baseMaxQuantity = $option?->max_quantity;
            $baseIsDefault = $boolValue($option?->is_default ?? false);
            $baseIsActive = $boolValue($option?->is_active ?? true);

            return [
                'id' => $pivotItem->id,
                'option_item_id' => $option?->id,
                'name' => $option?->name,
                'base_price_adjustment' => $basePrice,
                'price_adjustment' => $pivotItem->resolvedPriceAdjustment(),
                'price_adjustment_override' => $pivotItem->price_adjustment_override,
                'base_stock' => $baseStock,
                'stock' => $pivotItem->resolvedStock(),
                'stock_override' => $pivotItem->stock_override,
                'base_sku' => $baseSku,
                'sku' => $pivotItem->resolvedSku(),
                'sku_override' => $pivotItem->sku_override,
                'base_max_quantity' => $baseMaxQuantity,
                'max_quantity' => $pivotItem->resolvedMaxQuantity(),
                'max_quantity_override' => $pivotItem->max_quantity_override,
                'base_is_default' => $baseIsDefault,
                'is_default' => $boolValue($pivotItem->resolvedIsDefault()),
                'is_default_override' => $pivotItem->is_default_override,
                'base_is_active' => $baseIsActive,
                'is_active' => $boolValue($pivotItem->resolvedIsActive()),
                'is_active_override' => $pivotItem->is_active_override,
                'sort_order' => $pivotItem->sort_order,
            ];
        })->values()->all();

        return $base;
    };

    $mapSubmittedGroups = function ($groups, string $type) use ($boolValue, $optionGroupCollections, $mapBaseGroup) {
        $groups = is_array($groups) ? $groups : [];
        $itemsKey = match ($type) {
            'variant' => 'variants',
            'addon' => 'addons',
            default => 'preferences',
        };

        return array_values(array_map(function ($group) use ($boolValue, $itemsKey, $type, $optionGroupCollections, $mapBaseGroup) {
            $optionGroupId = Arr::get($group, 'option_group_id');
            $base = null;
            if ($optionGroupId && isset($optionGroupCollections[$type][$optionGroupId])) {
                $base = $mapBaseGroup($optionGroupCollections[$type][$optionGroupId]);
            }

            $result = $base ?? [
                'id' => $group['id'] ?? null,
                'option_group_id' => $optionGroupId,
                'name' => $group['name'] ?? null,
                'type' => $type,
                'selection_type' => $group['selection_type'] ?? ($type === 'variant' ? 'single' : 'multiple'),
                'selection_type_override' => $group['selection_type_override'] ?? null,
                'is_required' => $boolValue($group['is_required'] ?? ($type === 'variant')),
                'is_required_override' => $group['is_required_override'] ?? null,
                'min_select' => isset($group['min_select']) ? (int) $group['min_select'] : ($type === 'variant' ? 1 : 0),
                'min_select_override' => $group['min_select_override'] ?? null,
                'max_select' => array_key_exists('max_select', $group) && $group['max_select'] !== '' ? (int) $group['max_select'] : null,
                'max_select_override' => $group['max_select_override'] ?? null,
                'sort_order' => (int) ($group['sort_order'] ?? 0),
                'items' => [],
            ];

            $baseItems = collect($result['items'] ?? [])->keyBy('option_item_id');

            $items = array_map(function ($item) use ($boolValue, $baseItems) {
                $optionItemId = $item['option_item_id'] ?? null;
                $base = $optionItemId !== null ? ($baseItems[$optionItemId] ?? null) : null;

                return [
                    'id' => $item['id'] ?? null,
                    'option_item_id' => $optionItemId,
                    'name' => $base['name'] ?? ($item['name'] ?? null),
                    'price_adjustment' => $item['price_adjustment'] ?? ($base['price_adjustment'] ?? null),
                    'price_adjustment_override' => $item['price_adjustment_override'] ?? ($base['price_adjustment_override'] ?? null),
                    'stock' => $item['stock'] ?? ($base['stock'] ?? null),
                    'stock_override' => $item['stock_override'] ?? ($base['stock_override'] ?? null),
                    'sku' => $item['sku'] ?? ($base['sku'] ?? null),
                    'sku_override' => $item['sku_override'] ?? ($base['sku_override'] ?? null),
                    'max_quantity' => $item['max_quantity'] ?? ($base['max_quantity'] ?? null),
                    'max_quantity_override' => $item['max_quantity_override'] ?? ($base['max_quantity_override'] ?? null),
                    'is_default' => $boolValue($item['is_default'] ?? ($base['is_default'] ?? false)),
                    'is_default_override' => $item['is_default_override'] ?? ($base['is_default_override'] ?? null),
                    'is_active' => array_key_exists('is_active', $item)
                        ? $boolValue($item['is_active'])
                        : ($base['is_active'] ?? true),
                    'is_active_override' => $item['is_active_override'] ?? ($base['is_active_override'] ?? null),
                    'sort_order' => $item['sort_order'] ?? ($base['sort_order'] ?? null),
                ];
            }, array_values($group[$itemsKey] ?? []));

            $result['items'] = $items;
            $result['id'] = $group['id'] ?? $result['id'];
            $result['option_group_id'] = $optionGroupId ?? $result['option_group_id'];
            $result['selection_type'] = $group['selection_type'] ?? $result['selection_type'];
            $result['selection_type_override'] = $group['selection_type_override'] ?? $result['selection_type_override'];
            $result['is_required'] = $boolValue($group['is_required'] ?? $result['is_required']);
            $result['is_required_override'] = $group['is_required_override'] ?? $result['is_required_override'];
            $result['min_select'] = isset($group['min_select']) ? (int) $group['min_select'] : $result['min_select'];
            $result['min_select_override'] = $group['min_select_override'] ?? $result['min_select_override'];
            $result['max_select'] = array_key_exists('max_select', $group) && $group['max_select'] !== ''
                ? (int) $group['max_select']
                : $result['max_select'];
            $result['max_select_override'] = $group['max_select_override'] ?? $result['max_select_override'];
            $result['sort_order'] = (int) ($group['sort_order'] ?? $result['sort_order']);

            return $result;
        }, $groups));
    };

    $mapProductGroups = function (string $type) use ($product, $boolValue, $mapPivotGroup) {
        if (! $product) {
            return [];
        }

        return $product->optionGroups
            ->filter(fn ($group) => $group->optionGroup?->type === $type)
            ->values()
            ->map(fn ($pivot) => $mapPivotGroup($pivot, $type))
            ->all();
    };

    $selectedOptions = [
        'variant' => ($oldVariant = old('variant_groups')) !== null
            ? $mapSubmittedGroups($oldVariant, 'variant')
            : $mapProductGroups('variant'),
        'addon' => ($oldAddon = old('addon_groups')) !== null
            ? $mapSubmittedGroups($oldAddon, 'addon')
            : $mapProductGroups('addon'),
        'preference' => ($oldPreference = old('preference_groups', old('modifier_groups'))) !== null
            ? $mapSubmittedGroups($oldPreference, 'preference')
            : $mapProductGroups('preference'),
    ];

    $availableOptions = [
        'variant' => $optionGroupCollections['variant']->values()->map($mapBaseGroup)->values()->all(),
        'addon' => $optionGroupCollections['addon']->values()->map($mapBaseGroup)->values()->all(),
        'preference' => $optionGroupCollections['preference']->values()->map($mapBaseGroup)->values()->all(),
    ];

    $optionManagerPayload = [
        'selected' => $selectedOptions,
        'available' => $availableOptions,
    ];
@endphp

@once
    @push('style')
        <style>
            .wizard-stepper { display: flex; flex-wrap: wrap; gap: .75rem; }
            .wizard-stepper .step-btn {
                border-radius: 999px;
                padding: .6rem 1.4rem;
                border: 1px solid rgba(63,82,120,.2);
                background: #fff;
                color: #6777ef;
                display: flex;
                align-items: center;
                font-weight: 600;
                transition: all .2s ease;
            }
            .wizard-stepper .step-btn .step-index {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 1.8rem;
                height: 1.8rem;
                border-radius: 50%;
                margin-right: .6rem;
                background: rgba(103,119,239,.15);
            }
            .wizard-stepper .step-btn.active {
                background: #6777ef;
                color: #fff;
                box-shadow: 0 4px 14px rgba(103,119,239,.35);
            }
            .wizard-stepper .step-btn.active .step-index {
                background: rgba(255,255,255,.25);
            }
            .wizard-stepper .step-btn.completed {
                border-color: #6777ef;
                color: #6777ef;
            }
            .wizard-step { display: none; }
            .wizard-step.active { display: block; }
            .wizard .table td { vertical-align: middle; }
            .wizard .table-danger td { background: rgba(252,129,152,.1) !important; }
            .product-option-section .option-card { border-radius: 1rem; border: 1px solid rgba(63,82,120,.12); }
            .product-option-section .option-card .custom-switch .custom-control-label::before { top: -.3rem; }
            .product-option-section .option-card .custom-switch .custom-control-label::after { top: calc(-.3rem + 2px); }
            .product-option-section .option-card .custom-switch .custom-control-input { z-index: 1; }
            .product-option-section .option-card .custom-switch .custom-control-label { cursor: pointer; }
            .custom-switch .custom-control-input { position: absolute; z-index: -1; opacity: 0; }
            .custom-switch .custom-control-input:checked ~ .custom-control-label::before { background-color: #6777ef; border-color: #6777ef; }
            .custom-switch .custom-control-input:checked ~ .custom-control-label::after { transform: translateX(1.5rem); }
            .custom-switch .custom-control-label { position: relative; margin-bottom: 0; color: #495057; cursor: pointer; }
            .custom-switch .custom-control-label::before { position: absolute; top: 0.25rem; left: -2rem; display: block; width: 3rem; height: 1.5rem; pointer-events: none; content: ""; background-color: #dee2e6; border: #dee2e6 solid 1px; border-radius: 1rem; transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out; }
            .custom-switch .custom-control-label::after { position: absolute; top: calc(0.25rem + 2px); left: calc(-2rem + 2px); display: block; width: calc(1.5rem - 4px); height: calc(1.5rem - 4px); content: ""; background: #fff; border-radius: 1rem; transition: transform 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out; }
            .product-option-groups[data-selected-container] > p { background: #f8faff; border: 1px dashed rgba(63,82,120,.2); border-radius: .75rem; padding: 1rem; }
            .option-items-table th, .option-items-table td { vertical-align: middle; }
            .option-items-table .form-control-sm { height: calc(1.5em + .5rem + 2px); }
            .option-items-table .input-group-text { font-size: .75rem; }
        </style>
    @endpush
@endonce

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Periksa kembali isian Anda.</strong>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="wizard" data-wizard data-import-products='@json($importProductsPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)' data-option-preset-url="{{ route('product.option-presets', ['product' => '__PRODUCT__']) }}">
    <div class="wizard-stepper mb-4">
        <button type="button" class="step-btn active" data-step-target="1">
            <span class="step-index">1</span>
            <span>Detail Produk</span>
        </button>
        <button type="button" class="step-btn" data-step-target="2">
            <span class="step-index">2</span>
            <span>Resep (Opsional)</span>
        </button>
        <button type="button" class="step-btn" data-step-target="3">
            <span class="step-index">3</span>
            <span>Opsi Produk</span>
        </button>
        <button type="button" class="step-btn" data-step-target="4">
            <span class="step-index">4</span>
            <span>Pratinjau</span>
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
                        <div class="wizard-step active" data-step="1">
                <h5 class="font-weight-semibold mb-3">Informasi Dasar</h5>
                <div class="row">
                    <div class="col-lg-7">
                        <div class="form-group">
                            <label class="font-weight-semibold">Nama Produk</label>
                            <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name', optional($product)->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-semibold">Kategori</label>
                                <select name="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                                    <option value="">Pilih kategori</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ (string) old('category_id', optional($product)->category_id) === (string) $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-semibold">Harga Jual</label>
                                <input type="number" step="0.01" min="0" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', optional($product)->price) }}" required>
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="form-group">
                            <label class="font-weight-semibold d-flex justify-content-between align-items-center">
                                Foto Produk
                                <small class="text-muted font-italic">PNG/JPG maks 2 MB</small>
                            </label>
                            <div class="custom-file">
                                <input type="file" name="image" class="custom-file-input @error('image') is-invalid @enderror" id="product-image">
                                <label class="custom-file-label" for="product-image">Pilih file…</label>
                                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @if($isEdit && $product?->image)
                                <small class="form-text text-muted mt-2">Biarkan kosong apabila tidak ingin mengubah foto. Saat ini: {{ $product->image }}</small>
                            @endif
                        </div>
                        <div class="alert alert-info mb-0">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <i class="fas fa-info-circle fa-lg"></i>
                                </div>
                                <div>
                                    Stok produk akan dihitung otomatis dari persediaan bahan baku pada langkah resep.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wizard-step" data-step="2">
                <div class="alert alert-secondary d-flex align-items-center">
                    <i class="fas fa-leaf fa-lg mr-2 text-success"></i>
                    <div>
                        Langkah ini opsional. Tambahkan komposisi bahan jika ingin memantau HPP dan stok berbasis bahan baku.
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-sm" data-recipe-table>
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 45%">Bahan</th>
                                <th style="width: 25%">Takaran</th>
                                <th style="width: 20%">Satuan</th>
                                <th style="width: 10%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rawRows as $index => $row)
                                <tr data-row-index="{{ $index }}">
                                    <td>
                                        <select name="recipe[{{ $index }}][raw_material_id]" class="form-control" data-material-select>
                                            <option value="">Pilih bahan</option>
                                            @foreach($materials as $material)
                                                <option value="{{ $material->id }}" data-name="{{ $material->name }}" data-unit="{{ $material->unit }}" data-cost="{{ $material->unit_cost }}" data-stock="{{ $material->stock_qty }}" {{ (string) ($row['raw_material_id'] ?? '') === (string) $material->id ? 'selected' : '' }}>
                                                    {{ $material->name }} &middot; Stok: {{ number_format($material->stock_qty, 2) }} {{ $material->unit }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.0001" min="0" class="form-control" name="recipe[{{ $index }}][qty_per_yield]" value="{{ $row['qty_per_yield'] ?? '' }}" placeholder="Contoh: 50">
                                    </td>
                                    <td>
                                        <span class="badge badge-light" data-unit-label>—</span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-danger small d-none" data-recipe-error></span>
                    <button type="button" class="btn btn-outline-primary" data-add-row><i class="fas fa-plus mr-1"></i>Tambah Bahan</button>
                </div>
            </div>

            <div class="wizard-step" data-step="3" data-option-context='@json($optionManagerPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)'>
                @php
                    $optionTypeConfig = [
                        'variant' => [
                            'title' => 'Varian Produk',
                            'description' => 'Gunakan varian untuk pilihan wajib seperti ukuran atau level rasa.',
                            'items_key' => 'variants',
                            'empty' => 'Belum ada varian yang diterapkan pada produk ini.',
                            'show_stock' => true,
                            'show_sku' => true,
                        ],
                        'addon' => [
                            'title' => 'Addon Produk',
                            'description' => 'Addon cocok untuk menambah pilihan tambahan seperti topping atau ekstra.',
                            'items_key' => 'addons',
                            'empty' => 'Belum ada addon yang diterapkan.',
                            'show_stock' => false,
                            'show_sku' => false,
                        ],
                        'preference' => [
                            'title' => 'Preference Produk',
                            'description' => 'Preference membantu pelanggan memilih preferensi seperti tingkat gula atau es.',
                            'items_key' => 'preferences',
                            'empty' => 'Belum ada preference yang diterapkan.',
                            'show_stock' => false,
                            'show_sku' => false,
                        ],
                    ];
                @endphp

                <h5 class="font-weight-semibold mb-3">Opsi Produk</h5>
                <p class="text-muted small mb-4">Terapkan Product Options yang sudah tersedia. Anda dapat menyesuaikan harga atau batasan pilihan khusus untuk produk ini tanpa mengubah data master.</p>

                @foreach($optionTypeConfig as $type => $config)
                    <section class="product-option-section mb-5" data-option-type="{{ $type }}" data-items-key="{{ $config['items_key'] }}" data-next-index="0">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="font-weight-semibold mb-1">{{ $config['title'] }}</h5>
                                <p class="text-muted small mb-0">{{ $config['description'] }}</p>
                            </div>
                            <div class="form-inline flex-shrink-0">
                                <select class="custom-select custom-select-sm mr-2" data-option-select="{{ $type }}">
                                    <option value="">Pilih Product Option</option>
                                    @foreach($availableOptions[$type] as $availableGroup)
                                        <option value="{{ $availableGroup['option_group_id'] }}">{{ $availableGroup['name'] }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-add-option="{{ $type }}">
                                    <i class="fas fa-plus mr-1"></i>Tambah
                                </button>
                            </div>
                        </div>

                        <div class="product-option-groups" data-selected-container="{{ $type }}">
                            <p class="text-muted small mb-0" data-empty-hint>{{ $config['empty'] }}</p>
                        </div>
                    </section>
                @endforeach

                <template id="product-option-group-template">
                    <div class="card shadow-sm mb-3 option-card" data-option-group>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="font-weight-semibold mb-1" data-group-name></h6>
                                    <div class="text-muted small" data-group-summary></div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-danger" data-remove-option>
                                        <i class="fas fa-trash mr-1"></i>Hapus
                                    </button>
                                </div>
                            </div>

                            <div class="group-settings mb-3">
                                <div class="form-row">
                                    <div class="form-group col-md-3">
                                        <label class="small font-weight-semibold mb-1">Tipe Pilihan</label>
                                        <select class="custom-select custom-select-sm" data-input="selection_type">
                                            <option value="single">Single</option>
                                            <option value="multiple">Multiple</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label class="small font-weight-semibold mb-1">Minimal Pilih</label>
                                        <input type="number" min="0" class="form-control form-control-sm" data-input="min_select">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label class="small font-weight-semibold mb-1">Maksimal Pilih</label>
                                        <input type="number" min="1" class="form-control form-control-sm" data-input="max_select" placeholder="Kosongkan untuk mengikuti default">
                                    </div>
                                    <div class="form-group col-md-3 d-flex align-items-center">
                                        <div class="custom-control custom-switch mt-3">
                                            <input type="checkbox" class="custom-control-input" data-input="is_required" value="1">
                                            <label class="custom-control-label">Wajib Dipilih</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm mb-0 option-items-table">
                                    <thead class="thead-light">
                                        <tr data-items-header>
                                            <th>Opsi</th>
                                            <th data-field="price_adjustment">Harga Tambahan</th>
                                            <th data-field="stock">Stok</th>
                                            <th data-field="sku">SKU</th>
                                            <th data-field="max_quantity">Jumlah Maks</th>
                                            <th data-field="is_default">Default</th>
                                            <th data-field="is_active">Aktif</th>
                                        </tr>
                                    </thead>
                                    <tbody data-option-items></tbody>
                                </table>
                            </div>

                            <input type="hidden" data-input="id">
                            <input type="hidden" data-input="option_group_id">
                            <input type="hidden" data-input="sort_order">
                            <input type="hidden" data-input="items_key">
                        </div>
                    </div>
                </template>

                <template id="product-option-item-template">
                    <tr data-option-item>
                        <td class="align-middle">
                            <div class="font-weight-semibold" data-item-name></div>
                            <div class="text-muted small" data-item-meta></div>
                            <input type="hidden" data-input="id">
                            <input type="hidden" data-input="option_item_id">
                            <input type="hidden" data-input="sort_order">
                        </td>
                        <td data-field="price_adjustment" class="align-middle">
                            <div class="input-group input-group-sm">
                                <input type="number" step="1" class="form-control" data-input="price_adjustment" placeholder="Harga khusus">
                                <div class="input-group-append">
                                    <span class="input-group-text">Rp</span>
                                </div>
                            </div>
                            <small class="text-muted" data-base-price></small>
                        </td>
                        <td data-field="stock" class="align-middle">
                            <input type="number" min="0" class="form-control form-control-sm" data-input="stock">
                            <small class="text-muted" data-base-stock></small>
                        </td>
                        <td data-field="sku" class="align-middle">
                            <input type="text" class="form-control form-control-sm" data-input="sku">
                            <small class="text-muted" data-base-sku></small>
                        </td>
                        <td data-field="max_quantity" class="align-middle">
                            <input type="number" min="1" class="form-control form-control-sm" data-input="max_quantity">
                            <small class="text-muted" data-base-max></small>
                        </td>
                        <td data-field="is_default" class="align-middle text-center">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" data-input="is_default">
                                <label class="custom-control-label"></label>
                            </div>
                        </td>
                        <td data-field="is_active" class="align-middle text-center">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" data-input="is_active">
                                <label class="custom-control-label"></label>
                            </div>
                        </td>
                    </tr>
                </template>
            </div>
            <div class="wizard-step" data-step="4">
                <div class="row">
                    <div class="col-lg-5 mb-4 mb-lg-0">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase mb-3">Ringkasan Produk</h6>
                                <div class="mb-3">
                                    <div class="text-muted">Nama</div>
                                    <div class="font-weight-semibold" id="preview-name">—</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted">Kategori</div>
                                    <div class="font-weight-semibold" id="preview-category">—</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted">Harga Jual</div>
                                    <div class="font-weight-semibold" id="preview-price">—</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted">Estimasi Biaya per Unit</div>
                                    <div class="font-weight-semibold text-primary" id="preview-hpp">—</div>
                                </div>
                                <div>
                                    <div class="text-muted">Estimasi Produksi dari Stok Bahan</div>
                                    <div class="font-weight-semibold" id="preview-stock">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="text-muted text-uppercase mb-0">Komposisi Bahan</h6>
                                    <span class="badge badge-light" id="preview-total-items">0 bahan</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" id="preview-table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Bahan</th>
                                                <th>Takaran</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="text-muted">
                                                <td colspan="2">Belum ada bahan dipilih.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase mb-3">Ringkasan Opsi Produk</h6>

                                <div class="mb-4" id="preview-variants-wrapper">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Varian</h6>
                                        <span class="badge badge-light" id="preview-variant-count">0 grup</span>
                                    </div>
                                    <div class="text-muted small mt-2" id="preview-variant-empty">Belum ada varian yang ditambahkan.</div>
                                    <div class="list-group list-group-flush mt-2" id="preview-variant-groups"></div>
                                </div>

                                <div class="mb-4" id="preview-addons-wrapper">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Addon</h6>
                                        <span class="badge badge-light" id="preview-addon-count">0 grup</span>
                                    </div>
                                    <div class="text-muted small mt-2" id="preview-addon-empty">Belum ada addon yang ditambahkan.</div>
                                    <div class="list-group list-group-flush mt-2" id="preview-addon-groups"></div>
                                </div>

                                <div id="preview-preferences-wrapper">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Preference</h6>
                                        <span class="badge badge-light" id="preview-preference-count">0 grup</span>
                                    </div>
                                    <div class="text-muted small mt-2" id="preview-preference-empty">Belum ada preference yang ditambahkan.</div>
                                    <div class="list-group list-group-flush mt-2" id="preview-preference-groups"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center bg-light">
            <button type="button" class="btn btn-light" data-action="prev" disabled><i class="fas fa-arrow-left mr-1"></i>Sebelumnya</button>
            <div>
                <button type="button" class="btn btn-primary" data-action="next">Berikutnya<i class="fas fa-arrow-right ml-2"></i></button>
                <button type="submit" class="btn btn-success d-none" data-action="submit"><i class="fas fa-save mr-1"></i>Simpan Produk</button>
            </div>
        </div>
</div>
</div>


@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const wizard = document.querySelector('[data-wizard]');
                if (!wizard) {
                    return;
                }

                const steps = Array.from(wizard.querySelectorAll('[data-step]'));
                const stepButtons = Array.from(wizard.querySelectorAll('.step-btn'));
                const prevBtn = wizard.querySelector('[data-action="prev"]');
                const nextBtn = wizard.querySelector('[data-action="next"]');
                const submitBtn = wizard.querySelector('[data-action="submit"]');
                let currentStep = 1;
                const formEl = wizard.closest('form');

                const formatCurrency = (value) => {
                    const number = Number(value) || 0;
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
                };

                const showStep = (step) => {
                    if (step < 1 || step > steps.length) {
                        return;
                    }

                    steps.forEach((stepEl) => {
                        const stepNumber = Number(stepEl.getAttribute('data-step'));
                        stepEl.classList.toggle('active', stepNumber === step);
                    });
                    currentStep = step;

                    stepButtons.forEach((btn) => {
                        const stepNumber = Number(btn.dataset.stepTarget);
                        btn.classList.toggle('active', stepNumber === currentStep);
                        btn.classList.toggle('completed', stepNumber < currentStep);
                    });

                    if (prevBtn) {
                        prevBtn.disabled = currentStep === 1;
                    }
                    if (nextBtn) {
                        nextBtn.classList.toggle('d-none', currentStep === steps.length);
                    }
                    if (submitBtn) {
                        submitBtn.classList.toggle('d-none', currentStep !== steps.length);
                    }
                };

                const validateStep = () => true;

                const goToStep = (step) => {
                    if (step === currentStep) {
                        return;
                    }
                    if (step > currentStep && !validateStep(currentStep)) {
                        return;
                    }
                    showStep(step);
                };

                if (prevBtn) {
                    prevBtn.addEventListener('click', () => goToStep(currentStep - 1));
                }
                if (nextBtn) {
                    nextBtn.addEventListener('click', () => goToStep(currentStep + 1));
                }
                stepButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const target = Number(btn.dataset.stepTarget);
                        if (Number.isFinite(target)) {
                            goToStep(target);
                        }
                    });
                });

                showStep(1);

                /* -------------------------------------------------- */
                /* Resep handling                                      */
                /* -------------------------------------------------- */
                const recipeTableBody = wizard.querySelector('[data-recipe-table] tbody');
                let recipeRowIndex = recipeTableBody ? recipeTableBody.querySelectorAll('tr').length : 0;

                const setUnitLabel = (row) => {
                    const select = row.querySelector('[data-material-select]');
                    const unitBadge = row.querySelector('[data-unit-label]');
                    if (!select || !unitBadge) {
                        return;
                    }
                    const option = select.options[select.selectedIndex];
                    const unit = option ? option.getAttribute('data-unit') : null;
                    unitBadge.textContent = unit || '—';
                };

                const refreshRecipePreview = () => {
                    const previewTableBody = document.querySelector('#preview-table tbody');
                    const totalItemsBadge = document.querySelector('#preview-total-items');
                    if (!previewTableBody || !totalItemsBadge) {
                        return;
                    }

                    const rows = Array.from(recipeTableBody ? recipeTableBody.querySelectorAll('tr') : []);
                    const items = rows
                        .map((row) => {
                            const select = row.querySelector('[data-material-select]');
                            const option = select ? select.options[select.selectedIndex] : null;
                            const name = option ? (option.getAttribute('data-name') || option.textContent.trim()) : '';
                            if (!name) {
                                return null;
                            }
                            const qtyInput = row.querySelector('input[name$="[qty_per_yield]"]');
                            const qty = qtyInput ? qtyInput.value : '';
                            const unit = option ? (option.getAttribute('data-unit') || '') : '';
                            return { name, qty, unit };
                        })
                        .filter(Boolean);

                    totalItemsBadge.textContent = `${items.length} bahan`;
                    previewTableBody.innerHTML = '';

                    if (!items.length) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.className = 'text-muted';
                        const td = document.createElement('td');
                        td.colSpan = 2;
                        td.textContent = 'Belum ada bahan dipilih.';
                        emptyRow.appendChild(td);
                        previewTableBody.appendChild(emptyRow);
                        return;
                    }

                    items.forEach((item) => {
                        const tr = document.createElement('tr');
                        const nameTd = document.createElement('td');
                        nameTd.textContent = item.name;
                        const qtyTd = document.createElement('td');
                        qtyTd.textContent = item.qty ? `${item.qty} ${item.unit}`.trim() : '—';
                        tr.appendChild(nameTd);
                        tr.appendChild(qtyTd);
                        previewTableBody.appendChild(tr);
                    });
                };

                if (recipeTableBody) {
                    const baseSelect = recipeTableBody.querySelector('[data-material-select]');
                    const baseOptionsHtml = baseSelect ? baseSelect.innerHTML : '<option value="">Pilih bahan</option>';

                    const addRecipeRow = (prefill = null) => {
                        const index = recipeRowIndex++;
                        const row = document.createElement('tr');
                        row.dataset.rowIndex = String(index);

                        const selectTd = document.createElement('td');
                        const select = document.createElement('select');
                        select.name = `recipe[${index}][raw_material_id]`;
                        select.className = 'form-control';
                        select.setAttribute('data-material-select', 'true');
                        select.innerHTML = baseOptionsHtml;
                        if (prefill && prefill.raw_material_id) {
                            select.value = String(prefill.raw_material_id);
                        }
                        selectTd.appendChild(select);

                        const qtyTd = document.createElement('td');
                        const qtyInput = document.createElement('input');
                        qtyInput.type = 'number';
                        qtyInput.step = '0.0001';
                        qtyInput.min = '0';
                        qtyInput.className = 'form-control';
                        qtyInput.name = `recipe[${index}][qty_per_yield]`;
                        qtyInput.placeholder = 'Contoh: 50';
                        if (prefill && prefill.qty_per_yield) {
                            qtyInput.value = prefill.qty_per_yield;
                        }
                        qtyTd.appendChild(qtyInput);

                        const unitTd = document.createElement('td');
                        const unitBadge = document.createElement('span');
                        unitBadge.className = 'badge badge-light';
                        unitBadge.setAttribute('data-unit-label', 'true');
                        unitBadge.textContent = '—';
                        unitTd.appendChild(unitBadge);

                        const actionTd = document.createElement('td');
                        actionTd.className = 'text-center';
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn btn-outline-danger btn-sm';
                        removeBtn.setAttribute('data-remove-row', 'true');
                        removeBtn.innerHTML = '&times;';
                        actionTd.appendChild(removeBtn);

                        row.appendChild(selectTd);
                        row.appendChild(qtyTd);
                        row.appendChild(unitTd);
                        row.appendChild(actionTd);
                        recipeTableBody.appendChild(row);

                        setUnitLabel(row);
                        refreshRecipePreview();
                    };

                    recipeTableBody.querySelectorAll('tr').forEach((row) => {
                        setUnitLabel(row);
                    });

                    recipeTableBody.addEventListener('change', (event) => {
                        if (event.target.matches('[data-material-select]')) {
                            setUnitLabel(event.target.closest('tr'));
                            refreshRecipePreview();
                        }
                    });

                    recipeTableBody.addEventListener('input', (event) => {
                        if (event.target.matches('input[name$="[qty_per_yield]"]')) {
                            refreshRecipePreview();
                        }
                    });

                    recipeTableBody.addEventListener('click', (event) => {
                        const removeBtn = event.target.closest('[data-remove-row]');
                        if (!removeBtn) {
                            return;
                        }
                        const rows = Array.from(recipeTableBody.querySelectorAll('tr'));
                        if (rows.length > 1) {
                            removeBtn.closest('tr').remove();
                        } else {
                            const row = rows[0];
                            row.querySelectorAll('select, input').forEach((el) => {
                                if (el.tagName === 'SELECT') {
                                    el.selectedIndex = 0;
                                } else {
                                    el.value = '';
                                }
                            });
                            const unitBadge = row.querySelector('[data-unit-label]');
                            if (unitBadge) {
                                unitBadge.textContent = '—';
                            }
                        }
                        refreshRecipePreview();
                    });

                    const addRowBtn = wizard.querySelector('[data-add-row]');
                    if (addRowBtn) {
                        addRowBtn.addEventListener('click', () => addRecipeRow());
                    }
                }

                /* -------------------------------------------------- */
                /* Product options handling                           */
                /* -------------------------------------------------- */
                const optionStep = wizard.querySelector('[data-step="3"]');
                const optionContextRaw = optionStep ? optionStep.getAttribute('data-option-context') : null;

                const optionConfig = {
                    variant: {
                        itemsKey: 'variants',
                        showStock: true,
                        showSku: true,
                        showMaxQuantity: false,
                        lockPrice: false,
                        allowDefault: true,
                        forceSelection: 'single',
                        forceMin: 1,
                        forceMax: 1,
                        forceRequired: true,
                    },
                    addon: {
                        itemsKey: 'addons',
                        showStock: false,
                        showSku: false,
                        showMaxQuantity: true,
                        lockPrice: false,
                        allowDefault: true,
                        forceSelection: null,
                        forceMin: null,
                        forceRequired: null,
                    },
                    preference: {
                        itemsKey: 'preferences',
                        showStock: false,
                        showSku: false,
                        showMaxQuantity: true,
                        lockPrice: true,
                        allowDefault: false,
                        forceSelection: null,
                        forceMin: 0,
                        forceMax: null,
                        forceRequired: false,
                    },
                };

                const optionTemplates = {
                    group: document.getElementById('product-option-group-template'),
                    item: document.getElementById('product-option-item-template'),
                };

                const optionSections = new Map();
                wizard.querySelectorAll('.product-option-section').forEach((section) => {
                    const type = section.getAttribute('data-option-type');
                    if (!type || !optionConfig[type]) {
                        return;
                    }
                    optionSections.set(type, {
                        section,
                        select: section.querySelector(`[data-option-select="${type}"]`),
                        addButton: section.querySelector(`[data-add-option="${type}"]`),
                        container: section.querySelector('[data-selected-container]'),
                        emptyHint: section.querySelector('[data-empty-hint]'),
                    });
                });

                const optionState = {
                    selected: { variant: [], addon: [], preference: [] },
                    available: { variant: [], addon: [], preference: [] },
                    lookup: {
                        available: {
                            variant: new Map(),
                            addon: new Map(),
                            preference: new Map(),
                        },
                    },
                };

                const cloneDeep = (value) => JSON.parse(JSON.stringify(value));

                const normalizeItem = (item, type) => {
                    const config = optionConfig[type];

                    const basePriceRaw = Number(item.base_price_adjustment ?? item.price_adjustment ?? 0);
                    const basePrice = config.lockPrice ? 0 : basePriceRaw;
                    const overridePrice = config.lockPrice ? null : item.price_adjustment_override;
                    const resolvedPrice = config.lockPrice
                        ? 0
                        : (overridePrice === null || overridePrice === undefined || overridePrice === ''
                            ? Number(item.price_adjustment ?? basePriceRaw)
                            : Number(overridePrice));

                    const baseStock = item.base_stock ?? item.stock ?? null;
                    const overrideStock = item.stock_override;
                    const resolvedStock = overrideStock === null || overrideStock === undefined || overrideStock === ''
                        ? (baseStock === null || baseStock === undefined ? null : Number(baseStock))
                        : Number(overrideStock);

                    const baseSku = item.base_sku ?? item.sku ?? '';
                    const overrideSku = item.sku_override;
                    const resolvedSku = overrideSku === null || overrideSku === undefined ? (item.sku ?? baseSku ?? '') : overrideSku;

                    const baseMax = item.base_max_quantity ?? item.max_quantity ?? null;
                    const overrideMax = item.max_quantity_override;
                    const resolvedMax = overrideMax === null || overrideMax === undefined || overrideMax === ''
                        ? (baseMax === null || baseMax === undefined ? null : Number(baseMax))
                        : Number(overrideMax);

                    const baseDefaultRaw = typeof item.base_is_default !== 'undefined' ? !!item.base_is_default : !!item.is_default;
                    const baseDefault = config.allowDefault ? baseDefaultRaw : false;
                    const overrideDefault = config.allowDefault ? item.is_default_override : null;
                    const resolvedDefault = config.allowDefault
                        ? (overrideDefault === null || overrideDefault === undefined
                            ? (typeof item.is_default !== 'undefined' ? !!item.is_default : baseDefault)
                            : !!overrideDefault)
                        : false;

                    const baseActive = typeof item.base_is_active !== 'undefined' ? !!item.base_is_active : (typeof item.is_active !== 'undefined' ? !!item.is_active : true);
                    const overrideActive = item.is_active_override;
                    const resolvedActive = overrideActive === null || overrideActive === undefined
                        ? (typeof item.is_active !== 'undefined' ? !!item.is_active : baseActive)
                        : !!overrideActive;

                    return {
                        id: item.id ?? null,
                        option_item_id: item.option_item_id ?? null,
                        name: item.name ?? '',
                        base_price_adjustment: basePrice,
                        price_adjustment_override: overridePrice === '' ? null : (overridePrice === null || overridePrice === undefined ? null : Number(overridePrice)),
                        resolved_price_adjustment: resolvedPrice,
                        base_stock: baseStock === '' ? null : (baseStock === undefined ? null : baseStock),
                        stock_override: overrideStock === '' ? null : (overrideStock === null || overrideStock === undefined ? null : Number(overrideStock)),
                        resolved_stock: resolvedStock,
                        base_sku: baseSku,
                        sku_override: overrideSku === undefined ? null : overrideSku,
                        resolved_sku: resolvedSku,
                        base_max_quantity: baseMax === '' ? null : (baseMax === undefined ? null : baseMax),
                        max_quantity_override: overrideMax === '' ? null : (overrideMax === null || overrideMax === undefined ? null : Number(overrideMax)),
                        resolved_max_quantity: resolvedMax,
                        base_is_default: baseDefault,
                        is_default_override: overrideDefault === undefined ? null : (overrideDefault === null ? null : !!overrideDefault),
                        resolved_is_default: resolvedDefault,
                        base_is_active: baseActive,
                        is_active_override: overrideActive === undefined ? null : (overrideActive === null ? null : !!overrideActive),
                        resolved_is_active: resolvedActive,
                        sort_order: item.sort_order ?? 0,
                    };
                };

                const normalizeGroup = (group, type) => {
                    const config = optionConfig[type];
                    const itemsKey = config.itemsKey;
                    const itemsSource = Array.isArray(group[itemsKey]) ? group[itemsKey] : (Array.isArray(group.items) ? group.items : []);
                    const normalizedItems = itemsSource.map((item) => normalizeItem(item, type));

                    return {
                        id: group.id ?? null,
                        option_group_id: group.option_group_id ?? null,
                        name: group.name ?? '',
                        type,
                        items_key: itemsKey,
                        selection_type: group.selection_type ?? (type === 'variant' ? 'single' : 'multiple'),
                        selection_type_override: group.selection_type_override ?? null,
                        is_required: typeof group.is_required !== 'undefined' ? !!group.is_required : (type === 'variant'),
                        is_required_override: group.is_required_override ?? null,
                        min_select: group.min_select === null || group.min_select === undefined || group.min_select === ''
                            ? (type === 'variant' ? 1 : 0)
                            : Number(group.min_select),
                        min_select_override: group.min_select_override ?? null,
                        max_select: group.max_select === null || group.max_select === undefined || group.max_select === ''
                            ? null
                            : Number(group.max_select),
                        max_select_override: group.max_select_override ?? null,
                        sort_order: group.sort_order ?? 0,
                        items: normalizedItems,
                    };
                };

                const syncWithBase = (group, baseGroup, type) => {
                    if (!baseGroup) {
                        group.items = group.items.map((item) => normalizeItem(item, type));
                        return group;
                    }

                    const baseItemsById = new Map(baseGroup.items.map((item) => [String(item.option_item_id), item]));
                    group.name = baseGroup.name;
                    group.selection_type = group.selection_type || baseGroup.selection_type;
                    group.is_required = typeof group.is_required === 'boolean' ? group.is_required : baseGroup.is_required;
                    group.min_select = group.min_select ?? baseGroup.min_select;
                    group.max_select = group.max_select ?? baseGroup.max_select;

                    group.items = baseGroup.items.map((baseItem) => {
                        const existing = group.items.find((item) => String(item.option_item_id) === String(baseItem.option_item_id));
                        if (existing) {
                            const merged = { ...baseItem, ...existing };
                            return normalizeItem(merged, type);
                        }
                        return normalizeItem(baseItem, type);
                    });

                    return group;
                };

                if (optionContextRaw) {
                    try {
                        const context = JSON.parse(optionContextRaw);
                        ['variant', 'addon', 'preference'].forEach((type) => {
                            const available = Array.isArray(context.available?.[type]) ? context.available[type] : [];
                            optionState.available[type] = available.map((group) => normalizeGroup(group, type));
                            optionState.lookup.available[type] = new Map(optionState.available[type].map((group) => [String(group.option_group_id), group]));

                            const selected = Array.isArray(context.selected?.[type]) ? context.selected[type] : [];
                            optionState.selected[type] = selected.map((group) => {
                                const normalized = normalizeGroup(group, type);
                                const base = optionState.lookup.available[type].get(String(normalized.option_group_id));
                                return syncWithBase(normalized, base, type);
                            });
                        });
                    } catch (error) {
                        console.warn('Gagal memuat konfigurasi Product Options', error);
                    }
                }

                const updateSelectOptions = (type) => {
                    const section = optionSections.get(type);
                    if (!section || !section.select) {
                        return;
                    }
                    const selectedIds = new Set(optionState.selected[type].map((group) => String(group.option_group_id)));
                    Array.from(section.select.options).forEach((option) => {
                        if (!option.value) {
                            return;
                        }
                        option.disabled = selectedIds.has(option.value);
                    });
                    section.select.value = '';
                };

                const updateGroupSummary = (type, group, cardEl) => {
                    const summaryEl = cardEl.querySelector('[data-group-summary]');
                    if (!summaryEl) {
                        return;
                    }
                    const parts = [];
                    parts.push(group.selection_type === 'multiple' ? 'Multiple' : 'Single');
                    parts.push(group.is_required ? 'Wajib' : 'Opsional');
                    if (group.min_select !== null && group.min_select !== undefined && group.min_select !== '') {
                        parts.push(`Min ${group.min_select}`);
                    }
                    if (group.max_select !== null && group.max_select !== undefined && group.max_select !== '') {
                        parts.push(`Max ${group.max_select}`);
                    }
                    parts.push(`${group.items.length} opsi`);
                    summaryEl.textContent = parts.join(' • ');
                };

                const refreshOptionPreview = () => {
                    const previewConfig = {
                        variant: {
                            count: document.getElementById('preview-variant-count'),
                            container: document.getElementById('preview-variant-groups'),
                            empty: document.getElementById('preview-variant-empty'),
                        },
                        addon: {
                            count: document.getElementById('preview-addon-count'),
                            container: document.getElementById('preview-addon-groups'),
                            empty: document.getElementById('preview-addon-empty'),
                        },
                        preference: {
                            count: document.getElementById('preview-preference-count'),
                            container: document.getElementById('preview-preference-groups'),
                            empty: document.getElementById('preview-preference-empty'),
                        },
                    };

                    ['variant', 'addon', 'preference'].forEach((type) => {
                        const config = previewConfig[type];
                        if (!config || !config.container || !config.count || !config.empty) {
                            return;
                        }

                        const groups = optionState.selected[type];
                        config.container.innerHTML = '';

                        if (!groups.length) {
                            config.count.textContent = '0 grup';
                            config.empty.classList.remove('d-none');
                            return;
                        }

                        config.count.textContent = `${groups.length} grup`;
                        config.empty.classList.add('d-none');

                        groups.forEach((group) => {
                            const item = document.createElement('div');
                            item.className = 'list-group-item';

                            const metaParts = [];
                            metaParts.push(group.selection_type === 'multiple' ? 'Multiple' : 'Single');
                            metaParts.push(group.is_required ? 'Wajib' : 'Opsional');
                            if (group.min_select !== null && group.min_select !== undefined && group.min_select !== '') {
                                metaParts.push(`Min ${group.min_select}`);
                            }
                            if (group.max_select !== null && group.max_select !== undefined && group.max_select !== '') {
                                metaParts.push(`Max ${group.max_select}`);
                            }

                            const header = document.createElement('div');
                            header.className = 'd-flex justify-content-between align-items-center';
                            header.innerHTML = `<strong>${group.name || 'Product Option'}</strong><span class="badge badge-light">${metaParts.join(' • ')}</span>`;
                            item.appendChild(header);

                            if (group.items.length) {
                                const list = document.createElement('ul');
                                list.className = 'mb-0 mt-2 pl-3';
                                group.items.forEach((option) => {
                                    const li = document.createElement('li');
                                    const parts = [];
                                    const delta = Number(option.resolved_price_adjustment || 0);
                                    if (delta === 0) {
                                        parts.push('Harga Rp0');
                                    } else {
                                        const sign = delta > 0 ? '+' : '-';
                                        parts.push(`Harga ${sign}${formatCurrency(Math.abs(delta))}`);
                                    }
                                    if (type === 'variant') {
                                        if (option.resolved_stock !== null && option.resolved_stock !== undefined && option.resolved_stock !== '') {
                                            parts.push(`Stok ${option.resolved_stock}`);
                                        }
                                        if (option.resolved_sku) {
                                            parts.push(`SKU ${option.resolved_sku}`);
                                        }
                                    } else {
                                        if (option.resolved_max_quantity !== null && option.resolved_max_quantity !== undefined && option.resolved_max_quantity !== '') {
                                            parts.push(`Qty Maks ${option.resolved_max_quantity}`);
                                        }
                                    }
                                    if (option.resolved_is_default) {
                                        parts.push('Default');
                                    }
                                    if (!option.resolved_is_active) {
                                        parts.push('Nonaktif');
                                    }
                                    li.textContent = `${option.name || 'Opsi'} • ${parts.join(' • ')}`;
                                    list.appendChild(li);
                                });
                                item.appendChild(list);
                            }

                            config.container.appendChild(item);
                        });
                    });
                };

                const prepareOptionFormSubmission = () => {
                    if (!formEl) {
                        return;
                    }

                    const isBlank = (value) => value === null || typeof value === 'undefined' || value === '';
                    const computeOverrideFieldName = (input) => {
                        if (!input || !input.name) {
                            return null;
                        }
                        return input.name.match(/\[([^\]]+)\]$/)
                            ? input.name.replace(/\[([^\]]+)\]$/, (_, field) => `[${field}_override]`)
                            : null;
                    };

                    const assignNumeric = (input, override, shared, fallback) => {
                        if (!input) {
                            return;
                        }
                        input.disabled = false;
                        if (!shared) {
                            if (!isBlank(override)) {
                                input.value = override;
                            }
                            return;
                        }
                        if (isBlank(override)) {
                            input.disabled = true;
                            const fallbackValue = isBlank(fallback) ? '' : fallback;
                            input.value = fallbackValue;
                        } else {
                            input.value = override;
                        }
                    };

                    const assignText = (input, override, shared) => {
                        if (!input) {
                            return;
                        }
                        input.disabled = false;
                        if (!shared) {
                            if (!isBlank(override)) {
                                input.value = override;
                            }
                            return;
                        }
                        if (isBlank(override)) {
                            input.disabled = true;
                        } else {
                            input.value = override;
                        }
                    };

                    const assignBoolean = (input, override, shared) => {
                        if (!input || !input.name) {
                            return;
                        }
                        const container = input.parentNode || input.closest('div');
                        if (!container) {
                            return;
                        }
                        const booleanSelector = `input[type="hidden"][data-boolean-for="${input.name}"]`;
                        container.querySelectorAll(booleanSelector).forEach((hidden) => hidden.remove());
                        const overrideFieldName = computeOverrideFieldName(input);
                        if (overrideFieldName) {
                            const overrideSelector = `input[type="hidden"][data-override-for="${overrideFieldName}"]`;
                            container.querySelectorAll(overrideSelector).forEach((hidden) => hidden.remove());
                        }
                        input.disabled = false;

                        if (!shared) {
                            return;
                        }

                        if (override === null || typeof override === 'undefined') {
                            input.disabled = true;
                            if (overrideFieldName) {
                                const overrideHidden = document.createElement('input');
                                overrideHidden.type = 'hidden';
                                overrideHidden.name = overrideFieldName;
                                overrideHidden.value = '';
                                overrideHidden.setAttribute('data-override-for', overrideFieldName);
                                container.insertBefore(overrideHidden, input);
                            }
                            return;
                        }

                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = input.name;
                        hidden.value = override ? '1' : '0';
                        hidden.setAttribute('data-boolean-for', input.name);
                        container.insertBefore(hidden, input);
                        input.disabled = true;
                    };

                    const assignSelectionType = (select, group, shared, type) => {
                        if (!select || !select.name) {
                            return;
                        }
                        const container = select.parentNode || select.closest('div');
                        if (!container) {
                            return;
                        }

                        container.querySelectorAll(`input[type="hidden"][data-selection-for="${select.name}"]`).forEach((hidden) => hidden.remove());

                        const fallback = group.selection_type_override
                            ?? group.selection_type
                            ?? (type === 'variant' ? 'single' : 'multiple');
                        select.value = fallback;
                        select.disabled = false;

                        if (!shared) {
                            return;
                        }

                        if (group.selection_type_override === null || typeof group.selection_type_override === 'undefined') {
                            const baseSelection = optionState.lookup.available[type].get(String(group.option_group_id))?.selection_type
                                ?? group.selection_type
                                ?? (type === 'variant' ? 'single' : 'multiple');
                            select.value = baseSelection;
                            select.disabled = true;
                            return;
                        }

                        select.value = group.selection_type_override;
                    };

                    ['variant', 'addon', 'preference'].forEach((type) => {
                        const groups = optionState.selected[type];
                        groups.forEach((group, groupIndex) => {
                            const isShared = Boolean(group.option_group_id);
                            
                            // Process group-level fields
                            const findGroupInput = (field) => formEl.querySelector(`[name="${type}_groups[${groupIndex}][${field}]"]`);
                            assignSelectionType(findGroupInput('selection_type'), group, isShared, type);
                            assignBoolean(findGroupInput('is_required'), group.is_required_override, isShared);
                            
                            const baseKey = `${type}_groups[${groupIndex}][${group.items_key}]`;
                            group.items.forEach((item, itemIndex) => {
                                const prefix = `${baseKey}[${itemIndex}]`;
                                const findInput = (field) => formEl.querySelector(`[name="${prefix}[${field}]"]`);

                                assignNumeric(findInput('price_adjustment_override'), item.price_adjustment_override, isShared, item.resolved_price_adjustment);
                                assignNumeric(findInput('stock'), item.stock_override, isShared, item.resolved_stock);
                                assignText(findInput('sku'), item.sku_override, isShared);
                                assignNumeric(findInput('max_quantity'), item.max_quantity_override, isShared, item.resolved_max_quantity);
                                assignBoolean(findInput('is_default'), item.is_default_override, isShared);
                                assignBoolean(findInput('is_active'), item.is_active_override, isShared);
                            });
                        });
                    });
                };

                const renderOptionSection = (type) => {
                    const section = optionSections.get(type);
                    if (!section) {
                        return;
                    }
                    const { container, emptyHint } = section;
                    const config = optionConfig[type];
                    const groups = optionState.selected[type];
                    container.innerHTML = '';

                    if (!groups.length) {
                        if (emptyHint) {
                            emptyHint.classList.remove('d-none');
                        }
                        updateSelectOptions(type);
                        return;
                    }

                    if (emptyHint) {
                        emptyHint.classList.add('d-none');
                    }

                    groups.forEach((group, groupIndex) => {
                        group.sort_order = groupIndex;
                        const template = optionTemplates.group;
                        if (!template) {
                            return;
                        }
                        const card = template.content.firstElementChild.cloneNode(true);
                        card.dataset.optionType = type;
                        card.dataset.groupIndex = String(groupIndex);

                        const nameEl = card.querySelector('[data-group-name]');
                        if (nameEl) {
                            nameEl.textContent = group.name || 'Product Option';
                        }

                        const selectionSelect = card.querySelector('[data-input="selection_type"]');
                        if (selectionSelect) {
                            const selectionFieldName = `${type}_groups[${groupIndex}][selection_type]`;
                            const forcedSelection = config.forceSelection ?? null;
                            if (forcedSelection !== null) {
                                selectionSelect.value = forcedSelection;
                                selectionSelect.classList.add('bg-light');
                                selectionSelect.disabled = true;
                                const hiddenSelection = document.createElement('input');
                                hiddenSelection.type = 'hidden';
                                hiddenSelection.name = selectionFieldName;
                                hiddenSelection.value = forcedSelection;
                                hiddenSelection.dataset.hiddenField = 'selection_type';
                                selectionSelect.parentNode.appendChild(hiddenSelection);
                                group.selection_type = forcedSelection;
                                group.selection_type_override = null;
                            } else {
                                selectionSelect.disabled = false;
                                selectionSelect.classList.remove('bg-light');
                                const existingHidden = selectionSelect.parentNode.querySelector('input[data-hidden-field="selection_type"]');
                                if (existingHidden) {
                                    existingHidden.remove();
                                }
                                selectionSelect.name = selectionFieldName;
                                selectionSelect.value = group.selection_type_override
                                    ?? group.selection_type
                                    ?? (type === 'variant' ? 'single' : 'multiple');
                                selectionSelect.addEventListener('change', (event) => {
                                    const value = event.target.value;
                                    group.selection_type_override = value === (optionState.lookup.available[type].get(String(group.option_group_id))?.selection_type ?? group.selection_type)
                                        ? null
                                        : value;
                                    group.selection_type = value;
                                    if (type === 'variant') {
                                        // ensure single default when switching to single
                                        if (value === 'single') {
                                            let defaultSet = false;
                                            group.items.forEach((item, index) => {
                                                if (item.resolved_is_default) {
                                                    if (!defaultSet) {
                                                        defaultSet = true;
                                                    } else {
                                                        item.resolved_is_default = false;
                                                        item.is_default_override = item.base_is_default ? null : false;
                                                        const checkbox = card.querySelectorAll('[data-input="is_default"]')[index];
                                                        if (checkbox) {
                                                            checkbox.checked = false;
                                                        }
                                                    }
                                                }
                                            });
                                        }
                                    }
                                    updateGroupSummary(type, group, card);
                                    refreshOptionPreview();
                                });
                            }
                        }

                        const minInput = card.querySelector('[data-input="min_select"]');
                        if (minInput) {
                            minInput.name = `${type}_groups[${groupIndex}][min_select]`;
                            const hasForcedMin = Object.prototype.hasOwnProperty.call(config, 'forceMin') && config.forceMin !== null && config.forceMin !== undefined;
                            if (hasForcedMin) {
                                const forcedMin = Number(config.forceMin);
                                minInput.value = forcedMin;
                                minInput.readOnly = true;
                                minInput.classList.add('bg-light');
                                group.min_select = forcedMin;
                                group.min_select_override = null;
                            } else {
                                const overrideValue = group.min_select_override;
                                minInput.value = overrideValue === null || overrideValue === undefined ? (group.min_select ?? '') : overrideValue;
                                minInput.addEventListener('input', (event) => {
                                    const value = event.target.value;
                                    group.min_select_override = value === '' ? null : Number(value);
                                    group.min_select = group.min_select_override === null ? (optionState.lookup.available[type].get(String(group.option_group_id))?.min_select ?? (type === 'variant' ? 1 : 0)) : group.min_select_override;
                                    updateGroupSummary(type, group, card);
                                    refreshOptionPreview();
                                });
                            }
                        }

                        const maxInput = card.querySelector('[data-input="max_select"]');
                        if (maxInput) {
                            maxInput.name = `${type}_groups[${groupIndex}][max_select]`;
                            const hasForcedMax = Object.prototype.hasOwnProperty.call(config, 'forceMax');
                            if (hasForcedMax) {
                                const forcedMax = config.forceMax;
                                if (forcedMax === null || forcedMax === undefined) {
                                    maxInput.value = '';
                                    maxInput.placeholder = 'Tidak dibatasi';
                                } else {
                                    maxInput.value = Number(forcedMax);
                                }
                                maxInput.readOnly = true;
                                maxInput.classList.add('bg-light');
                                group.max_select = forcedMax === undefined ? null : forcedMax;
                                group.max_select_override = null;
                            } else {
                                const overrideValue = group.max_select_override;
                                maxInput.value = overrideValue === null || overrideValue === undefined ? (group.max_select ?? '') : overrideValue;
                                maxInput.addEventListener('input', (event) => {
                                    const value = event.target.value;
                                    group.max_select_override = value === '' ? null : Number(value);
                                    group.max_select = group.max_select_override === null ? (optionState.lookup.available[type].get(String(group.option_group_id))?.max_select ?? null) : group.max_select_override;
                                    updateGroupSummary(type, group, card);
                                    refreshOptionPreview();
                                });
                            }
                        }

                        const requiredInput = card.querySelector('[data-input="is_required"]');
                        if (requiredInput) {
                            const forcedRequired = typeof config.forceRequired === 'boolean' ? config.forceRequired : null;
                            if (forcedRequired === null) {
                                requiredInput.name = `${type}_groups[${groupIndex}][is_required]`;
                                const resolvedRequired = group.is_required_override === null || group.is_required_override === undefined
                                    ? group.is_required
                                    : !!group.is_required_override;
                                requiredInput.checked = resolvedRequired;

                                requiredInput.addEventListener('change', (event) => {
                                    console.log('Switch button clicked:', event.target.checked);
                                    const value = !!event.target.checked;
                                    const baseRequired = optionState.lookup.available[type].get(String(group.option_group_id))?.is_required ?? (type === 'variant');
                                    group.is_required = value;
                                    group.is_required_override = value === baseRequired ? null : value;
                                    updateGroupSummary(type, group, card);
                                    refreshOptionPreview();
                                });
                            } else {
                                const hiddenRequired = document.createElement('input');
                                hiddenRequired.type = 'hidden';
                                hiddenRequired.name = `${type}_groups[${groupIndex}][is_required]`;
                                hiddenRequired.value = forcedRequired ? 1 : 0;
                                requiredInput.parentNode.appendChild(hiddenRequired);
                                requiredInput.checked = forcedRequired;
                                requiredInput.disabled = true;
                                group.is_required = forcedRequired;
                                group.is_required_override = null;
                            }
                        }

                        const idInput = card.querySelector('[data-input="id"]');
                        if (idInput) {
                            idInput.name = `${type}_groups[${groupIndex}][id]`;
                            idInput.value = group.id ?? '';
                        }

                        const optionGroupIdInput = card.querySelector('[data-input="option_group_id"]');
                        if (optionGroupIdInput) {
                            optionGroupIdInput.name = `${type}_groups[${groupIndex}][option_group_id]`;
                            optionGroupIdInput.value = group.option_group_id ?? '';
                        }

                        const sortOrderInput = card.querySelector('[data-input="sort_order"]');
                        if (sortOrderInput) {
                            sortOrderInput.name = `${type}_groups[${groupIndex}][sort_order]`;
                            sortOrderInput.value = groupIndex;
                        }

                        const itemsContainer = card.querySelector('[data-option-items]');
                        const headerRow = card.querySelector('[data-items-header]');
                        if (headerRow) {
                            headerRow.querySelectorAll('[data-field]').forEach((th) => {
                                const field = th.getAttribute('data-field');
                                let visible = true;
                                if (field === 'stock') {
                                    visible = optionConfig[type].showStock;
                                } else if (field === 'sku') {
                                    visible = optionConfig[type].showSku;
                                } else if (field === 'max_quantity') {
                                    visible = optionConfig[type].showMaxQuantity;
                                } else if (field === 'price_adjustment' && optionConfig[type].lockPrice) {
                                    th.innerHTML = 'Harga Tambahan <span class="badge badge-info">N/A</span>';
                                }
                                th.classList.toggle('d-none', !visible);
                            });
                        }

                        group.items.forEach((item, itemIndex) => {
                            const itemTemplate = optionTemplates.item;
                            if (!itemTemplate) {
                                return;
                            }
                            const row = itemTemplate.content.firstElementChild.cloneNode(true);

                            row.querySelectorAll('[data-field]').forEach((td) => {
                                const field = td.getAttribute('data-field');
                                let visible = true;
                                if (field === 'stock') {
                                    visible = optionConfig[type].showStock;
                                } else if (field === 'sku') {
                                    visible = optionConfig[type].showSku;
                                } else if (field === 'max_quantity') {
                                    visible = optionConfig[type].showMaxQuantity;
                                }
                                td.classList.toggle('d-none', !visible);
                            });

                            const nameEl = row.querySelector('[data-item-name]');
                            if (nameEl) {
                                nameEl.textContent = item.name || 'Opsi';
                            }

                            const metaEl = row.querySelector('[data-item-meta]');
                            if (metaEl) {
                                const metaParts = [];
                                if (optionConfig[type].showStock && item.base_stock !== null && item.base_stock !== undefined && item.base_stock !== '') {
                                    metaParts.push(`Stok dasar: ${item.base_stock}`);
                                }
                                if (optionConfig[type].showSku && item.base_sku) {
                                    metaParts.push(`SKU: ${item.base_sku}`);
                                }
                                metaEl.textContent = metaParts.join(' • ');
                            }

                            const priceInput = row.querySelector('[data-input="price_adjustment"]');
                            if (priceInput) {
                                priceInput.name = `${type}_groups[${groupIndex}][${group.items_key}][${itemIndex}][price_adjustment_override]`;
                                const basePriceLabel = row.querySelector('[data-base-price]');
                                if (config.lockPrice) {
                                    priceInput.value = '';
                                    priceInput.readOnly = true;
                                    priceInput.placeholder = '—';
                                    priceInput.classList.add('bg-light');
                                    if (basePriceLabel) {
                                        basePriceLabel.textContent = 'Dasar: Rp0';
                                    }
                                    item.price_adjustment_override = null;
                                    item.resolved_price_adjustment = 0;
                                } else {
                                    priceInput.value = item.price_adjustment_override === null || item.price_adjustment_override === undefined ? '' : item.price_adjustment_override;
                                    priceInput.addEventListener('input', (event) => {
                                        const value = event.target.value.trim();
                                        if (value === '') {
                                            item.price_adjustment_override = null;
                                        } else {
                                            const numeric = Number(value);
                                            item.price_adjustment_override = Number.isFinite(numeric) ? numeric : null;
                                        }
                                        item.resolved_price_adjustment = item.price_adjustment_override === null ? item.base_price_adjustment : item.price_adjustment_override;
                                        const baseLabel = row.querySelector('[data-base-price]');
                                        if (baseLabel) {
                                            baseLabel.textContent = `Dasar: ${formatCurrency(item.base_price_adjustment)}`;
                                        }
                                        refreshOptionPreview();
                                    });
                                    if (basePriceLabel) {
                                        basePriceLabel.textContent = `Dasar: ${formatCurrency(item.base_price_adjustment)}`;
                                    }
                                }
                            }

                            const stockInput = row.querySelector('[data-input="stock"]');
                            if (stockInput) {
                                stockInput.name = `${type}_groups[${groupIndex}][${group.items_key}][${itemIndex}][stock]`;
                                stockInput.value = item.stock_override === null || item.stock_override === undefined ? '' : item.stock_override;
                                stockInput.addEventListener('input', (event) => {
                                    const value = event.target.value.trim();
                                    if (value === '') {
                                        item.stock_override = null;
                                    } else {
                                        const numeric = Number(value);
                                        item.stock_override = Number.isFinite(numeric) ? numeric : null;
                                    }
                                    item.resolved_stock = item.stock_override === null ? item.base_stock : item.stock_override;
                                    const baseLabel = row.querySelector('[data-base-stock]');
                                    if (baseLabel) {
                                        baseLabel.textContent = item.base_stock !== null && item.base_stock !== undefined && item.base_stock !== ''
                                            ? `Dasar: ${item.base_stock}`
                                            : 'Dasar: —';
                                    }
                                    refreshOptionPreview();
                                });
                                const baseLabel = row.querySelector('[data-base-stock]');
                                if (baseLabel) {
                                    baseLabel.textContent = item.base_stock !== null && item.base_stock !== undefined && item.base_stock !== ''
                                        ? `Dasar: ${item.base_stock}`
                                        : 'Dasar: —';
                                }
                            }

                            const skuInput = row.querySelector('[data-input="sku"]');
                            if (skuInput) {
                                skuInput.name = `${type}_groups[${groupIndex}][${group.items_key}][${itemIndex}][sku]`;
                                skuInput.value = item.sku_override === null || item.sku_override === undefined ? '' : item.sku_override;
                                skuInput.addEventListener('input', (event) => {
                                    const value = event.target.value.trim();
                                    item.sku_override = value === '' ? null : value;
                                    item.resolved_sku = item.sku_override === null ? item.base_sku : item.sku_override;
                                    const baseLabel = row.querySelector('[data-base-sku]');
                                    if (baseLabel) {
                                        baseLabel.textContent = item.base_sku ? `Dasar: ${item.base_sku}` : 'Dasar: —';
                                    }
                                    refreshOptionPreview();
                                });
                                const baseLabel = row.querySelector('[data-base-sku]');
                                if (baseLabel) {
                                    baseLabel.textContent = item.base_sku ? `Dasar: ${item.base_sku}` : 'Dasar: —';
                                }
                            }

                            const maxInput = row.querySelector('[data-input="max_quantity"]');
                            if (maxInput) {
                                maxInput.name = `${type}_groups[${groupIndex}][${group.items_key}][${itemIndex}][max_quantity]`;
                                if (config.forceMax !== undefined) {
                                    if (config.forceMax === null || config.forceMax === undefined) {
                                        maxInput.value = '';
                                        maxInput.placeholder = 'Tidak dibatasi';
                                    } else {
                                        maxInput.value = config.forceMax;
                                    }
                                    maxInput.readOnly = true;
                                    maxInput.classList.add('bg-light');
                                    item.max_quantity_override = null;
                                    item.resolved_max_quantity = config.forceMax === undefined ? null : config.forceMax;
                                } else {
                                    maxInput.value = item.max_quantity_override === null || item.max_quantity_override === undefined ? '' : item.max_quantity_override;
                                    maxInput.addEventListener('input', (event) => {
                                        const value = event.target.value.trim();
                                        if (value === '') {
                                            item.max_quantity_override = null;
                                        } else {
                                            const numeric = Number(value);
                                            item.max_quantity_override = Number.isFinite(numeric) ? numeric : null;
                                        }
                                        item.resolved_max_quantity = item.max_quantity_override === null ? item.base_max_quantity : item.max_quantity_override;
                                        const baseLabel = row.querySelector('[data-base-max]');
                                        if (baseLabel) {
                                            baseLabel.textContent = item.base_max_quantity !== null && item.base_max_quantity !== undefined && item.base_max_quantity !== ''
                                                ? `Dasar: ${item.base_max_quantity}`
                                                : 'Dasar: —';
                                        }
                                        refreshOptionPreview();
                                    });
                                }
                                const baseLabel = row.querySelector('[data-base-max]');
                                if (baseLabel) {
                                    baseLabel.textContent = item.base_max_quantity !== null && item.base_max_quantity !== undefined && item.base_max_quantity !== ''
                                        ? `Dasar: ${item.base_max_quantity}`
                                        : 'Dasar: —';
                                }
                            }

                            const defaultCell = row.querySelector('[data-field="is_default"]');
                            const defaultInput = row.querySelector('[data-input="is_default"]');
                            if (defaultCell && defaultInput) {
                                if (!config.allowDefault) {
                                    defaultCell.innerHTML = '<span class="text-muted small">Tidak tersedia</span>';
                                    const hiddenDefault = document.createElement('input');
                                    hiddenDefault.type = 'hidden';
                                    hiddenDefault.name = `${type}_groups[${groupIndex}][${group.items_key}][${itemIndex}][is_default]`;
                                    hiddenDefault.value = 0;
                                    defaultCell.appendChild(hiddenDefault);
                                    item.resolved_is_default = false;
                                    item.is_default_override = null;
                                } else {
                                    defaultInput.name = `${type}_groups[${groupIndex}][${group.items_key}][${itemIndex}][is_default]`;
                                    defaultInput.checked = !!item.resolved_is_default;
                                    defaultInput.addEventListener('change', (event) => {
                                        console.log('Default switch clicked:', event.target.checked);
                                        const value = !!event.target.checked;
                                        item.resolved_is_default = value;
                                        item.is_default_override = value === item.base_is_default ? null : value;
                                        if (type === 'variant' && group.selection_type === 'single' && value) {
                                            const checkboxes = Array.from(card.querySelectorAll('[data-input="is_default"]'));
                                            group.items.forEach((otherItem, otherIndex) => {
                                                if (otherItem === item) {
                                                    return;
                                                }
                                                otherItem.resolved_is_default = false;
                                                otherItem.is_default_override = otherItem.base_is_default ? null : false;
                                                const otherCheckbox = checkboxes[otherIndex];
                                                if (otherCheckbox) {
                                                    otherCheckbox.checked = false;
                                                }
                                            });
                                        }
                                        refreshOptionPreview();
                                    });
                                }
                            }

                            const activeInput = row.querySelector('[data-input="is_active"]');
                            if (activeInput) {
                                activeInput.name = `${type}_groups[${groupIndex}][${group.items_key}][${itemIndex}][is_active]`;
                                activeInput.checked = item.resolved_is_active !== false;
                                activeInput.addEventListener('change', (event) => {
                                    console.log('Active switch clicked:', event.target.checked);
                                    const value = !!event.target.checked;
                                    item.resolved_is_active = value;
                                    item.is_active_override = value === item.base_is_active ? null : value;
                                    refreshOptionPreview();
                                });
                            }

                            const itemIdInput = row.querySelector('[data-input="id"]');
                            if (itemIdInput) {
                                itemIdInput.name = `${type}_groups[${groupIndex}][${group.items_key}][${itemIndex}][id]`;
                                itemIdInput.value = item.id ?? '';
                            }

                            const optionItemIdInput = row.querySelector('[data-input="option_item_id"]');
                            if (optionItemIdInput) {
                                optionItemIdInput.name = `${type}_groups[${groupIndex}][${group.items_key}][${itemIndex}][option_item_id]`;
                                optionItemIdInput.value = item.option_item_id ?? '';
                            }

                            const sortOrderInput = row.querySelector('[data-input="sort_order"]');
                            if (sortOrderInput) {
                                sortOrderInput.name = `${type}_groups[${groupIndex}][${group.items_key}][${itemIndex}][sort_order]`;
                                sortOrderInput.value = itemIndex;
                            }

                            itemsContainer.appendChild(row);
                        });

                        const removeBtn = card.querySelector('[data-remove-option]');
                        if (removeBtn) {
                            removeBtn.addEventListener('click', () => {
                                optionState.selected[type] = optionState.selected[type].filter((g) => String(g.option_group_id) !== String(group.option_group_id));
                                renderOptionSection(type);
                                refreshOptionPreview();
                            });
                        }

                        updateGroupSummary(type, group, card);
                        container.appendChild(card);
                    });

                    updateSelectOptions(type);
                };

                optionSections.forEach((section, type) => {
                    const { addButton, select } = section;
                    if (addButton) {
                        addButton.addEventListener('click', () => {
                            if (!select || !select.value) {
                                return;
                            }
                            const optionId = select.value;
                            const baseGroup = optionState.lookup.available[type].get(optionId);
                            if (!baseGroup) {
                                return;
                            }
                            if (optionState.selected[type].some((group) => String(group.option_group_id) === optionId)) {
                                select.value = '';
                                return;
                            }
                            const newGroup = syncWithBase(normalizeGroup(cloneDeep(baseGroup), type), baseGroup, type);
                            newGroup.id = null;
                            optionState.selected[type].push(newGroup);
                            renderOptionSection(type);
                            refreshOptionPreview();
                        });
                    }
                    renderOptionSection(type);
                });

                refreshOptionPreview();

                /* -------------------------------------------------- */
                /* Overview preview                                    */
                /* -------------------------------------------------- */
                const previewName = document.getElementById('preview-name');
                const previewCategory = document.getElementById('preview-category');
                const previewPrice = document.getElementById('preview-price');
                const previewHpp = document.getElementById('preview-hpp');
                const previewStock = document.getElementById('preview-stock');

                const nameInput = wizard.querySelector('input[name="name"]');
                const categorySelect = wizard.querySelector('select[name="category_id"]');
                const priceInput = wizard.querySelector('input[name="price"]');

                const refreshOverviewPreview = () => {
                    if (previewName && nameInput) {
                        previewName.textContent = nameInput.value.trim() || '—';
                    }
                    if (previewCategory && categorySelect) {
                        const option = categorySelect.selectedOptions[0];
                        previewCategory.textContent = option ? option.textContent.trim() : '—';
                    }
                    if (previewPrice && priceInput) {
                        previewPrice.textContent = formatCurrency(priceInput.value || 0);
                    }
                    if (previewHpp) {
                        previewHpp.textContent = '—';
                    }
                    if (previewStock) {
                        previewStock.textContent = '—';
                    }
                };

                if (nameInput) {
                    nameInput.addEventListener('input', refreshOverviewPreview);
                }
                if (categorySelect) {
                    categorySelect.addEventListener('change', refreshOverviewPreview);
                }
                if (priceInput) {
                    priceInput.addEventListener('input', refreshOverviewPreview);
                }

                if (formEl) {
                    formEl.addEventListener('submit', () => {
                        prepareOptionFormSubmission();
                    }, { capture: true });
                }

                refreshOverviewPreview();
                refreshRecipePreview();
                refreshOptionPreview();
            });
        </script>
    @endpush
@endonce
