@extends('layouts.app')

@section('title', 'Tambah Uang Keluar')

@section('main')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Tambah Uang Keluar</h1>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card expense-form-card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Tanggal</label>
                                            <input type="date" name="date" class="form-control" value="{{ old('date') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Kategori</label>
                                            <select name="category_id" class="form-control">
                                                <option value="">-</option>
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Vendor</label>
                                            <input type="text" name="vendor" class="form-control" value="{{ old('vendor') }}" list="vendor-suggestions">
                                            @if(isset($vendorSuggestions) && $vendorSuggestions->count())
                                                <datalist id="vendor-suggestions">
                                                    @foreach($vendorSuggestions as $v)
                                                        <option value="{{ $v }}">
                                                    @endforeach
                                                </datalist>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Catatan</label>
                                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Lampiran (JPG, PNG, PDF, maks 5MB)</label>
                                            <input type="file" name="attachment" class="form-control-file" accept="image/*,.pdf">
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h5 class="mb-3">Detail Pengeluaran</h5>
                                @php
                                    $oldItems = old('items', [[
                                        'raw_material_id' => null,
                                        'description' => null,
                                        'unit' => null,
                                        'qty' => 1,
                                        'item_price' => 0,
                                        'unit_cost' => 0,
                                        'notes' => null,
                                    ]]);
                                @endphp
                                <div class="expense-items-wrapper">
                                    <div class="expense-items-header d-none d-md-grid">
                                        <span>Bahan Baku</span>
                                        <span>Deskripsi</span>
                                        <span>Satuan</span>
                                        <span>Qty</span>
                                        <span>Harga (Total)</span>
                                        <span>Harga Satuan</span>
                                        <span>Subtotal</span>
                                        <span></span>
                                    </div>
                                    <div class="expense-items-container" data-expense-items>
                                        @foreach($oldItems as $index => $item)
                                            <div class="expense-item-card" data-row>
                                                <div class="expense-item-field">
                                                    <label class="expense-item-label d-md-none">Bahan Baku</label>
                                                    <select name="items[{{ $index }}][raw_material_id]" class="form-control" data-material-select>
                                                        <option value="">-</option>
                                                        @foreach($materials as $material)
                                                            @php
                                                                $materialCategories = $material->categories->pluck('name')->implode(', ');
                                                            @endphp
                                                            <option value="{{ $material->id }}"
                                                                data-unit="{{ $material->unit }}"
                                                                data-name="{{ $material->name }}"
                                                                data-categories="{{ $materialCategories }}"
                                                                {{ (string) ($item['raw_material_id'] ?? '') === (string) $material->id ? 'selected' : '' }}>
                                                                {{ $material->name }} ({{ $material->unit }})@if($materialCategories) • {{ $materialCategories }}@endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="expense-item-field">
                                                    <label class="expense-item-label d-md-none">Deskripsi &amp; Catatan</label>
                                                    <input type="text" class="form-control" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="Keterangan item">
                                                    <textarea name="items[{{ $index }}][notes]" class="form-control" rows="2" placeholder="Catatan tambahan">{{ $item['notes'] ?? '' }}</textarea>
                                                </div>
                                                <div class="expense-item-field">
                                                    <label class="expense-item-label d-md-none">Satuan</label>
                                                    <input type="text" class="form-control" name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? '' }}" data-unit-input readonly>
                                                </div>
                                                <div class="expense-item-field">
                                                    <label class="expense-item-label d-md-none">Qty</label>
                                                    <input type="number" step="0.0001" min="0" class="form-control" name="items[{{ $index }}][qty]" value="{{ $item['qty'] ?? 1 }}" data-qty-input>
                                                </div>
                                                <div class="expense-item-field">
                                                    <label class="expense-item-label d-md-none">Harga (Total)</label>
                                                    <input type="number" step="0.01" min="0" class="form-control" name="items[{{ $index }}][item_price]" value="{{ $item['item_price'] ?? ($item['total_cost'] ?? 0) }}" data-price-input>
                                                </div>
                                                <div class="expense-item-field">
                                                    <label class="expense-item-label d-md-none">Harga Satuan</label>
                                                    <input type="number" step="0.1" min="0" class="form-control" name="items[{{ $index }}][unit_cost]" value="{{ isset($item['unit_cost']) ? number_format((float) $item['unit_cost'], 1, '.', '') : 0 }}" data-unit-cost-input readonly>
                                                </div>
                                                <div class="expense-item-field expense-item-subtotal">
                                                    <label class="expense-item-label d-md-none">Subtotal</label>
                                                    <div class="expense-item-value" data-subtotal>Rp 0</div>
                                                </div>
                                                <div class="expense-item-actions">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>&times;</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3 expense-form-summary">
                                    <button type="button" class="btn btn-outline-primary" data-add-row><i class="fas fa-plus mr-1"></i>Tambah Item</button>
                                    <div class="text-right">
                                        <div class="h6 mb-0">Total Pengeluaran</div>
                                        <div class="h4 mb-0" id="expense-total">0</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('style')
