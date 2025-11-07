<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountProductRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'discount_id',
        'product_id',
        'outlet_id',
        'type_override',
        'value_override',
        'auto_apply',
        'priority',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'auto_apply' => 'boolean',
        'value_override' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
