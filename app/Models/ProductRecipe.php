<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id','yield_qty','unit','notes'
    ];

    protected $casts = [
        'yield_qty' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(ProductRecipeItem::class);
    }
}
