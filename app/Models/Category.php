<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Traits\BelongsToOutlet;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;
    use BelongsToOutlet;
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'outlet_id',
        'name',
        'image',
        'parent_id',
        'sync_status',
        'last_synced',
        'client_version',
        'version_id',
    ];

    protected $casts = [
        'last_synced' => 'datetime',
        'version_id' => 'integer',
        'deleted_at' => 'datetime',
        'parent_id' => 'integer',
    ];

    /**
     * Get the user that owns this category
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Get the products for this category
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function rawMaterials(): BelongsToMany
    {
        return $this->belongsToMany(RawMaterial::class, 'category_raw_material')
            ->withTimestamps();
    }

    /**
     * Get the parent category
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all descendants (recursive)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors (recursive)
     */
    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;
        
        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }
        
        return $ancestors;
    }

    /**
     * Get full path of category (Main > Sub > Sub-Sub)
     */
    public function getFullPathAttribute()
    {
        $path = $this->ancestors()->reverse()->pluck('name')->push($this->name);
        return $path->implode(' > ');
    }

    /**
     * Check if category is a root category (no parent)
     */
    public function isRoot()
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if category is a leaf category (no children)
     */
    public function isLeaf()
    {
        return $this->children()->count() === 0;
    }

    /**
     * Scope to get root categories only
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get categories by parent
     */
    public function scopeByParent($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Get categories as hierarchical tree structure
     */
    public static function getTree($parentId = null, $excludeId = null)
    {
        $query = static::query()
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            });
            
        if ($parentId === null) {
            $categories = $query->root()->orderBy('name')->get();
        } else {
            $categories = $query->byParent($parentId)->orderBy('name')->get();
        }
        
        return $categories->map(function ($category) use ($excludeId) {
            $category->children = static::getTree($category->id, $excludeId);
            return $category;
        });
    }

    /**
     * Get flattened list of categories with indentation for dropdowns
     */
    public static function getFlattenedList($parentId = null, $prefix = '', $excludeId = null)
    {
        $list = collect();
        $categories = static::query()
            ->when($parentId === null, function ($q) {
                $q->root();
            }, function ($q) use ($parentId) {
                $q->byParent($parentId);
            })
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            })
            ->orderBy('name')
            ->get();
            
        foreach ($categories as $category) {
            $list->push((object) [
                'id' => $category->id,
                'name' => $prefix . $category->name,
                'level' => substr_count($prefix, '─'),
            ]);
            
            $list = $list->merge(
                static::getFlattenedList($category->id, $prefix . '─ ', $excludeId)
            );
        }
        
        return $list;
    }

    /**
     * Validate that parent_id doesn't create circular reference
     */
    public function isValidParent($parentId)
    {
        if ($parentId === null) {
            return true;
        }
        
        if ($parentId == $this->id) {
            return false;
        }
        
        $descendants = $this->getAllDescendantIds();
        return !in_array($parentId, $descendants);
    }
    
    /**
     * Get all descendant IDs efficiently
     */
    public function getAllDescendantIds()
    {
        $descendants = [];
        $children = $this->children;
        
        foreach ($children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $child->getAllDescendantIds());
        }
        
        return $descendants;
    }
    
    /**
     * Boot method to register validation rules
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($category) {
            if ($category->parent_id) {
                // Validate parent exists and belongs to same user/outlet
                $parent = static::find($category->parent_id);
                if (!$parent) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator()->make([], [])->errors()->add('parent_id', 'Selected parent category does not exist.')
                    );
                }
                
                if ($parent->user_id !== $category->user_id) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator()->make([], [])->errors()->add('parent_id', 'Parent category must belong to the same user.')
                    );
                }
                
                if ($parent->outlet_id !== $category->outlet_id) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator()->make([], [])->errors()->add('parent_id', 'Parent category must belong to the same outlet.')
                    );
                }
                
                // Check for circular reference
                if ($category->id && !$category->isValidParent($category->parent_id)) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator()->make([], [])->errors()->add('parent_id', 'Cannot create circular reference in category hierarchy.')
                    );
                }
            }
        });
    }
}
