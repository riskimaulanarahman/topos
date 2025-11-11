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
                                        <div class="input-group mr-2">
                                            <input type="text" class="form-control" placeholder="Search name..." name="name" value="{{ request('name') }}">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                        <div class="form-group mr-2">
                                            <select name="view_mode" class="form-control" onchange="this.form.submit()">
                                                <option value="tree" {{ $viewMode == 'tree' ? 'selected' : '' }}>Tree View</option>
                                                <option value="flat" {{ $viewMode == 'flat' ? 'selected' : '' }}>Flat View</option>
                                            </select>
                                        </div>
                                        @if (request()->filled('name') || $viewMode != 'tree')
                                            <a href="{{ route('category.index') }}" class="btn btn-outline-secondary">
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
                                @if($viewMode === 'tree')
                                    <div class="category-tree">
                                        @include('pages.categories.partials.tree', ['categories' => $categories, 'level' => 0])
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table-striped table">
                                            <tr>
                                                <th>Id</th>
                                                <th>Category</th>
                                                <th>Parent</th>
                                                <th>Image</th>
                                                <th>Action</th>
                                            </tr>
                                            @forelse ($categories as $category)
                                                <tr>
                                                    <td>{{ $category->id }}</td>
                                                    <td>
                                                        @if($category->parent_id)
                                                            <span class="text-muted">{{ str_repeat('─ ', $category->level ?? 0) }}</span>
                                                        @endif
                                                        {{ $category->name }}
                                                    </td>
                                                    <td>
                                                        @if($category->parent)
                                                            {{ $category->parent->name }}
                                                        @else
                                                            <span class="text-muted">Main Category</span>
                                                        @endif
                                                    </td>
                                                    <td>
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
                                                    <td colspan="5" class="text-center text-muted">Belum ada kategori untuk outlet ini.</td>
                                                </tr>
                                            @endforelse
                                        </table>
                                    </div>
                                @endif
                                @if($viewMode === 'flat')
                                    <div class="float-right">
                                        {{ $categories->withQueryString()->links() }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('style')
<style>
.category-tree {
    margin: 20px 0;
}
.category-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 10px;
    background: #fff;
    transition: all 0.3s ease;
}
.category-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.category-header {
    display: flex;
    align-items: center;
    padding: 15px;
    cursor: pointer;
}
.category-info {
    display: flex;
    align-items: center;
    flex-grow: 1;
}
.category-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 15px;
}
.category-details h5 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}
.category-details .badge {
    font-size: 11px;
    margin-left: 8px;
}
.category-actions {
    display: flex;
    gap: 8px;
}
.category-children {
    padding-left: 30px;
    border-left: 2px solid #f0f0f0;
    margin-left: 25px;
    margin-top: 10px;
}
.toggle-icon {
    transition: transform 0.3s ease;
    margin-right: 10px;
    color: #6c757d;
}
.toggle-icon.collapsed {
    transform: rotate(-90deg);
}
.level-1 { margin-left: 20px; }
.level-2 { margin-left: 40px; }
.level-3 { margin-left: 60px; }
</style>
@endpush

@push('scripts')
    <!-- JS Libraries -->
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/features-posts.js') }}"></script>
    
    <script>
    function toggleCategory(element) {
        const children = element.closest('.category-item').querySelector('.category-children');
        const icon = element.querySelector('.toggle-icon');
        
        if (children) {
            children.style.display = children.style.display === 'none' ? 'block' : 'none';
            icon.classList.toggle('collapsed');
        }
    }
    
    function expandAll() {
        document.querySelectorAll('.category-children').forEach(el => el.style.display = 'block');
        document.querySelectorAll('.toggle-icon').forEach(el => el.classList.remove('collapsed'));
    }
    
    function collapseAll() {
        document.querySelectorAll('.category-children').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.toggle-icon').forEach(el => el.classList.add('collapsed'));
    }
    </script>
@endpush
