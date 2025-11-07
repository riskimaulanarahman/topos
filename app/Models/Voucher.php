<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'is_redeemed',
        'redeemed_at',
        'expired_at'
    ];

    public function scopeUnredeemed($query)
    {
        return $query->where('is_redeemed', false);

    }

    public function isExpired()
    {
        return $this->expires_at && Carbon::now()->greaterThan($this->expires_at);
    }
}
