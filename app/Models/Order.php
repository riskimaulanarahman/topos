<?php

namespace App\Models;

use App\Models\Traits\BelongsToOutlet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    use BelongsToOutlet;

    protected $fillable = [
        'user_id',
        'outlet_id',
        'transaction_number', 
        'transaction_time',
        'total_price',
        'total_item',
        'sub_total',
        'discount',
        'discount_amount',
        'payment_method',
        'nominal_bayar',
        'status',
        'refund_method',
        'refund_nominal',
        'sync_status',
        'last_synced',
        'client_version',
        'version_id'
    ];

    protected $casts = [
        'transaction_time' => 'datetime',
        'total_price' => 'integer',
        'total_item' => 'integer',
        'sub_total' => 'integer',
        'discount' => 'integer',
        'discount_amount' => 'integer',
        'nominal_bayar' => 'integer',
        'refund_nominal' => 'integer',
        'last_synced' => 'datetime',
        'version_id' => 'integer',
    ];

    // public function kasir()
    // {
    //     return $this->belongsTo(User::class, 'cashier_id', 'id');
    // }

    //
    // public function orderItems()
    // {
    //     return $this->hasMany(OrderItem::class, 'order_id', 'id');
    // }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
