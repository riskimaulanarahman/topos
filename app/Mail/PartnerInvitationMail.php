<?php

namespace App\Mail;

use App\Models\OutletUserRole;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartnerInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OutletUserRole $invitation)
    {
    }

    public function build(): self
    {
        return $this->subject('Undangan Mitra Outlet')
            ->markdown('emails.partners.invitation', [
                'invitation' => $this->invitation,
                'outlet' => $this->invitation->outlet,
            ]);
    }
}
