<?php

namespace App\Models;

use App\Models\Category;
use App\Models\Traits\BelongsToOutlet;
use App\Models\Traits\Blameable;
use App\Models\User;
use App\Services\PartnerCategoryAccessService;
use App\Support\OutletContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class RawMaterial extends Model
{
    use HasFactory, Blameable, BelongsToOutlet;

    protected $fillable = [
        'sku',
        'name',
        'unit',
        'unit_cost',
        'stock_qty',
        'min_stock',
        'created_by',
        'updated_by',
        'outlet_id',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'stock_qty' => 'decimal:4',
        'min_stock' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::saving(function (RawMaterial $material) {
            if (! $material->sku) {
                $material->sku = static::generateSku($material->name);
            }
        });

        static::creating(function (RawMaterial $material) {
            if (is_null($material->stock_qty)) {
                $material->stock_qty = 0;
            }
        });
    }

    public function movements()
    {
        return $this->hasMany(RawMaterialMovement::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function expenseItems()
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function recipeItems()
    {
        return $this->hasMany(ProductRecipeItem::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_raw_material')
            ->withTimestamps();
    }

    public function scopeAccessibleBy($query, ?User $user)
    {
        if (! $user || $user->roles !== 'admin') {
            $userId = $user?->id;
            $query->where(function ($q) use ($userId) {
                if ($userId) {
                    $q->where('created_by', $userId);
                }
                $q->orWhereNull('created_by');
            });
        }

        $role = OutletContext::currentRole();
        $outlet = OutletContext::currentOutlet();

        if ($user && $role && $role->role === 'partner' && $outlet) {
            /** @var PartnerCategoryAccessService $service */
            $service = app(PartnerCategoryAccessService::class);
            $categoryIds = $service->accessibleCategoryIdsFor($user, $outlet);

            if ($categoryIds === ['*']) {
                return;
            }

            if (empty($categoryIds)) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }
    }

    public static function generateSku(?string $name): string
    {
        $base = Str::upper(Str::slug($name ?? 'RM', ''));
        if ($base === '') {
            $base = 'RM';
        }

        $base = substr($base, 0, 8) ?: 'RM';
        $candidate = $base;
        $suffix = 1;

        while (static::where('sku', $candidate)->exists()) {
            $candidate = sprintf('%s-%02d', $base, $suffix);
            $suffix++;
            if ($suffix > 99) {
                $candidate = $base . '-' . Str::upper(Str::random(4));
                break;
            }
        }

        return $candidate;
    }
}
