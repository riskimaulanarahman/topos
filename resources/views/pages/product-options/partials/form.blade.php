@php
    $isEdit = isset($optionGroup) && $optionGroup;
    $typeLabel = [
        'variant' => 'Variant',
        'addon' => 'Addon',
        'preference' => 'Preference',
    ][$type] ?? ucfirst($type);

    $selectionType = old(
        'selection_type',
        $isEdit
            ? ($optionGroup->selection_type ?? ($type === 'variant' ? 'single' : 'multiple'))
            : ($type === 'variant' ? 'single' : 'multiple')
    );

    if ($type === 'variant') {
        $selectionType = 'single';
    }

    $isRequired = $type === 'variant'
        ? true
        : ($type === 'preference'
            ? false
            : (bool) old('is_required', $isEdit ? ($optionGroup->is_required ?? false) : false));

    $minSelect = $type === 'variant'
        ? 1
        : ($type === 'preference'
            ? 0
            : (int) old('min_select', $isEdit ? ($optionGroup->min_select ?? 0) : 0));

    $maxSelect = $type === 'variant'
        ? 1
        : ($type === 'preference'
            ? null
            : old('max_select', $isEdit ? $optionGroup->max_select : null));

    $productsCollection = collect($products ?? []);
    $productOptions = $productsCollection->map(function ($product) {
        $parts = [$product->name];
        if (! empty($product->sku ?? null)) {
            $parts[] = '(' . $product->sku . ')';
        }
        $parts[] = 'Rp ' . number_format((int) ($product->price ?? 0), 0, ',', '.');
        $label = implode(' ', $parts);

        return [
            'id' => $product->id,
            'label' => $label,
            'sku' => $product->sku ?? null,
            'price' => (int) ($product->price ?? 0),
        ];
    })->values()->all();

    $itemsInput = old('items');
    if (is_array($itemsInput)) {
        $items = array_values($itemsInput);
    } elseif ($isEdit) {
        $items = $optionGroup->items->map(function (\App\Models\OptionItem $item) use ($type) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price_adjustment' => $type === 'preference' ? 0 : ($item->price_adjustment ?? 0),
                'stock' => $item->stock,
                'sku' => $item->sku,
                'product_id' => $item->product_id,
                'use_product_price' => (bool) ($item->use_product_price ?? false),
                'max_quantity' => $item->max_quantity,
                'is_default' => $item->is_default ?? false,
                'is_active' => $item->is_active ?? true,
            ];
        })->toArray();
    } else {
        $items = [];
    }

    if (empty($items)) {
        $items[] = [
            'id' => null,
            'name' => '',
            'price_adjustment' => $type === 'preference' ? 0 : 0,
            'stock' => null,
            'sku' => null,
            'product_id' => null,
            'use_product_price' => false,
            'max_quantity' => 1,
            'is_default' => false,
            'is_active' => true,
        ];
    }

    $showStock = $type === 'variant';
    $showSku = $type === 'variant';
    $showMaxQuantity = $type !== 'variant';
    $allowPriceInput = $type !== 'preference';
    $showDefaultToggle = $type !== 'preference';
    $showProductLink = $type === 'addon';
@endphp

