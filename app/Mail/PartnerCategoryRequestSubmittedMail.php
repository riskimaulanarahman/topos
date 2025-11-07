<?php

namespace App\Mail;

use App\Models\PartnerCategoryChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartnerCategoryRequestSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PartnerCategoryChangeRequest $changeRequest)
    {
    }

    public function build(): self
    {
        return $this->subject('Permintaan Akses Kategori Baru')
            ->markdown('emails.partners.category_request_submitted', [
                'changeRequest' => $this->changeRequest,
            ]);
    }
}
