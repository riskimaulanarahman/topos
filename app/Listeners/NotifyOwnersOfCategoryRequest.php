<?php

namespace App\Listeners;

use App\Events\PartnerCategoryRequestCreated;
use App\Mail\PartnerCategoryRequestSubmittedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyOwnersOfCategoryRequest implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PartnerCategoryRequestCreated $event): void
    {
        $owners = $event->changeRequest->outlet
            ->owners()
            ->pluck('email')
            ->filter()
            ->unique()
            ->all();

        foreach ($owners as $email) {
            Mail::to($email)->queue(new PartnerCategoryRequestSubmittedMail($event->changeRequest));
        }
    }
}
