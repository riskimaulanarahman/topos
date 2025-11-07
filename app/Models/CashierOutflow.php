<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierOutflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_session_id',
        'user_id',
        'outlet_id',
        'client_id',
        'amount',
        'category',
        'note',
        'is_offline',
        'recorded_at',
        'synced_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'recorded_at' => 'datetime',
        'synced_at' => 'datetime',
        'is_offline' => 'boolean',
    ];

    public function session()
    {
        return $this->belongsTo(CashierSession::class, 'cashier_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}
