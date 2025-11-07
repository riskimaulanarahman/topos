<?php

namespace App\Listeners;

use App\Events\PartnerCategoryRequestApproved;
use App\Events\PartnerCategoryRequestRejected;
use App\Mail\PartnerCategoryRequestDecisionMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendPartnerCategoryDecisionEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PartnerCategoryRequestApproved|PartnerCategoryRequestRejected $event): void
    {
        $changeRequest = $event->changeRequest->fresh(['target.user']);

        Mail::to($changeRequest->target->user->email)
            ->queue(new PartnerCategoryRequestDecisionMail($changeRequest));
    }
}
