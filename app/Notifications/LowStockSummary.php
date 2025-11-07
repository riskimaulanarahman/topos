<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockSummary extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $items,
        public ?string $subject = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->subject ?? ('['.config('app.name').'] Ringkasan Stok Minimum');

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.low_stock_summary', [
                'appName' => config('app.name'),
                'logoUrl' => asset('img/toga-gold-ts.png'),
                'items' => $this->items,
            ]);
    }
}
