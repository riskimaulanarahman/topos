<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class OutletUserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'user_id',
        'role',
        'status',
        'can_manage_stock',
        'can_manage_expense',
        'can_manage_sales',
        'invitation_token',
        'invitation_sent_at',
        'accepted_at',
        'revoked_at',
        'created_by',
        'pin_last_set_at',
        'pin_last_verified_at',
    ];

    protected $casts = [
        'can_manage_stock' => 'boolean',
        'can_manage_expense' => 'boolean',
        'can_manage_sales' => 'boolean',
        'invitation_sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'pin_last_set_at' => 'datetime',
        'pin_last_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoryAssignments(): HasMany
    {
        return $this->hasMany(PartnerCategoryAssignment::class);
    }

    public function hasPin(): bool
    {
        return ! empty($this->pin_hash);
    }

    public function setPin(?string $pin): void
    {
        if (! $pin) {
            $this->pin_hash = null;
            $this->pin_last_set_at = null;
            return;
        }

        $this->pin_hash = Hash::make($pin);
        $this->pin_last_set_at = now();
    }

    public function verifyPin(string $pin): bool
    {
        if (! $this->hasPin()) {
            return false;
        }

        $valid = Hash::check($pin, $this->pin_hash);

        if ($valid) {
            $this->pin_last_verified_at = now();
        }

        return $valid;
    }
}
