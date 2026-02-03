<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->view('emails.verify-email-custom', [
                'user' => $notifiable,
                'verificationUrl' => $verificationUrl,
                'appName' => config('app.name'),
            ]);
    }

    protected function verificationUrl($notifiable): string
    {
        $frontendUrl = config('app.url');
        
        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        $parsedUrl = parse_url($url);
        parse_str($parsedUrl['query'] ?? '', $params);

        return $frontendUrl . '/email/verify?' . http_build_query([
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
            'expires' => $params['expires'] ?? '',
            'signature' => $params['signature'] ?? '',
        ]);
    }
}