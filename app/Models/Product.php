<?php

namespace App\Models;

use App\Models\Traits\BelongsToOutlet;
use App\Models\OrderItem;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionItem;
use App\Models\Discount;
use App\Models\DiscountProductRule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    use BelongsToOutlet;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'outlet_id',
        'name',
        'description',
        'price',
        'cost_price',
        'stock',
        'category_id',
        'image',
        'is_best_seller',
        'sync_status',
        'last_synced',
        'client_version',
        'version_id',
    ];

    protected $casts = [
        'price' => 'integer',
        'cost_price' => 'decimal:4',
        'stock' => 'integer',
        'category_id' => 'integer',
        'last_synced' => 'datetime',
        'version_id' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'default_price',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function recipe()
    {
        return $this->hasOne(ProductRecipe::class);
    }

    public function optionGroups()
    {
        return $this->hasMany(ProductOptionGroup::class)
            ->with(['optionGroup', 'optionItems.optionItem'])
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function variantGroups()
    {
        return $this->optionGroups()->whereHas('optionGroup', function ($query) {
            $query->where('type', 'variant');
        });
    }

    public function addonGroups()
    {
        return $this->optionGroups()->whereHas('optionGroup', function ($query) {
            $query->where('type', 'addon');
        });
    }

    public function preferenceGroups()
    {
        return $this->optionGroups()->whereHas('optionGroup', function ($query) {
            $query->where('type', 'preference');
        });
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function discountRules(): HasMany
    {
        return $this->hasMany(DiscountProductRule::class);
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'discount_product_rules')
            ->withPivot([
                'type_override',
                'value_override',
                'auto_apply',
                'priority',
                'valid_from',
                'valid_until',
                'outlet_id',
            ])
            ->withTimestamps();
    }

    public function activeDiscountRule(?Carbon $asOf = null): ?DiscountProductRule
    {
        $asOf = $asOf?->copy() ?? Carbon::now();

        $rules = $this->relationLoaded('discountRules')
            ? $this->discountRules
            : $this->discountRules()->with('discount')->get();

        $filtered = $rules
            ->filter(function (DiscountProductRule $rule) use ($asOf) {
                $discount = $rule->discount;
                if (! $discount || $discount->status !== 'active') {
                    return false;
                }

                if ($rule->outlet_id && $this->outlet_id && $rule->outlet_id !== $this->outlet_id) {
                    return false;
                }

                if ($discount->outlet_id && $this->outlet_id && $discount->outlet_id !== $this->outlet_id) {
                    return false;
                }

                if ($discount->expired_date && $discount->expired_date->endOfDay() < $asOf) {
                    return false;
                }

                if ($rule->valid_from && $rule->valid_from->gt($asOf)) {
                    return false;
                }

                if ($rule->valid_until && $rule->valid_until->lt($asOf)) {
                    return false;
                }

                return true;
            })
            ->values();

        if ($filtered->isEmpty()) {
            return null;
        }

        return $filtered
            ->sort(function (DiscountProductRule $a, DiscountProductRule $b) {
                $autoA = ($a->auto_apply ?? false) || ($a->discount?->auto_apply ?? false);
                $autoB = ($b->auto_apply ?? false) || ($b->discount?->auto_apply ?? false);

                if ($autoA !== $autoB) {
                    return $autoA ? -1 : 1;
                }

                $priorityA = $a->priority ?? $a->discount?->priority ?? 0;
                $priorityB = $b->priority ?? $b->discount?->priority ?? 0;
                if ($priorityA !== $priorityB) {
                    return $priorityA > $priorityB ? -1 : 1;
                }

                return $a->id <=> $b->id;
            })
            ->first();
    }

    /**
     * Calculate product base price including price adjustments for default options.
     */
    public function getDefaultPriceAttribute(): int
    {
        $base = (int) $this->price;

        $variantPrice = $base;

        $defaultVariant = $this->variantGroups()
            ->get()
            ->flatMap(function (ProductOptionGroup $group): Collection {
                return $group->optionItems
                    ->filter(fn (ProductOptionItem $item) => $item->resolvedIsActive())
                    ->sortBy(fn (ProductOptionItem $item) => $item->resolvedIsDefault() ? 0 : 1)
                    ->take(1);
            })
            ->first();

        if ($defaultVariant instanceof ProductOptionItem) {
            $variantPrice = $base + $defaultVariant->resolvedPriceAdjustment();
        }

        $defaultAddonAdjustments = $this->addonGroups()
            ->get()
            ->flatMap(function (ProductOptionGroup $group): Collection {
                return $group->optionItems
                    ->filter(fn (ProductOptionItem $item) => $item->resolvedIsActive() && $item->resolvedIsDefault())
                    ->map(fn (ProductOptionItem $item) => $item->resolvedPriceAdjustment());
            });

        return $variantPrice + $defaultAddonAdjustments->sum();
    }
}