<style>
    .expense-items-wrapper {
        width: 100%;
    }

    .expense-items-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .expense-item-card {
        display: flex;
        flex-direction: column;
        gap: .75rem;
        border: 1px solid #e4e6fc;
        border-radius: .75rem;
        padding: 1rem;
        background-color: #fff;
        box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .05);
    }

    .expense-item-field {
        display: flex;
        flex-direction: column;
        gap: .4rem;
    }

    .expense-item-label {
        font-size: .7rem;
        text-transform: uppercase;
        color: #6c757d;
        letter-spacing: .05em;
        font-weight: 600;
    }

    .expense-item-field .form-control,
    .expense-item-field select,
    .expense-item-field textarea {
        width: 100%;
        font-size: 1rem;
        box-sizing: border-box;
    }

    .expense-item-field textarea {
        min-height: 110px;
    }

    .expense-item-value {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .expense-item-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    .expense-items-header {
        display: none;
    }

    @media (max-width: 767.98px) {
        .expense-form-summary {
            flex-direction: column;
            align-items: stretch !important;
            gap: .75rem;
        }
        .expense-form-summary > div {
            text-align: left !important;
        }
    }

    @media (min-width: 768px) {
        .expense-items-wrapper {
            border: 1px solid #e4e6fc;
            border-radius: .5rem;
            overflow: hidden;
        }

        .expense-items-header {
            display: grid;
            grid-template-columns: 1.5fr 2.2fr 1fr .9fr 1.2fr 1.2fr 1.2fr auto;
            padding: .75rem 1rem;
            background: #f8f9fc;
            font-weight: 600;
            font-size: .85rem;
            letter-spacing: .03em;
            text-transform: uppercase;
            color: #6c757d;
        }

        .expense-items-container {
            gap: 0;
            background: #fff;
        }

        .expense-item-card {
            box-shadow: none;
            border-radius: 0;
            border: 0;
            border-bottom: 1px solid #eef1ff;
            padding: .75rem 1rem;
            display: grid;
            grid-template-columns: 1.5fr 2.2fr 1fr .9fr 1.2fr 1.2fr 1.2fr auto;
            column-gap: 1rem;
            align-items: center;
        }

        .expense-item-card:first-child {
            border-top: 1px solid #e4e6fc;
        }

        .expense-item-card:last-child {
            border-bottom: none;
        }

        .expense-item-card .expense-item-label {
            display: none;
        }

        .expense-item-field textarea {
            min-height: 80px;
        }

        .expense-item-value {
            font-size: 1rem;
        }

        .expense-item-actions {
            justify-content: center;
        }
    }
</style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const itemsContainer = document.querySelector('[data-expense-items]');
            const addBtn = document.querySelector('[data-add-row]');
            const totalLabel = document.getElementById('expense-total');
            const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' });

            const formatCurrency = (value) => {
                const numeric = Number.isFinite(value) ? value : 0;
                return currencyFormatter.format(numeric);
            };

            const createRow = (data = {}) => {
                const index = itemsContainer.querySelectorAll('[data-row]').length;
                const card = document.createElement('div');
                card.className = 'expense-item-card';
                card.setAttribute('data-row', '');
                card.innerHTML = `
                    <div class="expense-item-field">
                        <label class="expense-item-label d-md-none">Bahan Baku</label>
                        <select name="items[${index}][raw_material_id]" class="form-control" data-material-select>
                            <option value="">-</option>
                            @foreach($materials as $material)
                                @php
                                    $materialCategories = $material->categories->pluck('name')->implode(', ');
                                @endphp
                                <option value="{{ $material->id }}"
                                        data-unit="{{ $material->unit }}"
                                        data-name="{{ $material->name }}"
                                        data-categories="{{ $materialCategories }}">
                                    {{ $material->name }} ({{ $material->unit }})@if($materialCategories) • {{ $materialCategories }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="expense-item-field">
                        <label class="expense-item-label d-md-none">Deskripsi &amp; Catatan</label>
                        <input type="text" class="form-control" name="items[${index}][description]" placeholder="Keterangan item">
                        <textarea name="items[${index}][notes]" class="form-control" rows="2" placeholder="Catatan tambahan"></textarea>
                    </div>
                    <div class="expense-item-field">
                        <label class="expense-item-label d-md-none">Satuan</label>
                        <input type="text" class="form-control" name="items[${index}][unit]" data-unit-input>
                    </div>
                    <div class="expense-item-field">
                        <label class="expense-item-label d-md-none">Qty</label>
                        <input type="number" step="0.0001" min="0" class="form-control" name="items[${index}][qty]" value="1" data-qty-input>
                    </div>
                    <div class="expense-item-field">
                        <label class="expense-item-label d-md-none">Harga (Total)</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="items[${index}][item_price]" value="0" data-price-input>
                    </div>
                    <div class="expense-item-field">
                        <label class="expense-item-label d-md-none">Harga Satuan</label>
                        <input type="number" step="0.1" min="0" class="form-control" name="items[${index}][unit_cost]" value="0" data-unit-cost-input readonly>
                    </div>
                    <div class="expense-item-field expense-item-subtotal">
                        <label class="expense-item-label d-md-none">Subtotal</label>
                        <div class="expense-item-value" data-subtotal>Rp 0</div>
                    </div>
                    <div class="expense-item-actions">
                        <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>&times;</button>
                    </div>`;

                itemsContainer.appendChild(card);

                if (data.raw_material_id) {
                    card.querySelector('[data-material-select]').value = data.raw_material_id;
                }
                if (data.description) {
                    card.querySelector('input[name$="[description]"]').value = data.description;
                }
                if (data.notes) {
                    card.querySelector('textarea[name$="[notes]"]').value = data.notes;
                }
                if (data.unit) {
                    card.querySelector('[data-unit-input]').value = data.unit;
                }
                if (data.qty) {
                    card.querySelector('[data-qty-input]').value = data.qty;
                }
                if (typeof data.item_price !== 'undefined') {
                    card.querySelector('[data-price-input]').value = data.item_price;
                }
                if (typeof data.unit_cost !== 'undefined') {
                    card.querySelector('[data-unit-cost-input]').value = parseFloat(data.unit_cost).toFixed(1);
                }

                updateRowMeta(card);
                recalcRow(card);
            };

            const updateRowMeta = (row) => {
                const select = row.querySelector('[data-material-select]');
                const description = row.querySelector('input[name$="[description]"]');
                const unitInput = row.querySelector('[data-unit-input]');

                const selected = select.options[select.selectedIndex];
                if (selected && selected.dataset.unit) {
                    unitInput.value = selected.dataset.unit;
                }
                if (selected && selected.dataset.name && description.value.trim() === '') {
                    description.value = selected.dataset.name;
                }
            };

            const recalcRow = (row) => {
                const qty = parseFloat(row.querySelector('[data-qty-input]').value) || 0;
                const price = parseFloat(row.querySelector('[data-price-input]').value) || 0;
                const unitCostInput = row.querySelector('[data-unit-cost-input]');

                if (qty > 0) {
                    unitCostInput.value = (price / qty).toFixed(1);
                } else {
                    unitCostInput.value = '0.0';
                }

                row.querySelector('[data-subtotal]').textContent = formatCurrency(price);
                updateTotal();
            };

            const updateTotal = () => {
                let sum = 0;
                itemsContainer.querySelectorAll('[data-price-input]').forEach(input => {
                    sum += parseFloat(input.value) || 0;
                });
                totalLabel.textContent = formatCurrency(sum);
            };

            itemsContainer.addEventListener('change', (event) => {
                const row = event.target.closest('[data-row]');
                if (!row) {
                    return;
                }
                if (event.target.matches('[data-material-select]')) {
                    updateRowMeta(row);
                }
                if (event.target.matches('[data-qty-input]') || event.target.matches('[data-price-input]')) {
                    recalcRow(row);
                }
            });

            itemsContainer.addEventListener('input', (event) => {
                const row = event.target.closest('[data-row]');
                if (!row) {
                    return;
                }
                if (event.target.matches('[data-qty-input]') || event.target.matches('[data-price-input]')) {
                    recalcRow(row);
                }
            });

            itemsContainer.addEventListener('click', (event) => {
                if (event.target.closest('[data-remove-row]')) {
                    const rows = itemsContainer.querySelectorAll('[data-row]');
                    if (rows.length > 1) {
                        event.target.closest('[data-row]').remove();
                        renumberRows();
                        updateTotal();
                    }
                }
            });

            const renumberRows = () => {
                itemsContainer.querySelectorAll('[data-row]').forEach((row, idx) => {
                    row.querySelectorAll('select, input, textarea').forEach(input => {
                        const name = input.getAttribute('name');
                        if (!name) {
                            return;
                        }
                        input.setAttribute('name', name.replace(/items\[(\d+)\]/, `items[${idx}]`));
                    });
                });
            };

            addBtn.addEventListener('click', () => {
                createRow();
            });

            if (itemsContainer.querySelectorAll('[data-row]').length === 0) {
                createRow();
            } else {
                itemsContainer.querySelectorAll('[data-row]').forEach(row => {
                    updateRowMeta(row);
                    recalcRow(row);
                });
            }

            updateTotal();
        });
    </script>
@endpush
