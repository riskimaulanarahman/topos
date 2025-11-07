<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptionGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'option_group_id',
        'sort_order',
        'is_required_override',
        'selection_type_override',
        'min_select_override',
        'max_select_override',
        'sync_status',
        'last_synced',
        'client_version',
        'version_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_required_override' => 'boolean',
        'min_select_override' => 'integer',
        'max_select_override' => 'integer',
        'version_id' => 'integer',
        'last_synced' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function optionGroup()
    {
        return $this->belongsTo(OptionGroup::class);
    }

    public function optionItems()
    {
        return $this->hasMany(ProductOptionItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function getTypeAttribute(): string
    {
        return $this->optionGroup?->type ?? 'variant';
    }

    public function resolvedSelectionType(): string
    {
        return $this->selection_type_override ?? $this->optionGroup?->selection_type ?? 'single';
    }

    public function resolvedIsRequired(): bool
    {
        return $this->is_required_override ?? $this->optionGroup?->is_required ?? true;
    }

    public function resolvedMinSelect(): int
    {
        return $this->min_select_override ?? $this->optionGroup?->min_select ?? ($this->resolvedIsRequired() ? 1 : 0);
    }

    public function resolvedMaxSelect(): ?int
    {
        return $this->max_select_override ?? $this->optionGroup?->max_select;
    }
}
