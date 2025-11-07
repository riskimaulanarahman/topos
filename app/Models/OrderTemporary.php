<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTemporary extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'status',
        'sub_total',
        'discount',
        'discount_amount',
        'tax',
        'service_charge',
        'total_price',
        'total_item'
    ];

    public function orderTemporaryItems()
    {
        return $this->hasMany(OrderTemporaryItem::class, 'order_temporary_id', 'id');
    }
}