<input type="hidden" name="type" value="{{ $type }}">

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">{{ $typeLabel }} Product Option</h4>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-lg-6">
                <label class="font-weight-semibold">Nama Option</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $optionGroup->name ?? '') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group col-md-3">
                <label class="font-weight-semibold">Tipe Pilihan</label>
                <select name="selection_type" class="form-control @error('selection_type') is-invalid @enderror" {{ $type === 'variant' ? 'disabled' : '' }}>
                    <option value="single" {{ $selectionType === 'single' ? 'selected' : '' }}>Single (pilih satu)</option>
                    <option value="multiple" {{ $selectionType === 'multiple' ? 'selected' : '' }}>Multiple (boleh lebih dari satu)</option>
                </select>
                @error('selection_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($type === 'variant')
                    <small class="form-text text-muted">Varian selalu single-choice.</small>
                    <input type="hidden" name="selection_type" value="{{ $selectionType }}">
                @elseif($type === 'preference')
                    <small class="form-text text-muted">
                        Tentukan apakah pelanggan hanya boleh memilih satu preferensi atau beberapa sekaligus.
                    </small>
                @endif
            </div>
            <div class="form-group col-md-3 d-flex align-items-center">
                <div class="custom-control custom-switch mt-4">
                    @if($type !== 'addon')
                        <input type="hidden" name="is_required" value="{{ $isRequired ? 1 : 0 }}">
                    @endif
                    <input type="checkbox" class="custom-control-input" id="is-required-option" name="is_required" value="1" {{ $isRequired ? 'checked' : '' }} {{ $type !== 'addon' ? 'disabled' : '' }}>
                    <label class="custom-control-label" for="is-required-option">Wajib Dipilih</label>
                </div>
                @if($type !== 'addon')
                    <small class="form-text text-muted">
                        {{ $type === 'variant' ? 'Setiap produk wajib memiliki satu varian.' : 'Preference bersifat opsional.' }}
                    </small>
                @endif
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-3">
                <label class="font-weight-semibold">Minimal Pilih</label>
                <input type="number" min="0" name="min_select" class="form-control @error('min_select') is-invalid @enderror {{ $type !== 'addon' ? 'bg-light' : '' }}" value="{{ $minSelect }}" {{ $type !== 'addon' ? 'readonly' : '' }}>
                @error('min_select')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($type !== 'addon')
                    <small class="form-text text-muted">
                        {{ $type === 'variant' ? 'Minimal 1 varian perlu dipilih.' : 'Preference boleh dikosongkan.' }}
                    </small>
                @endif
            </div>
            <div class="form-group col-md-3">
                <label class="font-weight-semibold">Maksimal Pilih</label>
                <input type="number" min="1" name="max_select" class="form-control @error('max_select') is-invalid @enderror {{ $type !== 'addon' ? 'bg-light' : '' }}" value="{{ $type === 'variant' ? 1 : ($maxSelect ?? '') }}" placeholder="{{ $type === 'preference' ? 'Tidak dibatasi' : 'Kosongkan = bebas' }}" {{ $type !== 'addon' ? 'readonly' : '' }}>
                @error('max_select')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($type !== 'addon')
                    <small class="form-text text-muted">
                        {{ $type === 'variant' ? 'Varian maksimum 1 pilihan.' : 'Preference tidak dibatasi.' }}
                    </small>
                @endif
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 font-weight-semibold">Daftar Opsi</h5>
            <button type="button" class="btn btn-outline-primary btn-sm" data-add-option-row>
                <i class="fas fa-plus mr-1"></i>Tambah Opsi
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered"
                   data-option-items-table
                   data-show-stock="{{ $showStock ? '1' : '0' }}"
                   data-show-sku="{{ $showSku ? '1' : '0' }}"
                   data-show-max="{{ $showMaxQuantity ? '1' : '0' }}"
                   data-lock-price="{{ $allowPriceInput ? '0' : '1' }}"
                   data-show-default="{{ $showDefaultToggle ? '1' : '0' }}"
                   data-show-product="{{ $showProductLink ? '1' : '0' }}"
                   data-products='@json($productOptions)'
                   data-allow-product-price="{{ $showProductLink ? '1' : '0' }}">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 22%">Nama Opsi</th>
                        <th style="width: 16%">Harga Tambahan{!! $allowPriceInput ? '' : ' <span class="badge badge-info">N/A</span>' !!}</th>
                        @if($showStock)
                            <th style="width: 13%">Stok Default</th>
                        @endif
                        @if($showSku)
                            <th style="width: 13%">SKU</th>
                        @endif
                        @if($showProductLink)
                            <th style="width: 17%">Produk Terkait</th>
                        @endif
                        @if($showMaxQuantity)
                        <th style="width: 13%">Qty Maks</th>
                        @endif
                        @if($showProductLink)
                            <th style="width: 16%">Gunakan Harga Produk</th>
                        @endif
                        <th style="width: 10%">Default</th>
                        <th style="width: 10%">Aktif</th>
                        <th style="width: 6%" class="text-center"><i class="fas fa-cog"></i></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        <tr data-option-row>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}">
                                <input type="text" name="items[{{ $index }}][name]" class="form-control @error('items.' . $index . '.name') is-invalid @enderror" value="{{ $item['name'] ?? '' }}" placeholder="Contoh: Large" required>
                                @error('items.' . $index . '.name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number"
                                           step="1"
                                           class="form-control @error('items.' . $index . '.price_adjustment') is-invalid @enderror {{ $allowPriceInput ? '' : 'bg-light' }}"
                                           name="items[{{ $index }}][price_adjustment]"
                                           value="{{ $allowPriceInput ? ($item['price_adjustment'] ?? 0) : 0 }}"
                                           {{ $allowPriceInput ? '' : 'readonly' }}>
                                </div>
                                @if(! $allowPriceInput)
                                    <small class="form-text text-muted">Preference tidak menambah harga.</small>
                                @endif
                                @error('items.' . $index . '.price_adjustment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </td>
                            @if($showStock)
                                <td>
                                    <input type="number" min="0" class="form-control form-control-sm @error('items.' . $index . '.stock') is-invalid @enderror" name="items[{{ $index }}][stock]" value="{{ $item['stock'] ?? '' }}" placeholder="Opsional">
                                    @error('items.' . $index . '.stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </td>
                            @endif
                            @if($showSku)
                                <td>
                                    <input type="text" class="form-control form-control-sm @error('items.' . $index . '.sku') is-invalid @enderror" name="items[{{ $index }}][sku]" value="{{ $item['sku'] ?? '' }}" placeholder="Opsional">
                                    @error('items.' . $index . '.sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </td>
                            @endif
                            @if($showProductLink)
                                <td data-slot="product">
                                    <select class="form-control form-control-sm @error('items.' . $index . '.product_id') is-invalid @enderror"
                                            name="items[{{ $index }}][product_id]">
                                        <option value="">— Tidak terhubung —</option>
                                        @foreach($productOptions as $productOption)
                                            <option value="{{ $productOption['id'] }}"
                                                {{ (int)($item['product_id'] ?? 0) === (int) $productOption['id'] ? 'selected' : '' }}>
                                                {{ $productOption['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Opsional, gunakan untuk laporan add-on.</small>
                                    @error('items.' . $index . '.product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </td>
                            @endif
                            @if($showMaxQuantity)
                                <td>
                                    <input type="number" min="1" class="form-control form-control-sm @error('items.' . $index . '.max_quantity') is-invalid @enderror" name="items[{{ $index }}][max_quantity]" value="{{ isset($item['max_quantity']) && $item['max_quantity'] !== '' ? $item['max_quantity'] : 1 }}">
                                    @error('items.' . $index . '.max_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </td>
                            @endif
                            @if($showProductLink)
                                <td data-slot="use_product_price" class="text-center align-middle">
                                    <div class="custom-control custom-switch d-inline-block">
                                        <input type="checkbox"
                                               class="custom-control-input @error('items.' . $index . '.use_product_price') is-invalid @enderror"
                                               id="use-product-price-{{ $index }}"
                                               name="items[{{ $index }}][use_product_price]"
                                               value="1" {{ !empty($item['use_product_price']) ? 'checked' : '' }}
                                               data-field="use_product_price">
                                        <label class="custom-control-label" for="use-product-price-{{ $index }}"></label>
                                    </div>
                                    <small class="text-muted d-block" data-role="product-price-helper">Jika aktif, harga tambahan mengikuti produk.</small>
                                    <small class="text-muted d-block" data-role="product-price-preview">Pilih produk untuk preview harga.</small>
                                    @error('items.' . $index . '.use_product_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </td>
                            @endif
                            <td class="text-center">
                                @if($showDefaultToggle)
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="default-{{ $index }}" name="items[{{ $index }}][is_default]" value="1" {{ !empty($item['is_default']) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="default-{{ $index }}"></label>
                                    </div>
                                @else
                                    <span class="text-muted small">Tidak tersedia</span>
                                    <input type="hidden" name="items[{{ $index }}][is_default]" value="0">
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="active-{{ $index }}" name="items[{{ $index }}][is_active]" value="1" {{ array_key_exists('is_active', $item) ? ($item['is_active'] ? 'checked' : '') : 'checked' }}>
                                    <label class="custom-control-label" for="active-{{ $index }}"></label>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm" data-remove-option-row title="Hapus opsi">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <a href="{{ route('product-options.index', ['type' => $type]) }}" class="btn btn-light">
            <i class="fas fa-arrow-left mr-1"></i>Kembali
        </a>
        <div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Option' }}
            </button>
        </div>
    </div>
</div>

<template id="option-item-row-template">
    <tr data-option-row>
        <td>
            <input type="hidden" data-field="id">
            <input type="text" class="form-control" data-field="name" placeholder="Nama opsi" required>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                <input type="number"
                       step="1"
                       class="form-control {{ $allowPriceInput ? '' : 'bg-light' }}"
                       data-field="price_adjustment"
                       value="{{ $allowPriceInput ? 0 : 0 }}"
                       {{ $allowPriceInput ? '' : 'readonly' }}>
            </div>
            @unless($allowPriceInput)
                <small class="form-text text-muted mb-0">Preference tidak menambah harga.</small>
            @endunless
        </td>
        <td data-slot="stock">
            <input type="number" min="0" class="form-control form-control-sm" data-field="stock" placeholder="Opsional">
        </td>
        <td data-slot="sku">
            <input type="text" class="form-control form-control-sm" data-field="sku" placeholder="Opsional">
        </td>
        <td data-slot="product">
            <select class="form-control form-control-sm" data-field="product_id">
                <option value="">— Tidak terhubung —</option>
            </select>
            <small class="form-text text-muted mb-0">Opsional, gunakan untuk laporan add-on.</small>
        </td>
        <td data-slot="use_product_price" class="text-center">
            <div class="custom-control custom-switch d-inline-block">
                <input type="checkbox" class="custom-control-input" data-field="use_product_price" value="1">
                <label class="custom-control-label"></label>
            </div>
            <small class="text-muted d-block" data-role="product-price-helper">Jika aktif, harga tambahan mengikuti produk.</small>
            <small class="text-muted d-block" data-role="product-price-preview">Pilih produk untuk preview harga.</small>
        </td>
        <td data-slot="max_quantity">
            <input type="number" min="1" class="form-control form-control-sm" data-field="max_quantity" value="1">
        </td>
        <td class="text-center">
            @if($showDefaultToggle)
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" data-field="is_default">
                    <label class="custom-control-label"></label>
                </div>
            @else
                <span class="text-muted small">Tidak tersedia</span>
                <input type="hidden" data-field="is_default" value="0">
            @endif
        </td>
        <td class="text-center">
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" data-field="is_active" checked>
                <label class="custom-control-label"></label>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" data-remove-option-row title="Hapus opsi">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const table = document.querySelector('[data-option-items-table]');
                if (!table) {
                    return;
                }

                const showStock = table.dataset.showStock === '1';
                const showSku = table.dataset.showSku === '1';
                const showMax = table.dataset.showMax === '1';
                const priceLocked = table.dataset.lockPrice === '1';
                const showDefaultToggle = table.dataset.showDefault === '1';
                const showProduct = table.dataset.showProduct === '1';
                const allowProductPrice = table.dataset.allowProductPrice === '1';
                const products = (() => {
                    try {
                        return JSON.parse(table.dataset.products || '[]');
                    } catch (error) {
                        return [];
                    }
                })();
                const productOptionsHtml = ['<option value="">— Tidak terhubung —</option>']
                    .concat(products.map((product) => `<option value="${product.id}">${product.label}</option>`))
                    .join('');
                const template = document.getElementById('option-item-row-template');
                const tbody = table.querySelector('tbody');
                const addBtn = document.querySelector('[data-add-option-row]');
                const formatCurrency = (value) => {
                    const number = Number.isFinite(Number(value)) ? Number(value) : 0;
                    return `Rp ${number.toLocaleString('id-ID')}`;
                };

                const updateSlotsVisibility = (row) => {
                    const stockSlot = row.querySelector('[data-slot="stock"]');
                    if (stockSlot) {
                        stockSlot.classList.toggle('d-none', !showStock);
                    }
                    const skuSlot = row.querySelector('[data-slot="sku"]');
                    if (skuSlot) {
                        skuSlot.classList.toggle('d-none', !showSku);
                    }
                    const productSlot = row.querySelector('[data-slot="product"]');
                    if (productSlot) {
                        productSlot.classList.toggle('d-none', !showProduct);
                    }
                    const productPriceSlot = row.querySelector('[data-slot="use_product_price"]');
                    if (productPriceSlot) {
                        productPriceSlot.classList.toggle('d-none', !(showProduct && allowProductPrice));
                    }
                    const maxSlot = row.querySelector('[data-slot="max_quantity"]');
                    if (maxSlot) {
                        maxSlot.classList.toggle('d-none', !showMax);
                    }
                };

                const reindexRows = () => {
                    const rows = Array.from(tbody.querySelectorAll('[data-option-row]'));
                    rows.forEach((row, index) => {
                        row.querySelectorAll('[data-field]').forEach((input) => {
                            const field = input.getAttribute('data-field');
                            const name = `items[${index}][${field}]`;
                            input.name = name;

                            if (input.type === 'checkbox') {
                                const id = `${field}-${index}`;
                                input.id = id;
                                const label = input.closest('.custom-control')?.querySelector('label.custom-control-label');
                                if (label) {
                                    label.setAttribute('for', id);
                                }
                            }
                        });
                    });
                };

                const applyProductPricing = (row) => {
                    if (!allowProductPrice) {
                        return;
                    }
                    const productSelect = row.querySelector('[data-field="product_id"]');
                    const toggle = row.querySelector('[data-field="use_product_price"]');
                    const priceInput = row.querySelector('[data-field="price_adjustment"]');
                    const pricePreview = row.querySelector('[data-role="product-price-preview"]');
                    const priceHelper = row.querySelector('[data-role="product-price-helper"]');
                    if (!priceInput) return;

                    if (!row.dataset.manualPriceInitialized) {
                        row.dataset.manualPrice = priceInput.value || '0';
                        row.dataset.manualPriceInitialized = '1';
                    }

                    const toggleChecked = toggle && toggle.checked;
                    const selectedProductId = productSelect ? productSelect.value : '';
                    const selectedProduct = products.find((product) => String(product.id) === String(selectedProductId));

                    if (toggleChecked && selectedProduct) {
                        if (!row.dataset.manualPriceSaved) {
                            row.dataset.manualPrice = priceInput.value;
                            row.dataset.manualPriceSaved = '1';
                        }
                        priceInput.value = selectedProduct.price ?? 0;
                        priceInput.readOnly = true;
                        priceInput.classList.add('bg-light');
                        if (pricePreview) {
                            pricePreview.textContent = `Harga produk diterapkan: ${formatCurrency(selectedProduct.price ?? 0)}`;
                            pricePreview.classList.remove('text-muted');
                            pricePreview.classList.add('text-success');
                        }
                        if (priceHelper) {
                            priceHelper.textContent = 'Harga tambahan mengikuti harga produk terhubung.';
                        }
                    } else {
                        if (!priceLocked) {
                            priceInput.readOnly = false;
                            priceInput.classList.remove('bg-light');
                        } else {
                            priceInput.readOnly = true;
                            priceInput.classList.add('bg-light');
                        }

                        if (!toggleChecked && row.dataset.manualPrice !== undefined) {
                            priceInput.value = row.dataset.manualPrice;
                            row.dataset.manualPriceSaved = '0';
                        }

                        if (pricePreview) {
                            if (selectedProduct) {
                                pricePreview.textContent = `Harga produk: ${formatCurrency(selectedProduct.price ?? 0)}`;
                            } else {
                                pricePreview.textContent = 'Pilih produk untuk preview harga.';
                            }
                            pricePreview.classList.remove('text-success');
                            pricePreview.classList.add('text-muted');
                        }
                        if (priceHelper) {
                            priceHelper.textContent = 'Jika aktif, harga tambahan mengikuti produk.';
                        }
                    }

                    if (pricePreview && !selectedProduct) {
                        pricePreview.classList.remove('text-success');
                        pricePreview.classList.add('text-muted');
                        pricePreview.textContent = toggleChecked
                            ? 'Pilih produk terlebih dahulu untuk menerapkan harga.'
                            : 'Pilih produk untuk preview harga.';
                    }
                };

                const bindRowEvents = (row) => {
                    const priceInput = row.querySelector('[data-field="price_adjustment"]');
                    const productSelect = row.querySelector('[data-field="product_id"]');
                    const toggle = row.querySelector('[data-field="use_product_price"]');

                    if (priceInput && !row.dataset.manualPriceInitialized) {
                        row.dataset.manualPrice = priceInput.value || '0';
                        row.dataset.manualPriceInitialized = '1';
                    }

                    if (productSelect) {
                        productSelect.addEventListener('change', () => {
                            if (allowProductPrice) {
                                applyProductPricing(row);
                            }
                        });
                    }

                    if (toggle) {
                        toggle.addEventListener('change', () => {
                            if (priceInput && allowProductPrice && toggle.checked) {
                                row.dataset.manualPrice = priceInput.value;
                            }
                            applyProductPricing(row);
                        });
                    }

                    if (priceInput && !priceLocked) {
                        priceInput.addEventListener('input', () => {
                            if (!toggle || !toggle.checked) {
                                row.dataset.manualPrice = priceInput.value;
                            }
                        });
                    }

                    applyProductPricing(row);
                };

                const addRow = (data = {}) => {
                    const clone = template.content.firstElementChild.cloneNode(true);
                    updateSlotsVisibility(clone);

                    clone.querySelectorAll('[data-field]').forEach((input) => {
                        const field = input.getAttribute('data-field');
                        if (field === 'is_default') {
                            if (!showDefaultToggle) {
                                input.type = 'hidden';
                                input.value = 0;
                                return;
                            }
                            input.type = 'checkbox';
                            input.value = 1;
                            input.checked = data[field] !== false;
                        } else if (field === 'is_active') {
                            input.type = 'checkbox';
                            input.value = 1;
                            input.checked = data[field] !== false;
                        } else if (field === 'id') {
                            input.type = 'hidden';
                            input.value = data[field] ?? '';
                        } else if (field === 'price_adjustment') {
                            input.type = 'number';
                            input.step = '1';
                            input.value = priceLocked ? 0 : (data[field] ?? 0);
                            if (priceLocked) {
                                input.readOnly = true;
                                input.classList.add('bg-light');
                            }
                        } else if (field === 'stock' || field === 'max_quantity') {
                            input.type = 'number';
                            input.min = field === 'max_quantity' ? '1' : '0';
                            const raw = data[field];
                            input.value = (raw === undefined || raw === null || raw === '')
                                ? (field === 'max_quantity' ? 1 : '')
                                : raw;
                        } else if (field === 'product_id') {
                            input.innerHTML = productOptionsHtml;
                            input.value = data[field] ?? '';
                        } else if (field === 'use_product_price') {
                            input.type = 'checkbox';
                            input.value = 1;
                            input.checked = data[field] === true || data[field] === '1' || data[field] === 1;
                        } else {
                            input.value = data[field] ?? '';
                        }
                    });

                    tbody.appendChild(clone);
                    reindexRows();
                    bindRowEvents(clone);
                };

                const ensureMinimumRow = () => {
                    if (!tbody.querySelector('[data-option-row]')) {
                        addRow();
                    }
                };

                if (addBtn) {
                    addBtn.addEventListener('click', () => {
                        addRow();
                    });
                }

                tbody.addEventListener('click', (event) => {
                    if (event.target.closest('[data-remove-option-row]')) {
                        const row = event.target.closest('[data-option-row]');
                        row.remove();
                        reindexRows();
                        ensureMinimumRow();
                    }
                });

                tbody.querySelectorAll('[data-option-row]').forEach(row => {
                    updateSlotsVisibility(row);
                    bindRowEvents(row);
                });
                reindexRows();
                ensureMinimumRow();
            });
        </script>
    @endpush
@endonce
