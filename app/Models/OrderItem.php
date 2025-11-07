<?php

namespace App\Models;

use App\Models\OrderItemPreference;
use App\Models\Discount;
use App\Models\Traits\BelongsToOutlet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    use BelongsToOutlet;

    protected $fillable = [
        'order_id',
        'outlet_id',
        'product_id',
        'quantity',
        'unit_price_before_discount',
        'unit_price_after_discount',
        'discount_amount',
        'applied_discount_type',
        'applied_discount_value',
        'applied_discount_id',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_before_discount' => 'integer',
        'unit_price_after_discount' => 'integer',
        'discount_amount' => 'integer',
        'applied_discount_value' => 'decimal:2',
        'total_price' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id')->withTrashed();
    }

    public function variantSelections()
    {
        return $this->hasMany(OrderItemVariant::class);
    }

    public function addonSelections()
    {
        return $this->hasMany(OrderItemAddon::class);
    }

    public function preferenceSelections()
    {
        return $this->hasMany(OrderItemPreference::class);
    }

    /**
     * @deprecated Use preferenceSelections()
     */
    public function modifierSelections()
    {
        return $this->preferenceSelections();
    }

    public function appliedDiscount()
    {
        return $this->belongsTo(Discount::class, 'applied_discount_id');
    }

    public function getUnitPriceAttribute(): int
    {
        if ($this->unit_price_after_discount) {
            return (int) $this->unit_price_after_discount;
        }

        $quantity = max(1, (int) $this->quantity);
        return (int) round($this->total_price / $quantity);
    }
}
