<?php

namespace App\Listeners;

use App\Events\PartnerInvitationCreated;
use App\Mail\PartnerInvitationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendPartnerInvitationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PartnerInvitationCreated $event): void
    {
        Mail::to($event->invitation->user->email)
            ->queue(new PartnerInvitationMail($event->invitation));
    }
}
