<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'outlet_id',
        'name',
        'type',
        'selection_type',
        'is_required',
        'min_select',
        'max_select',
        'sort_order',
        'sync_status',
        'last_synced',
        'client_version',
        'version_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'outlet_id' => 'integer',
        'is_required' => 'boolean',
        'min_select' => 'integer',
        'max_select' => 'integer',
        'sort_order' => 'integer',
        'version_id' => 'integer',
        'last_synced' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OptionItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function productAssignments()
    {
        return $this->hasMany(ProductOptionGroup::class);
    }
}
