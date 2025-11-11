@extends('layouts.app')

@section('title', 'Products')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
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
                                                        <div class="d-flex flex-wrap justify-content-center">
                                                            <div class="btn-group btn-group-sm mb-2" role="group" aria-label="CRUD Actions">
                                                                <a href='{{ route('product.edit', $product->id) }}' class="btn btn-info">
                                                                    <i class="fas fa-edit"></i> Edit
                                                                </a>
                                                                <form action="{{ route('product.destroy', $product->id) }}" method="POST" class="js-product-delete-form" data-name="{{ $product->name }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button class="btn btn-danger">
                                                                        <i class="fas fa-times"></i> Delete
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
