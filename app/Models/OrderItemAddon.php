<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OptionItem;
use App\Models\ProductAddon;

class OrderItemAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'product_addon_id',
        'option_item_id',
        'user_id',
        'outlet_id',
        'addon_group_name',
        'addon_name',
        'price_adjustment',
        'quantity',
    ];

    protected $casts = [
        'price_adjustment' => 'integer',
        'quantity' => 'integer',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function addon()
    {
        return $this->belongsTo(ProductAddon::class, 'product_addon_id');
    }

    public function optionItem()
    {
        return $this->belongsTo(OptionItem::class, 'option_item_id');
    }
}
