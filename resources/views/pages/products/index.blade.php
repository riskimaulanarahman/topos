@extends('layouts.app')

@section('title', 'Products')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
    
    <style>
        .quick-action-btn {
            transition: all 0.2s ease;
        }
        .quick-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .modal .form-control-plaintext {
            padding: 0.375rem 0;
        }
        .modal .input-group-text {
            background-color: #f8f9fa;
            border-color: #ced4da;
        }
        .quick-loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Products</h1>
                @if($canManageProducts ?? false)
                    <div class="section-header-button">
                        <a href="{{ route('product.create') }}" class="btn btn-primary">Add New</a>
                    </div>
                @else
                    <div class="section-header-button">
                        <a href="{{ route('product.create') }}" class="btn btn-outline-primary">Ajukan Penambahan</a>
                    </div>
                @endif
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Products</a></div>
                    <div class="breadcrumb-item">All Products</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>
                {{-- <h2 class="section-title">Products</h2>
                <p class="section-lead">
                    You can manage all Products, such as editing, deleting and more.
                </p> --}}


                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">

                            <div class="card-body">
                                <div class="float-left">
                                    <div class="card-header">
                                        <h4>All Product</h4>
                                    </div>
                                </div>
                                <div class="float-right mt-2">
                                    <form method="GET" action="{{ route('product.index') }}" class="form-inline">
                                        <div class="form-group mr-2">
                                            <select name="category_id" class="form-control selectric">
                                                <option value="">All Categories</option>
                                                @isset($categories)
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                    @endforeach
                                                @endisset
                                            </select>
                                        </div>
                                        <div class="input-group mr-2">
                                            <input type="text" class="form-control" placeholder="Search name..." name="name" value="{{ request('name') }}">
                                        </div>
                                        <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                        @if (request()->filled('name') || request()->filled('category_id'))
                                            <a href="{{ route('product.index') }}" class="btn btn-outline-secondary ml-2">
                                                <i class="fas fa-undo"></i> Reset
                                            </a>
                                        @endif
                                    </form>
                                </div>

                                <div class="clearfix mb-3"></div>

                                @if(!($canManageProducts ?? true))
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Anda melihat produk yang sudah disetujui owner. Untuk menambah produk baru, gunakan tombol <strong>Ajukan Penambahan</strong> di atas.
                                    </div>
                                @endif

                                <div class="table-responsive">
                                    <table class="table-striped table">
                                        <tr>

                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Discount</th>
                                            <th>HPP</th>
                                            <th>Photo</th>
                                            <th>Product Options</th>
                                            <th>Created At</th>
                                            <th>Action</th>
                                        </tr>
                                        @forelse ($products as $product)
                                            <tr>

                                                <td>{{ $product->name }}
                                                </td>
                                                <td>
                                                    @if($product->category)
                                                        @if($product->category->parent_id)
                                                            <small class="text-muted">{{ $product->category->full_path }}</small>
                                                        @else
                                                            {{ $product->category->name }}
                                                        @endif
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                @php
                                                    $basePrice = (int) ($product->default_price ?? $product->price ?? 0);
                                                    $activeRule = method_exists($product, 'activeDiscountRule') ? $product->activeDiscountRule() : null;
                                                    $activeDiscount = $activeRule?->discount;
                                                    $type = $activeRule?->type_override ?? $activeDiscount?->type;
                                                    $value = $activeRule?->value_override ?? $activeDiscount?->value;

                                                    $discountAmount = 0;
                                                    if ($activeDiscount && $type) {
                                                        if ($type === 'percentage') {
                                                            $percent = min(100, max(0, (float) $value));
                                                            $discountAmount = (int) floor($basePrice * ($percent / 100));
                                                        } elseif ($type === 'fixed') {
                                                            $discountAmount = (int) min($basePrice, round((float) $value));
                                                        }
                                                    }
                                                    $discountedPrice = max(0, $basePrice - $discountAmount);
                                                @endphp
                                                <td>
                                                    @if ($discountAmount > 0)
                                                        <div class="text-muted mb-1">
                                                            <span style="text-decoration: line-through;">
                                                                {{ number_format($basePrice, 0, ',', '.') }}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class="badge badge-success">
                                                                {{ number_format($discountedPrice, 0, ',', '.') }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        {{ number_format($basePrice, 0, ',', '.') }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($discountAmount > 0 && $activeDiscount)
                                                        <div class="d-flex flex-column">
                                                            <span class="font-weight-bold">{{ $activeDiscount->name }}</span>
                                                            <small class="text-muted">
                                                                {{ $type === 'percentage' ? (float) $value . '%' : number_format((float) $value, 0, ',', '.') }}
                                                            </small>
                                                            <div>
                                                                @if (($activeRule->auto_apply ?? false) || ($activeDiscount->auto_apply ?? false))
                                                                    <span class="badge badge-primary mr-1">Auto</span>
                                                                @endif
                                                                <span class="badge badge-light">Priority {{ $activeRule->priority ?? $activeDiscount->priority ?? 0 }}</span>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php($hpp = $product->cost_price ?? null)
                                                    @if(!is_null($hpp))
                                                        <span class="badge badge-light">{{ number_format((float) $hpp, 2, ',', '.') }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($product->image)
                                                        @if (Str::contains($product->image, 'http'))
                                                            <img src="{{ $product->image }}" alt="" width="100px"
                                                                class="img-thumbnail">
                                                        @else
                                                            <img src="{{ asset('products/' . $product->image) }}"
                                                                alt="" width="100px" class="img-thumbnail">
                                                        @endif
                                                    @else
                                                        <span class="badge badge-danger">No Image</span>
                                                    @endif

                                                </td>
                                                <td>
                                                    <?php
                                                        $variantGroups = collect($product->variantGroups ?? []);
                                                        $addonGroups = collect($product->addonGroups ?? []);
                                                        $preferenceGroups = collect($product->preferenceGroups ?? []);

                                                        $hasVariants = $variantGroups->contains(function ($group) {
                                                            return collect($group->optionItems ?? [])->isNotEmpty();
                                                        });
                                                        $hasAddons = $addonGroups->contains(function ($group) {
                                                            return collect($group->optionItems ?? [])->isNotEmpty();
                                                        });
                                                        $hasPreferences = $preferenceGroups->contains(function ($group) {
                                                            return collect($group->optionItems ?? [])->isNotEmpty();
                                                        });
                                                    ?>
                                                    @if($hasVariants || $hasAddons || $hasPreferences)
                                                        <div class="d-flex flex-wrap">
                                                            @if($hasVariants)
                                                                <span class="badge badge-primary mr-1 mb-1" data-toggle="tooltip" title="Produk memiliki varian">Variant</span>
                                                            @endif
                                                            @if($hasAddons)
                                                                <span class="badge badge-success mr-1 mb-1" data-toggle="tooltip" title="Produk memiliki addon">Addon</span>
                                                            @endif
                                                            @if($hasPreferences)
                                                                <span class="badge badge-info mr-1 mb-1" data-toggle="tooltip" title="Produk memiliki preference">Preference</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>{{ $product->created_at }}</td>
                                                <td>
                                                    @if($canManageProducts ?? false)
                                                        <div class="d-flex flex-wrap justify-content-center gap-1">
                                                            <!-- Quick Actions -->
                                                            <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Quick Actions">
                                                                <button type="button" class="btn btn-warning js-quick-edit-price quick-action-btn" 
                                                                        data-product-id="{{ $product->id }}" 
                                                                        data-product-name="{{ $product->name }}"
                                                                        data-current-price="{{ $basePrice }}"
                                                                        title="Edit Harga">
                                                                    <i class="fas fa-money-bill-wave"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-success js-quick-edit-category quick-action-btn" 
                                                                        data-product-id="{{ $product->id }}" 
                                                                        data-product-name="{{ $product->name }}"
                                                                        data-current-category="{{ $product->category->id ?? '' }}"
                                                                        data-current-category-name="{{ $product->category->name ?? '' }}"
                                                                        title="Edit Kategori">
                                                                    <i class="fas fa-folder"></i>
                                                                </button>
                                                            </div>
                                                            <!-- Standard Actions -->
                                                            <div class="btn-group btn-group-sm mb-2" role="group" aria-label="CRUD Actions">
                                                                <a href='{{ route('product.edit', $product->id) }}' class="btn btn-info">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form action="{{ route('product.destroy', $product->id) }}" method="POST" class="js-product-delete-form" data-name="{{ $product->name }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button class="btn btn-danger">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="d-flex flex-column align-items-center">
                                                            <a href="{{ route('product.request.edit', $product->id) }}" class="btn btn-sm btn-outline-info mb-2">
                                                                <i class="fas fa-edit"></i> Ajukan Perubahan
                                                            </a>
                                                            <form action="{{ route('product.request.delete', $product->id) }}" method="POST" class="js-product-delete-request" data-name="{{ $product->name }}">
                                                                @csrf
                                                                <input type="hidden" name="notes" value="">
                                                                <button type="button" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-times"></i> Ajukan Penghapusan
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted">Belum ada produk untuk outlet ini.</td>
                                            </tr>
                                        @endforelse


                                    </table>
                                </div>
                                <div class="float-right">
                                    {{ $products->withQueryString()->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Quick Edit Price Modal -->
    <div class="modal fade" id="quickEditPriceModal" tabindex="-1" role="dialog" aria-labelledby="quickEditPriceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickEditPriceModalLabel">
                        <i class="fas fa-money-bill-wave"></i> Edit Harga Produk
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="quickEditPriceForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="productName" class="font-weight-bold">Nama Produk:</label>
                            <p id="productName" class="form-control-plaintext text-primary"></p>
                        </div>
                        <div class="form-group">
                            <label for="currentPrice" class="font-weight-bold">Harga Saat Ini:</label>
                            <p id="currentPrice" class="form-control-plaintext text-muted"></p>
                        </div>
                        <div class="form-group">
                            <label for="newPrice" class="font-weight-bold">Harga Baru:</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="number" class="form-control" id="newPrice" name="price" 
                                       step="0.01" min="0" max="999999999" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">.00</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">Masukkan harga baru tanpa titik atau koma.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Simpan Harga
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Edit Category Modal -->
    <div class="modal fade" id="quickEditCategoryModal" tabindex="-1" role="dialog" aria-labelledby="quickEditCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickEditCategoryModalLabel">
                        <i class="fas fa-folder"></i> Edit Kategori Produk
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="quickEditCategoryForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="categoryProductName" class="font-weight-bold">Nama Produk:</label>
                            <p id="categoryProductName" class="form-control-plaintext text-primary"></p>
                        </div>
                        <div class="form-group">
                            <label for="currentCategory" class="font-weight-bold">Kategori Saat Ini:</label>
                            <p id="currentCategory" class="form-control-plaintext text-muted"></p>
                        </div>
                        <div class="form-group">
                            <label for="newCategory" class="font-weight-bold">Kategori Baru:</label>
                            <select class="form-control" id="newCategory" name="category_id" required>
                                <option value="">-- Pilih Kategori --</option>
                                @isset($categories)
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Simpan Kategori
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/features-posts.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.$ && $.fn.tooltip) {
                $('[data-toggle="tooltip"]').tooltip();
            }

            // Quick Edit Price Modal
            const quickEditPriceModal = document.getElementById('quickEditPriceModal');
            const quickEditPriceForm = document.getElementById('quickEditPriceForm');
            let currentProductId = null;
            let currentProductRow = null;

            // Quick Edit Category Modal
            const quickEditCategoryModal = document.getElementById('quickEditCategoryModal');
            const quickEditCategoryForm = document.getElementById('quickEditCategoryForm');

            // Price edit button clicks
            document.querySelectorAll('.js-quick-edit-price').forEach(function (button) {
                button.addEventListener('click', function () {
                    const productId = this.dataset.productId;
                    const productName = this.dataset.productName;
                    const currentPrice = this.dataset.currentPrice;

                    currentProductId = productId;
                    currentProductRow = this.closest('tr');

                    // Populate modal
                    document.getElementById('productName').textContent = productName;
                    document.getElementById('currentPrice').textContent = 'Rp ' + Number(currentPrice).toLocaleString('id-ID');
                    document.getElementById('newPrice').value = currentPrice;

                    // Show modal
                    if (window.$) {
                        $('#quickEditPriceModal').modal('show');
                    } else {
                        quickEditPriceModal.style.display = 'block';
                        quickEditPriceModal.classList.add('show');
                    }
                });
            });

            // Category edit button clicks
            document.querySelectorAll('.js-quick-edit-category').forEach(function (button) {
                button.addEventListener('click', function () {
                    const productId = this.dataset.productId;
                    const productName = this.dataset.productName;
                    const currentCategoryId = this.dataset.currentCategory;
                    const currentCategoryName = this.dataset.currentCategoryName;

                    currentProductId = productId;
                    currentProductRow = this.closest('tr');

                    // Populate modal
                    document.getElementById('categoryProductName').textContent = productName;
                    document.getElementById('currentCategory').textContent = currentCategoryName || '-';
                    document.getElementById('newCategory').value = currentCategoryId;

                    // Show modal
                    if (window.$) {
                        $('#quickEditCategoryModal').modal('show');
                    } else {
                        quickEditCategoryModal.style.display = 'block';
                        quickEditCategoryModal.classList.add('show');
                    }
                });
            });

            // Quick Edit Price Form Submission
            quickEditPriceForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

                fetch(`{{ route('product.quick-update-price', ':id') }}`.replace(':id', currentProductId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        price: formData.get('price')
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update price in table
                        const priceCell = currentProductRow.querySelector('td:nth-child(3)');
                        priceCell.innerHTML = data.data.formatted_price;

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Close modal
                        if (window.$) {
                            $('#quickEditPriceModal').modal('hide');
                        } else {
                            quickEditPriceModal.style.display = 'none';
                            quickEditPriceModal.classList.remove('show');
                        }

                        // Reset form
                        quickEditPriceForm.reset();
                    } else {
                        throw new Error(data.message || 'Terjadi kesalahan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: error.message || 'Terjadi kesalahan saat memperbarui harga.'
                    });
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });

            // Quick Edit Category Form Submission
            quickEditCategoryForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

                fetch(`{{ route('product.quick-update-category', ':id') }}`.replace(':id', currentProductId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        category_id: formData.get('category_id')
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update category in table
                        const categoryCell = currentProductRow.querySelector('td:nth-child(2)');
                        if (data.data.category_full_path) {
                            categoryCell.innerHTML = `<small class="text-muted">${data.data.category_full_path}</small>`;
                        } else {
                            categoryCell.innerHTML = data.data.category_name;
                        }

                        // Update category button data
                        const categoryBtn = currentProductRow.querySelector('.js-quick-edit-category');
                        categoryBtn.dataset.currentCategory = data.data.category_id;
                        categoryBtn.dataset.currentCategoryName = data.data.category_name;

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Close modal
                        if (window.$) {
                            $('#quickEditCategoryModal').modal('hide');
                        } else {
                            quickEditCategoryModal.style.display = 'none';
                            quickEditCategoryModal.classList.remove('show');
                        }

                        // Reset form
                        quickEditCategoryForm.reset();
                    } else {
                        throw new Error(data.message || 'Terjadi kesalahan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: error.message || 'Terjadi kesalahan saat memperbarui kategori.'
                    });
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });

            // Reset forms when modals are hidden
            if (window.$) {
                $('#quickEditPriceModal').on('hidden.bs.modal', function () {
                    quickEditPriceForm.reset();
                });

                $('#quickEditCategoryModal').on('hidden.bs.modal', function () {
                    quickEditCategoryForm.reset();
                });
            }

            // Existing delete functionality
            document.querySelectorAll('.js-product-delete-form').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const name = form.dataset.name || 'produk ini';
                    Swal.fire({
                        title: 'Hapus produk?',
                        text: `Tindakan ini akan menghapus ${name}.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal'
                    }).then(result => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            document.querySelectorAll('.js-product-delete-request').forEach(function (form) {
                const button = form.querySelector('button[type="button"]');
                if (!button) return;
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const name = form.dataset.name || 'produk ini';
                    Swal.fire({
                        title: 'Ajukan penghapusan?',
                        text: `Permintaan penghapusan ${name} akan dikirim ke owner outlet.`,
                        input: 'textarea',
                        inputPlaceholder: 'Catatan untuk owner (opsional)',
                        showCancelButton: true,
                        confirmButtonText: 'Kirim Permintaan',
                        cancelButtonText: 'Batal'
                    }).then(result => {
                        if (result.isConfirmed) {
                            form.querySelector('input[name="notes"]').value = result.value || '';
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
