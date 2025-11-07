<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'status',
        'expired_date',
        'user_id',
        'outlet_id',
        'scope',
        'auto_apply',
        'priority',
    ];

    protected $casts = [
        'auto_apply' => 'boolean',
        'value' => 'decimal:2',
        'expired_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function productRules(): HasMany
    {
        return $this->hasMany(DiscountProductRule::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'discount_product_rules')
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
}
