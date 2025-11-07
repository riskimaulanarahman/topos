<?php

namespace App\Mail;

use App\Models\PartnerCategoryChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartnerCategoryRequestDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PartnerCategoryChangeRequest $changeRequest)
    {
    }

    public function build(): self
    {
        $subject = $this->changeRequest->status === 'approved'
            ? 'Akses Kategori Disetujui'
            : 'Akses Kategori Ditolak';

        return $this->subject($subject)
            ->markdown('emails.partners.category_request_decision', [
                'changeRequest' => $this->changeRequest,
            ]);
    }
}
