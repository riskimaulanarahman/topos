<?php

namespace App\Models;

use App\Models\Traits\BelongsToOutlet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;
    use BelongsToOutlet;

    protected $fillable = [
        'variant_group_id',
        'user_id',
        'outlet_id',
        'name',
        'price_adjustment',
        'stock',
        'sku',
        'is_default',
        'is_active',
        'sort_order',
        'sync_status',
        'last_synced',
        'client_version',
        'version_id',
    ];

    protected $casts = [
        'price_adjustment' => 'integer',
        'stock' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'version_id' => 'integer',
        'last_synced' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(ProductVariantGroup::class, 'variant_group_id');
    }

}
