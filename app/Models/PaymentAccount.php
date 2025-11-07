<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'bank_name',
        'account_number',
        'account_holder',
        'channel',
        'is_active',
        'sort_order',
        'instructions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function submissions()
    {
        return $this->hasMany(PaymentSubmission::class);
    }
}

