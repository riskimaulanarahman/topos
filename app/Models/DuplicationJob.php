<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DuplicationJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_outlet_id',
        'target_outlet_id',
        'requested_by',
        'status',
        'requested_resources',
        'options',
        'counts',
        'error_log',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'requested_resources' => 'array',
        'options' => 'array',
        'counts' => 'array',
        'error_log' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PARTIAL = 'partial';

    public function sourceOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'source_outlet_id');
    }

    public function targetOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'target_outlet_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DuplicationJobItem::class);
    }
}

