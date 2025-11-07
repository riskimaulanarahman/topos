<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTemporaryItem extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'order_temporary_id',
        'product_id',
        'quantity',
        'total_price'
    ];

    public function orderTemporary()
    {
        return $this->belongsTo(OrderTemporary::class, 'order_temporary_id', 'id');
    }


    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id')->withTrashed();
    }
}
