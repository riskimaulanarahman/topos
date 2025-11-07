<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CustomVerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        // Default saat DI-QUEUE (notify())
        $this->onConnection('database');
        $this->onQueue('mail');
        $this->afterCommit = true; // properti ini sudah ada di trait, cukup assign nilainya
    }
    
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'api.verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    public function toMail($notifiable)
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifikasi Email Akun Anda')
            ->markdown('emails.verify_email', [
                'appName' => config('app.name'),
                'logoUrl' => asset('img/toga-gold-ts.png'),
                'verificationUrl' => $url,
                'userName' => $notifiable->name,
            ]);
    }
}
