<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RawMaterialStockSummary extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $summary,
        public ?string $subject = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->subject ?? '[' . config('app.name') . '] Ringkasan Stok Bahan Baku';

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.raw_material_stock_summary', [
                'appName' => config('app.name'),
                'logoUrl' => asset('img/toga-gold-ts.png'),
                'summary' => $this->summary,
            ]);
    }
}
