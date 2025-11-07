<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_option_group_id',
        'option_item_id',
        'price_adjustment_override',
        'stock_override',
        'sku_override',
        'max_quantity_override',
        'is_default_override',
        'is_active_override',
        'sort_order',
        'sync_status',
        'last_synced',
        'client_version',
        'version_id',
    ];

    protected $casts = [
        'price_adjustment_override' => 'integer',
        'stock_override' => 'integer',
        'max_quantity_override' => 'integer',
        'is_default_override' => 'boolean',
        'is_active_override' => 'boolean',
        'sort_order' => 'integer',
        'version_id' => 'integer',
        'last_synced' => 'datetime',
    ];

    public function productOptionGroup()
    {
        return $this->belongsTo(ProductOptionGroup::class);
    }

    public function optionItem()
    {
        return $this->belongsTo(OptionItem::class);
    }

    public function resolvedPriceAdjustment(): int
    {
        $type = $this->productOptionGroup?->optionGroup?->type;
        if ($type === 'preference') {
            return 0;
        }

        $base = $this->optionItem?->price_adjustment ?? 0;
        if ($this->price_adjustment_override !== null) {
            return (int) $this->price_adjustment_override;
        }

        return (int) $base;
    }

    public function resolvedStock(): ?int
    {
        if ($this->stock_override !== null) {
            return (int) $this->stock_override;
        }

        return $this->optionItem?->stock === null ? null : (int) $this->optionItem->stock;
    }

    public function resolvedSku(): ?string
    {
        return $this->sku_override ?? $this->optionItem?->sku;
    }

    public function resolvedMaxQuantity(): int
    {
        $base = $this->optionItem?->max_quantity ?? 1;

        return (int) ($this->max_quantity_override ?? $base);
    }

    public function resolvedIsDefault(): bool
    {
        return $this->is_default_override ?? $this->optionItem?->is_default ?? false;
    }

    public function resolvedIsActive(): bool
    {
        return $this->is_active_override ?? $this->optionItem?->is_active ?? true;
    }
}
