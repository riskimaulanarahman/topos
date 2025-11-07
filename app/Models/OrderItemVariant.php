<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OptionItem;
use App\Models\ProductVariant;

class OrderItemVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'product_variant_id',
        'option_item_id',
        'user_id',
        'outlet_id',
        'variant_group_name',
        'variant_name',
        'price_adjustment',
    ];

    protected $casts = [
        'price_adjustment' => 'integer',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function optionItem()
    {
        return $this->belongsTo(OptionItem::class, 'option_item_id');
    }
}
