<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_from_id',
        'raw_material_to_id',
        'outlet_from_id',
        'outlet_to_id',
        'qty',
        'notes',
        'movement_out_id',
        'movement_in_id',
        'initiated_by',
        'transferred_at',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'transferred_at' => 'datetime',
    ];

    public function sourceMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_from_id');
    }

    public function targetMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_to_id');
    }

    public function sourceOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_from_id');
    }

    public function targetOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_to_id');
    }

    public function movementOut(): BelongsTo
    {
        return $this->belongsTo(RawMaterialMovement::class, 'movement_out_id');
    }

    public function movementIn(): BelongsTo
    {
        return $this->belongsTo(RawMaterialMovement::class, 'movement_in_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
