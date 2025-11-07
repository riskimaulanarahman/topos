<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $items // [['name' => ..., 'sku' => ..., 'stock' => ..., 'min' => ...], ...]
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = '['.config('app.name').'] Peringatan Stok Minimum';
        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.low_stock_alert', [
                'appName' => config('app.name'),
                'logoUrl' => asset('img/toga-gold-ts.png'),
                'items' => $this->items,
            ]);
    }
}
