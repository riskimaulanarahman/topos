<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'type',
        'qty_change',
        'counted_qty',
        'unit_cost',
        'adjustment_reason',
        'reference_type',
        'reference_id',
        'notes',
        'occurred_at',
        'created_by'
    ];

    protected $casts = [
        'qty_change' => 'decimal:4',
        'counted_qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'occurred_at' => 'datetime',
    ];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
