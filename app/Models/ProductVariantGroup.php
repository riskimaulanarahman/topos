<?php

namespace App\Models;

use App\Models\Traits\BelongsToOutlet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantGroup extends Model
{
    use HasFactory;
    use BelongsToOutlet;

    protected $fillable = [
        'product_id',
        'user_id',
        'outlet_id',
        'name',
        'is_required',
        'selection_type',
        'min_select',
        'max_select',
        'sort_order',
        'sync_status',
        'last_synced',
        'client_version',
        'version_id',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'min_select' => 'integer',
        'max_select' => 'integer',
        'sort_order' => 'integer',
        'version_id' => 'integer',
        'last_synced' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'variant_group_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
