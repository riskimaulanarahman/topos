<?php

namespace App\Mail;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartnerProductRequestMail extends Mailable
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
        return $this->subject('Permintaan Produk dari Mitra')
            ->markdown('emails.partners.product_request', [
                'partner' => $this->partner,
                'outlet' => $this->outlet,
                'payload' => $this->payload,
            ]);
    }
}
