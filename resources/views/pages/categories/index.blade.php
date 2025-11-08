@extends('layouts.app')

@section('title', 'Categories')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Categories</h1>
                @if($canManageCategories ?? false)
                    <div class="section-header-button">
                        <a href="{{ route('category.create') }}" class="btn btn-primary">Add New</a>
                    </div>
                @else
                    <div class="section-header-button">
                        <a href="{{ route('category.create') }}" class="btn btn-outline-primary">Ajukan Kategori</a>
                    </div>
                @endif
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Categoris</a></div>
                    <div class="breadcrumb-item">All Category</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>


                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">

                            <div class="card-body">
                                <div class="float-left">
                                    <div class="card-header">
                                        <h4>All Category</h4>
                                    </div>
                                </div>
                                <div class="float-right mt-2">
                                    <form method="GET" action="{{ route('category.index') }}" class="form-inline">
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Search name..." name="name" value="{{ request('name') }}">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                        @if (request()->filled('name'))
                                            <a href="{{ route('category.index') }}" class="btn btn-outline-secondary ml-2">
                                                <i class="fas fa-undo"></i> Reset
                                            </a>
                                        @endif
                                    </form>
                                </div>
                                <div class="clearfix mb-3"></div>

                                @if(!($canManageCategories ?? true))
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Owner outlet harus menyetujui kategori baru. Gunakan tombol <strong>Ajukan Kategori</strong> di atas untuk mengirim permintaan.
                                    </div>
                                @endif
                                <div class="table-responsive">
                                    <table class="table-striped table">
                                        <tr>
                                            <th>Id</th>
                                            <th>Category</th>
                                            <th>Image</th>
                                            <th>Action</th>
                                        </tr>
                                        @forelse ($categories as $category)
                                            <tr>
                                                <td>{{ $category->id }}
                                                </td>
                                                <td>
                                                    {{ $category->name }}
                                                </td>
                                                <td >
                                                    @if($category->image)
                                                    <img src="{{ asset('storage/categories/'.$category->image) }}" alt=""
                                                    width="100px" class="img-thumbnail">
                                                    @else
                                                        <img src="{{ asset('img/products/product-5-50.png') }}" alt="Default Image">
                                                    @endif
                                                </td>

                                                <td>
                                                    @if($canManageCategories ?? false)
                                                        <div class="d-flex justify-content-left">
                                                            <a href='{{ route('category.edit', $category->id) }}'
                                                                class="btn btn-sm btn-info btn-icon">
                                                                <i class="fas fa-edit"></i>
                                                                Edit
                                                            </a>

                                                            <form action="{{ route('category.destroy', $category->id) }}"
                                                                method="POST" class="ml-2">
                                                                <input type="hidden" name="_method" value="DELETE" />
                                                                <input type="hidden" name="_token"
                                                                    value="{{ csrf_token() }}" />
                                                                <button class="btn btn-sm btn-danger btn-icon confirm-delete">
                                                                    <i class="fas fa-times"></i> Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">Hanya owner yang dapat mengubah.</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Belum ada kategori untuk outlet ini.</td>
                                            </tr>
                                        @endforelse


                                    </table>
                                </div>
                                <div class="float-right">
                                    {{ $categories->withQueryString()->links() }}
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

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/features-posts.js') }}"></script>
@endpush
