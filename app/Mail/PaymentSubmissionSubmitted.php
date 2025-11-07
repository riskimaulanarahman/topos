<?php

namespace App\Mail;

use App\Models\PaymentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentSubmissionSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PaymentSubmission $submission, public string $detailsUrl)
    {
        $this->submission->loadMissing(['user', 'paymentAccount']);
    }

    public function build(): self
    {
        $storeName = $this->submission->user?->store_name ?: $this->submission->user?->name;
        $subject = 'Pengajuan pembayaran baru - ' . ($storeName ?: 'Pengguna');

        return $this->subject($subject)
            ->view('emails.admin.payment_submission_submitted', [
                'submission' => $this->submission,
                'detailsUrl' => $this->detailsUrl,
            ]);
    }
}

