<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_name',
        'name',
        'email',
        'password',
        'phone',
        'roles',
        'trial_started_at',
        'subscription_expires_at',
        'subscription_status',
        'store_logo_path',
        'store_description',
        'operating_hours',
        'store_addresses',
        'map_links',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'store_logo_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'trial_started_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'subscription_status' => SubscriptionStatus::class,
        'operating_hours' => 'array',
        'store_addresses' => 'array',
        'map_links' => 'array',
    ];

    /**
     * Additional attributes to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'store_logo_url',
    ];

    /**
     * Get the categories for this user
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the products for this user
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function outletRoles()
    {
        return $this->hasMany(OutletUserRole::class);
    }

    public function outlets()
    {
        return $this->belongsToMany(Outlet::class, 'outlet_user_roles')
            ->withPivot([
                'role',
                'status',
                'can_manage_stock',
                'can_manage_expense',
                'can_manage_sales',
                'accepted_at',
            ]);
    }

    public function ownedOutlets()
    {
        return $this->outlets()->wherePivot('role', 'owner');
    }

    /**
     * Get the orders for this user
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getStoreLogoUrlAttribute(): ?string
    {
        if (! $this->store_logo_path) {
            return null;
        }

        if (
            str_starts_with($this->store_logo_path, 'http://') ||
            str_starts_with($this->store_logo_path, 'https://')
        ) {
            return $this->store_logo_path;
        }

        $publicFile = public_path($this->store_logo_path);
        if ($publicFile && File::exists($publicFile)) {
            return asset($this->store_logo_path);
        }

        if (Storage::disk('public')->exists($this->store_logo_path)) {
            return Storage::disk('public')->url($this->store_logo_path);
        }

        return asset($this->store_logo_path);
    }
}
