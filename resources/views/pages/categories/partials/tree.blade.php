@foreach ($categories as $category)
    <div class="category-item level-{{ $level }}">
        <div class="category-header" onclick="toggleCategory(this)">
            <div class="category-info">
                @if($category->children && $category->children->count() > 0)
                    <i class="fas fa-chevron-down toggle-icon"></i>
                @else
                    <span style="width: 20px; display: inline-block;"></span>
                @endif
                
                @if($category->image)
                    <img src="{{ asset('storage/categories/'.$category->image) }}" alt="{{ $category->name }}" class="category-image">
                @else
                    <img src="{{ asset('img/products/product-5-50.png') }}" alt="Default Image" class="category-image">
                @endif
                
                <div class="category-details">
                    <h5>{{ $category->name }}</h5>
                    <span class="badge badge-info">ID: {{ $category->id }}</span>
                    @if($category->children && $category->children->count() > 0)
                        <span class="badge badge-secondary">{{ $category->children->count() }} subcategories</span>
                    @endif
                    @if($category->products && $category->products->count() > 0)
                        <span class="badge badge-success">{{ $category->products->count() }} products</span>
                    @endif
                </div>
            </div>
            
            <div class="category-actions">
                @if($canManageCategories ?? false)
                    <a href="{{ route('category.edit', $category->id) }}" class="btn btn-sm btn-info">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    
                    <form action="{{ route('category.destroy', $category->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger confirm-delete" onclick="return confirm('Are you sure you want to delete this category?')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>
        
        @if($category->children && $category->children->count() > 0)
            <div class="category-children">
                @include('pages.categories.partials.tree', ['categories' => $category->children, 'level' => $level + 1])
            </div>
        @endif
    </div>
@endforeach