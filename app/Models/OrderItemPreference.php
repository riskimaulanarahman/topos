<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'option_item_id',
        'user_id',
        'outlet_id',
        'preference_group_name',
        'preference_name',
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

    public function optionItem()
    {
        return $this->belongsTo(OptionItem::class, 'option_item_id');
    }
}
