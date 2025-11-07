<?php

namespace App\Jobs;

use App\Models\CashierClosureReport;
use App\Notifications\CashierSummaryNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendCashierSummaryEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $reportId)
    {
    }

    public function handle(): void
    {
        $report = CashierClosureReport::with(['user', 'session'])->find($this->reportId);

        if (!$report || !$report->email_to) {
            return;
        }

        Notification::route('mail', $report->email_to)
            ->notify(new CashierSummaryNotification($report));

        $report->forceFill([
            'email_status' => 'sent',
            'emailed_at' => now(),
            'email_error' => null,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        $report = CashierClosureReport::find($this->reportId);

        if (!$report) {
            return;
        }

        $report->forceFill([
            'email_status' => 'failed',
            'email_error' => $exception->getMessage(),
        ])->save();
    }
}
