<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Traits\BelongsToOutlet;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;
    use BelongsToOutlet;
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'outlet_id',
        'name',
        'image',
        'sync_status',
        'last_synced',
        'client_version',
        'version_id',
    ];

    protected $casts = [
        'last_synced' => 'datetime',
        'version_id' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns this category
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Get the products for this category
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function rawMaterials(): BelongsToMany
    {
        return $this->belongsToMany(RawMaterial::class, 'category_raw_material')
            ->withTimestamps();
    }
}
