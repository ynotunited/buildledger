<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailAddress extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        $verificationUrl = "{$frontendUrl}/verify-email?email=" . urlencode($notifiable->email) . "&token={$this->token}";

        return (new MailMessage)
            ->subject('Verify your BuildLedger email')
            ->greeting("Hi {$notifiable->name},")
            ->line('Verify your email address to secure your account and unlock the full BuildLedger workflow.')
            ->action('Verify Email', $verificationUrl)
            ->line('If you did not create this account, you can ignore this email.');
    }
}
