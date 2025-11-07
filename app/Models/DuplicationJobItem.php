<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuplicationJobItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'duplication_job_id',
        'entity_type',
        'source_id',
        'target_id',
        'status',
        'notes',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public function job(): BelongsTo
    {
        return $this->belongsTo(DuplicationJob::class, 'duplication_job_id');
    }
}

