<?php

namespace App\Jobs;

use App\Models\DuplicationJob;
use App\Services\CatalogDuplicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDuplicationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private readonly DuplicationJob $jobRecord)
    {
        $this->onQueue('catalog');
    }

    public function handle(CatalogDuplicationService $service): void
    {
        $service->runJob($this->jobRecord->fresh());
    }
}

