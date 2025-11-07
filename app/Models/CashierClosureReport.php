<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierClosureReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cashier_session_id',
        'summary',
        'email_to',
        'email_status',
        'emailed_at',
        'printed_at',
        'email_error',
    ];

    protected $casts = [
        'summary' => 'array',
        'emailed_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(CashierSession::class, 'cashier_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
