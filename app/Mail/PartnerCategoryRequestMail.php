<?php

namespace App\Mail;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartnerCategoryRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $partner;
    public ?Outlet $outlet;
    public array $payload;

    public function __construct(User $partner, ?Outlet $outlet, array $payload)
    {
        $this->partner = $partner;
        $this->outlet = $outlet;
        $this->payload = $payload;
    }

    public function build(): self
    {
        return $this->subject('Permintaan Penambahan Kategori dari Mitra')
            ->markdown('emails.partners.category_request', [
                'partner' => $this->partner,
                'outlet' => $this->outlet,
                'payload' => $this->payload,
            ]);
    }
}
