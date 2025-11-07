<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductRecipeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_recipe_id','raw_material_id','qty_per_yield','waste_pct'
    ];

    protected $casts = [
        'qty_per_yield' => 'decimal:4',
        'waste_pct' => 'decimal:2',
    ];

    public function recipe()
    {
        return $this->belongsTo(ProductRecipe::class, 'product_recipe_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}

