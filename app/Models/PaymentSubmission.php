<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSubmission extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'payment_account_id',
        'plan_code',
        'plan_name',
        'plan_duration',
        'base_amount',
        'unique_code',
        'paid_amount',
        'payment_channel',
        'transferred_at',
        'payer_name',
        'customer_note',
        'destination_snapshot',
        'proof_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'metadata',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'transferred_at' => 'datetime',
        'destination_snapshot' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
