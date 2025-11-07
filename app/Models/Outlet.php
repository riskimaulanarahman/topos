<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Outlet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'notes',
        'created_by',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(OutletUserRole::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'outlet_user_roles')
            ->wherePivot('role', 'owner');
    }

    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'outlet_user_roles')
            ->wherePivot('role', 'partner');
    }

    public function printerSetting(): HasOne
    {
        return $this->hasOne(PrinterSetting::class, 'outlet_id');
    }
}
