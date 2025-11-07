<?php

namespace App\Events;

use App\Models\OutletUserRole;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartnerInvitationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public OutletUserRole $invitation)
    {
    }
}
