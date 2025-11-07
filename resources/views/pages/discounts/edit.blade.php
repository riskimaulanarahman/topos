@extends('layouts.app')

@section('title', ' Discount Forms')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/bootstrap-daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('library/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/select2/dist/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    <link rel="stylesheet" href="{{ asset('library/bootstrap-timepicker/css/bootstrap-timepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/bootstrap-tagsinput/dist/bootstrap-tagsinput.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Edit Discount Forms</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Forms</a></div>
                    <div class="breadcrumb-item">Discount</div>
                </div>
            </div>

            <div class="section-body">
                <div class="card">
                    <form action="{{ route('discount.update', $discount) }}" method="POST">
                        @csrf
                        @method('PUT')
                        {{-- <div class="card-header">
                            <h4>Input Text</h4>
                        </div> --}}
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Name</label>
                                        <input type="text"
                                            class="form-control @error('name') is-invalid @enderror"
                                            name="name" value="{{ old('name', $discount->name) }}">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Deskripsi</label>
                                        <input type="text"
                                            class="form-control @error('description') is-invalid @enderror"
                                            name="description" value="{{ old('description', $discount->description) }}">
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label d-block">Tipe Diskon</label>
                                        <div class="selectgroup w-100">
                                            <label class="selectgroup-item">
                                                <input type="radio" name="type" value="percentage" class="selectgroup-input"
                                                    {{ old('type', $discount->type) === 'percentage' ? 'checked' : '' }}>
                                                <span class="selectgroup-button">Persentase</span>
                                            </label>
                                            <label class="selectgroup-item">
                                                <input type="radio" name="type" value="fixed" class="selectgroup-input"
                                                    {{ old('type', $discount->type) === 'fixed' ? 'checked' : '' }}>
                                                <span class="selectgroup-button">Nominal</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nilai Diskon</label>
                                        <input type="number" step="0.01"
                                            class="form-control @error('value') is-invalid @enderror"
                                            name="value" value="{{ old('value', $discount->value) }}">
                                        @error('value')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label d-block">Status</label>
                                        <div class="selectgroup w-100">
                                            <label class="selectgroup-item">
                                                <input type="radio" name="status" value="active" class="selectgroup-input"
                                                    {{ old('status', $discount->status) === 'active' ? 'checked' : '' }}>
                                                <span class="selectgroup-button">Aktif</span>
                                            </label>
                                            <label class="selectgroup-item">
                                                <input type="radio" name="status" value="inactive" class="selectgroup-input"
                                                    {{ old('status', $discount->status) === 'inactive' ? 'checked' : '' }}>
                                                <span class="selectgroup-button">Nonaktif</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Berakhir Pada</label>
                                        <input type="date"
                                            class="form-control @error('expired_date') is-invalid @enderror"
                                            name="expired_date" value="{{ old('expired_date', optional($discount->expired_date)->format('Y-m-d')) }}">
                                        @error('expired_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label d-block">Ruang Lingkup</label>
                                        <select name="scope" id="discount-scope-select"
                                            class="form-control selectric @error('scope') is-invalid @enderror">
                                            <option value="global" {{ old('scope', $discount->scope) === 'global' ? 'selected' : '' }}>Global</option>
                                            <option value="outlet" {{ old('scope', $discount->scope) === 'outlet' ? 'selected' : '' }}>Outlet</option>
                                            <option value="product" {{ old('scope', $discount->scope) === 'product' ? 'selected' : '' }}>Produk Tertentu</option>
                                        </select>
                                        @error('scope')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Prioritas</label>
                                        <input type="number" name="priority"
                                            class="form-control @error('priority') is-invalid @enderror"
                                            value="{{ old('priority', $discount->priority ?? 0) }}">
                                        <small class="form-text text-muted">Semakin tinggi prioritas, diskon akan diterapkan lebih dahulu.</small>
                                        @error('priority')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" id="discount-outlet-field" style="display: none;">
                                <label>Outlet (opsional)</label>
                                <input type="number" name="outlet_id"
                                    class="form-control @error('outlet_id') is-invalid @enderror"
                                    value="{{ old('outlet_id', $discount->outlet_id) }}"
                                    placeholder="Masukkan ID Outlet">
                                <small class="form-text text-muted">Kosongkan bila diskon berlaku untuk semua outlet.</small>
                                @error('outlet_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="autoApplyCheck"
                                        name="auto_apply" value="1" {{ old('auto_apply', $discount->auto_apply) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="autoApplyCheck">Auto apply diskon saat produk dipilih</label>
                                </div>
                                @error('auto_apply')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group" id="discount-product-field" style="display: none;">
                                <label>Pilih Produk</label>
                                <select name="product_ids[]" id="discount-product-select"
                                    class="form-control select2 w-100 @error('product_ids') is-invalid @enderror"
                                    multiple data-placeholder="Pilih produk" data-width="100%"
                                    data-ajax-url="{{ route('discount.products.search') }}"
                                    data-minimum-input-length="0" data-dropdown-parent="#discount-product-field" data-allow-clear="true"
                                    style="width: 100%;">
                                    @foreach ($selectedProducts as $product)
                                        <option value="{{ $product->id }}" selected>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Pilih satu atau lebih produk ketika scope = Produk.</small>
                                @error('product_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>

            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('library/select2/dist/js/select2.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const scopeSelect = document.getElementById('discount-scope-select');
            const outletField = document.getElementById('discount-outlet-field');
            const productField = document.getElementById('discount-product-field');

            function refreshScopeFields() {
                const scope = scopeSelect.value;
                outletField.style.display = scope === 'outlet' ? 'block' : 'none';
                productField.style.display = scope === 'product' ? 'block' : 'none';
            }

            scopeSelect.addEventListener('change', refreshScopeFields);
            refreshScopeFields();

            if (window.jQuery && typeof $.fn.select2 === 'function') {
                $('.select2').each(function () {
                    const $element = $(this);
                    if ($element.hasClass('select2-hidden-accessible')) {
                        return;
                    }

                    const config = {
                        width: $element.data('width') || '100%',
                    };

                    const dropdownParentSelector = $element.data('dropdown-parent');
                    if (dropdownParentSelector) {
                        const $parent = $(dropdownParentSelector);
                        if ($parent.length) {
                            config.dropdownParent = $parent;
                        }
                    }

                    if ($element.data('placeholder')) {
                        config.placeholder = $element.data('placeholder');
                    }

                    if (typeof $element.data('allow-clear') !== 'undefined') {
                        config.allowClear = $element.data('allow-clear') === true || $element.data('allow-clear') === 'true';
                    }

                    const ajaxUrl = $element.data('ajax-url');
                    if (ajaxUrl) {
                        const minInput = parseInt($element.data('minimum-input-length'), 10);
                        if (!Number.isNaN(minInput)) {
                            config.minimumInputLength = minInput;
                        }

                        const ajaxConfig = {
                            url: ajaxUrl,
                            dataType: 'json',
                            delay: 300,
                            cache: true,
                            data(params) {
                                return {
                                    q: params.term || '',
                                    page: params.page || 1,
                                };
                            },
                            processResults(data) {
                                return {
                                    results: data.results || [],
                                    pagination: {
                                        more: data.pagination ? Boolean(data.pagination.more) : false,
                                    },
                                };
                            },
                        };

                        const csrfTag = document.querySelector('meta[name="csrf-token"]');
                        if (csrfTag && csrfTag.content) {
                            ajaxConfig.headers = {
                                'X-CSRF-TOKEN': csrfTag.content,
                            };
                        }

                        config.ajax = ajaxConfig;
                    }

                    $element.select2(config);
                });
            }
        });
    </script>
@endpush
