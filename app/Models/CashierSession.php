<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'outlet_id',
        'opening_balance',
        'opened_at',
        'closing_balance',
        'closed_at',
        'opened_by',
        'closed_by',
        'status',
        'remarks',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opened_at' => 'datetime',
        'closing_balance' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function closureReport()
    {
        return $this->hasOne(CashierClosureReport::class, 'cashier_session_id');
    }

    public function outflows()
    {
        return $this->hasMany(CashierOutflow::class, 'cashier_session_id');
    }
}
