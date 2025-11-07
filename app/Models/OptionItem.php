<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'option_group_id',
        'product_id',
        'name',
        'price_adjustment',
        'stock',
        'sku',
        'max_quantity',
        'is_default',
        'is_active',
        'sort_order',
        'sync_status',
        'last_synced',
        'client_version',
        'version_id',
        'use_product_price',
    ];

    protected $casts = [
        'price_adjustment' => 'integer',
        'stock' => 'integer',
        'max_quantity' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'product_id' => 'integer',
        'use_product_price' => 'boolean',
        'version_id' => 'integer',
        'last_synced' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(OptionGroup::class, 'option_group_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productAssignments()
    {
        return $this->hasMany(ProductOptionItem::class);
    }
}
