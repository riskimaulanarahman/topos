<?php

namespace App\Events;

use App\Models\PartnerCategoryChangeRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartnerCategoryRequestRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(public PartnerCategoryChangeRequest $changeRequest)
    {
    }
}
